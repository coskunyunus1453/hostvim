<?php
    $footerStyle = $themeFooterStyle ?? 'default';
    $footerClass = 'border-t border-hv-border hv-footer-' . $footerStyle;
    $legalLinks = [
        'mesafeli-satis-sozlesmesi' => 'Mesafeli Satış Sözleşmesi',
        'iade-iptal-politikasi' => 'İade & İptal',
        'kvkk' => 'KVKK',
        'gizlilik' => 'Gizlilik',
        'kullanim-sartlari' => 'Kullanım Şartları',
        'cerez-politikasi' => 'Çerez Politikası',
    ];
?>
<footer class="<?php echo e($footerClass); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($footerStyle === 'minimal'): ?>
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 text-center text-sm text-hv-muted md:flex-row">
                <div class="flex items-center gap-2">
                    <?php echo $__env->make('partials.site-logo', ['height' => $siteLogoFooterHeight ?? 32, 'nameClass' => 'text-sm font-bold'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
                <p>&copy; <?php echo e(date('Y')); ?> <?php echo e($siteName); ?>. Tüm hakları saklıdır.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="<?php echo e(route('domain.index')); ?>" class="hover:text-hv-primary">Domain</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($panelLoginUrl && $panelLoginUrl !== '/login'): ?>
                        <a href="<?php echo e($panelLoginUrl); ?>" class="hover:text-hv-primary" target="_blank" rel="noopener noreferrer">Müşteri Paneli</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <a href="<?php echo e(route('blog.index')); ?>" class="hover:text-hv-primary">Blog</a>
                    <a href="<?php echo e(route('contact.index')); ?>" class="hover:text-hv-primary">İletişim</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($footerMenu): ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $footerMenu->activeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <a href="<?php echo e($item->href); ?>" class="hover:text-hv-primary" target="<?php echo e($item->safe_target); ?>"><?php echo e($item->label); ?></a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1 border-t border-hv-border pt-4 text-xs text-hv-muted">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $legalLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slug => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('pages.show', $slug)); ?>" class="hover:text-hv-primary"><?php echo e($label); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <div class="flex items-center gap-2">
                        <?php echo $__env->make('partials.site-logo', ['height' => $siteLogoFooterHeight ?? 32, 'nameClass' => 'text-lg font-bold'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-hv-muted">
                        <?php echo e($siteSettings['footer_text'] ?? 'Türkiye\'nin güvenilir hosting, VPS, VDS ve sunucu çözüm ortağı. 7/24 teknik destek, yüksek performans altyapısı.'); ?>

                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">Hizmetler</h4>
                    <ul class="mt-4 space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navCategories->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <li><a href="<?php echo e(route('products.category', $cat->slug)); ?>" class="text-sm text-hv-muted hover:text-hv-primary"><?php echo e($cat->name); ?></a></li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">Destek</h4>
                    <ul class="mt-4 space-y-2 text-sm text-hv-muted">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($panelLoginUrl && $panelLoginUrl !== '/login'): ?>
                            <li><a href="<?php echo e($panelLoginUrl); ?>" class="hover:text-hv-primary" target="_blank" rel="noopener noreferrer">Müşteri Paneli</a></li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <li><a href="<?php echo e(route('pages.show', 'sss')); ?>" class="hover:text-hv-primary">SSS</a></li>
                        <li><a href="<?php echo e(route('contact.index')); ?>" class="hover:text-hv-primary">İletişim & Destek</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">Kurumsal</h4>
                    <ul class="mt-4 space-y-2 text-sm text-hv-muted">
                        <li><a href="<?php echo e(route('domain.index')); ?>" class="hover:text-hv-primary">Domain Sorgula</a></li>
                        <li><a href="<?php echo e(route('blog.index')); ?>" class="hover:text-hv-primary">Blog</a></li>
                        <li><a href="<?php echo e(route('contact.index')); ?>" class="hover:text-hv-primary">İletişim</a></li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($footerMenu): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $footerMenu->activeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <li><a href="<?php echo e($item->href); ?>" class="hover:text-hv-primary" target="<?php echo e($item->safe_target); ?>" <?php if($item->safe_target === '_blank'): ?> rel="noopener noreferrer" <?php endif; ?>><?php echo e($item->label); ?></a></li>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-hv-text">İletişim</h4>
                    <ul class="mt-4 space-y-2 text-sm text-hv-muted">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phone = $siteSettings['contact_phone'] ?? null): ?>
                            <li><?php echo e($phone); ?></li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($email = $siteSettings['contact_email'] ?? null): ?>
                            <li><a href="mailto:<?php echo e($email); ?>" class="hover:text-hv-primary"><?php echo e($email); ?></a></li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($address = $siteSettings['contact_address'] ?? null): ?>
                            <li><?php echo e($address); ?></li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-wrap justify-center gap-x-5 gap-y-2 border-t border-hv-border pt-8 text-sm text-hv-muted md:justify-start">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $legalLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slug => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('pages.show', $slug)); ?>" class="hover:text-hv-primary"><?php echo e($label); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
            <div class="mt-6 flex flex-col items-center justify-between gap-4 text-sm text-hv-muted md:flex-row">
                <p>&copy; <?php echo e(date('Y')); ?> <?php echo e($siteName); ?>. Tüm hakları saklıdır.</p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($themeFooterShowStats ?? true): ?>
                    <div class="flex gap-4">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hv-secondary"></span> 7/24 Destek</span>
                        <span>%99.9 Uptime SLA</span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</footer>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/footer.blade.php ENDPATH**/ ?>