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
        <div class="panel-heading"><strong>{{ __('Level Repair / XP Re-sync') }}</strong></div>
        <div class="panel-body">
            <form class="form-horizontal" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/repair-levels') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Target') }}</label>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-xs-6">
                                <select class="form-control" name="repair[user_id]">
                                    <option value="me">{{ __('Me') }}</option>
                                    <option value="all">{{ __('All users with stats') }}</option>
                                </select>
                            </div>
                            <div class="col-xs-6">
                                <input type="number" class="form-control" name="repair[user_id_custom]" placeholder="{{ __('User ID') }}">
                            </div>
                        </div>
                        <div class="help-block">{{ __('Scans current XP totals and checks whether the stored level still matches the corrected progression curve.') }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Repair scope') }}</label>
                    <div class="col-sm-6">
                        <select class="form-control" name="repair[scope]">
                            <option value="invalid_only">{{ __('Only mismatched levels (recommended)') }}</option>
                            <option value="all_selected">{{ __('Recalculate all selected stat rows from XP') }}</option>
                        </select>
                        <div class="help-block">{{ __('This does not change XP. It only recalculates the saved level from current XP.') }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Confirmation') }}</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="repair[confirm]" placeholder="{{ __('Type REPAIR or REPAIR ALL when running a fix') }}">
                        <div class="help-block">{{ __('Scan does not need confirmation. Repair for one user requires REPAIR. Repair for all users requires REPAIR ALL.') }}</div>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-default" name="repair[action]" value="scan">{{ __('Scan now') }}</button>
                        <button type="submit" class="btn btn-warning" name="repair[action]" value="repair">{{ __('Repair levels') }}</button>
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

