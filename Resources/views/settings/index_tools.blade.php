@php
    $me = Auth::user();
@endphp

<div class="text-muted">{{ __('Danger buttons and test buttons. Use responsibly, earthling.') }}</div>

    <div class="panel panel-default" style="margin-top:12px;">
        <div class="panel-heading"><strong>{{ __('Reset Progress') }}</strong></div>
        <div class="panel-body">
            <form class="form-horizontal" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/reset') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Target') }}</label>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-xs-6">
                                <select class="form-control" name="reset[user_id]">
                                    <option value="me">{{ __('Me') }}</option>
                                    <option value="all">{{ __('All users') }}</option>
                                </select>
                            </div>
                            <div class="col-xs-6">
                                <input type="number" class="form-control" name="reset[user_id_custom]" placeholder="{{ __('User ID') }}">
                            </div>
                        </div>
                        <div class="help-block">{{ __('Use the dropdown, or enter a specific User ID to reset that user.') }}</div>
                    <div class="row" style="margin-top:10px;">
                        <div class="col-xs-12">
                            <input type="text" class="form-control" name="reset[confirm]" placeholder="{{ __('Type RESET (or RESET ALL) to confirm') }}">
                            <div class="help-block">{{ __('This action wipes stats, events, and unseen unlocks for the selected user(s).') }}</div>
                        </div>
                    </div>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-danger">{{ __('Reset') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>{{ __('Test Notification') }}</strong></div>
        <div class="panel-body">
            <form class="form-horizontal" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/test-toast') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Queue for') }}</label>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-xs-6">
                                <select class="form-control" name="test[user_id]">
                                    <option value="me">{{ __('Me') }}</option>
                                </select>
                            </div>
                            <div class="col-xs-6">
                                <input type="number" class="form-control" name="test[user_id_custom]" placeholder="{{ __('User ID') }}">
                            </div>
                        </div>
                        <div class="help-block">{{ __('Reload as that user to see it. Or use Preview for instant UI check.') }}</div>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-default">{{ __('Queue test') }}</button>
                    </div>
                </div>
            </form>

            <hr>
            <div class="text-muted">{{ __('For instant UI checks, use the Preview popup button in the Appearance tab.') }}</div>
        </div>
    </div>

