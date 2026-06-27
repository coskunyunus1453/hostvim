<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupCampaign): ?>
    <?php
        $endsAt = $popupCampaign->ends_at?->toIso8601String();
        $popupImage = $popupCampaign->popup_image ? asset('storage/' . $popupCampaign->popup_image) : null;
    ?>
    <div
        id="campaign-popup"
        class="campaign-popup"
        data-campaign-id="<?php echo e($popupCampaign->id); ?>"
        data-ends-at="<?php echo e($endsAt); ?>"
        data-show-countdown="<?php echo e($popupCampaign->show_countdown ? '1' : '0'); ?>"
        hidden
    >
        <div class="campaign-popup-backdrop" data-popup-close></div>
        <div class="campaign-popup-panel" role="dialog" aria-modal="true" aria-labelledby="campaign-popup-title">
            <button type="button" class="campaign-popup-close" data-popup-close aria-label="Kapat">&times;</button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupImage): ?>
                <img src="<?php echo e($popupImage); ?>" alt="" class="campaign-popup-image">
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="campaign-popup-body">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupCampaign->badge_text): ?>
                    <span class="campaign-popup-badge"><?php echo e($popupCampaign->badge_text); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <h2 id="campaign-popup-title" class="campaign-popup-title"><?php echo e($popupCampaign->title); ?></h2>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupCampaign->description): ?>
                    <p class="campaign-popup-desc"><?php echo e($popupCampaign->description); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupCampaign->show_countdown && $popupCampaign->ends_at): ?>
                    <div class="campaign-popup-countdown">
                        <span class="text-sm text-hv-muted">Kampanya bitişine:</span>
                        <span class="campaign-countdown campaign-countdown-lg" data-countdown-for="campaign-popup"></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupCampaign->code): ?>
                    <div class="campaign-popup-code">
                        <span class="campaign-popup-code-label">Kupon kodu</span>
                        <button type="button" class="campaign-code-copy campaign-code-copy-lg" data-code="<?php echo e($popupCampaign->code); ?>"><?php echo e($popupCampaign->code); ?></button>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($popupCampaign->cta_text && $popupCampaign->cta_url): ?>
                    <a href="<?php echo e($popupCampaign->cta_url); ?>" class="btn-primary campaign-popup-cta"><?php echo e($popupCampaign->cta_text); ?></a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/campaign-popup.blade.php ENDPATH**/ ?>