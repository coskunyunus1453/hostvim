<?php
    $stats = array_filter([
        ['value' => $hero?->stat_1_value, 'label' => $hero?->stat_1_label],
        ['value' => $hero?->stat_2_value, 'label' => $hero?->stat_2_label],
        ['value' => $hero?->stat_3_value, 'label' => $hero?->stat_3_label],
    ], fn ($s) => filled($s['value']));
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats !== []): ?>
    <div class="mt-10 flex flex-wrap gap-8 text-sm text-hv-muted">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="hv-hero-stat animate-fade-up" style="animation-delay: <?php echo e(0.15 + $i * 0.1); ?>s">
                <span class="block text-2xl font-bold <?php echo e($i % 2 === 0 ? 'text-hv-secondary' : 'text-hv-primary'); ?>"><?php echo e($stat['value']); ?></span>
                <?php echo e($stat['label']); ?>

            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/hero/stats.blade.php ENDPATH**/ ?>