

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Modul Navigasi & Filter Dinamis Tanpa Reload (Progressive AJAX)
 * Menangani pencarian real-time, filter dropdown, status pills, filter chips,
 * paginasi, per-page, dan tab periode statistik tanpa reload halaman penuh.
 */
(() => {
    const REGION_SELECTOR = '#app-ajax-region';
    const PROGRESS_SELECTOR = '#app-ajax-progress';
    const AJAX_LINK_SELECTOR = [
        '.stat-period-link',
        '.filter-status-pill',
        '.active-filter-chip',
        '.active-filter-reset',
        '.modern-filter-bar a.btn-outline-secondary',
        '.pagination a.page-link',
    ].join(', ');

    let activeController = null;
    let progressTimer = null;

    function startProgress(region) {
        const bar = document.querySelector(PROGRESS_SELECTOR);
        if (bar) {
            clearTimeout(progressTimer);
            bar.classList.remove('is-done');
            bar.classList.add('is-loading');
        }
        if (region) {
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
            }, 240);
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

    async function navigateAjax(targetUrl, { pushState = true, scrollToTop = false } = {}) {
        const region = document.querySelector(REGION_SELECTOR);
        if (!region) {
            window.location.assign(targetUrl);
            return;
        }

        if (activeController) {
            activeController.abort();
        }
        const controller = new AbortController();
        activeController = controller;

        startProgress(region);

        try {
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
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextRegion = doc.querySelector(REGION_SELECTOR);

            if (!nextRegion) {
                window.location.assign(response.url || targetUrl);
                return;
            }

            const inputSnapshot = captureActiveInput(region);

            region.innerHTML = nextRegion.innerHTML;

            if (doc.title) {
                document.title = doc.title;
            }

            const finalUrl = response.url || targetUrl;
            if (pushState && finalUrl !== window.location.href) {
                window.history.pushState({ ajax: true }, doc.title || document.title, finalUrl);
            }

            restoreActiveInput(region, inputSnapshot);

            if (scrollToTop) {
                const rect = region.getBoundingClientRect();
                if (rect.top < 0) {
                    window.scrollTo({ top: Math.max(0, window.scrollY + rect.top - 24), behavior: 'smooth' });
                }
            }
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

    // Intercept submit form GET di dalam region dinamis
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if ((form.method || 'GET').toUpperCase() !== 'GET') {
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

    // Intercept klik link filter, periode statistik, dan paginasi
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
        if (!link || !(link instanceof HTMLAnchorElement)) {
            return;
        }

        if (link.target && link.target !== '_self') {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        const region = document.querySelector(REGION_SELECTOR);
        if (!region || !region.contains(link)) {
            return;
        }

        const targetUrl = new URL(link.href, window.location.origin);
        if (targetUrl.origin !== window.location.origin) {
            return;
        }

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
