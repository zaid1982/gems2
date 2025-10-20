function MainItemTypeManagement () {

    const className = 'MainItemTypeManagement';
    let self = this;
    let oTableItm;
    let modalConfirmDeleteClass;
    let modalItemTypeClass;
    let refStatus;
    let refAssetGroup;
    let itemTypeDataCache = [];
    let lastUpdatedText = '—';
    let assetGroupFilterValue = '';
    let statusFilterValue = '';

    const statusChipMap = {
        '': '#linkItmAll',
        '1': '#linkItmActive',
        '2': '#linkItmInactive',
        '5': '#linkItmArchived'
    };

    const tableHeaders = ['#', 'Asset Group', 'Item Type', 'Option Turn', 'Status', 'Actions'];

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
        $('#dtItmData tbody tr').each(function () {
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
        if (!oTableItm) {
            return;
        }
        const info = oTableItm.page.info();
        const showing = info ? info.end - info.start : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblItmFilterCount').text(summaryText);
        $('#lblItmFilterUpdated').text(lastUpdatedText);
        $('#lblItmListCount').text(summaryText);
        $('#lblItmListUpdated').text(lastUpdatedText);
    };

    const getAssetGroupName = function (assetGroupId) {
        if (!assetGroupId || !refAssetGroup || !refAssetGroup[assetGroupId]) {
            return '—';
        }
        return refAssetGroup[assetGroupId]['assetGroupName'] || '—';
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
        const groupSet = new Set();
        const statusCounts = { '': 0 };

        dataSet.forEach(function (item) {
            total += 1;
            statusCounts[''] += 1;
            const statusKey = item['itemTypeStatus'] !== undefined && item['itemTypeStatus'] !== null ? String(item['itemTypeStatus']) : '';
            statusCounts[statusKey] = (statusCounts[statusKey] || 0) + 1;

            if (statusKey === '1') {
                active += 1;
            } else if (statusKey === '2') {
                inactive += 1;
            } else if (statusKey === '5') {
                archived += 1;
            }

            if (item['assetGroupId']) {
                groupSet.add(String(item['assetGroupId']));
            }
        });

        $('#metricItmTotal').text(mzFormatNumber(total, 0));
        $('#metricItmActive').text(mzFormatNumber(active, 0));
        $('#metricItmInactive').text(mzFormatNumber(inactive, 0));
        $('#metricItmGroups').text(mzFormatNumber(groupSet.size, 0));

        updateStatusChips(statusCounts);
        if (statusFilterValue === '5' && archived === 0) {
            // keep summary consistent if archived empty
        }
    };

    const bindActionButtons = function () {
        $('.btn-itm-edit').off('click').on('click', function () {
            const rowIndex = parseInt($(this).attr('data-row-index'), 10);
            const rowData = oTableItm.row(rowIndex).data();
            if (rowData) {
                modalItemTypeClass.edit(rowData['itemTypeId']);
            }
        });
        $('.btn-itm-delete').off('click').on('click', function () {
            const rowIndex = parseInt($(this).attr('data-row-index'), 10);
            const rowData = oTableItm.row(rowIndex).data();
            if (rowData) {
                modalConfirmDeleteClass.delete(rowData['itemTypeId'], modalItemTypeClass);
            }
        });
    };

    const populateAssetGroupFilter = function () {
        const $select = $('#optItmAssetGroup');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Asset Groups</option>'];
        if (refAssetGroup) {
            Object.keys(refAssetGroup).sort(function (a, b) {
                const nameA = (refAssetGroup[a]['assetGroupName'] || '').toLowerCase();
                const nameB = (refAssetGroup[b]['assetGroupName'] || '').toLowerCase();
                return nameA.localeCompare(nameB);
            }).forEach(function (key) {
                options.push(`<option value="${key}">${refAssetGroup[key]['assetGroupName']}</option>`);
            });
        }
        $select.html(options.join(''));
        initMaterialSelect('#optItmAssetGroup');
        assetGroupFilterValue = '';
    };

    const populateStatusFilter = function () {
        const $select = $('#optItmStatus');
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
        initMaterialSelect('#optItmStatus');
        statusFilterValue = '';
    };

    const handleAssetGroupChange = function () {
        assetGroupFilterValue = $('#optItmAssetGroup').val() || '';
        if (oTableItm) {
            oTableItm.draw();
            refreshListSummary();
        }
    };

    const handleStatusChange = function () {
        setStatusFilter($('#optItmStatus').val() || '', true);
    };

    const bindSearchField = function () {
        $('#txtItmSearch').off('input change keyup').on('input change keyup', function () {
            if (!oTableItm) {
                return;
            }
            oTableItm.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableItm) {
            oTableItm.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optItmStatus');
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

    const itemTypeFilterFn = function (settings, data, dataIndex) {
        if (!oTableItm || settings.nTable.id !== 'dtItmData') {
            return true;
        }
        const rowData = oTableItm.row(dataIndex).data();
        if (!rowData) {
            return true;
        }

        if (assetGroupFilterValue) {
            if (String(rowData['assetGroupId']) !== String(assetGroupFilterValue)) {
                return false;
            }
        }

        if (statusFilterValue) {
            if (String(rowData['itemTypeStatus']) !== String(statusFilterValue)) {
                return false;
            }
        }

        return true;
    };

    $.fn.dataTable.ext.search.push(itemTypeFilterFn);

    this.init = function () {
        populateAssetGroupFilter();
        populateStatusFilter();

        $('#optItmAssetGroup').off('change').on('change', handleAssetGroupChange);
        $('#optItmStatus').off('change').on('change', handleStatusChange);
        bindSearchField();

        oTableItm = $('#dtItmData').DataTable({
            bLengthChange: false,
            searching: true,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 25,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            columnDefs: [
                { orderable: false, targets: [0, 5] },
                { className: 'text-center align-middle', targets: [0, 4, 5] },
                { className: 'text-right align-middle', targets: [3] },
                { className: 'align-middle', targets: [1, 2] }
            ],
            fnRowCallback : function (nRow, aData, iDisplayIndex) {
                const info = oTableItm.page.info();
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
                { mData: 'assetGroupId', mRender: function (data, type) {
                        const value = getAssetGroupName(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'itemTypeDesc', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">—</span>';
                    } },
                { mData: 'itemTypeTurn', defaultContent: '', mRender: function (data, type) {
                        const value = mzFormatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'itemTypeStatus', mRender: function (data, type) {
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
                                    <button type="button" class="btn btn-link btn-sm px-2 btn-itm-edit" data-row-index="${meta.row}" data-toggle="tooltip" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn btn-link btn-sm px-2 text-danger btn-itm-delete" data-row-index="${meta.row}" data-toggle="tooltip" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>`;
                    } }
            ]
        });

        $('#dtItmData_filter').hide();

        let exportCounter = 1;
        const exportOptions = {
            columns: [0, 1, 2, 3, 4],
            format: {
                body: function (data, row, column) {
                    if (column === 0) {
                        if (row === 0) {
                            exportCounter = 1;
                        }
                        return exportCounter++;
                    }
                    if (column === 4) {
                        const rowData = oTableItm.row(row).data();
                        return rowData ? getStatusDesc(rowData['itemTypeStatus']) : stripHtml(data);
                    }
                    if (column === 1) {
                        const rowData = oTableItm.row(row).data();
                        return rowData ? getAssetGroupName(rowData['assetGroupId']) : stripHtml(data);
                    }
                    return stripHtml(data);
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableItm, {
            buttons: [
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    columns: [1, 2, 3, 4]
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Item Type List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Item Type List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Item Type List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ]
        }).container().appendTo($('#btnDtItmExport'));

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        $('#btnItmRefresh').off('click').on('click', function () {
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

        $('#btnItmAdd').off('click').on('click', function () {
            modalItemTypeClass.add();
        });

        self.genTable();
        setStatusFilter('', true);
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('item_type', 'GET');
        itemTypeDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTableItm.clear().rows.add(itemTypeDataCache).draw();
        itemTypeDataCache = oTableItm.rows().data().toArray();
        lastUpdatedText = getNowStamp();
        updateMetrics(itemTypeDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue, true);
    };

    this.getClassName = function () {
        return className;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalItemTypeClass = function (_modalItemTypeClass) {
        modalItemTypeClass = _modalItemTypeClass;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
}