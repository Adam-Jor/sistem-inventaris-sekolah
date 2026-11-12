import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Modul Navigasi Instan, Hover Prefetching & Filter Dinamis Tanpa Reload (Progressive AJAX)
 * Menangani perpindahan menu sidebar, breadcrumb, filter statistik, pencarian real-time,
 * status pills, filter chips, dan paginasi secara instan dengan in-memory prefetch cache.
 */
(() => {
    const REGION_SELECTOR = '#app-ajax-region';
    const PROGRESS_SELECTOR = '#app-ajax-progress';
    const BREADCRUMB_SELECTOR = '#navbarBlur nav[aria-label="breadcrumb"]';
    const CACHE_TTL_MS = 20000;

    // Tautan di dalam #app-ajax-region maupun navigasi global (Sidebar, Navbar, Bottom Nav)
    const AJAX_LINK_SELECTOR = [
        '.app-nav-link',
        '.app-mobile-bottom-link',
        '#sidenav-main .navbar-brand',
        '#navbarBlur .breadcrumb a',
        '.stat-ajax-link',
        '.stat-period-link',
        '.stat-chart-range-link',
        '.stat-bar-col-interactive',
        '.stat-trend-mobile-link',
        '.stat-active-month-reset',
        '.stat-kpi-link',
        '.stat-quiet-action',
        '.stat-drill-link',
        '.stat-legend-link',
        '.stat-sunburst-group-pill',
        '.filter-status-pill',
        '.active-filter-chip',
        '.active-filter-reset',
        '.modern-filter-bar a.btn-outline-secondary',
        '.pagination a.page-link',
    ].join(', ');

    const prefetchCache = new Map();
    const inflightPrefetches = new Map();
    let activeController = null;
    let progressTimer = null;
    let hoverTimer = null;

    function normalizeUrl(rawUrl) {
        try {
            const u = new URL(rawUrl, window.location.origin);
            u.hash = '';
            return u.toString();
        } catch (_) {
            return rawUrl;
        }
    }

    function isAjaxEligibleLink(link) {
        if (!link || !(link instanceof HTMLAnchorElement)) {
            return false;
        }
        if (link.hasAttribute('download') || link.hasAttribute('data-no-ajax')) {
            return false;
        }
        if (link.target && link.target !== '_self') {
            return false;
        }
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return false;
        }
        try {
            const targetUrl = new URL(link.href, window.location.origin);
            if (targetUrl.origin !== window.location.origin) {
                return false;
            }
            // Jangan intercept route unduhan/cetak laporan
            if (/\/(export|print|download)(\/|$)/i.test(targetUrl.pathname)) {
                return false;
            }
            return true;
        } catch (_) {
            return false;
        }
    }

    function getCachedHtml(url) {
        const key = normalizeUrl(url);
        const entry = prefetchCache.get(key);
        if (!entry) {
            return null;
        }
        if (Date.now() - entry.timestamp > CACHE_TTL_MS) {
            prefetchCache.delete(key);
            return null;
        }
        return entry;
    }

    function setCachedHtml(url, html, finalUrl) {
        const key = normalizeUrl(url);
        if (prefetchCache.size >= 25) {
            const oldestKey = prefetchCache.keys().next().value;
            if (oldestKey) {
                prefetchCache.delete(oldestKey);
            }
        }
        prefetchCache.set(key, {
            html,
            finalUrl: finalUrl || key,
            timestamp: Date.now(),
        });
    }

    function prefetchUrl(targetUrl) {
        const key = normalizeUrl(targetUrl);
        if (getCachedHtml(key) || inflightPrefetches.has(key)) {
            return inflightPrefetches.get(key) || Promise.resolve(getCachedHtml(key));
        }

        const promise = fetch(key, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html, application/xhtml+xml',
                'X-Prefetch': '1',
            },
            credentials: 'same-origin',
        })
            .then(async (response) => {
                if (!response.ok || (response.redirected && !response.url.startsWith(window.location.origin))) {
                    return null;
                }
                const html = await response.text();
                const finalUrl = response.url || key;
                setCachedHtml(key, html, finalUrl);
                if (finalUrl !== key) {
                    setCachedHtml(finalUrl, html, finalUrl);
                }
                return { html, finalUrl };
            })
            .catch(() => null)
            .finally(() => {
                inflightPrefetches.delete(key);
            });

        inflightPrefetches.set(key, promise);
        return promise;
    }

    function startProgress(region, isInstant = false) {
        const bar = document.querySelector(PROGRESS_SELECTOR);
        if (bar && !isInstant) {
            clearTimeout(progressTimer);
            bar.classList.remove('is-done');
            bar.classList.add('is-loading');
        }
        if (region && !isInstant) {
            region.classList.add('is-fetching');
        }
    }

    function finishProgress(region) {
        const bar = document.querySelector(PROGRESS_SELECTOR);
        if (bar) {
            bar.classList.remove('is-loading');
            bar.classList.add('is-done');
            clearTimeout(progressTimer);
            progressTimer = setTimeout(() => {
                bar.classList.remove('is-done');
            }, 200);
        }
        if (region) {
            region.classList.remove('is-fetching');
        }
    }

    function captureActiveInput(region) {
        const active = document.activeElement;
        if (
            !active ||
            !region.contains(active) ||
            active.tagName !== 'INPUT' ||
            !active.name ||
            !['text', 'search'].includes((active.type || 'text').toLowerCase())
        ) {
            return null;
        }

        return {
            name: active.name,
            value: active.value,
            selectionStart: active.selectionStart,
            selectionEnd: active.selectionEnd,
        };
    }

    function restoreActiveInput(region, snapshot) {
        if (!snapshot) {
            return;
        }

        const escapedName = snapshot.name.replace(/"/g, '\\"');
        const nextInput = region.querySelector(`input[name="${escapedName}"]`);
        if (!nextInput) {
            return;
        }

        nextInput.value = snapshot.value;
        nextInput.focus({ preventScroll: true });

        try {
            if (typeof snapshot.selectionStart === 'number' && typeof snapshot.selectionEnd === 'number') {
                nextInput.setSelectionRange(snapshot.selectionStart, snapshot.selectionEnd);
            }
        } catch (_) {
            // Abaikan jika tipe input tidak mendukung setSelectionRange
        }
    }

    function syncShellNavigation(doc) {
        // 1. Sinkronkan status menu aktif di Sidebar
        const currentNavLinks = document.querySelectorAll('#sidenav-main .app-nav-link');
        const nextNavLinks = doc.querySelectorAll('#sidenav-main .app-nav-link');
        if (currentNavLinks.length === nextNavLinks.length) {
            currentNavLinks.forEach((link, idx) => {
                const isNextActive = nextNavLinks[idx].classList.contains('active');
                link.classList.toggle('active', isNextActive);
            });
        }

        // 2. Sinkronkan status menu aktif di Bottom Nav (Mobile User)
        const currentBottomLinks = document.querySelectorAll('.app-mobile-bottom-nav .app-mobile-bottom-link');
        const nextBottomLinks = doc.querySelectorAll('.app-mobile-bottom-nav .app-mobile-bottom-link');
        if (currentBottomLinks.length === nextBottomLinks.length) {
            currentBottomLinks.forEach((link, idx) => {
                const isNextActive = nextBottomLinks[idx].classList.contains('active');
                link.classList.toggle('active', isNextActive);
            });
        }

        // 3. Sinkronkan Breadcrumb & Judul Halaman di Navbar Atas
        const currentBreadcrumb = document.querySelector(BREADCRUMB_SELECTOR);
        const nextBreadcrumb = doc.querySelector(BREADCRUMB_SELECTOR);
        if (currentBreadcrumb && nextBreadcrumb) {
            currentBreadcrumb.innerHTML = nextBreadcrumb.innerHTML;
        }

        // 4. Tutup drawer sidebar mobile bila sedang terbuka di layar kecil
        if (window.innerWidth < 1200 && document.body.classList.contains('g-sidenav-pinned')) {
            document.body.classList.remove('g-sidenav-pinned');
        }
    }

    function applyHtmlSwap(region, html, finalUrl, { pushState = true, scrollToTop = false } = {}) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextRegion = doc.querySelector(REGION_SELECTOR);

        if (!nextRegion) {
            window.location.assign(finalUrl);
            return false;
        }

        const isSamePath = new URL(finalUrl, window.location.origin).pathname === window.location.pathname;
        const inputSnapshot = isSamePath ? captureActiveInput(region) : null;
        const prevMinHeight = region.style.minHeight;

        if (isSamePath) {
            region.style.minHeight = `${region.offsetHeight}px`;
        }

        region.innerHTML = nextRegion.innerHTML;
        syncShellNavigation(doc);

        if (isSamePath) {
            requestAnimationFrame(() => {
                region.style.minHeight = prevMinHeight;
            });
        }

        if (doc.title) {
            document.title = doc.title;
        }

        if (pushState && finalUrl !== window.location.href) {
            window.history.pushState({ ajax: true }, doc.title || document.title, finalUrl);
        }

        if (inputSnapshot) {
            restoreActiveInput(region, inputSnapshot);
        }

        if (scrollToTop || !isSamePath) {
            window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
        }

        return true;
    }

    async function navigateAjax(targetUrl, { pushState = true, scrollToTop = false } = {}) {
        const region = document.querySelector(REGION_SELECTOR);
        if (!region) {
            window.location.assign(targetUrl);
            return;
        }

        if (activeController) {
            activeController.abort();
            activeController = null;
        }

        const key = normalizeUrl(targetUrl);

        // 1. Jika sudah ada di cache memori (hasil hover prefetch), tampilkan instan 0ms!
        const cached = getCachedHtml(key);
        if (cached) {
            applyHtmlSwap(region, cached.html, cached.finalUrl, { pushState, scrollToTop });
            // Segarkan cache di latar belakang secara senyap
            prefetchUrl(key);
            return;
        }

        const controller = new AbortController();
        activeController = controller;
        startProgress(region);

        try {
            // 2. Jika sedang di-prefetch saat ini, tunggu promise prefetch yang sedang berjalan
            if (inflightPrefetches.has(key)) {
                const prefetched = await inflightPrefetches.get(key);
                if (prefetched && activeController === controller) {
                    applyHtmlSwap(region, prefetched.html, prefetched.finalUrl, { pushState, scrollToTop });
                    return;
                }
            }

            const response = await fetch(targetUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            if (!response.ok || (response.redirected && !response.url.startsWith(window.location.origin))) {
                window.location.assign(targetUrl);
                return;
            }

            const html = await response.text();
            const finalUrl = response.url || targetUrl;

            setCachedHtml(key, html, finalUrl);
            applyHtmlSwap(region, html, finalUrl, { pushState, scrollToTop });
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            window.location.assign(targetUrl);
        } finally {
            if (activeController === controller) {
                activeController = null;
                finishProgress(region);
            }
        }
    }

    function buildFormGetUrl(form) {
        const url = new URL(form.action || window.location.href, window.location.origin);
        url.search = '';

        const formData = new FormData(form);
        for (const [key, rawValue] of formData.entries()) {
            const value = typeof rawValue === 'string' ? rawValue.trim() : '';
            if (value !== '') {
                url.searchParams.append(key, value);
            }
        }

        return url.toString();
    }

    // Hover & Touchstart Prefetching: muat halaman di latar belakang saat kursor mendekati menu
    document.addEventListener('mouseover', (event) => {
        const link = event.target.closest(AJAX_LINK_SELECTOR);
        if (!isAjaxEligibleLink(link)) {
            return;
        }
        const url = normalizeUrl(link.href);
        if (url === normalizeUrl(window.location.href) || getCachedHtml(url)) {
            return;
        }
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => {
            prefetchUrl(url);
        }, 45);
    }, { passive: true });

    document.addEventListener('touchstart', (event) => {
        const link = event.target.closest(AJAX_LINK_SELECTOR);
        if (!isAjaxEligibleLink(link)) {
            return;
        }
        const url = normalizeUrl(link.href);
        if (url !== normalizeUrl(window.location.href) && !getCachedHtml(url)) {
            prefetchUrl(url);
        }
    }, { passive: true });

    // Intercept perubahan select filter [data-ajax-select] di dalam region dinamis
    document.addEventListener('change', (event) => {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || !select.hasAttribute('data-ajax-select')) {
            return;
        }

        const form = select.form;
        if (!form || (form.method || 'GET').toUpperCase() !== 'GET') {
            return;
        }

        const region = document.querySelector(REGION_SELECTOR);
        if (!region || !region.contains(select)) {
            return;
        }

        const targetUrl = buildFormGetUrl(form);
        navigateAjax(targetUrl, { pushState: true, scrollToTop: false });
    });

    // Intercept submit form di halaman
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const method = (form.method || 'GET').toUpperCase();
        if (method !== 'GET') {
            // Bersihkan prefetch cache setiap kali ada aksi mutasi data (POST/PUT/DELETE)
            prefetchCache.clear();
            return;
        }

        const region = document.querySelector(REGION_SELECTOR);
        if (!region || !region.contains(form)) {
            return;
        }

        event.preventDefault();
        const targetUrl = buildFormGetUrl(form);
        navigateAjax(targetUrl, { pushState: true, scrollToTop: false });
    });

    // Intercept klik menu sidebar, filter, periode statistik, dan paginasi
    document.addEventListener('click', (event) => {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const link = event.target.closest(AJAX_LINK_SELECTOR);
        if (!isAjaxEligibleLink(link)) {
            return;
        }

        const region = document.querySelector(REGION_SELECTOR);
        if (!region) {
            return;
        }

        const targetUrl = new URL(link.href, window.location.origin);
        event.preventDefault();

        const isPagination = Boolean(link.closest('.pagination'));
        navigateAjax(targetUrl.toString(), { pushState: true, scrollToTop: isPagination });
    });

    // Dukung tombol Back / Forward browser
    window.addEventListener('popstate', () => {
        if (document.querySelector(REGION_SELECTOR)) {
            navigateAjax(window.location.href, { pushState: false, scrollToTop: false });
        }
    });
})();

Alpine.start();
