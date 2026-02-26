@extends('layouts.app')

@section('title', __('Achievement'))

@section('content')
    <div class="container" style="max-width: 980px">
        <div class="panel panel-default" style="margin-top:20px">
            <div class="panel-heading">
                <strong>{{ __('Achievement') }}</strong>
            </div>
            <div class="panel-body">
                <p style="font-size:16px">
                    {{ __('OverflowAchievement is enabled, but its database tables are missing.') }}
                </p>
                <p>
                    {{ __('This usually happens when migrations have not been executed after enabling/updating the module.') }}
                </p>

                <pre style="margin-top:12px">php artisan migrate</pre>

                <p style="margin-top:12px; opacity:.8">
                    {{ __('After running migrations, refresh this page.') }}
                </p>
            </div>
        </div>
    </div>
@endsection
