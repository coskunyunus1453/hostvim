<style id="hostvim-theme">{!! $themeCssVariables ?? '' !!}</style>
<script>
    (function () {
        const defaultMode = @json($themeDefaultMode ?? 'system');
        const stored = localStorage.getItem('hostvim-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        let mode = stored || defaultMode;
        if (mode === 'system') mode = prefersDark ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', mode === 'dark');
        document.documentElement.dataset.theme = mode;
    })();
</script>
