@php
    $gaId = \App\Models\AppSetting::getValue('google_analytics_id', '');
    $gaId = trim((string) $gaId);
@endphp
@if(preg_match('/^G-[A-Z0-9]+$/i', $gaId))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $gaId }}');
</script>
@endif
