<div role="tabpanel" class="tab-pane" id="oa-tab-visibility">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>{{ __('Visibility') }}</strong></div>
                            <div class="panel-body">

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Leaderboard') }}</label>
                                    <div class="col-sm-9">
                                        <label class="checkbox">
                                            <input type="hidden" name="settings[overflowachievement.show_leaderboard]" value="0" />
                                            <input type="checkbox" name="settings[overflowachievement.show_leaderboard]" value="1" @if($show_leaderboard) checked="checked" @endif />
                                            {{ __('Show leaderboard to users') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Navbar badge') }}</label>
                                    <div class="col-sm-9">
                                        <label class="checkbox">
                                            <input type="hidden" name="settings[overflowachievement.ui.show_user_meta]" value="0" />
                                            <input type="checkbox" name="settings[overflowachievement.ui.show_user_meta]" value="1" @if($show_user_meta) checked="checked" @endif />
                                            {{ __('Show level & XP bar in the top navigation') }}
                                        </label>
                                        <div class="help-block">{{ __('If disabled, the module still tracks XP and trophies; it just hides the small UI widget.') }}</div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="alert alert-info">
                            <strong>{{ __('Note') }}</strong><br>
                            {{ __('Admins always see the Achievements management tab in Settings.') }}
                        </div>
                    </div>
                </div>
            </div>