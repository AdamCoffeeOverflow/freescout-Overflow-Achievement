@php
    // Some installs render this tab without precomputed variables.
    // Derive values from settings payload to avoid "Undefined variable" notices.
    $oldSettings = old('settings', []);
    $toast_theme = $oldSettings['overflowachievement.ui.toast_theme'] ?? ($settings_values['overflowachievement.ui.toast_theme'] ?? 'business_teal');
    $effect = $oldSettings['overflowachievement.ui.effect'] ?? ($settings_values['overflowachievement.ui.effect'] ?? 'confetti');

    $sound_enabled = (bool)($oldSettings['overflowachievement.ui.sound_enabled'] ?? ($settings_values['overflowachievement.ui.sound_enabled'] ?? 1));
    $sound_cooldown_ms = (int)($oldSettings['overflowachievement.ui.sound_cooldown_ms'] ?? ($settings_values['overflowachievement.ui.sound_cooldown_ms'] ?? 1200));

    $toast_sticky = (bool)($oldSettings['overflowachievement.ui.toast_sticky'] ?? ($settings_values['overflowachievement.ui.toast_sticky'] ?? 0));
    $toast_duration_ms = (int)($oldSettings['overflowachievement.ui.toast_duration_ms'] ?? ($settings_values['overflowachievement.ui.toast_duration_ms'] ?? 10000));

    $toast_stack_enabled = (bool)($oldSettings['overflowachievement.ui.toast_stack_enabled'] ?? ($settings_values['overflowachievement.ui.toast_stack_enabled'] ?? 0));
    $toast_stack_max = (int)($oldSettings['overflowachievement.ui.toast_stack_max'] ?? ($settings_values['overflowachievement.ui.toast_stack_max'] ?? 1));
@endphp

<div role="tabpanel" class="tab-pane" id="oa-tab-appearance">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>{{ __('Popups') }}</strong></div>
                            <div class="panel-body">

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Theme') }}</label>
                                    <div class="col-sm-9">
                                        <select class="form-control input-sized" name="settings[overflowachievement.ui.toast_theme]">
                                            @foreach ([
                                                'business_teal' => 'Business Central (Teal)',
                                                'playstation'   => 'PlayStation',
                                                'xbox'          => 'Xbox',
                                                'saas'          => 'SaaS (Clean)',
                                                'neon'          => 'Neon',
                                                'dark'          => 'Dark',
                                                'classic'       => 'Classic',
                                            ] as $k=>$label)
                                                <option value="{{ $k }}" @if((string)$toast_theme===(string)$k) selected @endif>{{ __($label) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="help-block">{{ __('Controls the look of achievement popups.') }}</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Celebration') }}</label>
                                    <div class="col-sm-9">
                                        <select class="form-control input-sized" name="settings[overflowachievement.ui.effect]">
                                            <option value="confetti" @if((string)$effect==='confetti') selected @endif>{{ __('Confetti') }}</option>
                                            <option value="fireworks" @if((string)$effect==='fireworks') selected @endif>{{ __('Fireworks') }}</option>
                                            <option value="off" @if((string)$effect==='off') selected @endif>{{ __('Off') }}</option>
                                        </select>
                                        <div class="help-block">{{ __('Shown on level-ups and epic/legendary trophies.') }}</div>
                                    </div>
                                </div>

                                <div class="oa-advanced-toggle text-muted" data-oa-advanced="appearance" style="margin-top:6px;">
                                    <i class="glyphicon glyphicon-chevron-right"></i>{{ __('Advanced') }}
                                </div>
                                <div class="oa-advanced" data-oa-advanced-panel="appearance" style="margin-top:10px;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Compatibility switch') }}</label>
                                        <div class="col-sm-9">
                                            <label class="checkbox">
                                                <input type="hidden" name="settings[overflowachievement.ui.confetti]" value="0" />
                                                <input type="checkbox" name="settings[overflowachievement.ui.confetti]" value="1" @if(old('settings[overflowachievement.ui.confetti]', $settings_values['overflowachievement.ui.confetti'] ?? 1)) checked="checked" @endif />
                                                {{ __('Enable celebrations') }}
                                            </label>
                                            <div class="help-block">{{ __('If disabled, celebration effects will not run even if selected above.') }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Sound') }}</label>
                                        <div class="col-sm-9">
                                            <label class="checkbox">
                                                <input type="hidden" name="settings[overflowachievement.ui.sound_enabled]" value="0" />
                                                <input type="checkbox" name="settings[overflowachievement.ui.sound_enabled]" value="1" @if($sound_enabled) checked="checked" @endif />
                                                {{ __('Play a subtle sound on unlock') }}
                                            </label>
                                            <div class="help-block">{{ __('Browsers may block sound until the user interacts with the page.') }}</div>
                                            <div style="margin-top:8px;">
                                                <label class="text-muted" style="font-weight:400;">{{ __('Sound cooldown') }}</label>
                                                <input type="number" min="200" max="5000" step="100" class="form-control input-sized" name="settings[overflowachievement.ui.sound_cooldown_ms]" value="{{ $sound_cooldown_ms }}" />
                                                <div class="help-block">{{ __('Minimum milliseconds between sounds (prevents burst spam). Default: 1200.') }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Sticky') }}</label>
                                        <div class="col-sm-9">
                                            <label class="checkbox">
                                                <input type="hidden" name="settings[overflowachievement.ui.toast_sticky]" value="0" />
                                                <input type="checkbox" name="settings[overflowachievement.ui.toast_sticky]" value="1" @if($toast_sticky) checked="checked" @endif />
                                                {{ __('Require manual dismiss') }}
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Auto-close time') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" min="1000" step="500" class="form-control input-sized" name="settings[overflowachievement.ui.toast_duration_ms]" value="{{ $toast_duration_ms }}" />
                                            <div class="help-block">{{ __('Milliseconds before auto-close (ignored when sticky). Default: 10000.') }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Toast stacking') }}</label>
                                        <div class="col-sm-9">
                                            <label class="checkbox">
                                                <input type="hidden" name="settings[overflowachievement.ui.toast_stack_enabled]" value="0" />
                                                <input type="checkbox" name="settings[overflowachievement.ui.toast_stack_enabled]" value="1" @if($toast_stack_enabled) checked="checked" @endif />
                                                {{ __('Show up to') }}
                                                <input type="number" min="1" max="5" step="1" class="form-control input-sized" style="display:inline-block;vertical-align:middle;margin:0 6px;width:90px;" name="settings[overflowachievement.ui.toast_stack_max]" value="{{ $toast_stack_max }}" />
                                                {{ __('popups at once') }}
                                            </label>
                                            <div class="help-block">{{ __('Default is one-at-a-time. Enable stacking if you want bursts to appear simultaneously.') }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if ($is_admin)
                                    <div class="form-group">
                                        <div class="col-sm-9 col-sm-offset-3">
                                            <button type="button" class="btn btn-primary oa-preview-toast">
                                                <i class="glyphicon glyphicon-eye-open"></i> {{ __('Preview popup') }}
                                            </button>
                                            <div class="help-block">{{ __('Uses your current selections (theme, effect, duration). Does not save.') }}</div>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="alert alert-info">
                            <strong>{{ __('Queue behavior') }}</strong><br>
                            {{ __('To avoid missing popups, only one achievement is shown at a time. Others wait in line.') }}
                        </div>
                    </div>
                </div>
            </div>