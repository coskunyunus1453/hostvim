<style id="hostvim-theme"><?php echo $themeCssVariables ?? ''; ?></style>
<script>
    (function () {
        const defaultMode = <?php echo json_encode($themeDefaultMode ?? 'system', 15, 512) ?>;
        const stored = localStorage.getItem('hostvim-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        let mode = stored || defaultMode;
        if (mode === 'system') mode = prefersDark ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', mode === 'dark');
        document.documentElement.dataset.theme = mode;
    })();
</script>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/theme-styles.blade.php ENDPATH**/ ?>