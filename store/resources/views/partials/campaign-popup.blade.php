@if($popupCampaign)
    @php
        $endsAt = $popupCampaign->ends_at?->toIso8601String();
        $popupImage = $popupCampaign->popup_image ? asset('storage/' . $popupCampaign->popup_image) : null;
    @endphp
    <div
        id="campaign-popup"
        class="campaign-popup"
        data-campaign-id="{{ $popupCampaign->id }}"
        data-ends-at="{{ $endsAt }}"
        data-show-countdown="{{ $popupCampaign->show_countdown ? '1' : '0' }}"
        hidden
    >
        <div class="campaign-popup-backdrop" data-popup-close></div>
        <div class="campaign-popup-panel" role="dialog" aria-modal="true" aria-labelledby="campaign-popup-title">
            <button type="button" class="campaign-popup-close" data-popup-close aria-label="Kapat">&times;</button>
            @if($popupImage)
                <img src="{{ $popupImage }}" alt="" class="campaign-popup-image">
            @endif
            <div class="campaign-popup-body">
                @if($popupCampaign->badge_text)
                    <span class="campaign-popup-badge">{{ $popupCampaign->badge_text }}</span>
                @endif
                <h2 id="campaign-popup-title" class="campaign-popup-title">{{ $popupCampaign->title }}</h2>
                @if($popupCampaign->description)
                    <p class="campaign-popup-desc">{{ $popupCampaign->description }}</p>
                @endif
                @if($popupCampaign->show_countdown && $popupCampaign->ends_at)
                    <div class="campaign-popup-countdown">
                        <span class="text-sm text-hv-muted">Kampanya bitişine:</span>
                        <span class="campaign-countdown campaign-countdown-lg" data-countdown-for="campaign-popup"></span>
                    </div>
                @endif
                @if($popupCampaign->code)
                    <div class="campaign-popup-code">
                        <span class="campaign-popup-code-label">Kupon kodu</span>
                        <button type="button" class="campaign-code-copy campaign-code-copy-lg" data-code="{{ $popupCampaign->code }}">{{ $popupCampaign->code }}</button>
                    </div>
                @endif
                @if($popupCampaign->cta_text && $popupCampaign->cta_url)
                    <a href="{{ $popupCampaign->cta_url }}" class="btn-primary campaign-popup-cta">{{ $popupCampaign->cta_text }}</a>
                @endif
            </div>
        </div>
    </div>
@endif
