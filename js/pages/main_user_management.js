function MainUserManagement() {

    const className = 'MainUserManagement';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let refStatus;
    let refRole;
    let refDesignation;
    let refGroup;
    let refSite;
    let oTableUser;
    let modalUserClass;
    let refUserType;
    let modalEditPasswordClass;
    let lastUpdated = null;

    function formatTimestamp(value) {
        if (!value) {
            return 'Updated —';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function displayText(value, type) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        return GemsUI.escape(text);
    }

    function stripHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        if (typeof value !== 'string') {
            return value;
        }
        return value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    }

    function siteRows() {
        const rows = [];
        $.each(refSite || {}, function (key, site) {
            if (!site || typeof site !== 'object') {
                return true;
            }
            const row = $.extend({}, site);
            if (row['siteId'] === undefined || row['siteId'] === null || row['siteId'] === '') {
                row['siteId'] = key;
            }
            if (String(row['siteStatus']) !== '1') {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['siteName'] || '').localeCompare(b['siteName'] || '');
        });
        return rows;
    }

    function getSiteName(siteId) {
        if (siteId === '' || siteId === null || typeof siteId === 'undefined') {
            return '';
        }
        if (refSite && refSite[siteId] && refSite[siteId]['siteName']) {
            return refSite[siteId]['siteName'];
        }
        let name = '';
        $.each(refSite || {}, function (key, site) {
            if (site && String(site['siteId'] || key) === String(siteId) && site['siteName']) {
                name = site['siteName'];
                return false;
            }
            return true;
        });
        return name;
    }

    function statusLabel(statusId, fallback) {
        if (refStatus && refStatus[statusId] && refStatus[statusId]['statusDesc']) {
            return refStatus[statusId]['statusDesc'];
        }
        switch (String(statusId)) {
            case '1':
                return 'Active';
            case '2':
                return 'Inactive';
            default:
                return fallback || 'Unknown';
        }
    }

    function statusBadgeKind(status) {
        return String(status) === '1' ? 'success' : 'secondary';
    }

    function statusBadge(statusId, type) {
        const label = statusLabel(statusId, 'Unknown');
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
    }

    function roleListHtml(roles, type) {
        if (!roles) {
            return '';
        }
        const dataSplit = String(roles).split(',');
        const names = [];
        for (let j = 0; j < dataSplit.length; j++) {
            const roleId = (dataSplit[j] || '').toString().trim();
            if (!roleId) {
                continue;
            }
            let label = '';
            if (refRole && refRole[roleId] && (refRole[roleId]['roleName'] || refRole[roleId]['roleDesc'])) {
                label = refRole[roleId]['roleName'] || refRole[roleId]['roleDesc'];
            } else if (roleId === '27') {
                label = 'MR Reviewer';
            } else {
                label = 'Role ' + roleId;
            }
            names.push(label);
        }
        if (type !== 'display') {
            return names.join(', ');
        }
        if (!names.length) {
            return '';
        }
        let html = '<ul class="mb-0 ps-3">';
        names.forEach(function (name) {
            html += '<li>' + GemsUI.escape(name) + '</li>';
        });
        html += '</ul>';
        return html;
    }

    function rowDataFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        if (linkIndex <= 0) {
            return null;
        }
        const rowId = linkId.substr(linkIndex + 1);
        return {
            rowId: rowId,
            data: oTableUser.row(parseInt(rowId, 10)).data()
        };
    }

    function updateMetrics() {
        if (!oTableUser) {
            return;
        }
        const data = oTableUser.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        const siteSet = new Set();
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['userStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
            if (row['siteId']) {
                siteSet.add(row['siteId']);
            }
        }
        $('#metricUmnTotal').text(total.toLocaleString());
        $('#metricUmnActive').text(active.toLocaleString());
        $('#metricUmnInactive').text(inactive.toLocaleString());
        $('#metricUmnSites').text(siteSet.size.toLocaleString());
    }

    function updateSummary() {
        if (!oTableUser) {
            return;
        }
        const info = (oTableUser && typeof oTableUser.page === 'function' && typeof oTableUser.page.info === 'function')
            ? oTableUser.page.info()
            : null;
        if (info) {
            $('#lblUmnUserCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        } else {
            $('#lblUmnUserCount').text('Showing 0 of 0 records');
        }
        $('#lblUmnUserUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const $select = $('#optUmnGroupId');
        if (!$select.length) {
            return;
        }
        const value = $select.val();
        let label = 'Site: All Sites';
        if (value) {
            const text = ($select.find('option:selected').text() || '').trim();
            if (text) {
                label = 'Site: ' + text;
            }
        }
        $('#lblUmnUserFilter').text(label);
    }

    function populateSiteFilter() {
        GemsUI.fillSelect(
            'optUmnGroupId',
            siteRows(),
            'siteId',
            function (row) {
                return row['siteName'] || '';
            },
            'All Sites',
            ''
        );
        updateFilterSummary();
    }

    this.init = function () {
        populateSiteFilter();

        refUserType = ['', 'GFM Internal', 'Client', 'Public User'];

        let exportCounter = 1;
        const exportOpt = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        exportCounter = 1;
                    }
                    if (column === 0) {
                        return exportCounter++;
                    }
                    return stripHtml(data);
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - System User List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableUser = $('#dtUmnUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            searching: true,
            autoWidth: false,
            aaSorting: [9, 'desc'],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-users', 'No users recorded yet.', 'No users match the current search or filter.'),
            pagingType: 'simple_numbers',
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableUser && oTableUser.page && typeof oTableUser.page.info === 'function')
                    ? oTableUser.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                updateMetrics();
                updateSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false, sClass: 'text-center'},
                {mData: 'userFullName',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: null,
                    mRender: function (data, type, row) {
                        return displayText(getSiteName(row['siteId']), type);
                    }
                },
                {mData: 'userType',
                    mRender: function (data, type) {
                        return displayText(refUserType[data] || '', type);
                    }
                },
                {mData: 'userContactNo',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'userEmail',
                    mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        const parts = String(mzEmailShort(data || '', 20)).split('<br>');
                        return parts.map(function (part) {
                            return GemsUI.escape(part);
                        }).join('<br>');
                    }
                },
                {mData: null,
                    mRender: function (data, type, row) {
                        return roleListHtml(row['roles'], type);
                    }
                },
                {mData: null,
                    mRender: function (data, type, row) {
                        return statusBadge(row['userStatus'], type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkUmnUserEdit',
                            id: 'lnkUmnUserEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-view',
                            cls: 'lnkUmnUserPassword',
                            id: 'lnkUmnUserPassword_' + meta.row,
                            title: 'Edit Password',
                            icon: 'fas fa-unlock-alt'
                        });
                        if (row['userStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkUmnUserDeactivate',
                                id: 'lnkUmnUserDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkUmnUserActivate',
                                id: 'lnkUmnUserActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        return html;
                    }
                },
                {mData: 'userId', visible: false, sClass: 'noVis'},
                {mData: 'userStatus', visible: false, sClass: 'noVis'},
                {mData: 'roles', visible: false, sClass: 'noVis'},
                {mData: 'groupId', visible: false, sClass: 'noVis'},
                {mData: 'designationId', visible: false, sClass: 'noVis'},
                {mData: 'siteId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableUser.buttons().container().appendTo($('#btnDtUmnUserExport'));
        GemsUI.bindDtTooltips('#dtUmnUser');

        const tbody = $('#dtUmnUser tbody');
        tbody.on('click', '.lnkUmnUserEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalUserClass.edit(current.data['userId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkUmnUserPassword', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalEditPasswordClass.edit(current.data['userId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkUmnUserDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalUserClass.deactivate(current.data['userId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkUmnUserActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalUserClass.activate(current.data['userId'], current.rowId);
            }
        });

        $('#txtUmnUserSearch').on('keyup change input', function () {
            oTableUser.search($(this).val()).draw();
        });
        $('#optUmnGroupId').on('change', function () {
            const value = $(this).val();
            if (value) {
                oTableUser.column(14).search('^' + value + '$', true, false, true).draw();
            } else {
                oTableUser.column(14).search('', true, false, true).draw();
            }
            updateFilterSummary();
        });

        $('#btnDtUmnUserRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableUser();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnUmnUserAdd').on('click', function () {
            modalUserClass.add('umn');
        });

        updateMetrics();
        updateSummary();
        this.genTableUser();
    };

    this.genTableUser = function () {
        console.log('genTableUser called, refRole:', refRole);
        if (!refRole || Object.keys(refRole).length === 0) {
            console.warn('Role data not loaded yet, retrying in 500ms...');
            setTimeout(() => this.genTableUser(), 500);
            return;
        }

        mzAjaxRequest('profile.php', 'GET', {Reportid: '1', 'Cache-Control': 'no-cache, no-transform'}, 'userManagementClass_.displayChart()');
        const dataUser = mzAjaxRequest('profile.php', 'GET');
        lastUpdated = new Date();
        oTableUser.clear().rows.add(dataUser).draw();
    };

    this.displayChart = function (result) {
        if (typeof result === 'string') {
            try {
                result = JSON.parse(result);
            } catch (e) {
                console.error('User Management chart: invalid JSON string result', e, result);
                return;
            }
        }

        if (!Array.isArray(result)) {
            console.warn('User Management chart: unexpected result type', result);
            return;
        }

        if (!refRole || Object.keys(refRole).length === 0) {
            console.warn('Role data not loaded yet, chart will be updated when data is available');
            console.log('refRole:', refRole);
            return;
        }

        console.log('Processing chart data with refRole:', refRole);

        let chartData = [];

        $.each(result, function (n, u) {
            if (refRole && refRole[u['roleId']] && refRole[u['roleId']]['roleDesc']) {
                chartData.push({name: refRole[u['roleId']]['roleDesc'], y: parseInt(u['total'])});
            } else {
                chartData.push({name: 'Role ' + u['roleId'], y: parseInt(u['total'])});
            }
        });

        const categories = chartData.map(function (item) {
            return item.name;
        });
        const seriesData = chartData.map(function (item) {
            return item.y;
        });

        if (typeof Highcharts === 'undefined' || typeof Highcharts.chart !== 'function') {
            console.error('Highcharts is not available; cannot render user role chart');
            return;
        }

        Highcharts.chart('chartUmnLeaveByStatus', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Role Distribution'
            },
            xAxis: {
                categories: categories,
                crosshair: true,
                labels: {
                    style: {
                        color: '#0F172A',
                        fontWeight: '600'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'No. of Users',
                    style: {
                        color: '#0F172A',
                        fontWeight: '600'
                    }
                },
                gridLineColor: 'rgba(15, 23, 42, 0.08)',
                labels: {
                    style: {
                        color: '#64748B'
                    }
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                headerFormat: '<span style="font-size: 12px">{point.key}</span><br/>',
                pointFormat: '<span style="color:{point.color}">\u25CF</span> Users: <b>{point.y}</b>'
            },
            credits: {
                enabled: false
            },
            colors: (window.GemsUI && GemsUI.chartColors()) || ['#6b9cd6', '#6bcfcd', '#7ab597', '#c4a46f', '#eb8181', '#70bfd2'],
            plotOptions: {
                column: {
                    colorByPoint: true,
                    borderRadius: 6,
                    pointPadding: 0.18,
                    groupPadding: 0.12,
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: '#0F172A',
                            fontWeight: '600'
                        }
                    }
                }
            },
            series: [{
                name: 'Users',
                data: seriesData
            }]
        });
    };

    this.updateTableUmn = function (_dataEdit, _rowEdit) {
        const currentRow = oTableUser.row(_rowEdit).data();
        if (typeof _dataEdit['userStatus'] !== 'undefined') {
            currentRow['userStatus'] = _dataEdit['userStatus'];
        }
        lastUpdated = new Date();
        oTableUser.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefRole = function (_refRole) {
        console.log('setRefRole called with:', _refRole);
        refRole = _refRole;
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefGroup = function (_refGroup) {
        refGroup = _refGroup;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
        updateFilterSummary();
    };

    this.setModalUserClass = function (_modalUserClass) {
        modalUserClass = _modalUserClass;
    };

    this.setModalEditPasswordClass = function (_modalEditPasswordClass) {
        modalEditPasswordClass = _modalEditPasswordClass;
    };
}
