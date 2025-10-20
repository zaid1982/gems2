function MainStoreManagement () {

    const className = 'MainStoreManagement';
    let self = this;
    let oTableStm;
    let modalConfirmDeleteClass;
    let modalStoreClass;
    let refStatus;
    let refSite;
    let userSite;
    let storeDataCache = [];
    let lastUpdatedText = '—';
    let siteFilterValue = '';
    let statusFilterValue = '';
    const isAdmin = mzIsRoleExist('1,10');

    const statusChipMap = {
        '': '#linkStmAll',
        '1': '#linkStmActive',
        '2': '#linkStmInactive',
        '5': '#linkStmArchived'
    };

    const tableHeaders = ['#', 'Site', 'Store Name', 'Description', 'Total Item Descriptions', 'Total Items', 'Status', 'Actions'];

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy warnings
        }
        const $wrapper = $element.parent('.select-wrapper');
        if ($wrapper.length) {
            $wrapper.before($element);
            $wrapper.remove();
        }
        $element.siblings('.select-dropdown').remove();
        $element.materialSelect();
    };

    const applyTableDataLabels = function () {
        $('#dtStmData tbody tr').each(function () {
            $('td', this).each(function (index) {
                if (tableHeaders[index]) {
                    $(this).attr('data-label', tableHeaders[index]);
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
        if (typeof moment !== 'undefined' && moment) {
            return moment().format('MMM D, YYYY h:mm A');
        }
        return new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableStm) {
            return;
        }
        const info = oTableStm.page.info();
        const showing = info ? info.end - info.start : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblStmFilterCount').text(summaryText);
        $('#lblStmFilterUpdated').text(lastUpdatedText);
        $('#lblStmListCount').text(summaryText);
        $('#lblStmListUpdated').text(lastUpdatedText);
    };

    const getSiteName = function (siteId) {
        if (!siteId || !refSite || !refSite[siteId]) {
            return '—';
        }
        return refSite[siteId]['siteName'] || '—';
    };

    const getStatusDesc = function (statusId) {
        if (statusId === undefined || statusId === null || statusId === '') {
            return 'Unknown';
        }
        const key = String(statusId);
        if (refStatus && refStatus[key] && refStatus[key]['statusDesc']) {
            return refStatus[key]['statusDesc'];
        }
        return 'Unknown';
    };

    const getStatusBadge = function (statusId) {
        const key = statusId === undefined || statusId === null ? '' : String(statusId);
        if (refStatus && refStatus[key]) {
            const desc = refStatus[key]['statusDesc'] || 'Unknown';
            const badgeClass = refStatus[key]['statusColor'] || 'badge-secondary';
            return `<span class="badge badge-pill ${badgeClass} z-depth-2">${desc}</span>`;
        }
        return '<span class="badge badge-pill badge-secondary">Unknown</span>';
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

    const updateMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let totalItems = 0;
        const statusCounts = { '': 0 };

        dataSet.forEach(function (item) {
            total += 1;
            statusCounts[''] += 1;
            const statusKey = item['storeStatus'] !== undefined && item['storeStatus'] !== null ? String(item['storeStatus']) : '';
            statusCounts[statusKey] = (statusCounts[statusKey] || 0) + 1;

            if (statusKey === '1') {
                active += 1;
            } else if (statusKey === '2') {
                inactive += 1;
            } else if (statusKey === '5') {
                archived += 1;
            }

            const itemTotal = parseFloat(item['totalItem']);
            if (!isNaN(itemTotal)) {
                totalItems += itemTotal;
            }
        });

        $('#metricStmTotal').text(mzFormatNumber(total, 0));
        $('#metricStmActive').text(mzFormatNumber(active, 0));
        $('#metricStmInactive').text(mzFormatNumber(inactive, 0));
        $('#metricStmItems').text(mzFormatNumber(totalItems, 0));

        updateStatusChips(statusCounts);
    };

    const bindActionButtons = function () {
        $('.btn-stm-edit').off('click').on('click', function () {
            const rowIndex = parseInt($(this).attr('data-row-index'), 10);
            const rowData = oTableStm.row(rowIndex).data();
            if (rowData) {
                modalStoreClass.edit(rowData['storeId']);
            }
        });
        $('.btn-stm-delete').off('click').on('click', function () {
            const rowIndex = parseInt($(this).attr('data-row-index'), 10);
            const rowData = oTableStm.row(rowIndex).data();
            if (rowData) {
                modalConfirmDeleteClass.delete(rowData['storeId'], modalStoreClass);
            }
        });
    };

    const populateSiteFilter = function () {
        const $select = $('#optStmSite');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Sites</option>'];
        if (refSite) {
            Object.keys(refSite).sort(function (a, b) {
                const nameA = (refSite[a]['siteName'] || '').toLowerCase();
                const nameB = (refSite[b]['siteName'] || '').toLowerCase();
                return nameA.localeCompare(nameB);
            }).forEach(function (key) {
                options.push(`<option value="${key}">${refSite[key]['siteName']}</option>`);
            });
        }
        if (!isAdmin) {
            userSite = mzGetUserInfoByParam('siteId');
            if (userSite) {
                options.forEach(function (option, index) {
                    if (option.indexOf(`value="${userSite}"`) > -1) {
                        options[index] = option.replace('selected', '');
                    }
                });
            }
        }
        $select.html(options.join(''));
        if (!isAdmin && userSite) {
            $select.val(userSite);
            $select.prop('disabled', true);
        }
        initMaterialSelect('#optStmSite');
        if (!isAdmin && userSite) {
            siteFilterValue = String(userSite);
            const $dropdown = $('#optStmSite').siblings('input.select-dropdown');
            $dropdown.prop('disabled', true).addClass('disabled');
        } else {
            siteFilterValue = '';
        }
    };

    const populateStatusFilter = function () {
        const $select = $('#optStmStatus');
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
        initMaterialSelect('#optStmStatus');
        statusFilterValue = '';
    };

    const handleSiteChange = function () {
        if (!isAdmin) {
            return;
        }
        siteFilterValue = $('#optStmSite').val() || '';
        self.genTable();
    };

    const handleStatusChange = function () {
        setStatusFilter($('#optStmStatus').val() || '', true);
    };

    const bindSearchField = function () {
        $('#txtStmSearch').off('input change keyup').on('input change keyup', function () {
            if (!oTableStm) {
                return;
            }
            oTableStm.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableStm) {
            oTableStm.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optStmStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy warnings
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusChange);
            }
        }
    };

    const storeFilterFn = function (settings, data, dataIndex) {
        if (!oTableStm || settings.nTable.id !== 'dtStmData') {
            return true;
        }
        const rowData = oTableStm.row(dataIndex).data();
        if (!rowData) {
            return true;
        }

        if (siteFilterValue) {
            if (String(rowData['siteId']) !== String(siteFilterValue)) {
                return false;
            }
        }

        if (statusFilterValue) {
            if (String(rowData['storeStatus']) !== String(statusFilterValue)) {
                return false;
            }
        }

        return true;
    };

    $.fn.dataTable.ext.search.push(storeFilterFn);

    this.init = function () {
        populateSiteFilter();
        populateStatusFilter();
        bindSearchField();

        $('#optStmSite').off('change').on('change', handleSiteChange);
        $('#optStmStatus').off('change').on('change', handleStatusChange);

        oTableStm = $('#dtStmData').DataTable({
            bLengthChange: false,
            searching: true,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 20,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            columnDefs: [
                { orderable: false, targets: [0, 7] },
                { className: 'text-center align-middle', targets: [0, 6, 7] },
                { className: 'text-right align-middle', targets: [4, 5] },
                { className: 'align-middle', targets: [1, 2, 3] }
            ],
            fnRowCallback : function (nRow, aData, iDisplayIndex) {
                const info = oTableStm.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels();
                refreshListSummary();
                bindActionButtons();
                $('[data-toggle="tooltip"]').tooltip();
            },
            aoColumns: [
                { mData: null },
                { mData: 'siteId', mRender: function (data, type) {
                        const value = getSiteName(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'storeName', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">—</span>';
                    } },
                { mData: 'storeDesc', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">No description</span>';
                    } },
                { mData: 'totalItemDesc', mRender: function (data, type) {
                        const value = mzFormatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'totalItem', mRender: function (data, type) {
                        const value = mzFormatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'storeStatus', mRender: function (data, type) {
                        if (type !== 'display') {
                            return getStatusDesc(data);
                        }
                        return `<h6 class="mb-0">${getStatusBadge(data)}</h6>`;
                    } },
                { mData: null, orderable: false, width: '90px', mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return '';
                        }
                        return `<div class="table-actions d-flex justify-content-center">
                                    <button type="button" class="btn btn-link btn-sm px-2 btn-stm-edit" data-row-index="${meta.row}" data-toggle="tooltip" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn btn-link btn-sm px-2 text-danger btn-stm-delete" data-row-index="${meta.row}" data-toggle="tooltip" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>`;
                    } }
            ]
        });

        $('#dtStmData_filter').hide();

        let exportCounter = 1;
        const exportOptions = {
            columns: [0, 1, 2, 3, 4, 5, 6],
            format: {
                body: function (data, row, column) {
                    if (column === 0) {
                        if (row === 0) {
                            exportCounter = 1;
                        }
                        return exportCounter++;
                    }
                    if (column === 1) {
                        const rowData = oTableStm.row(row).data();
                        return rowData ? getSiteName(rowData['siteId']) : stripHtml(data);
                    }
                    if (column === 6) {
                        const rowData = oTableStm.row(row).data();
                        return rowData ? getStatusDesc(rowData['storeStatus']) : stripHtml(data);
                    }
                    return stripHtml(data);
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableStm, {
            buttons: [
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    columns: [1, 2, 3, 4, 5, 6]
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Inventory Store List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Inventory Store List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Inventory Store List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ]
        }).container().appendTo($('#btnDtStmExport'));

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        $('#btnStmRefresh').off('click').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnStmAdd').off('click').on('click', function () {
            modalStoreClass.add();
        });

        self.genTable();
        setStatusFilter('', true);
    };

    this.genTable = function () {
        if (!userSite) {
            userSite = mzGetUserInfoByParam('siteId');
        }
        const apiUrl = !isAdmin && userSite ? `store/site/${userSite}` : 'store';
        const dataDb = mzAjaxRequest2(apiUrl, 'GET');
        storeDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTableStm.clear().rows.add(storeDataCache).draw();
        storeDataCache = oTableStm.rows().data().toArray();
        lastUpdatedText = getNowStamp();
        updateMetrics(storeDataCache);
        refreshListSummary();
        if (!isAdmin && userSite) {
            siteFilterValue = String(userSite);
        }
        setStatusFilter(statusFilterValue, true);
    };

    this.getClassName = function () {
        return className;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalStoreClass = function (_modalStoreClass) {
        modalStoreClass = _modalStoreClass;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
}