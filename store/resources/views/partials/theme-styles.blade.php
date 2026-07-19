<style id="hostvim-theme">{!! $themeCssVariables ?? '' !!}</style>
<script>
    (function () {
        // Tema yalnızca cihaz/sistem tercihine göre (manuel buton yok)
        try { localStorage.removeItem('hostvim-theme'); } catch (e) {}
        const mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', mode === 'dark');
        document.documentElement.dataset.theme = mode;
    })();
</script>
