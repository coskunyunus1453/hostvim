<?php
    $logoH = $height ?? ($siteLogoHeight ?? 40);
    $logoUrl = $siteLogoUrl ?? null;
    $logoDarkUrl = $siteLogoDarkUrl ?? null;
    $hasDarkVariant = filled($logoDarkUrl) && $logoDarkUrl !== $logoUrl;
    $showName = $siteLogoShowName ?? true;
    $logoSlotW = max($logoH * 2, $logoH);
?>
<a href="<?php echo e(route('home')); ?>" class="flex shrink-0 items-center gap-2 <?php echo e($class ?? ''); ?>">
    <span class="inline-flex shrink-0 items-center justify-center" style="width: <?php echo e($logoSlotW); ?>px; min-width: <?php echo e($logoSlotW); ?>px; height: <?php echo e($logoH); ?>px;">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logoUrl): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDarkVariant): ?>
                <img
                    src="<?php echo e($logoUrl); ?>"
                    alt="<?php echo e($siteName); ?>"
                    class="h-full w-auto max-w-full object-contain dark:hidden"
                    width="<?php echo e($logoSlotW); ?>"
                    height="<?php echo e($logoH); ?>"
                    decoding="async"
                >
                <img
                    src="<?php echo e($logoDarkUrl); ?>"
                    alt="<?php echo e($siteName); ?>"
                    class="hidden h-full w-auto max-w-full object-contain dark:block"
                    width="<?php echo e($logoSlotW); ?>"
                    height="<?php echo e($logoH); ?>"
                    decoding="async"
                >
            <?php else: ?>
                <img
                    src="<?php echo e($logoUrl); ?>"
                    alt="<?php echo e($siteName); ?>"
                    class="h-full w-auto max-w-full object-contain"
                    width="<?php echo e($logoSlotW); ?>"
                    height="<?php echo e($logoH); ?>"
                    decoding="async"
                >
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php else: ?>
            <span class="inline-flex items-center justify-center rounded-xl bg-gradient-to-br from-hv-primary to-hv-secondary font-extrabold text-white shadow-md" style="width: <?php echo e($logoH); ?>px; height: <?php echo e($logoH); ?>px; font-size: <?php echo e(max(12, (int) ($logoH * 0.4))); ?>px;">
                <?php echo e(mb_substr($siteName, 0, 1)); ?>

            </span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </span>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showName): ?>
        <span class="font-bold tracking-tight text-hv-text <?php echo e($nameClass ?? 'text-xl'); ?>"><?php echo e($siteName); ?></span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</a>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/site-logo.blade.php ENDPATH**/ ?>