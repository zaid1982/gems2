/**
 * Single source of truth for frontend runtime configuration.
 *
 * Replaces the per-file, comment-toggled API URLs (previously duplicated in
 * js/common.js and api/library/constant.php). Include this script BEFORE
 * js/common.js on each page:
 *
 *   <script src="js/config.js"></script>
 *   <script src="js/common.js"></script>
 *
 * The API base is resolved by hostname so the same build works in every
 * environment without editing source. Add entries to HOST_MAP for hosts whose
 * API lives at a non-default path.
 */
(function () {
    'use strict';

    // host (optionally with port) -> API base URL.
    var HOST_MAP = {
        'localhost': '//localhost/gems2/api/',
        '127.0.0.1': '//127.0.0.1/gems2/api/',
        'gems.local': '//gems.local/api/',
        'gems.globalfm.com.my': '//gems.globalfm.com.my/api/',
        'gems.metadatasystem.my': '//gems.metadatasystem.my/api/'
    };

    function resolveApiBase() {
        var host = window.location.host;
        if (HOST_MAP[host]) {
            return HOST_MAP[host];
        }
        var hostname = window.location.hostname;
        if (HOST_MAP[hostname]) {
            return HOST_MAP[hostname];
        }
        // Sensible default: same origin, /api/.
        return '//' + host + '/api/';
    }

    window.GFM_CONFIG = window.GFM_CONFIG || {};
    if (!window.GFM_CONFIG.apiBase) {
        window.GFM_CONFIG.apiBase = resolveApiBase();
    }
    // Convenience global used by legacy scripts.
    window.GFM_API_BASE = window.GFM_CONFIG.apiBase;
})();
