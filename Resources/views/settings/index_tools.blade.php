@php
    $me = Auth::user();
@endphp

<div class="row">
    <div class="col-sm-8">
        <div class="text-muted">
            {{ __('Maintenance tools for resets, repairs, and notification testing.') }}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="alert alert-info" style="margin-bottom: 12px;">
            <strong>{{ __('Use with care') }}</strong><br>
            {{ __('Reset removes progress data. Repair only recalculates stored levels from existing XP totals.') }}
        </div>
    </div>
</div>

<div class="panel panel-default" style="margin-top: 12px;">
    <div class="panel-heading"><strong>{{ __('Reset Progress') }}</strong></div>
    <div class="panel-body">
        <div class="text-muted" style="margin-bottom: 12px;">
            {{ __('Delete achievement progress, event history, and queued unlock notifications for the selected user scope.') }}
        </div>

        <form class="form-horizontal oa-admin-tool-form" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/reset') }}">
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
                            <input type="number" class="form-control" name="reset[user_id_custom]" placeholder="{{ __('Specific User ID') }}">
                        </div>
                    </div>
                    <div class="help-block">{{ __('Choose a preset target or enter a specific user ID.') }}</div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('Confirmation') }}</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="reset[confirm]" placeholder="{{ __('Type RESET or RESET ALL') }}">
                    <div class="help-block">{{ __('Required by the controller before a destructive reset is allowed.') }}</div>
                </div>
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-danger" data-oa-confirm="{{ __('Reset progress for the selected user scope? This cannot be undone.') }}">
                        {{ __('Reset progress') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><strong>{{ __('Level Repair / XP Re-sync') }}</strong></div>
    <div class="panel-body">
        <div class="text-muted" style="margin-bottom: 12px;">
            {{ __('Use this after changing progression rules or importing corrected XP totals.') }}
        </div>

        <form class="form-horizontal oa-admin-tool-form" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/repair-levels') }}">
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
                            <input type="number" class="form-control" name="repair[user_id_custom]" placeholder="{{ __('Specific User ID') }}">
                        </div>
                    </div>
                    <div class="help-block">{{ __('Scan checks stored levels against the current XP curve.') }}</div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('Repair scope') }}</label>
                <div class="col-sm-6">
                    <select class="form-control" name="repair[scope]">
                        <option value="invalid_only">{{ __('Only mismatched levels (recommended)') }}</option>
                        <option value="all_selected">{{ __('Recalculate every selected stat row') }}</option>
                    </select>
                    <div class="help-block">{{ __('XP values stay unchanged. Only stored level fields are recalculated.') }}</div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">{{ __('Confirmation') }}</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="repair[confirm]" placeholder="{{ __('Type REPAIR or REPAIR ALL') }}">
                    <div class="help-block">{{ __('A scan does not need confirmation. The repair action does.') }}</div>
                </div>
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-default" name="repair[action]" value="scan">{{ __('Scan') }}</button>
                    <button type="submit" class="btn btn-warning" name="repair[action]" value="repair" data-oa-confirm="{{ __('Repair stored levels for the selected user scope? XP values will be kept.') }}">
                        {{ __('Repair levels') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><strong>{{ __('Test Notification') }}</strong></div>
    <div class="panel-body">
        <div class="text-muted" style="margin-bottom: 12px;">
            {{ __('Queue a sample unseen unlock so you can verify the toast flow without changing real progress.') }}
        </div>

        <form class="form-horizontal oa-admin-tool-form" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/test-toast') }}">
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
                            <input type="number" class="form-control" name="test[user_id_custom]" placeholder="{{ __('Specific User ID') }}">
                        </div>
                    </div>
                    <div class="help-block">{{ __('Reload as that user to consume the queued sample notification.') }}</div>
                </div>
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-default">{{ __('Queue test notification') }}</button>
                </div>
            </div>
        </form>

        <hr>
        <div class="text-muted">
            {{ __('For immediate UI checks, use Preview popup in the Appearance tab.') }}
        </div>
    </div>
</div>
