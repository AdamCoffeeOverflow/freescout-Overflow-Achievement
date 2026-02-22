<div role="tabpanel" class="tab-pane active" id="oa-tab-general">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>{{ __('General') }}</strong></div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Enabled') }}</label>
                                    <div class="col-sm-9">
                                        <label class="checkbox">
                                            <input type="hidden" name="settings[overflowachievement.enabled]" value="0" />
                                            <input type="checkbox" name="settings[overflowachievement.enabled]" value="1" @if(old('settings[overflowachievement.enabled]', $settings_values['overflowachievement.enabled'] ?? 0)) checked="checked" @endif />
                                            {{ __('Enable achievements for users') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Diagnostics') }}</label>
                                    <div class="col-sm-9">
                                        <div class="text-muted">{{ __('Use this only if popups don’t show up.') }}</div>
                                        <div style="margin-top:8px;">
                                            <button type="button" class="btn btn-default oa-health-check">{{ __('Test connection') }}</button>
                                            <span class="text-muted oa-health-output" style="margin-left:10px;">{{ __('Not tested') }}</span>
                                        </div>
                                        <div class="help-block">{{ __('Checks whether the /unseen polling endpoint is reachable for your session.') }}</div>
                                    </div>
                                </div>

                                <div class="oa-advanced-toggle text-muted" data-oa-advanced="general" style="margin-top:6px;">
                                    <i class="glyphicon glyphicon-chevron-right"></i>{{ __('Advanced') }}
                                </div>
                                <div class="oa-advanced" data-oa-advanced-panel="general" style="margin-top:10px;">
                                    <div class="alert alert-info" style="margin-bottom:0;">
                                        <strong>{{ __('Tip') }}</strong><br>
                                        {{ __('If users change pages quickly, set popups to sticky or increase auto-close time in Appearance.') }}
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="alert alert-info">
                            <strong>{{ __('Scope') }}</strong><br>
                            {{ __('Achievements are tracked per user. Admins can reset progress in Admin Tools.') }}
                        </div>
                    </div>
                </div>
            </div>