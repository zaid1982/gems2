function MainZone () {

    const className = 'MainZone';
    let self = this;
    let oTableZne;
    let refStatus;
    let refSite;
    let modalZoneClass;
    let urlLinkBase;
    let baseAppPath;
    let zoneDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';
    let siteFilterValue = '';
    let typeFilterValue = '';

    const statusChipMap = {
        '': '#linkZneAll',
        '1': '#linkZneActive',
        '2': '#linkZneInactive',
        '5': '#linkZneArchived'
    };

    const tableHeaders = ['#', 'Site', 'Zone Type', 'Zone Code', 'Zone Name', 'Public QR Link', 'Status'];

    const initMaterialSelect = function (selector) {
        // const $element = $(selector);
        // if (!$element.length || typeof $element.materialSelect !== 'function') {
        //     return;
        // }
        // try {
        //     $element.materialSelect('destroy');
        // } catch (e) {
        //     // ignore destroy errors when reinitialising
        // }
        // const $wrapper = $element.parent('.select-wrapper');
        // if ($wrapper.length) {
        //     $wrapper.before($element);
        //     $wrapper.remove();
        // }
        // $element.siblings('.select-dropdown').remove();
        // $element.materialSelect();
    };

    const applyTableDataLabels = function (tableSelector, headers) {
        $(`${tableSelector} tbody tr`).each(function () {
            $('td', this).each(function (index) {
                if (headers[index]) {
                    $(this).attr('data-label', headers[index]);
                }
            });
        });
    };

    const stripHtml = function (value) {
        if (value === null || value === undefined) {
            return '';
        }
        if (typeof value !== 'string') {
            return value;
        }
        return value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    };

    const getNowStamp = function () {
        return (typeof moment !== 'undefined' && moment) ? moment().format('MMM D, YYYY h:mm A') : new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableZne) {
            return;
        }
        const info = oTableZne.page.info();
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblZneFilterCount').text(summaryText);
        $('#lblZneListCount').text(summaryText);
        $('#lblZneFilterUpdated').text(lastListUpdatedText);
        $('#lblZneListUpdated').text(lastListUpdatedText);
    };

    const syncSearchInputs = function (value, source) {
        if (source !== '#txtZneZoneSearch' && $('#txtZneZoneSearch').length) {
            $('#txtZneZoneSearch').val(value);
        }
        if (source !== '#txtZneQuickSearch' && $('#txtZneQuickSearch').length) {
            $('#txtZneQuickSearch').val(value);
        }
    };

    const applyZoneSearch = function (term, source) {
        const safeTerm = term || '';
        if (oTableZne) {
            oTableZne.search(safeTerm).draw();
        }
        syncSearchInputs(safeTerm, source);
    };

    const bindSearchField = function (selector) {
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).off('input change keyup').on('input change keyup', function () {
            applyZoneSearch($(this).val(), selector);
        });
    };

    const getStatusDesc = function (status) {
        if (!status || !refStatus || !refStatus[status]) {
            return 'Unknown';
        }
        return refStatus[status]['statusDesc'] || 'Unknown';
    };

    const getStatusBadge = function (status) {
        if (!status || !refStatus || !refStatus[status]) {
            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
        }
        const statusObj = refStatus[status];
        return `<span class="badge badge-pill ${statusObj['statusColor']} z-depth-2">${statusObj['statusDesc']}</span>`;
    };

    const getSiteName = function (siteId) {
        if (refSite && refSite[siteId]) {
            return refSite[siteId]['siteName'];
        }
        return 'Unknown Site';
    };

    const updateStatusChips = function (counts) {
        const labelMap = {
            '': 'All',
            '1': refStatus && refStatus['1'] ? refStatus['1']['statusDesc'] : 'Active',
            '2': refStatus && refStatus['2'] ? refStatus['2']['statusDesc'] : 'Inactive',
            '5': refStatus && refStatus['5'] ? refStatus['5']['statusDesc'] : 'Archived'
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(`${labelMap[status]} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
    };

    const updateZoneMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        const typeSet = {};

        dataSet.forEach(function (item) {
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

        const archived = dataSet.filter(function (item) {
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
    };

    const populateStatusFilter = function () {
        const $select = $('#optZneStatus');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Status</option>'];
        ['1', '2', '5'].forEach(function (statusId) {
            if (refStatus && refStatus[statusId]) {
                options.push(`<option value="${statusId}">${refStatus[statusId]['statusDesc']}</option>`);
            }
        });
        $select.html(options.join(''));
        initMaterialSelect('#optZneStatus');
        $select.off('change').on('change', handleStatusSelectChange);
    };

    const populateSiteFilter = function () {
        const $select = $('#optZneSiteId');
        if (!$select.length) {
            return;
        }
        const sites = [];
        if (refSite) {
            $.each(refSite, function (key, site) {
                if (!site || typeof site !== 'object') {
                    return true;
                }
                const siteId = site['siteId'] || key;
                const siteName = site['siteName'] || '';
                sites.push({ id: siteId, name: siteName });
                return true;
            });
        }
        sites.sort(function (a, b) {
            return (a.name || '').localeCompare(b.name || '');
        });
        const options = ['<option value="" selected>All Sites</option>'];
        sites.forEach(function (site) {
            options.push(`<option value="${site.id}">${site.name}</option>`);
        });
        $select.html(options.join(''));
        initMaterialSelect('#optZneSiteId');
        if (siteFilterValue) {
            $select.val(siteFilterValue);
            $select.materialSelect();
        }
        $select.off('change').on('change', handleSiteSelectChange);
    };

    const populateTypeFilter = function (dataSet) {
        const $select = $('#optZneType');
        if (!$select.length) {
            return;
        }
        const typeSet = {};
        (dataSet || []).forEach(function (item) {
            if (item['zoneType']) {
                typeSet[item['zoneType']] = true;
            }
        });
        const types = Object.keys(typeSet).sort(function (a, b) {
            return a.localeCompare(b);
        });
        const options = ['<option value="" selected>All Types</option>'];
        types.forEach(function (type) {
            options.push(`<option value="${type}">${type}</option>`);
        });
        if (typeFilterValue && types.indexOf(typeFilterValue) === -1) {
            typeFilterValue = '';
        }
        $select.html(options.join(''));
        initMaterialSelect('#optZneType');
        if (typeFilterValue) {
            $select.val(typeFilterValue);
            $select.materialSelect();
        }
        $select.off('change').on('change', handleTypeSelectChange);
    };

    const setActiveStatusChip = function (value) {
        $.each(statusChipMap, function (status, selector) {
            if (status === value) {
                $(selector).addClass('active');
            } else {
                $(selector).removeClass('active');
            }
        });
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableZne) {
            oTableZne.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optZneStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusSelectChange);
            }
        }
    };

    const setSiteFilter = function (value, skipSelect) {
        siteFilterValue = value || '';
        if (oTableZne) {
            oTableZne.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            const $siteSelect = $('#optZneSiteId');
            if ($siteSelect.length) {
                try {
                    $siteSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $siteSelect.val(siteFilterValue);
                $siteSelect.materialSelect();
                $siteSelect.off('change').on('change', handleSiteSelectChange);
            }
        }
    };

    const setTypeFilter = function (value, skipSelect) {
        typeFilterValue = value || '';
        if (oTableZne) {
            oTableZne.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            const $typeSelect = $('#optZneType');
            if ($typeSelect.length) {
                try {
                    $typeSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $typeSelect.val(typeFilterValue);
                $typeSelect.materialSelect();
                $typeSelect.off('change').on('change', handleTypeSelectChange);
            }
        }
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const handleSiteSelectChange = function () {
        setSiteFilter($(this).val() || '', true);
    };

    const handleTypeSelectChange = function () {
        setTypeFilter($(this).val() || '', true);
    };

    const zoneFilterFn = function (settings, data, dataIndex) {
        if (!oTableZne || settings.nTable.id !== 'dtZneData') {
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

    const bindRowInteractions = function () {
        $('#dtZneData tbody tr').off('click').on('click', function (evt) {
            const rowData = oTableZne.row(this).data();
            if (!rowData) {
                return;
            }
            modalZoneClass.edit(rowData['zoneId']);
        }).off('mouseenter').on('mouseenter', function (evt) {
            const rowData = oTableZne.row(this).data();
            const zoneName = rowData && rowData['zoneName'] ? rowData['zoneName'] : '';
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', zoneName ? `Click to edit ${zoneName}` : 'Click to edit zone');
            $('[data-toggle="tooltip"]').tooltip();
        });
    };

    this.init = function () {
        const urlParams = window.location.href.split('/p_');
        baseAppPath = urlParams[0];
        urlLinkBase = baseAppPath + '/p_complaint?z=';

        $.fn.dataTable.ext.search.push(zoneFilterFn);

        populateStatusFilter();
        populateSiteFilter();
        populateTypeFilter([]);

        oTableZne = $('#dtZneData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            pagingType: 'simple_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: [0], orderable: false, className: 'text-center' },
                { targets: [1, 2, 3], className: 'text-nowrap' },
                { targets: [4, 5, 6], className: 'align-middle' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableZne.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels('#dtZneData', tableHeaders);
                refreshListSummary();
                bindRowInteractions();
            },
            aoColumns: [
                { mData: null },
                { mData: 'siteId', mRender: function (data, type) {
                        const name = getSiteName(data);
                        return type !== 'display' ? name : name;
                    } },
                { mData: 'zoneType', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data || '<span class="text-muted">—</span>';
                    } },
                { mData: 'zoneCode', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data || '<span class="text-muted">—</span>';
                    } },
                { mData: 'zoneName', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data || '<span class="text-muted">No name</span>';
                    } },
                { mData: null, mRender: function (data, type, row) {
                        const zoneId = row['zoneId'];
                        const complaintLink = urlLinkBase + zoneId;
                        if (type !== 'display') {
                            return complaintLink;
                        }
                        return `<div class="small zone-link-text">${complaintLink}</div>`;
                    } },
                { mData: 'zoneStatus', mRender: function (data, type) {
                        if (type !== 'display') {
                            return getStatusDesc(data);
                        }
                        return `<h6 class="mb-0">${getStatusBadge(data)}</h6>`;
                    } }
            ]
        });
        $('#dtZneData_filter').hide();

        bindSearchField('#txtZneZoneSearch');
        bindSearchField('#txtZneQuickSearch');
        syncSearchInputs(oTableZne.search() || '', null);

        let exportCounter = 1;
        const btnZoneOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportCounter = 1;
                            }
                            return exportCounter++;
                        }
                        if (column === 5) {
                            return stripHtml(data);
                        }
                        if (column === 6) {
                            const rowData = oTableZne.row(row).data();
                            const statusVal = rowData ? rowData['zoneStatus'] : '';
                            return getStatusDesc(statusVal);
                        }
                        return stripHtml(data);
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableZne, {
            buttons: [
                $.extend(true, {}, btnZoneOpt, {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnZoneOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Zone List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnZoneOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Zone List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnZoneOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Zone List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtZneZoneExport'));

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
        lastListUpdatedText = getNowStamp();
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
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to download template');
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    const now = new Date();
                    const pad = n => n.toString().padStart(2, '0');
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
                .catch(error => {
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
                .then(response => response.json())
                .then(data => {
                    HideLoader();
                    if (data.success) {
                        const stats = data.result;
                        let message = `Import completed:\n`;
                        message += `✓ ${stats.success} zones added\n`;
                        if (stats.failed > 0) {
                            message += `✗ ${stats.failed} failed\n`;
                        }
                        if (stats.skipped > 0) {
                            message += `⊘ ${stats.skipped} skipped\n`;
                        }

                        if (stats.errors && stats.errors.length > 0) {
                            message += `\nErrors:\n${stats.errors.slice(0, 5).join('\n')}`;
                            if (stats.errors.length > 5) {
                                message += `\n... and ${stats.errors.length - 5} more`;
                            }
                        }

                        toastr['success'](message, _ALERT_TITLE_SUCCESS);
                        self.genTable(0);
                    } else {
                        throw new Error(data.errmsg || 'Import failed');
                    }
                })
                .catch(error => {
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