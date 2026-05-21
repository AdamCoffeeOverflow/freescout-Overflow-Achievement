@php
    // Render hidden inputs for all known settings keys.
    // This keeps other tab values intact when only one tab form is submitted.
@endphp

@if (!empty($settings_values) && is_array($settings_values))
    @php
        $oa_preserve_exclude = isset($oa_preserve_exclude) && is_array($oa_preserve_exclude)
            ? $oa_preserve_exclude
            : [];
        $posted_settings = old('settings', null);
    @endphp
    @foreach ($settings_values as $k => $v)
        @continue(in_array($k, $oa_preserve_exclude, true))
        @php
            $preserveValue = $settings_values[$k] ?? '';
            if (is_array($posted_settings) && array_key_exists($k, $posted_settings)) {
                $preserveValue = $posted_settings[$k];
            }
            if (is_array($preserveValue) || is_object($preserveValue)) {
                $preserveValue = json_encode($preserveValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        @endphp
        <input type="hidden" name="settings[{{ $k }}]" value="{{ e((string)$preserveValue) }}" />
    @endforeach
@endif
