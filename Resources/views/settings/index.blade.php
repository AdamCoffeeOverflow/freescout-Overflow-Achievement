@php
    $is_admin = Auth::user() && Auth::user()->isAdmin();
    $achievements = $is_admin
        ? \Modules\OverflowAchievement\Entities\Achievement::query()
            ->orderBy('trigger')
            ->orderBy('threshold')
            ->get()
        : collect();

    // FreeScout passes $settings as key => ['value' => ...] arrays.
    $settings_values = [];
    if (isset($settings) && is_array($settings)) {
        foreach ($settings as $k => $v) {
            if (is_array($v) && array_key_exists('value', $v)) {
                $settings_values[$k] = $v['value'];
            } else {
                $settings_values[$k] = $v;
            }
        }
    }

    $mailboxes = collect();
    $rules_arr = [];
    $tones = [
        'funny' => __('Funny'),
        'epic' => __('Epic'),
        'philosophical' => __('Philosophical'),
    ];

    $mailbox_quote_rules_json = old(
        'settings[overflowachievement.quotes.mailbox_rules]',
        $settings_values['overflowachievement.quotes.mailbox_rules'] ?? ''
    );

    if ($is_admin) {
        try {
            $mailboxes = \App\Mailbox::query()->orderBy('name')->get();
        } catch (\Throwable $e) {
            $mailboxes = collect();
        }

        if (!empty($mailbox_quote_rules_json)) {
            $decoded = json_decode($mailbox_quote_rules_json, true);
            if (is_array($decoded)) {
                $rules_arr = $decoded;
            }
        }
    }

    $show_leaderboard = (int) old(
        'settings[overflowachievement.show_leaderboard]',
        $settings_values['overflowachievement.show_leaderboard'] ?? 1
    );
    $show_user_meta = (int) old(
        'settings[overflowachievement.ui.show_user_meta]',
        $settings_values['overflowachievement.ui.show_user_meta'] ?? 1
    );
@endphp

<div class="oa-settings">
    <style>
        .oa-settings .nav-tabs > li > a { cursor: pointer; }
        .oa-settings .oa-settings-header { margin-bottom: 10px; }
        .oa-settings .oa-settings-note { margin-top: 12px; }
        .oa-advanced { display: none; }
        .oa-advanced.oa-open { display: block; }
        .oa-advanced-toggle { cursor: pointer; user-select: none; }
        .oa-advanced-toggle .glyphicon { margin-right: 6px; }
    </style>

    <div class="oa-settings-header">
        <h3 style="margin:0;">{{ __('Achievement') }}</h3>
        <div class="text-muted">
            {{ __('XP, levels, trophies, and small celebrations that don’t break your helpdesk.') }}
        </div>
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#oa-tab-general" role="tab" data-toggle="tab">{{ __('General') }}</a>
        </li>
        <li role="presentation">
            <a href="#oa-tab-progression" role="tab" data-toggle="tab">{{ __('Progression') }}</a>
        </li>
        <li role="presentation">
            <a href="#oa-tab-appearance" role="tab" data-toggle="tab">{{ __('Appearance') }}</a>
        </li>
        <li role="presentation">
            <a href="#oa-tab-visibility" role="tab" data-toggle="tab">{{ __('Visibility') }}</a>
        </li>
        @if ($is_admin)
            <li role="presentation">
                <a href="#oa-tab-manage" role="tab" data-toggle="tab">{{ __('Achievements') }}</a>
            </li>
            <li role="presentation">
                <a href="#oa-tab-quotes" role="tab" data-toggle="tab">{{ __('Quotes') }}</a>
            </li>
            <li role="presentation">
                <a href="#oa-tab-tools" role="tab" data-toggle="tab">{{ __('Admin Tools') }}</a>
            </li>
        @endif
    </ul>

    <div class="tab-content" style="padding-top: 14px;">
        @include('overflowachievement::settings.tabs.general')
        @include('overflowachievement::settings.tabs.progression')
        @include('overflowachievement::settings.tabs.appearance')
        @include('overflowachievement::settings.tabs.visibility')

        @if ($is_admin)
            @include('overflowachievement::settings.tabs.quotes', [
                'mailboxes' => $mailboxes,
                'rules_arr' => $rules_arr,
                'tones' => $tones,
            ])

            <div role="tabpanel" class="tab-pane" id="oa-tab-manage">
                @php
                    $quote_library = (array) config('overflowachievement.quotes.library', []);
                    $quote_buckets = (array) config('overflowachievement.quotes.buckets', []);

                    $mailboxes_arr = [];
                    try {
                        if (class_exists('\App\Mailbox')) {
                            $mailboxes_arr = \App\Mailbox::query()
                                ->orderBy('name')
                                ->get(['id', 'name'])
                                ->toArray();
                        }
                    } catch (\Throwable $e) {
                        $mailboxes_arr = [];
                    }
                @endphp

                @include('overflowachievement::settings/index_manage', [
                    'achievements' => $achievements,
                    'quote_library' => $quote_library,
                    'quote_buckets' => $quote_buckets,
                    'mailboxes' => $mailboxes_arr,
                ])
            </div>

            <div role="tabpanel" class="tab-pane" id="oa-tab-tools">
                @include('overflowachievement::settings/index_tools')
            </div>
        @endif
    </div>
</div>
