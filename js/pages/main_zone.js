function MainZone() {

    const className = 'MainZone';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let oTableZne;
    let refStatus;
    let refSite;
    let modalZoneClass;
    let urlLinkBase;
    let baseAppPath;
    let zoneDataCache = [];
    let lastListUpdated = null;
    let statusFilterValue = '';
    let siteFilterValue = '';
    let typeFilterValue = '';
    let zoneFilterFn;

    const statusChipMap = {
        '': '#linkZneAll',
        '1': '#linkZneActive',
        '2': '#linkZneInactive',
        '5': '#linkZneArchived'
    };

    function formatTimestamp(value) {
        if (!value) {
            return '—';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function displayText(value, type, emptyDisplay) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        if (!text) {
            return emptyDisplay || '';
        }
        return GemsUI.escape(text);
    }

    function siteRows() {
        const rows = [];
        $.each(refSite, function (key, site) {
            if (!site || typeof site !== 'object') {
                return true;
            }
            const row = $.extend({}, site);
            if (!row['siteId']) {
                row['siteId'] = key;
            }
            if (!row['siteId'] && !row['siteName']) {
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
        if (refSite && refSite[siteId] && refSite[siteId]['siteName']) {
            return refSite[siteId]['siteName'];
        }
        let name = 'Unknown Site';
        $.each(refSite, function (key, site) {
            if (site && String(site['siteId']) === String(siteId) && site['siteName']) {
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
            case '5':
                return 'Archived';
            default:
                return fallback || 'Unknown';
        }
    }

    function statusBadgeKind(status) {
        switch (String(status)) {
            case '1':
                return 'success';
            case '5':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function statusBadge(statusId, type) {
        const label = statusLabel(statusId, 'Unknown');
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
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

    function refreshListSummary() {
        if (!oTableZne) {
            return;
        }
        const info = (typeof oTableZne.page === 'function' && typeof oTableZne.page.info === 'function')
            ? oTableZne.page.info()
            : null;
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = 'Showing ' + mzFormatNumber(showing, 0) + ' of ' + mzFormatNumber(total, 0);
        const updatedText = formatTimestamp(lastListUpdated);
        $('#lblZneFilterCount').text(summaryText);
        $('#lblZneListCount').text(summaryText + ' results');
        $('#lblZneFilterUpdated').text(updatedText);
        $('#lblZneListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
    }

    function syncSearchInputs(value, source) {
        if (source !== '#txtZneZoneSearch' && $('#txtZneZoneSearch').length) {
            $('#txtZneZoneSearch').val(value);
        }
        if (source !== '#txtZneQuickSearch' && $('#txtZneQuickSearch').length) {
            $('#txtZneQuickSearch').val(value);
        }
    }

    function applyZoneSearch(term, source) {
        const safeTerm = term || '';
        if (oTableZne) {
            oTableZne.search(safeTerm).draw();
        }
        syncSearchInputs(safeTerm, source);
    }

    function bindSearchField(selector) {
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).off('input change keyup').on('input change keyup', function () {
            applyZoneSearch($(this).val(), selector);
        });
    }

    function updateStatusChips(counts) {
        const labelMap = {
            '': 'All',
            '1': statusLabel('1', 'Active'),
            '2': statusLabel('2', 'Inactive'),
            '5': statusLabel('5', 'Archived')
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(GemsUI.escape(labelMap[status]) + ' <span class="badge bg-secondary-lt ms-1">' + mzFormatNumber(count, 0) + '</span>');
        });
        setActiveStatusChip(statusFilterValue);
    }

    function updateZoneMetrics(dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        const typeSet = {};

        (dataSet || []).forEach(function (item) {
            if (!item) {
                return;
            }
            total += 1;
            const status = String(item['zoneStatus']);
            if (status === '1') {
                active += 1;
            } else if (status === '2') {
                inactive += 1;
            }
            if (item['zoneType']) {
                typeSet[item['zoneType']] = true;
            }
        });

        const archived = (dataSet || []).filter(function (item) {
            return String(item['zoneStatus']) === '5';
        }).length;

        $('#metricZneTotal').text(mzFormatNumber(total, 0));
        $('#metricZneActive').text(mzFormatNumber(active, 0));
        $('#metricZneInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricZneTypes').text(mzFormatNumber(Object.keys(typeSet).length, 0)).toggleClass('text-info', Object.keys(typeSet).length > 0);

        updateStatusChips({
            '': total,
            '1': active,
            '2': inactive,
            '5': archived
        });
    }

    function populateStatusFilter() {
        const rows = [];
        ['1', '2', '5'].forEach(function (statusId) {
            if (refStatus && refStatus[statusId]) {
                const row = $.extend({}, refStatus[statusId]);
                if (!row['statusId']) {
                    row['statusId'] = statusId;
                }
                rows.push(row);
            } else {
                rows.push({
                    statusId: statusId,
                    statusDesc: statusLabel(statusId)
                });
            }
        });
        GemsUI.fillSelect(
            'optZneStatus',
            rows,
            'statusId',
            function (row) {
                return row['statusDesc'] || '';
            },
            'All Status',
            statusFilterValue
        );
    }

    function populateSiteFilter() {
        GemsUI.fillSelect(
            'optZneSiteId',
            siteRows(),
            'siteId',
            function (row) {
                return row['siteName'] || '';
            },
            'All Sites',
            siteFilterValue
        );
    }

    function populateTypeFilter(dataSet) {
        const typeSet = {};
        (dataSet || []).forEach(function (item) {
            if (item && item['zoneType']) {
                typeSet[item['zoneType']] = true;
            }
        });
        const types = Object.keys(typeSet).sort(function (a, b) {
            return a.localeCompare(b);
        });
        if (typeFilterValue && types.indexOf(typeFilterValue) === -1) {
            typeFilterValue = '';
        }
        const rows = types.map(function (type) {
            return { zoneType: type };
        });
        GemsUI.fillSelect(
            'optZneType',
            rows,
            'zoneType',
            function (row) {
                return row['zoneType'] || '';
            },
            'All Types',
            typeFilterValue
        );
    }

    function setActiveStatusChip(value) {
        $.each(statusChipMap, function (status, selector) {
            if (status === value) {
                $(selector).addClass('active btn-primary').removeClass('btn-outline-secondary');
            } else {
                $(selector).removeClass('active btn-primary').addClass('btn-outline-secondary');
            }
        });
    }

    function setStatusFilter(value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableZne) {
            oTableZne.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            $('#optZneStatus').val(statusFilterValue);
        }
    }

    function setSiteFilter(value, skipSelect) {
        siteFilterValue = value || '';
        if (oTableZne) {
            oTableZne.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            $('#optZneSiteId').val(siteFilterValue);
        }
    }

    function setTypeFilter(value, skipSelect) {
        typeFilterValue = value || '';
        if (oTableZne) {
            oTableZne.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            $('#optZneType').val(typeFilterValue);
        }
    }

    function handleStatusSelectChange() {
        setStatusFilter($(this).val() || '', true);
    }

    function handleSiteSelectChange() {
        setSiteFilter($(this).val() || '', true);
    }

    function handleTypeSelectChange() {
        setTypeFilter($(this).val() || '', true);
    }

    this.init = function () {
        const urlParams = window.location.href.split('/p_');
        baseAppPath = urlParams[0];
        urlLinkBase = baseAppPath + '/p_complaint?z=';

        populateStatusFilter();
        populateSiteFilter();
        populateTypeFilter([]);

        let exportCounter = 1;
        const exportOpt = {
            columns: [0, 1, 2, 3, 4, 5, 6],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        exportCounter = 1;
                    }
                    if (column === 0) {
                        return exportCounter++;
                    }
                    if (column === 5) {
                        return stripHtml(data);
                    }
                    if (column === 6) {
                        const rowData = oTableZne.row(row).data();
                        return rowData ? statusLabel(rowData['zoneStatus']) : stripHtml(data);
                    }
                    return stripHtml(data);
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Zone List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableZne = $('#dtZneData').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-vector-square', 'No zones recorded yet.', 'No zones match the current search or filter.'),
            pagingType: 'simple_numbers',
            columnDefs: [
                {targets: [0], orderable: false, className: 'text-center'},
                {targets: [1, 2, 3], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableZne && oTableZne.page && typeof oTableZne.page.info === 'function')
                    ? oTableZne.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                refreshListSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'siteId',
                    mRender: function (data, type) {
                        return displayText(getSiteName(data), type);
                    }
                },
                {mData: 'zoneType',
                    mRender: function (data, type) {
                        return displayText(data, type, '<span class="text-muted">—</span>');
                    }
                },
                {mData: 'zoneCode',
                    mRender: function (data, type) {
                        return displayText(data, type, '<span class="text-muted">—</span>');
                    }
                },
                {mData: 'zoneName',
                    mRender: function (data, type) {
                        return displayText(data, type, '<span class="text-muted">No name</span>');
                    }
                },
                {mData: null,
                    mRender: function (data, type, row) {
                        const complaintLink = urlLinkBase + row['zoneId'];
                        if (type !== 'display') {
                            return complaintLink;
                        }
                        return '<div class="small">' + GemsUI.escape(complaintLink) + '</div>';
                    }
                },
                {mData: 'zoneStatus',
                    mRender: function (data, type) {
                        return statusBadge(data, type);
                    }
                }
            ]
        });

        oTableZne.buttons().container().appendTo($('#btnDtZneZoneExport'));
        GemsUI.bindDtTooltips('#dtZneData');

        zoneFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtZneData') {
                return true;
            }
            const rowData = oTableZne.row(dataIndex).data();
            if (!rowData) {
                return true;
            }
            if (statusFilterValue && String(rowData['zoneStatus']) !== statusFilterValue) {
                return false;
            }
            if (siteFilterValue && String(rowData['siteId']) !== siteFilterValue) {
                return false;
            }
            if (typeFilterValue && String(rowData['zoneType']) !== typeFilterValue) {
                return false;
            }
            return true;
        };
        $.fn.dataTable.ext.search.push(zoneFilterFn);

        $('#dtZneData tbody').on('click', 'tr', function () {
            const rowData = oTableZne.row(this).data();
            if (!rowData) {
                return;
            }
            modalZoneClass.edit(rowData['zoneId']);
        });

        bindSearchField('#txtZneZoneSearch');
        bindSearchField('#txtZneQuickSearch');
        syncSearchInputs(oTableZne.search() || '', null);

        $('#optZneStatus').on('change', handleStatusSelectChange);
        $('#optZneSiteId').on('change', handleSiteSelectChange);
        $('#optZneType').on('change', handleTypeSelectChange);

        $('#btnDtZneZoneRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnZneDownloadTemplate').on('click', function () {
            self.downloadTemplate();
        });

        $('#btnZneImport').on('click', function () {
            self.showImportDialog();
        });

        $('#btnZneAdd').on('click', function () {
            modalZoneClass.add();
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);
        setSiteFilter('', true);
        setTypeFilter('', true);

        self.genTable(0);
    };

    this.genTable = function (_type) {
        if (_type === 1) {
            // refresh dataset version if needed
        }
        const dataDb = mzAjaxRequest2('zone', 'GET');
        zoneDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTableZne.clear().rows.add(zoneDataCache).draw();
        zoneDataCache = oTableZne.rows().data().toArray();
        lastListUpdated = new Date();
        updateZoneMetrics(zoneDataCache);
        populateTypeFilter(zoneDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
        setTypeFilter(typeFilterValue || '', true);
        syncSearchInputs(oTableZne.search() || '', null);
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalZoneClass = function (_modalZoneClass) {
        modalZoneClass = _modalZoneClass;
    };

    this.getUrlLinkBase = function () {
        return urlLinkBase;
    };

    this.getBaseAppPath = function () {
        return baseAppPath;
    };

    this.downloadTemplate = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                const header = {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                };
                if (sessionStorage.getItem('token') !== null) {
                    header['Authorization'] = 'Bearer ' + sessionStorage.getItem('token');
                }

                fetch('api/zone_template.php', {
                    method: 'GET',
                    headers: header
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to download template');
                    }
                    return response.blob();
                })
                .then(function (blob) {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    const now = new Date();
                    const pad = function (n) { return n.toString().padStart(2, '0'); };
                    const formattedDate = now.getFullYear().toString() +
                        pad(now.getMonth() + 1) +
                        pad(now.getDate()) + '_' +
                        pad(now.getHours()) +
                        pad(now.getMinutes()) +
                        pad(now.getSeconds());
                    link.download = 'zone_template_' + formattedDate + '.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    HideLoader();
                    toastr['success']('Template downloaded successfully', _ALERT_TITLE_SUCCESS);
                })
                .catch(function (error) {
                    HideLoader();
                    toastr['error'](error.message, _ALERT_TITLE_ERROR);
                });
            } catch (e) {
                HideLoader();
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        }, 200);
    };

    this.showImportDialog = function () {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.xlsx,.xls';
        fileInput.style.display = 'none';

        fileInput.onchange = function (e) {
            const file = e.target.files[0];
            if (file) {
                self.uploadZoneFile(file);
            }
            document.body.removeChild(fileInput);
        };

        document.body.appendChild(fileInput);
        fileInput.click();
    };

    this.uploadZoneFile = function (file) {
        ShowLoader();
        setTimeout(function () {
            try {
                const formData = new FormData();
                formData.append('file', file);

                const header = {};
                if (sessionStorage.getItem('token') !== null) {
                    header['Authorization'] = 'Bearer ' + sessionStorage.getItem('token');
                }

                fetch('zone/import', {
                    method: 'POST',
                    headers: header,
                    body: formData
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    HideLoader();
                    if (data.success) {
                        const stats = data.result;
                        let message = 'Import completed:\n';
                        message += '✓ ' + stats.success + ' zones added\n';
                        if (stats.failed > 0) {
                            message += '✗ ' + stats.failed + ' failed\n';
                        }
                        if (stats.skipped > 0) {
                            message += '⊘ ' + stats.skipped + ' skipped\n';
                        }

                        if (stats.errors && stats.errors.length > 0) {
                            message += '\nErrors:\n' + stats.errors.slice(0, 5).join('\n');
                            if (stats.errors.length > 5) {
                                message += '\n... and ' + (stats.errors.length - 5) + ' more';
                            }
                        }

                        toastr['success'](message, _ALERT_TITLE_SUCCESS);
                        self.genTable(0);
                    } else {
                        throw new Error(data.errmsg || 'Import failed');
                    }
                })
                .catch(function (error) {
                    HideLoader();
                    toastr['error'](error.message, _ALERT_TITLE_ERROR);
                });
            } catch (e) {
                HideLoader();
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        }, 200);
    };
}
