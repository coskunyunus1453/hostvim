<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($seo)): ?>
    <title><?php echo e($seo['title'] ?? $siteName); ?></title>
    <meta name="description" content="<?php echo e($seo['description'] ?? ''); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($seo['keywords'])): ?>
        <meta name="keywords" content="<?php echo e($seo['keywords']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <meta name="robots" content="<?php echo e($seo['robots'] ?? 'index,follow'); ?>">
    <link rel="canonical" href="<?php echo e($seo['canonical'] ?? url()->current()); ?>">

    
    <meta property="og:locale" content="<?php echo e($seo['locale'] ?? 'tr_TR'); ?>">
    <meta property="og:type" content="<?php echo e($seo['og_type'] ?? 'website'); ?>">
    <meta property="og:title" content="<?php echo e($seo['title'] ?? $siteName); ?>">
    <meta property="og:description" content="<?php echo e($seo['description'] ?? ''); ?>">
    <meta property="og:url" content="<?php echo e($seo['canonical'] ?? url()->current()); ?>">
    <meta property="og:site_name" content="<?php echo e($seo['site_name'] ?? $siteName); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($seo['og_image'])): ?>
        <meta property="og:image" content="<?php echo e($seo['og_image']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($seo['published_at'])): ?>
        <meta property="article:published_time" content="<?php echo e($seo['published_at']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($seo['modified_at'])): ?>
        <meta property="article:modified_time" content="<?php echo e($seo['modified_at']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <meta name="twitter:card" content="<?php echo e($seo['twitter_card'] ?? 'summary_large_image'); ?>">
    <meta name="twitter:title" content="<?php echo e($seo['title'] ?? $siteName); ?>">
    <meta name="twitter:description" content="<?php echo e($seo['description'] ?? ''); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($seo['og_image'])): ?>
        <meta name="twitter:image" content="<?php echo e($seo['og_image']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($siteSettings['seo_google_verification'])): ?>
        <meta name="google-site-verification" content="<?php echo e($siteSettings['seo_google_verification']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($siteSettings['seo_bing_verification'])): ?>
        <meta name="msvalidate.01" content="<?php echo e($siteSettings['seo_bing_verification']); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php else: ?>
    <?php
        $isPrivatePage = request()->routeIs(['cart.*', 'checkout.*', 'payment.*', 'account.*', 'login', 'register']);
    ?>
    <title><?php echo $__env->yieldContent('title', $siteName); ?> — Güvenilir Hosting & Sunucu Çözümleri</title>
    <meta name="description" content="<?php echo $__env->yieldContent('meta_description', $siteSettings['meta_description'] ?? ''); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isPrivatePage): ?>
        <meta name="robots" content="noindex,nofollow">
        <link rel="canonical" href="<?php echo e(url()->current()); ?>">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/seo-head.blade.php ENDPATH**/ ?>