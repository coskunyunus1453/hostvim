<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashCampaign): ?>
    <?php
        $barColor = $flashCampaign->bar_color ?: null;
        $endsAt = $flashCampaign->ends_at?->toIso8601String();
    ?>
    <div
        id="campaign-flash-bar"
        class="campaign-flash-bar"
        <?php if($barColor): ?> style="--campaign-bar-bg: <?php echo e($barColor); ?>;" <?php endif; ?>
        data-ends-at="<?php echo e($endsAt); ?>"
        data-show-countdown="<?php echo e($flashCampaign->show_countdown ? '1' : '0'); ?>"
    >
        <div class="campaign-flash-inner">
            <div class="campaign-flash-content">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashCampaign->badge_text): ?>
                    <span class="campaign-flash-badge"><?php echo e($flashCampaign->badge_text); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <strong class="campaign-flash-title"><?php echo e($flashCampaign->title); ?></strong>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashCampaign->description): ?>
                    <span class="campaign-flash-desc"><?php echo e($flashCampaign->description); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <div class="campaign-flash-actions">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashCampaign->show_countdown && $flashCampaign->ends_at): ?>
                    <span class="campaign-countdown" data-countdown-for="campaign-flash-bar"></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashCampaign->cta_text && $flashCampaign->cta_url): ?>
                    <a href="<?php echo e($flashCampaign->cta_url); ?>" class="campaign-flash-cta"><?php echo e($flashCampaign->cta_text); ?></a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($flashCampaign->code): ?>
                    <button type="button" class="campaign-code-copy" data-code="<?php echo e($flashCampaign->code); ?>" title="Kodu kopyala"><?php echo e($flashCampaign->code); ?></button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <button type="button" class="campaign-flash-close" aria-label="Kapat">&times;</button>
            </div>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/campaign-flash-bar.blade.php ENDPATH**/ ?>