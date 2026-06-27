        <nav class="hidden items-center gap-5 xl:gap-6 lg:flex" aria-label="Ana menü">
            <a href="<?php echo e(route('home')); ?>" class="nav-link <?php echo e(request()->routeIs('home') ? 'nav-link-active' : ''); ?>">Ana Sayfa</a>

            <?php echo $__env->make('partials.nav-services-mega', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <a href="<?php echo e(route('domain.index')); ?>" class="nav-link <?php echo e(request()->routeIs('domain.*') ? 'nav-link-active' : ''); ?>">Domain</a>
            <a href="<?php echo e(route('blog.index')); ?>" class="nav-link <?php echo e(request()->routeIs('blog.*') ? 'nav-link-active' : ''); ?>">Blog</a>
            <a href="<?php echo e(route('contact.index')); ?>" class="nav-link <?php echo e(request()->routeIs('contact.*') ? 'nav-link-active' : ''); ?>">İletişim</a>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isCustomerLoggedIn ?? false): ?>
                <a href="<?php echo e($accountUrl ?? route('account.dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('account.*') ? 'nav-link-active' : ''); ?>">Hesabım</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($headerMenu): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $headerMenu->activeRootItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php echo $__env->make('partials.nav-menu-item', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </nav>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/nav-desktop.blade.php ENDPATH**/ ?>