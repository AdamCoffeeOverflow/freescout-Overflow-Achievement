@php
    // Render hidden inputs for all known settings keys.
    // This keeps other tab values intact when only one tab form is submitted.
@endphp

@if (!empty($settings_values) && is_array($settings_values))
    @foreach ($settings_values as $k => $v)
        @php
            $preserveValue = old('settings.'.$k, $settings_values[$k] ?? '');
            if (is_array($preserveValue) || is_object($preserveValue)) {
                $preserveValue = json_encode($preserveValue);
            }
        @endphp
        <input type="hidden" name="settings[{{ $k }}]" value="{{ e((string)$preserveValue) }}" />
    @endforeach
@endif
