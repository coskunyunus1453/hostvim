<?php
    $authUser = auth()->user();
    $isAdmin = $authUser?->is_admin ?? false;
    $loggedIn = $isCustomerLoggedIn ?? (auth()->check() && ! $isAdmin);
    $accountHref = $accountUrl ?? route('account.dashboard');
?>
<div class="flex items-center gap-1.5 sm:gap-2">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isAdmin): ?>
        <a href="<?php echo e(url('/admin')); ?>" class="btn-secondary hidden px-3 py-2 text-xs sm:inline-flex sm:text-sm">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Yönetim
        </a>
    <?php elseif($loggedIn): ?>
        <a href="<?php echo e($accountHref); ?>" class="btn-secondary hidden px-3 py-2 text-xs sm:inline-flex sm:text-sm <?php echo e(request()->routeIs('account.*') ? 'ring-2 ring-hv-primary/40' : ''); ?>">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="max-w-[8rem] truncate"><?php echo e(auth()->user()->name); ?></span>
        </a>
        <a href="<?php echo e($accountHref); ?>" class="btn-secondary inline-flex px-2.5 py-2 text-xs sm:hidden" aria-label="Hesabım">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </a>
    <?php else: ?>
        <a href="<?php echo e(route('login')); ?>" class="btn-ghost hidden px-3 py-2 text-xs sm:inline-flex sm:text-sm">Giriş</a>
        <a href="<?php echo e(route('register')); ?>" class="btn-primary hidden px-3 py-2 text-xs sm:inline-flex sm:text-sm">Kayıt Ol</a>
        <a href="<?php echo e(route('login')); ?>" class="btn-ghost inline-flex px-2.5 py-2 text-xs sm:hidden" aria-label="Giriş">Giriş</a>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/header-account.blade.php ENDPATH**/ ?>