import './bootstrap';

document.querySelectorAll('.flash-message').forEach((el) => {
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.4s ease';
        setTimeout(() => el.remove(), 400);
    }, 4500);
});

const cartBadge = document.getElementById('cart-badge');
if (cartBadge) {
    fetch('/sepet/sayac', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then(({ count }) => {
            const n = Number(count) || 0;
            if (n > 0) {
                cartBadge.textContent = String(n);
                cartBadge.classList.remove('hidden');
            } else {
                cartBadge.classList.add('hidden');
            }
        })
        .catch(() => {});
}

function applySystemTheme() {
    const mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark', mode === 'dark');
    document.documentElement.dataset.theme = mode;
    try { localStorage.removeItem('hostvim-theme'); } catch (e) {}
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applySystemTheme);

const mobileSidebar = document.getElementById('mobile-sidebar');
const mobileMenuOpen = document.getElementById('mobile-menu-open');
const mobileSidebarClose = document.getElementById('mobile-sidebar-close');
const mobileSidebarBackdrop = document.getElementById('mobile-sidebar-backdrop');

function openMobileSidebar() {
    if (!mobileSidebar) return;
    mobileSidebar.classList.add('is-open');
    mobileSidebar.setAttribute('aria-hidden', 'false');
    mobileMenuOpen?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('sidebar-open');
}

function closeMobileSidebar() {
    if (!mobileSidebar) return;
    mobileSidebar.classList.remove('is-open');
    mobileSidebar.setAttribute('aria-hidden', 'true');
    mobileMenuOpen?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('sidebar-open');
}

mobileMenuOpen?.addEventListener('click', openMobileSidebar);
mobileSidebarClose?.addEventListener('click', closeMobileSidebar);
mobileSidebarBackdrop?.addEventListener('click', closeMobileSidebar);

mobileSidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMobileSidebar);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMobileSidebar();
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        closeMobileSidebar();
    }
});

function formatCountdown(ms) {
    if (ms <= 0) return '00:00:00';
    const total = Math.floor(ms / 1000);
    const h = String(Math.floor(total / 3600)).padStart(2, '0');
    const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
    const s = String(total % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function initCountdowns() {
    document.querySelectorAll('[data-countdown-for]').forEach((el) => {
        const parentId = el.getAttribute('data-countdown-for');
        const parent = document.getElementById(parentId);
        const endsAt = parent?.dataset?.endsAt;
        if (!endsAt || parent?.dataset?.showCountdown !== '1') return;

        const tick = () => {
            const diff = new Date(endsAt).getTime() - Date.now();
            el.textContent = formatCountdown(diff);
            if (diff <= 0) el.textContent = 'Sona erdi';
        };
        tick();
        setInterval(tick, 1000);
    });
}

const flashBar = document.getElementById('campaign-flash-bar');
if (flashBar) {
    document.body.classList.add('has-flash-bar');
    if (sessionStorage.getItem('hostvim-flash-closed') === '1') {
        flashBar.classList.add('is-hidden');
    }
    flashBar.querySelector('.campaign-flash-close')?.addEventListener('click', () => {
        flashBar.classList.add('is-hidden');
        sessionStorage.setItem('hostvim-flash-closed', '1');
    });
}

const campaignPopup = document.getElementById('campaign-popup');
if (campaignPopup) {
    const popupId = campaignPopup.dataset.campaignId;
    const storageKey = `hostvim-popup-${popupId}`;
    const closePopup = () => {
        campaignPopup.hidden = true;
        localStorage.setItem(storageKey, '1');
        document.body.classList.remove('campaign-popup-open');
    };

    if (!localStorage.getItem(storageKey)) {
        campaignPopup.hidden = false;
        document.body.classList.add('campaign-popup-open');
    }

    campaignPopup.querySelectorAll('[data-popup-close]').forEach((btn) => {
        btn.addEventListener('click', closePopup);
    });
}

document.querySelectorAll('.campaign-code-copy').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const code = btn.dataset.code;
        if (!code) return;
        try {
            await navigator.clipboard.writeText(code);
            const original = btn.textContent;
            btn.textContent = 'Kopyalandı!';
            setTimeout(() => { btn.textContent = original; }, 1500);
        } catch {
            btn.textContent = code;
        }
    });
});

initCountdowns();

function initNavDropdowns() {
    const roots = document.querySelectorAll('[data-nav-dropdown]');
    if (!roots.length) return;

    const closeAll = (except = null) => {
        roots.forEach((root) => {
            if (root === except) return;
            root.classList.remove('is-open');
            root.querySelector('[data-nav-dropdown-trigger]')?.setAttribute('aria-expanded', 'false');
        });
    };

    roots.forEach((root) => {
        const trigger = root.querySelector('[data-nav-dropdown-trigger]');
        const panel = root.querySelector('[data-nav-dropdown-panel]');
        if (!trigger || !panel) return;

        let closeTimer = null;

        const open = () => {
            clearTimeout(closeTimer);
            closeAll(root);
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        };

        const scheduleClose = () => {
            closeTimer = setTimeout(() => {
                root.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }, 220);
        };

        root.addEventListener('mouseenter', open);
        root.addEventListener('mouseleave', scheduleClose);

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            if (root.classList.contains('is-open')) {
                root.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            } else {
                open();
            }
        });

        panel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => closeAll());
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-nav-dropdown]')) {
            closeAll();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
}

initNavDropdowns();
