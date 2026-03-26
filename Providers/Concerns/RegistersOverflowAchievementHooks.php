<?php

namespace Modules\OverflowAchievement\Providers\Concerns;

trait RegistersOverflowAchievementHooks
{
protected function registerHooks(): void
    {
        // Award XP on close
        \Eventy::addAction('conversation.status_changed', function ($conversation, $user, $changed_on_reply, $prev_status) {
            try {
                if (!$user || empty($user->id)) {
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
                \Log::error('OverflowAchievement: status_changed hook failed: '.$e->getMessage());
            }
        }, 10, 4);

        // Award XP on first reply (final, after undo timeout)
        \Eventy::addAction('conversation.user_replied', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id) {
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
                \Log::error('OverflowAchievement: user_replied hook failed: '.$e->getMessage());
            }
        }, 10, 2);

        // Customer replied (incoming email). Credit the currently assigned agent (if any).
        \Eventy::addAction('conversation.customer_replied', function ($conversation, $thread, $customer) {
            try {
                if (!$conversation) {
                    return;
                }
                $user_id = (int)($conversation->user_id ?? 0);
                if (!$user_id) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerReplied($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer_replied hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for creating a new conversation (outbound/proactive).
        // FreeScout fires this after undo window is done.
        \Eventy::addAction('conversation.created_by_user_can_undo', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardConversationCreated($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: created_by_user_can_undo hook failed: '.$e->getMessage());
            }
        }, 10, 2);


        // Award XP for internal note (after final save)
        \Eventy::addAction('conversation.note_added', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardNoteAdded($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: note_added hook failed: '.$e->getMessage());
            }
        }, 10, 2);

        // Award XP for assignment changes (taking ownership / reassigning)
        \Eventy::addAction('conversation.user_changed', function ($conversation, $user, $prev_user_id) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
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
                \Log::error('OverflowAchievement: user_changed hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for moving conversations between mailboxes
        \Eventy::addAction('conversation.moved', function ($conversation, $user, $prev_mailbox) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardMoved((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: moved hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for deleting conversations (state changed to deleted).
        \Eventy::addAction('conversation.state_changed', function ($conversation, $user, $prev_state) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                if ((int)($conversation->state ?? 0) === (int)\App\Conversation::STATE_DELETED
                    && (int)$prev_state !== (int)\App\Conversation::STATE_DELETED
                ) {
                    app('overflowachievement.rewards')->awardDeletedConversation((int)$user->id, (int)$conversation->id);
                }
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: state_changed hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for subject edits (capped once per conversation per day).
        \Eventy::addAction('conversation.subject_changed', function ($conversation, $user, $prev_subject) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardSubjectChanged((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: subject_changed hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for merges
        \Eventy::addAction('conversation.merged', function ($conversation, $second_conversation, $user) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardMerged((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: merged hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for forwarding
        \Eventy::addAction('conversation.user_forwarded', function ($conversation, $thread, $forwarded_conversation, $forwarded_thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardForwarded($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: user_forwarded hook failed: '.$e->getMessage());
            }
        }, 10, 4);

        // Award XP for attachments
        \Eventy::addAction('attachment.created', function ($attachment) {
            try {
                // Attachment model has: created_by_user_id, thread_id; infer conversation if possible
                $user_id = (int)($attachment->created_by_user_id ?? 0);
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
                $rewards->awardAttachmentAdded($user_id, $conversation_id ?: null);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: attachment.created hook failed: '.$e->getMessage());
            }
        }, 10, 1);

        // Award XP for creating customers (typically done while working)
        \Eventy::addAction('customer.created', function ($customer) {
            try {
                $user_id = (int)($customer->created_by_user_id ?? 0);
                if (!$user_id) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerCreated($user_id, (int)$customer->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer.created hook failed: '.$e->getMessage());
            }
        }, 10, 1);

        // Award XP for updating customers (capped per day)
        \Eventy::addAction('customer.updated', function ($customer) {
            try {
                $user_id = (int)($customer->updated_by_user_id ?? 0);
                if (!$user_id) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerUpdated($user_id, (int)$customer->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer.updated hook failed: '.$e->getMessage());
            }
        }, 10, 1);

        // Award XP for merging customers.
        \Eventy::addAction('customer.merged', function ($customer, $customer2, $user) {
            try {
                if (!$user || empty($user->id) || !$customer) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerMerged((int)$user->id, (int)$customer->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer.merged hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for focus time (viewer tracker).
        \Eventy::addAction('conversation.view.finish', function ($conversation_id, $user_id, $seconds) {
            try {
                $uid = (int)$user_id;
                $cid = (int)$conversation_id;
                $sec = (int)$seconds;
                if (!$uid || !$cid || $sec <= 0) {
                    return;
                }
                app('overflowachievement.rewards')->awardFocusTime($uid, $cid, $sec);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: view.finish hook failed: '.$e->getMessage());
            }
        }, 10, 3);
    }
}
