/**
 * Client-side gate for maintenance HTML tools.
 *
 * Operators enter the shared secret on dashboard.html. The key is kept in
 * sessionStorage and sent as X-Api-Key on fetch() calls. Direct navigation
 * to .php URLs (window.open, form submit, anchor clicks) gets api_key appended.
 */
(function () {
    const STORAGE_KEY = 'gfm_maintenance_api_key';
    const DASHBOARD = 'dashboard.html';
    const origFetch = window.fetch.bind(window);
    const origOpen = window.open.bind(window);

    function getKey() {
        return sessionStorage.getItem(STORAGE_KEY) || '';
    }

    function setKey(key) {
        sessionStorage.setItem(STORAGE_KEY, key);
    }

    function clearKey() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function isPhpUrl(urlString) {
        try {
            const url = new URL(urlString, window.location.href);
            return url.pathname.indexOf('.php') !== -1;
        } catch (e) {
            return String(urlString).indexOf('.php') !== -1;
        }
    }

    function withApiKey(urlString) {
        const key = getKey();
        if (!key || !isPhpUrl(urlString)) {
            return urlString;
        }
        try {
            const url = new URL(urlString, window.location.href);
            url.searchParams.set('api_key', key);
            return url.pathname + url.search + url.hash;
        } catch (e) {
            return urlString;
        }
    }

    function isDashboardPage() {
        return /\/dashboard\.html$/i.test(window.location.pathname)
            || /\/maintenance\/?$/i.test(window.location.pathname);
    }

    window.fetch = function (input, init) {
        init = init || {};
        const headers = new Headers(init.headers || {});
        const key = getKey();
        if (key && !headers.has('X-Api-Key')) {
            headers.set('X-Api-Key', key);
        }
        init.headers = headers;
        return origFetch(input, init);
    };

    window.open = function (url, target, features) {
        if (typeof url === 'string' && isPhpUrl(url)) {
            url = withApiKey(url);
        }
        return origOpen(url, target, features);
    };

    function ensureFormApiKey(form) {
        const key = getKey();
        if (!key) {
            return;
        }
        const action = form.getAttribute('action') || '';
        if (!isPhpUrl(action)) {
            return;
        }
        if (form.querySelector('input[name="api_key"]')) {
            return;
        }
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'api_key';
        input.value = key;
        form.appendChild(input);
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (form && form.tagName === 'FORM') {
            ensureFormApiKey(form);
        }
    }, true);

    function decoratePhpLinks(root) {
        const key = getKey();
        if (!key) {
            return;
        }
        (root || document).querySelectorAll('a[href*=".php"]').forEach(function (anchor) {
            try {
                const href = anchor.getAttribute('href');
                if (!href || !isPhpUrl(href)) {
                    return;
                }
                anchor.setAttribute('href', withApiKey(href));
            } catch (e) {
                // ignore malformed href
            }
        });
    }

    async function verifyKey(key) {
        const response = await origFetch('auth_verify.php', {
            headers: { 'X-Api-Key': key },
        });
        return response.ok;
    }

    async function downloadPhp(url, init) {
        const response = await origFetch(url, init || {});
        if (!response.ok) {
            let message = 'Download failed (' + response.status + ')';
            try {
                const data = await response.json();
                if (data.error) {
                    message = data.error;
                }
            } catch (e) {
                // non-json error body
            }
            throw new Error(message);
        }

        const blob = await response.blob();
        const disposition = response.headers.get('content-disposition') || '';
        const match = disposition.match(/filename="?([^";]+)"?/i);
        const filename = match ? match[1] : 'download';

        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(link.href);

        return filename;
    }

    function showUnlockOverlay() {
        if (document.getElementById('gfm-maintenance-unlock')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.id = 'gfm-maintenance-unlock';
        overlay.style.cssText = [
            'position:fixed', 'inset:0', 'z-index:99999', 'display:flex',
            'align-items:center', 'justify-content:center',
            'background:rgba(15,23,42,0.85)', 'padding:20px',
        ].join(';');

        overlay.innerHTML = [
            '<div style="background:#fff;border-radius:12px;max-width:420px;width:100%;',
            'padding:28px;box-shadow:0 20px 50px rgba(0,0,0,0.35);font-family:Segoe UI,sans-serif;">',
            '<h2 style="margin:0 0 8px;color:#1e293b;">Maintenance access</h2>',
            '<p style="margin:0 0 20px;color:#64748b;line-height:1.5;">',
            'Enter the maintenance API key from <code>api/library/config.ini</code>',
            ' (<code>[maintenance] api_key</code>).',
            '</p>',
            '<label style="display:block;font-weight:600;margin-bottom:8px;color:#334155;">X-Api-Key</label>',
            '<input id="gfm-maintenance-key-input" type="password" autocomplete="off" ',
            'style="width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px;font-size:16px;box-sizing:border-box;" ',
            'placeholder="Paste maintenance API key">',
            '<p id="gfm-maintenance-key-error" style="color:#dc2626;margin:12px 0 0;min-height:20px;"></p>',
            '<button id="gfm-maintenance-key-submit" type="button" ',
            'style="margin-top:16px;width:100%;padding:12px;border:none;border-radius:8px;',
            'background:#2563eb;color:#fff;font-size:16px;font-weight:600;cursor:pointer;">',
            'Unlock maintenance tools</button>',
            '</div>',
        ].join('');

        document.body.appendChild(overlay);

        const input = document.getElementById('gfm-maintenance-key-input');
        const error = document.getElementById('gfm-maintenance-key-error');
        const submit = document.getElementById('gfm-maintenance-key-submit');

        async function attemptUnlock() {
            const candidate = (input.value || '').trim();
            error.textContent = '';
            if (!candidate) {
                error.textContent = 'API key is required.';
                return;
            }
            submit.disabled = true;
            submit.textContent = 'Verifying...';
            try {
                const ok = await verifyKey(candidate);
                if (!ok) {
                    error.textContent = 'Invalid API key. Check config.ini and try again.';
                    return;
                }
                setKey(candidate);
                overlay.remove();
                decoratePhpLinks();
                const params = new URLSearchParams(window.location.search);
                const returnTo = params.get('return');
                if (returnTo) {
                    window.location.replace(returnTo);
                    return;
                }
                window.location.reload();
            } catch (err) {
                error.textContent = 'Could not verify key: ' + err.message;
            } finally {
                submit.disabled = false;
                submit.textContent = 'Unlock maintenance tools';
            }
        }

        submit.addEventListener('click', attemptUnlock);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                attemptUnlock();
            }
        });
        input.focus();
    }

    function injectSessionBadge() {
        if (!getKey() || document.getElementById('gfm-maintenance-session-badge')) {
            return;
        }
        const badge = document.createElement('div');
        badge.id = 'gfm-maintenance-session-badge';
        badge.style.cssText = [
            'position:fixed', 'bottom:16px', 'right:16px', 'z-index:9998',
            'background:#0f172a', 'color:#e2e8f0', 'padding:10px 14px',
            'border-radius:999px', 'font:13px/1.4 Segoe UI,sans-serif',
            'box-shadow:0 8px 24px rgba(0,0,0,0.2)',
        ].join(';');
        badge.innerHTML = 'Maintenance unlocked <button type="button" id="gfm-maintenance-lock-btn" '
            + 'style="margin-left:10px;border:none;background:#334155;color:#fff;padding:4px 10px;'
            + 'border-radius:999px;cursor:pointer;">Lock</button>';
        document.body.appendChild(badge);
        document.getElementById('gfm-maintenance-lock-btn').addEventListener('click', function () {
            clearKey();
            window.location.href = DASHBOARD;
        });
    }

    window.GfmMaintenanceAuth = {
        getKey: getKey,
        setKey: setKey,
        clearKey: clearKey,
        verifyKey: verifyKey,
        withApiKey: withApiKey,
        downloadPhp: downloadPhp,
    };

    if (!getKey()) {
        if (isDashboardPage()) {
            document.addEventListener('DOMContentLoaded', showUnlockOverlay);
        } else {
            const params = new URLSearchParams();
            params.set('return', window.location.pathname.split('/').pop() + window.location.search);
            window.location.replace(DASHBOARD + '?' + params.toString());
        }
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        decoratePhpLinks();
        injectSessionBadge();
    });
})();
