import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
if (!window.__hvAlpineStarted) {
    Alpine.start();
    window.__hvAlpineStarted = true;
}

if (document.querySelector('[data-hv-quill]')) {
    import('./admin-editor.js').then((m) => m.initHvRichEditors());
}
