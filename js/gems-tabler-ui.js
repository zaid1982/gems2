/**
 * gems-tabler-ui.js — shared Tabler presentation helpers (P3 H1).
 *
 * Load after js/common.js on Tabler pages only (see html/scripts_tabler.html).
 * This file assigns window.GemsUI and does nothing else on load: no DOM
 * queries, no event binding, no tooltip init. Chart/status helpers are
 * side-effect-free except emptyChart(), which only writes the named node.
 *
 * Do not load on MDB pages (kpi_in.html). Do not grow this surface without
 * another architect pass. Module commons keep their public APIs and only
 * delegate the helpers listed here.
 */
(function (window) {
    'use strict';

    function escapeHtml(value) {
        if (value === null || value === undefined) { return ''; }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isNavigableHref(href) {
        if (href === undefined || href === null) { return false; }
        const s = String(href).trim();
        return s !== '' && s !== '#';
    }

    /**
     * Resolve a tooltip root.
     * No argument → document (documented whole-page contract).
     * Explicit falsy (null / undefined / false / '') → null; caller must abort
     * so a missed lookup never falls through to the whole document.
     */
    function resolveTooltipRoot(argsLength, root) {
        if (argsLength === 0) { return document; }
        return root || null;
    }

    const DT_DOM = "<'d-none'f>r<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>";
    const DT_DOM_BUTTONS = "<'d-none'B>r<'table-responsive't><'card-footer d-flex align-items-center py-2'i<'ms-auto'p>>";

    const GemsUI = {
        dtDom: DT_DOM,
        dtDomButtons: DT_DOM_BUTTONS,

        dtEmpty: function (icon, emptyText, zeroText) {
            const lang = (typeof _DATATABLE_LANGUAGE !== 'undefined') ? _DATATABLE_LANGUAGE : {};
            return window.jQuery.extend({}, lang, {
                emptyTable: GemsUI.emptyState(icon || 'fa-inbox', emptyText),
                zeroRecords: GemsUI.emptyState('fa-filter', zeroText || 'No records match the current filters.')
            });
        },

        dtButtons: function (title) {
            return [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, text: '<i class="fas fa-columns"></i>', className: 'btn btn-outline-secondary btn-sm', titleAttr: 'Column visibility' },
                { extend: 'print', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-print"></i>', title: title, titleAttr: 'Print', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} },
                { extend: 'excelHtml5', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-excel"></i>', title: title, titleAttr: 'Excel', exportOptions: typeof mzExportExcelOpt !== 'undefined' ? mzExportExcelOpt : {} },
                { extend: 'pdfHtml5', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-pdf"></i>', title: title, titleAttr: 'PDF', orientation: 'landscape', exportOptions: typeof mzExportOpt !== 'undefined' ? mzExportOpt : {} }
            ];
        },

        actionBtn: function (opts) {
            opts = opts || {};
            const navigable = isNavigableHref(opts.href);
            const tag = navigable ? 'a' : 'button';
            const href = navigable ? ' href="' + opts.href + '"' : ' type="button"';
            const extra = opts.extra || '';
            const id = opts.id ? ' id="' + opts.id + '"' : '';
            return '<' + tag + href + id + ' class="btn gems-btn-action ' + (opts.tint || '') + ' ' + (opts.cls || '') +
                '" data-toggle="tooltip" title="' + opts.title + '" aria-label="' + (opts.label || opts.title) + '" ' + extra +
                '><i class="' + opts.icon + '"></i></' + tag + '>';
        },

        emptyState: function (icon, text) {
            return '<div class="gems-empty-state"><i class="fas ' + (icon || 'fa-inbox') + '"></i><p>' + text + '</p></div>';
        },

        badge: function (kind, label) {
            return '<span class="badge gems-badge gems-badge-' + kind + '">' + label + '</span>';
        },

        /* Chart + status helpers. Side-effect-free except emptyChart, which
           only writes the named container. No module-specific overrides. */

        chartColors: function () {
            const style = window.getComputedStyle(document.documentElement);
            const colors = [];
            for (let i = 1; i <= 6; i++) {
                const value = (style.getPropertyValue('--gems-chart-' + i) || '').trim();
                if (value) { colors.push(value); }
            }
            return colors.length ? colors : ['#6b9cd6', '#6bcfcd', '#7ab597', '#c4a46f', '#eb8181', '#70bfd2'];
        },

        chartColor: function (index) {
            const colors = GemsUI.chartColors();
            const i = Number(index);
            const n = (isNaN(i) ? 0 : i);
            return colors[((n % colors.length) + colors.length) % colors.length];
        },

        kindColor: function (kind) {
            const keys = {
                primary: '--gems-primary',
                success: '--gems-success',
                warning: '--gems-warning',
                danger: '--gems-danger',
                info: '--gems-info',
                secondary: '--gems-text-soft'
            };
            const token = keys[kind] || keys.primary;
            const value = (window.getComputedStyle(document.documentElement).getPropertyValue(token) || '').trim();
            return value || '#0055b8';
        },

        statusKind: function (label) {
            const key = String(label || '').trim().toLowerCase().replace(/[_-]+/g, ' ').replace(/\s+/g, ' ');
            /* Documented default vocabulary only. Module exceptions stay
               in the module (PTW Completed, Waste Draft/Cancelled). */
            const map = {
                active: 'success',
                completed: 'success',
                approved: 'success',
                success: 'success',
                available: 'success',
                responding: 'info',
                processing: 'info',
                assigned: 'info',
                'in progress': 'primary',
                pending: 'warning',
                verify: 'warning',
                verification: 'warning',
                attention: 'warning',
                awaiting: 'warning',
                draft: 'secondary',
                inactive: 'secondary',
                archived: 'secondary',
                closed: 'secondary',
                'not applicable': 'secondary',
                cancelled: 'danger',
                canceled: 'danger',
                rejected: 'danger',
                failed: 'danger',
                error: 'danger',
                overdue: 'danger',
                critical: 'danger'
            };
            return map[key] || null;
        },

        statusColor: function (label) {
            const kind = GemsUI.statusKind(label);
            return kind ? GemsUI.kindColor(kind) : GemsUI.chartColor(0);
        },

        badgeKindFromColor: function (statusColor) {
            const raw = String(statusColor || '').trim().toLowerCase();
            const token = raw.replace(/^badge-/, '').replace(/^bg-/, '').split(/[\s_]+/)[0];
            switch (token) {
                case 'success':
                case 'green':
                case 'light-green':
                case 'lime':
                case 'teal':
                    return 'success';
                case 'warning':
                case 'orange':
                case 'deep-orange':
                case 'amber':
                case 'yellow':
                    return 'warning';
                case 'danger':
                case 'error':
                case 'red':
                case 'pink':
                    return 'danger';
                case 'info':
                case 'cyan':
                    return 'info';
                case 'primary':
                case 'blue':
                    return 'primary';
                case 'secondary':
                case 'grey':
                case 'gray':
                    return 'secondary';
                case 'light-blue':
                case 'indigo':
                case 'purple':
                case 'deep-purple':
                    return 'info';
                case 'blue-grey':
                case 'blue-gray':
                    return 'secondary';
                default:
                    return 'secondary';
            }
        },

        paintStatusSeries: function (series) {
            return (series || []).map(function (item) {
                const next = Object.assign({}, item);
                next.color = GemsUI.kindColor(GemsUI.statusKind(next.name) || 'secondary');
                return next;
            });
        },

        paintStatusPoints: function (points, categories) {
            return (points || []).map(function (point, index) {
                const next = (point && typeof point === 'object') ? Object.assign({}, point) : { y: point };
                const label = next.name || (categories && categories[index]) || '';
                next.color = GemsUI.kindColor(GemsUI.statusKind(label) || 'secondary');
                return next;
            });
        },

        emptyChartHtml: function (title, detail) {
            return '<div class="gems-empty-state"><i class="fas fa-chart-bar" aria-hidden="true"></i><p>' +
                escapeHtml(title || 'No data available') + '</p>' +
                (detail ? '<p class="gems-empty-detail">' + escapeHtml(detail) + '</p>' : '') +
                '</div>';
        },

        emptyChart: function (containerId, title, detail) {
            const el = document.getElementById(containerId);
            if (!el) { return; }
            if (typeof window.Highcharts !== 'undefined' && Highcharts.charts) {
                Highcharts.charts.forEach(function (chart) {
                    if (chart && chart.renderTo && chart.renderTo.id === containerId) {
                        chart.destroy();
                    }
                });
            }
            el.innerHTML = GemsUI.emptyChartHtml(title, detail);
        },

        /* P4 contract. Do not call from Waste/KPA/Energy during the P3 retrofit:
           those modules keep their own fillSelect semantics. */
        fillSelect: function (id, rows, valueKey, labelFn, placeholder, selected) {
            const el = document.getElementById(id);
            if (!el) { return; }
            el.classList.add('form-select');
            el.classList.remove('custom-select', 'gems-plain-select', 'browser-default');
            el.innerHTML = '';
            if (placeholder !== null) {
                const first = document.createElement('option');
                first.value = '';
                first.text = placeholder === undefined ? 'Please choose' : placeholder;
                el.appendChild(first);
            }
            (rows || []).forEach(function (row) {
                const opt = document.createElement('option');
                opt.value = row[valueKey];
                opt.text = typeof labelFn === 'function' ? labelFn(row) : row[labelFn];
                if (String(selected) === String(row[valueKey])) { opt.selected = true; }
                el.appendChild(opt);
            });
        },

        initTooltips: function (root) {
            if (typeof window.gemsInitTooltips !== 'function') { return; }
            const resolved = resolveTooltipRoot(arguments.length, root);
            if (!resolved) { return; }
            window.gemsInitTooltips(resolved);
        },

        disposeTooltips: function (root) {
            if (!window.jQuery || typeof window.jQuery.fn.tooltip !== 'function') { return; }
            const resolved = resolveTooltipRoot(arguments.length, root);
            if (!resolved) { return; }
            window.jQuery(resolved).find('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip('dispose');
        },

        refreshTooltips: function (root) {
            if (arguments.length === 0) {
                GemsUI.disposeTooltips();
                GemsUI.initTooltips();
                return;
            }
            if (!root) { return; }
            GemsUI.disposeTooltips(root);
            GemsUI.initTooltips(root);
        },

        bindDtTooltips: function (table) {
            if (!table) { return; }
            const $table = window.jQuery(table);
            if (!$table.length) { return; }
            $table.on('preDraw.dt', function () {
                GemsUI.disposeTooltips($table.find('tbody')[0]);
            });
            $table.on('draw.dt', function () {
                GemsUI.initTooltips($table.find('tbody')[0]);
            });
        },

        escape: escapeHtml
    };

    window.GemsUI = GemsUI;
}(window));
