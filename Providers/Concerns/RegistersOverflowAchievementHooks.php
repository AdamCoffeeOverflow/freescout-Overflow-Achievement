<?php

namespace Modules\OverflowAchievement\Providers\Concerns;

trait RegistersOverflowAchievementHooks
{
protected function registerHooks(): void
    {
        // Award XP on close
        \Eventy::addAction('conversation.status_changed', function ($conversation, $user, $changed_on_reply, $prev_status) {
            try {
                if (!$user || empty($user->id) || !$conversation
                    || !$this->rewardActorCanAccessConversation((int)$user->id, (int)$conversation->id)
                ) {
                    return;
                }
                if ((int)$conversation->status === (int)\App\Conversation::STATUS_CLOSED
                    && (int)$prev_status !== (int)\App\Conversation::STATUS_CLOSED
                ) {
                    app('overflowachievement.rewards')->awardCloseConversation((int)$user->id, (int)$conversation->id);

                    // SLA: fast resolution relative to ticket creation
                    try {
                        app('overflowachievement.rewards')->awardSlaResolve((int)$user->id, (int)$conversation->id, $conversation->created_at ?? null, $conversation->updated_at ?? null);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                // Set pending (triage)
                if ((int)$conversation->status === (int)\App\Conversation::STATUS_PENDING
                    && (int)$prev_status !== (int)\App\Conversation::STATUS_PENDING
                ) {
                    app('overflowachievement.rewards')->awardSetPending((int)$user->id, (int)$conversation->id);
                }

                // Mark spam
                if ((int)$conversation->status === (int)\App\Conversation::STATUS_SPAM
                    && (int)$prev_status !== (int)\App\Conversation::STATUS_SPAM
                ) {
                    app('overflowachievement.rewards')->awardMarkedSpam((int)$user->id, (int)$conversation->id);
                }
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.status_changed', $e);
            }
        }, 10, 4);

        // Award XP on first reply (final, after undo timeout)
        \Eventy::addAction('conversation.user_replied', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation
                    || !$this->rewardActorCanAccessConversation($user_id, (int)$conversation->id)
                ) {
                    return;
                }

                $rewards = app('overflowachievement.rewards');

                // If first-reply XP + SLA-first-response are both disabled, skip the expensive pre-query.
                $checkFirstReply = method_exists($rewards, 'wantsFirstReplyCheck') ? $rewards->wantsFirstReplyCheck() : true;

                // If there are no other user message threads, this is the first reply.
                // Use EXISTS instead of COUNT() for performance on large threads tables.
                if ($checkFirstReply) {
                    $has_other_user_message = \App\Thread::query()
                        ->where('conversation_id', $conversation->id)
                        ->where('type', \App\Thread::TYPE_MESSAGE)
                        ->whereNotNull('created_by_user_id')
                        ->where('id', '<>', (int)($thread->id ?? 0))
                        ->exists();

                    if (!$has_other_user_message) {
                        $rewards->awardFirstReply($user_id, (int)$conversation->id);

                        // SLA: fast first response (relative to ticket creation)
                        try {
                            $rewards->awardSlaFirstResponse($user_id, (int)$conversation->id, $conversation->created_at ?? null, $thread->created_at ?? null);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }

                // Any reply (capped per conversation per day).
                $rewards->awardReplySent($user_id, (int)$conversation->id);

                // SLA: fast follow-up after a customer reply (uses our customer_replied event timestamp)
                try {
                    $rewards->awardSlaFastReply($user_id, (int)$conversation->id, $thread->created_at ?? null);
                } catch (\Throwable $e) {
                    // ignore
                }
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.user_replied', $e);
            }
        }, 10, 2);

        // Customer replied (incoming email). Credit the currently assigned agent (if any).
        \Eventy::addAction('conversation.customer_replied', function ($conversation, $thread, $customer) {
            try {
                if (!$conversation) {
                    return;
                }
                $user_id = (int)($conversation->user_id ?? 0);
                if (!$user_id || !$this->rewardActorCanAccessConversation($user_id, (int)$conversation->id)) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerReplied($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.customer_replied', $e);
            }
        }, 10, 3);

        // Award XP for creating a new conversation (outbound/proactive).
        // Use the final delayed hook so an undone send never leaves XP behind.
        \Eventy::addAction('conversation.created_by_user', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation
                    || !$this->rewardActorCanAccessConversation($user_id, (int)$conversation->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardConversationCreated($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.created_by_user', $e);
            }
        }, 10, 2);


        // Award XP for internal note (after final save)
        \Eventy::addAction('conversation.note_added', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation
                    || !$this->rewardActorCanAccessConversation($user_id, (int)$conversation->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardNoteAdded($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.note_added', $e);
            }
        }, 10, 2);

        // Award XP for assignment changes (taking ownership / reassigning)
        \Eventy::addAction('conversation.user_changed', function ($conversation, $user, $prev_user_id) {
            try {
                if (!$user || empty($user->id) || !$conversation
                    || !$this->rewardActorCanAccessConversation((int)$user->id, (int)$conversation->id)
                ) {
                    return;
                }
                // FreeScout passes $user as the actor who changed the assignee.
                // Semantics: we treat this trigger as "took ownership" (self-assign), not "assigned someone".
                $new_user_id = (int)($conversation->user_id ?? 0);
                if ((int)$prev_user_id === $new_user_id) {
                    return;
                }
                if ($new_user_id !== (int)$user->id) {
                    return;
                }

                app('overflowachievement.rewards')->awardAssigned((int)$user->id, (int)$conversation->id, (int)$prev_user_id, $new_user_id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.user_changed', $e);
            }
        }, 10, 3);

        // Award XP for moving conversations between mailboxes
        \Eventy::addAction('conversation.moved', function ($conversation, $user, $prev_mailbox) {
            try {
                if (!$user || empty($user->id) || !$conversation
                    || !$this->rewardActorCanAccessConversation((int)$user->id, (int)$conversation->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardMoved((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.moved', $e);
            }
        }, 10, 3);

        // Award XP for deleting conversations (state changed to deleted).
        \Eventy::addAction('conversation.state_changed', function ($conversation, $user, $prev_state) {
            try {
                if (!$user || empty($user->id) || !$conversation
                    || !$this->rewardActorCanAccessConversation((int)$user->id, (int)$conversation->id)
                ) {
                    return;
                }
                if ((int)($conversation->state ?? 0) === (int)\App\Conversation::STATE_DELETED
                    && (int)$prev_state !== (int)\App\Conversation::STATE_DELETED
                ) {
                    app('overflowachievement.rewards')->awardDeletedConversation((int)$user->id, (int)$conversation->id);
                }
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.state_changed', $e);
            }
        }, 10, 3);

        // Award XP for subject edits (capped once per conversation per day).
        \Eventy::addAction('conversation.subject_changed', function ($conversation, $user, $prev_subject) {
            try {
                if (!$user || empty($user->id) || !$conversation
                    || !$this->rewardActorCanAccessConversation((int)$user->id, (int)$conversation->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardSubjectChanged((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.subject_changed', $e);
            }
        }, 10, 3);

        // Award XP for merges
        \Eventy::addAction('conversation.merged', function ($conversation, $second_conversation, $user) {
            try {
                if (!$user || empty($user->id) || !$conversation
                    || !$this->rewardActorCanAccessConversation((int)$user->id, (int)$conversation->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardMerged((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.merged', $e);
            }
        }, 10, 3);

        // Award XP for forwarding
        \Eventy::addAction('conversation.user_forwarded', function ($conversation, $thread, $forwarded_conversation, $forwarded_thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation
                    || !$this->rewardActorCanAccessConversation($user_id, (int)$conversation->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardForwarded($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.user_forwarded', $e);
            }
        }, 10, 4);

        // Award XP for attachments
        \Eventy::addAction('attachment.created', function ($attachment) {
            try {
                // FreeScout Attachment stores the uploader in user_id.
                $user_id = (int)($attachment->user_id ?? 0);
                if (!$user_id) {
                    return;
                }

                $rewards = app('overflowachievement.rewards');
                if (method_exists($rewards, 'wantsAttachmentAward') && !$rewards->wantsAttachmentAward()) {
                    return;
                }

                $conversation_id = 0;
                if (!empty($attachment->thread_id)) {
                    $thread = \App\Thread::query()->select(['id', 'conversation_id'])->find((int)$attachment->thread_id);
                    if ($thread) {
                        $conversation_id = (int)$thread->conversation_id;
                    }
                }
                if (!$conversation_id || !$this->rewardActorCanAccessConversation($user_id, $conversation_id)) {
                    return;
                }
                $rewards->awardAttachmentAdded($user_id, $conversation_id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('attachment.created', $e);
            }
        }, 10, 1);

        // Award XP for customer changes only when an authenticated FreeScout user
        // is the current actor. Core Customer does not carry created_by/updated_by user IDs,
        // and these same hooks also fire from system/inbound paths where no agent should be credited.
        \Eventy::addAction('customer.created', function ($customer) {
            try {
                $user = \Auth::user();
                if (!$user || empty($user->id) || !$customer
                    || !$this->rewardActorIsEligible((int)$user->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerCreated((int)$user->id, (int)$customer->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('customer.created', $e);
            }
        }, 10, 1);

        // Award XP for updating customers (capped per day).
        \Eventy::addAction('customer.updated', function ($customer) {
            try {
                $user = \Auth::user();
                if (!$user || empty($user->id) || !$customer
                    || !$this->rewardActorIsEligible((int)$user->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerUpdated((int)$user->id, (int)$customer->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('customer.updated', $e);
            }
        }, 10, 1);

        // Award XP for merging customers.
        \Eventy::addAction('customer.merged', function ($customer, $customer2, $user) {
            try {
                if (!$user || empty($user->id) || !$customer
                    || !$this->rewardActorIsEligible((int)$user->id)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerMerged((int)$user->id, (int)$customer->id);
            } catch (\Throwable $e) {
                $this->logRewardHookException('customer.merged', $e);
            }
        }, 10, 3);

        // Award XP for focus time (viewer tracker).
        \Eventy::addAction('conversation.view.finish', function ($conversation_id, $user_id, $seconds) {
            try {
                $uid = (int)$user_id;
                $cid = (int)$conversation_id;
                $sec = (int)$seconds;
                if (!$uid || !$cid || $sec <= 0
                    || !$this->rewardActorCanAccessConversation($uid, $cid)
                ) {
                    return;
                }
                app('overflowachievement.rewards')->awardFocusTime($uid, $cid, $sec);
            } catch (\Throwable $e) {
                $this->logRewardHookException('conversation.view.finish', $e);
            }
        }, 10, 3);
    }

    protected function logRewardHookException(string $hook, \Throwable $e): void
    {
        \Log::error('OverflowAchievement: reward hook failed', [
            'hook' => $hook,
            'exception' => get_class($e),
        ]);
    }

    protected function rewardActorIsEligible(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = \App\User::query()->find($userId);
        if (!$user) {
            return false;
        }
        if (defined('\\App\\User::TYPE_ROBOT') && (int)$user->type === (int)\App\User::TYPE_ROBOT) {
            return false;
        }
        if (defined('\\App\\User::STATUS_ACTIVE') && (int)$user->status !== (int)\App\User::STATUS_ACTIVE) {
            return false;
        }

        return true;
    }

    protected function rewardActorCanAccessConversation(int $userId, int $conversationId): bool
    {
        if (!$this->rewardActorIsEligible($userId) || $conversationId <= 0) {
            return false;
        }

        $conversation = \App\Conversation::query()
            ->select(['id', 'mailbox_id'])
            ->find($conversationId);
        if (!$conversation) {
            return false;
        }

        $user = \App\User::query()->find($userId);
        if (!$user) {
            return false;
        }

        return (bool)$user->hasAccessToMailbox((int)$conversation->mailbox_id);
    }
}
