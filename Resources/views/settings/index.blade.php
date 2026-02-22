@php
    $is_admin = Auth::user() && Auth::user()->isAdmin();
    $achievements = $is_admin ? \Modules\OverflowAchievement\Entities\Achievement::query()->orderBy('trigger')->orderBy('threshold')->get() : collect();

    // FreeScout passes $settings as key => ['value'=>...] arrays.
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

    // Quotes (admin)
    $mailboxes = collect();
    $rules_arr = [];
    $tones = [
        'funny' => __('Funny'),
        'epic' => __('Epic'),
        'philosophical' => __('Philosophical'),
    ];

    // Mailbox-aware quote rules stored as JSON in settings.
    $mailbox_quote_rules_json = old('settings[overflowachievement.quotes.mailbox_rules]', $settings_values['overflowachievement.quotes.mailbox_rules'] ?? '');
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

    // Visibility defaults
    $show_leaderboard = (int)old('settings[overflowachievement.show_leaderboard]', $settings_values['overflowachievement.show_leaderboard'] ?? 1);
    $show_user_meta = (int)old('settings[overflowachievement.ui.show_user_meta]', $settings_values['overflowachievement.ui.show_user_meta'] ?? 1);
@endphp

<div class="oa-settings">
    <style>
        /* Keep module settings self-contained and robust across FreeScout themes */
        .oa-settings .nav-tabs > li > a { cursor:pointer; }
        .oa-advanced { display:none; }
        .oa-advanced.oa-open { display:block; }
        .oa-advanced-toggle { cursor:pointer; user-select:none; }
        .oa-advanced-toggle .glyphicon { margin-right:6px; }
    </style>

    <div class="oa-settings-header">
        <h3 style="margin:0;">{{ __('Achievement') }}</h3>
        <div class="text-muted">{{ __('XP, levels, trophies, and small celebrations that don’t break your helpdesk.') }}</div>
    </div>

    <ul class="nav nav-tabs" role="tablist" style="margin-top:12px;">
        <li role="presentation" class="active"><a href="#oa-tab-general" role="tab" data-toggle="tab">{{ __('General') }}</a></li>
        <li role="presentation"><a href="#oa-tab-progression" role="tab" data-toggle="tab">{{ __('Progression') }}</a></li>
        <li role="presentation"><a href="#oa-tab-appearance" role="tab" data-toggle="tab">{{ __('Appearance') }}</a></li>
        <li role="presentation"><a href="#oa-tab-visibility" role="tab" data-toggle="tab">{{ __('Visibility') }}</a></li>
        @if ($is_admin)
            <li role="presentation"><a href="#oa-tab-manage" role="tab" data-toggle="tab">{{ __('Achievements') }}</a></li>
            <li role="presentation"><a href="#oa-tab-quotes" role="tab" data-toggle="tab">{{ __('Quotes') }}</a></li>
            <li role="presentation"><a href="#oa-tab-tools" role="tab" data-toggle="tab">{{ __('Admin Tools') }}</a></li>
        @endif
    </ul>

    {{--
        Settings save form (single form): includes all option inputs across tabs.
        Admin "Manage" and "Tools" tabs contain their own forms and must NOT be nested.
    --}}
    <form class="form-horizontal margin-bottom oa-settings-form oa-quotes-form" method="POST" action="{{ url()->current() }}">
        {{ csrf_field() }}
        <input type="hidden" name="tab" value="" />

        {{-- JSON payload for mailbox quote rules (built in JS on submit) --}}
        <input type="hidden" id="oa-mailbox-quotes-json" name="settings[overflowachievement.quotes.mailbox_rules]" value="{{ e($mailbox_quote_rules_json) }}" />

        <div class="tab-content" style="padding-top:14px;">

    <form class="form-horizontal margin-bottom oa-settings-form oa-quotes-form" method="POST" action="{{ url()->current() }}">
        {{ csrf_field() }}
        <input type="hidden" name="tab" value="" />

        {{-- JSON payload for mailbox quote rules (built in JS on submit) --}}
        <input type="hidden" id="oa-mailbox-quotes-json" name="settings[overflowachievement.quotes.mailbox_rules]" value="{{ e($mailbox_quote_rules_json) }}" />

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
        @endif

        <div class="form-group margin-top">
            <div class="col-sm-10 col-sm-offset-2">
                <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
            </div>
        </div>
    </form>

    {{-- ADMIN: MANAGE ACHIEVEMENTS (separate forms inside) --}}
    @if ($is_admin)
        <div role="tabpanel" class="tab-pane" id="oa-tab-manage">
            @php
                $quote_library = (array)config('overflowachievement.quotes.library', []);
                $quote_buckets = (array)config('overflowachievement.quotes.buckets', []);

                // Mailboxes list for optional mailbox-aware quote vibe.
                $mailboxes_arr = [];
                try {
                    if (class_exists('\\App\\Mailbox')) {
                        $mailboxes_arr = \App\Mailbox::query()->orderBy('name')->get(['id','name'])->toArray();
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
