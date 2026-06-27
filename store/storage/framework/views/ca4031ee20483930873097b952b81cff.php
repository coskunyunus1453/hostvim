<?php $__env->startSection('content'); ?>
<section class="py-16">
    <div class="mx-auto max-w-4xl px-4 lg:px-8">
        <h1 class="text-4xl font-extrabold text-stone-900"><?php echo e($page->title); ?></h1>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($page->excerpt): ?><p class="mt-4 text-lg text-stone-600"><?php echo e($page->excerpt); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="prose-hostvim mt-10"><?php echo safe_html($page->content); ?></div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/pages/show.blade.php ENDPATH**/ ?>