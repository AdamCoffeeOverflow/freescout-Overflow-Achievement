@php
    // Render hidden inputs for all known settings keys.
    // This prevents FreeScout SettingsController from resetting omitted options to defaults.
@endphp

@if (!empty($settings_values) && is_array($settings_values))
    @foreach ($settings_values as $k => $v)
        <input type="hidden" name="settings[{{ $k }}]" value="{{ e($settings_scalar($k, '')) }}" />
    @endforeach
@endif
