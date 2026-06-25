@if($flashCampaign)
    @php
        $barColor = $flashCampaign->bar_color ?: null;
        $endsAt = $flashCampaign->ends_at?->toIso8601String();
    @endphp
    <div
        id="campaign-flash-bar"
        class="campaign-flash-bar"
        @if($barColor) style="--campaign-bar-bg: {{ $barColor }};" @endif
        data-ends-at="{{ $endsAt }}"
        data-show-countdown="{{ $flashCampaign->show_countdown ? '1' : '0' }}"
    >
        <div class="campaign-flash-inner">
            <div class="campaign-flash-content">
                @if($flashCampaign->badge_text)
                    <span class="campaign-flash-badge">{{ $flashCampaign->badge_text }}</span>
                @endif
                <strong class="campaign-flash-title">{{ $flashCampaign->title }}</strong>
                @if($flashCampaign->description)
                    <span class="campaign-flash-desc">{{ $flashCampaign->description }}</span>
                @endif
            </div>
            <div class="campaign-flash-actions">
                @if($flashCampaign->show_countdown && $flashCampaign->ends_at)
                    <span class="campaign-countdown" data-countdown-for="campaign-flash-bar"></span>
                @endif
                @if($flashCampaign->cta_text && $flashCampaign->cta_url)
                    <a href="{{ $flashCampaign->cta_url }}" class="campaign-flash-cta">{{ $flashCampaign->cta_text }}</a>
                @endif
                @if($flashCampaign->code)
                    <button type="button" class="campaign-code-copy" data-code="{{ $flashCampaign->code }}" title="Kodu kopyala">{{ $flashCampaign->code }}</button>
                @endif
                <button type="button" class="campaign-flash-close" aria-label="Kapat">&times;</button>
            </div>
        </div>
    </div>
@endif
