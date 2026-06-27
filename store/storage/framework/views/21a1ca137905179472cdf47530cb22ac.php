<?php
    $panelTitle = $siteSettings['nav_services_mega_title'] ?? 'Doğru paketi seçin';
    $panelText = $siteSettings['nav_services_mega_text'] ?? '';
    $panelCtaLabel = $siteSettings['nav_services_mega_cta_label'] ?? 'Tüm paketleri gör';
    $panelCtaUrl = $siteSettings['nav_services_mega_cta_url'] ?? route('products.index');
?>
<div class="hv-nav-dropdown hv-nav-dropdown-mega hv-nav-dropdown-wide" data-nav-dropdown>
    <button type="button" class="nav-link hv-nav-trigger flex items-center gap-1" data-nav-dropdown-trigger aria-expanded="false" aria-haspopup="true">
        Hizmetler
        <svg class="hv-nav-chevron h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="hv-nav-dropdown-panel" data-nav-dropdown-panel role="menu">
        <div class="hv-mega-grid">
            <div class="hv-mega-links">
                <p class="hv-mega-section-title">Kategoriler</p>
                <div class="hv-mega-items">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e(route('products.category', $cat->slug)); ?>" class="hv-mega-link" role="menuitem">
                            <span class="hv-mega-link-icon" style="--mega-accent: <?php echo e($cat->color ?? 'var(--hv-primary)'); ?>">
                                <?php echo $__env->make('partials.nav-icon', ['icon' => 'server', 'class' => 'h-5 w-5'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </span>
                            <span class="hv-mega-link-body">
                                <span class="hv-mega-link-label"><?php echo e($cat->name); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cat->description): ?>
                                    <span class="hv-mega-link-desc"><?php echo e(\Illuminate\Support\Str::limit($cat->description, 72)); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
                <a href="<?php echo e(route('products.index')); ?>" class="hv-mega-footer-link" role="menuitem">Tüm paketler →</a>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($panelTitle || $panelText): ?>
                <aside class="hv-mega-panel" aria-label="Bilgilendirme">
                    <div class="hv-mega-panel-inner">
                        <?php echo $__env->make('partials.nav-icon', ['icon' => 'sparkles', 'class' => 'h-8 w-8 text-hv-primary'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($panelTitle): ?>
                            <h3 class="hv-mega-panel-title"><?php echo e($panelTitle); ?></h3>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($panelText): ?>
                            <p class="hv-mega-panel-text"><?php echo e($panelText); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($panelCtaLabel && $panelCtaUrl): ?>
                            <a href="<?php echo e($panelCtaUrl); ?>" class="btn-primary mt-4 inline-flex text-sm"><?php echo e($panelCtaLabel); ?></a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </aside>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/nav-services-mega.blade.php ENDPATH**/ ?>