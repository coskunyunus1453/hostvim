<?php
    $variant = $hero?->layout_variant ?? 'split';
    $variant = in_array($variant, ['split', 'centered', 'aurora'], true) ? $variant : 'split';
?>

<?php echo $__env->make('partials.hero.' . $variant, ['hero' => $hero], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/hero/index.blade.php ENDPATH**/ ?>