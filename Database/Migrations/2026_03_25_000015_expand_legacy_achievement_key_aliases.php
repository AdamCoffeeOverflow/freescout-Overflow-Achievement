<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpandLegacyAchievementKeyAliases extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        $rows = DB::table('overflowachievement_achievements')
            ->orderBy('id')
            ->get(['id', 'key', 'title', 'description', 'trigger', 'threshold', 'created_by']);

        if ($rows->isEmpty()) {
            return;
        }

        $existing = [];
        foreach ($rows as $row) {
            $existing[(string) $row->key] = true;
        }

        foreach ($rows as $row) {
            if (!$this->looksBuiltin($row)) {
                continue;
            }

            $legacy = trim((string) $row->key);
            $canonical = $this->canonicalKey($legacy, trim((string) $row->trigger), (int) $row->threshold);
            if ($canonical === '' || $canonical === $legacy) {
                continue;
            }

            $updates = [
                'title' => 'overflowachievement::achievements.' . $canonical . '.title',
                'description' => 'overflowachievement::achievements.' . $canonical . '.description',
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (!empty($existing[$canonical])) {
                $updates['is_active'] = false;
                DB::table('overflowachievement_achievements')->where('id', $row->id)->update($updates);
            } else {
                $updates['key'] = $canonical;
                DB::table('overflowachievement_achievements')->where('id', $row->id)->update($updates);
                $existing[$canonical] = true;
            }

            $this->migrateUnlockedRows($legacy, $canonical);
        }
    }

    protected function looksBuiltin($row)
    {
        if (is_null($row->created_by)) {
            return true;
        }

        foreach ([(string) $row->title, (string) $row->description] as $value) {
            if (strpos($value, 'overflowachievement::achievements.') === 0) {
                return true;
            }
        }

        return false;
    }

    protected function migrateUnlockedRows(string $legacyKey, string $canonicalKey): void
    {
        if ($legacyKey === $canonicalKey || !Schema::hasTable('overflowachievement_unlocked')) {
            return;
        }

        $rows = DB::table('overflowachievement_unlocked')
            ->where('achievement_key', $legacyKey)
            ->orderBy('id')
            ->get(['id', 'user_id']);

        foreach ($rows as $row) {
            $duplicate = DB::table('overflowachievement_unlocked')
                ->where('user_id', $row->user_id)
                ->where('achievement_key', $canonicalKey)
                ->exists();

            if ($duplicate) {
                DB::table('overflowachievement_unlocked')->where('id', $row->id)->delete();
                continue;
            }

            DB::table('overflowachievement_unlocked')
                ->where('id', $row->id)
                ->update([
                    'achievement_key' => $canonicalKey,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    protected function canonicalKey(string $key, string $trigger, int $threshold): string
    {
        $exact = [
            'notes_1' => 'first_note',
            'note_1' => 'first_note',
            'assigned_1' => 'first_assign',
            'assign_1' => 'first_assign',
            'closes_1' => 'first_close',
            'close_1' => 'first_close',
            'finish_1' => 'first_close',
            'finished_1' => 'first_close',
            'replies_1' => 'hello_world',
            'reply_1' => 'hello_world',
            'first_replies_1' => 'hello_world',
            'moves_1' => 'first_move',
            'move_1' => 'first_move',
            'forwards_1' => 'first_forward',
            'forward_1' => 'first_forward',
            'attachments_1' => 'first_attachment',
            'attachment_1' => 'first_attachment',
            'customers_1' => 'first_customer',
            'customer_1' => 'first_customer',
            'created_1' => 'first_created',
            'subjects_1' => 'first_subject_edit',
            'subject_1' => 'first_subject_edit',
            'reply_sent_1' => 'first_reply_sent',
            'customer_reply_1' => 'first_customer_reply',
            'pending_1' => 'first_pending',
            'spam_1' => 'first_spam',
            'delete_1' => 'first_delete',
            'cust_merge_1' => 'first_customer_merge',
            'focus_10' => 'first_focus_10',
        ];

        if (isset($exact[$key])) {
            return $exact[$key];
        }

        $patternMap = [
            '/^notes?_(\d+)$/i' => 'note_added',
            '/^assigned_(\d+)$/i' => 'assigned',
            '/^assigns?_(\d+)$/i' => 'assigned',
            '/^closes?_(\d+)$/i' => 'close_conversation',
            '/^finish(?:ed|es)?_(\d+)$/i' => 'close_conversation',
            '/^(?:first_)?repl(?:y|ies)_(\d+)$/i' => 'first_reply',
            '/^moves?_(\d+)$/i' => 'moved',
            '/^forwards?_(\d+)$/i' => 'forwarded',
            '/^attachments?_(\d+)$/i' => 'attachment_added',
            '/^customers?_(\d+)$/i' => 'customer_created',
            '/^created_(\d+)$/i' => 'conversation_created',
            '/^subjects?_(\d+)$/i' => 'subject_changed',
            '/^reply_sent_(\d+)$/i' => 'reply_sent',
            '/^customer_reply_(\d+)$/i' => 'customer_replied',
            '/^pending_(\d+)$/i' => 'set_pending',
            '/^spam_(\d+)$/i' => 'marked_spam',
            '/^delete_(\d+)$/i' => 'deleted_conversation',
            '/^merge(?:d|s)?_(\d+)$/i' => 'merged',
            '/^cust_merge_(\d+)$/i' => 'customer_merged',
            '/^focus_(\d+)$/i' => 'focus_time',
        ];

        foreach ($patternMap as $pattern => $mappedTrigger) {
            if (preg_match($pattern, $key, $m)) {
                $candidate = $this->canonicalKeyForTrigger($mappedTrigger, (int) ($m[1] ?? 0));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        if ($trigger !== '') {
            $candidate = $this->canonicalKeyForTrigger($trigger, $threshold);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $key;
    }

    protected function canonicalKeyForTrigger(string $trigger, int $threshold): string
    {
        $threshold = max(0, (int) $threshold);

        switch ($trigger) {
            case 'close_conversation':
                return $threshold <= 1 ? 'first_close' : 'closer_' . $threshold;
            case 'first_reply':
                return $threshold <= 1 ? 'hello_world' : 'responder_' . $threshold;
            case 'note_added':
                return $threshold <= 1 ? 'first_note' : 'notekeeper_' . $threshold;
            case 'assigned':
                return $threshold <= 1 ? 'first_assign' : 'owner_' . $threshold;
            case 'merged':
                return 'merger_' . $threshold;
            case 'moved':
                return $threshold <= 1 ? 'first_move' : 'mover_' . $threshold;
            case 'forwarded':
                return $threshold <= 1 ? 'first_forward' : 'forwarder_' . $threshold;
            case 'attachment_added':
                return $threshold <= 1 ? 'first_attachment' : 'attachments_' . $threshold;
            case 'customer_created':
                return $threshold <= 1 ? 'first_customer' : 'customers_' . $threshold;
            case 'customer_updated':
                return 'profile_polish_' . $threshold;
            case 'conversation_created':
                return $threshold <= 1 ? 'first_created' : 'creator_' . $threshold;
            case 'subject_changed':
                return $threshold <= 1 ? 'first_subject_edit' : 'subject_' . $threshold;
            case 'reply_sent':
                return $threshold <= 1 ? 'first_reply_sent' : 'reply_sent_' . $threshold;
            case 'customer_replied':
                return $threshold <= 1 ? 'first_customer_reply' : 'customer_reply_' . $threshold;
            case 'set_pending':
                return $threshold <= 1 ? 'first_pending' : 'pending_' . $threshold;
            case 'marked_spam':
                return $threshold <= 1 ? 'first_spam' : 'spam_' . $threshold;
            case 'deleted_conversation':
                return $threshold <= 1 ? 'first_delete' : 'delete_' . $threshold;
            case 'customer_merged':
                return $threshold <= 1 ? 'first_customer_merge' : 'cust_merge_' . $threshold;
            case 'focus_time':
                return $threshold <= 10 ? 'first_focus_10' : 'focus_' . $threshold;
            case 'streak_days':
                return 'streak_' . $threshold;
            case 'xp_total':
                return 'xp_' . $threshold;
            case 'actions_total':
                return 'actions_' . $threshold;
            case 'sla_first_response_ultra':
                return 'sla_first_response_ultra_' . $threshold;
            case 'sla_first_response_fast':
                return 'sla_first_response_fast_' . $threshold;
            case 'sla_fast_reply_ultra':
                return 'sla_fast_reply_ultra_' . $threshold;
            case 'sla_fast_reply':
                return 'sla_fast_reply_' . $threshold;
            case 'sla_resolve_4h':
                return 'sla_resolve_4h_' . $threshold;
            case 'sla_resolve_24h':
                return 'sla_resolve_24h_' . $threshold;
            default:
                return '';
        }
    }

    public function down()
    {
        // Keep canonical built-in keys in place on rollback.
    }
}
