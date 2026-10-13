(function () {
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function render(devices) {
        const box = document.getElementById('signedInDevices');
        if (!box) return;
        const mine = (typeof mzBrowserDeviceKey === 'function') ? mzBrowserDeviceKey() : '';
        if (!devices || !devices.length) {
            box.innerHTML = '<div class="text-secondary">No devices have signed in yet.</div>';
            return;
        }
        box.innerHTML = devices.map(function (device) {
            const here = device.deviceKey === mine ? ' · This browser' : '';
            const version = device.appVersion ? ' · ' + device.appVersion : '';
            return '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom py-2">' +
                '<div><strong>' + escapeHtml(device.label) + '</strong>' +
                '<div class="text-secondary" style="font-size:.85rem">' +
                escapeHtml(device.platform) + here + version +
                '<br>Last opened ' + escapeHtml(device.lastSeenAt || '') +
                '</div></div>' +
                '<button type="button" class="btn btn-outline-danger btn-sm device-remove" data-key="' +
                escapeHtml(device.deviceKey) + '">Remove</button></div>';
        }).join('');
        box.querySelectorAll('.device-remove').forEach(function (button) {
            button.addEventListener('click', function () {
                removeDevice(button.getAttribute('data-key'));
            });
        });
    }

    function loadDevices() {
        if (typeof mzAjaxRequest !== 'function') return;
        try {
            render(mzAjaxRequest('m_device.php', 'POST', {action: 'list'}));
        } catch (err) {
            const box = document.getElementById('signedInDevices');
            if (box) box.innerHTML = '<div class="text-danger">Devices could not be loaded.</div>';
        }
    }

    function removeDevice(deviceKey) {
        if (!deviceKey || !window.confirm('Remove this device? It will sign out and clear saved account data the next time it opens.')) {
            return;
        }
        try {
            mzAjaxRequest('m_device.php', 'POST', {action: 'remove', deviceKey: deviceKey});
            if (deviceKey === mzBrowserDeviceKey()) {
                localStorage.setItem('gems_device_resign', '1');
                sessionStorage.clear();
                window.location.href = 'p_login?f=1';
                return;
            }
            loadDevices();
        } catch (err) {
            if (typeof toastr !== 'undefined') toastr['error'](err.message, 'ERROR');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(loadDevices, 400);
    });
})();
