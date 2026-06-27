<?php
    $visualClass = $class ?? '';
?>

<div class="hv-hero-visual <?php echo e($visualClass); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hero?->image): ?>
        <div class="hv-hero-visual-image hv-hero-slide-in mb-4 overflow-hidden rounded-2xl border border-hv-border shadow-lg">
            <img src="<?php echo e(asset('storage/' . $hero->image)); ?>" alt="" class="aspect-video w-full object-cover">
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('partials.hero.illustration', ['class' => $illustrationClass ?? 'max-w-xl'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/hero/visual.blade.php ENDPATH**/ ?>