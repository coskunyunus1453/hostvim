@php
    $hvAnalyticsBodyCode = \App\Services\LandingAppearance::analyticsBodyCode();
@endphp
@if ($hvAnalyticsBodyCode)
    {!! $hvAnalyticsBodyCode !!}
@endif
