function MainAssetCategory() {

    const className = 'MainAssetCategory';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let oTableAssetCategory;
    let modalAssetCategoryClass;
    let assetCategoryDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';
    let groupFilterValue = '';

    const statusChipMap = {
        '': '#linkActAll',
        '1': '#linkActActive',
        '2': '#linkActInactive',
        '5': '#linkActArchived'
    };

    const tableHeaders = ['#', 'Asset Group', 'Asset Category', 'Description', 'Status', 'Actions'];

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        
        // Only initialize if not yet wrapped (prevents double initialization like in initiatePages())
        if (!$element.parent().hasClass('select-wrapper')) {
            try {
                $element.materialSelect();
            } catch (e) {
                // ignore initialization errors
            }
        }
        
        // BRUTE FORCE FIX: If we have nested wrappers, unwrap the outer one
        setTimeout(function() {
            const $outline = $element.closest('.select-outline');
            if ($outline.length) {
                const $outerWrapper = $outline.children('.select-wrapper');
                if ($outerWrapper.length) {
                    const $innerWrapper = $outerWrapper.children('.select-wrapper.initialized');
                    if ($innerWrapper.length) {
                        // Move inner wrapper directly under select-outline and remove outer wrapper
                        $innerWrapper.appendTo($outline);
                        $outerWrapper.remove();
                    }
                }
            }
        }, 50);
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

    const getNowStamp = function () {
        return (typeof moment !== 'undefined' && moment) ? moment().format('MMM D, YYYY h:mm A') : new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableAssetCategory) {
            return;
        }
        const info = oTableAssetCategory.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblActFilterCount').text(summaryText);
        $('#lblActListCount').text(summaryText);
        $('#lblActFilterUpdated').text(lastListUpdatedText);
        $('#lblActListUpdated').text(lastListUpdatedText);
    };

    const updateStatusChips = function (counts) {
        const labelMap = {
            '': 'All',
            '1': refStatus && refStatus[1] ? refStatus[1]['statusDesc'] : 'Active',
            '2': refStatus && refStatus[2] ? refStatus[2]['statusDesc'] : 'Inactive',
            '5': refStatus && refStatus[5] ? refStatus[5]['statusDesc'] : 'Archived'
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(`${labelMap[status]} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
    };

    const updateAssetCategoryMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;

        dataSet.forEach(function (item) {
            total += 1;
            switch (String(item['assetCategoryStatus'])) {
                case '1':
                    active += 1;
                    break;
                case '2':
                    inactive += 1;
                    break;
                case '5':
                    archived += 1;
                    break;
                default:
                    break;
            }
        });

        $('#metricActTotal').text(mzFormatNumber(total, 0));
        $('#metricActActive').text(mzFormatNumber(active, 0));
        $('#metricActInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricActArchived').text(mzFormatNumber(archived, 0)).toggleClass('text-danger', archived > 0);

        updateStatusChips({
            '': total,
            '1': active,
            '2': inactive,
            '5': archived
        });
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
        if (oTableAssetCategory) {
            oTableAssetCategory.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optActStatus');
            if ($statusSelect.length) {
                // Just update the value - don't reinitialize materialSelect!
                $statusSelect.val(statusFilterValue);
                // Update the display text manually
                const $dropdown = $statusSelect.siblings('input.select-dropdown');
                if ($dropdown.length) {
                    const selectedOption = $statusSelect.find('option:selected');
                    $dropdown.val(selectedOption.text());
                }
            }
        }
    };

    const setGroupFilter = function (value, skipSelect) {
        groupFilterValue = value || '';
        if (oTableAssetCategory) {
            if (groupFilterValue) {
                oTableAssetCategory.column(6).search(`^${groupFilterValue}$`, true, false, true).draw();
            } else {
                oTableAssetCategory.column(6).search('', true, false, true).draw();
            }
            refreshListSummary();
        }
        if (!skipSelect) {
            const $groupSelect = $('#optActGroupId');
            if ($groupSelect.length) {
                // Just update the value - don't reinitialize materialSelect!
                $groupSelect.val(groupFilterValue);
                // Update the display text manually
                const $dropdown = $groupSelect.siblings('input.select-dropdown');
                if ($dropdown.length) {
                    const selectedOption = $groupSelect.find('option:selected');
                    $dropdown.val(selectedOption.text());
                }
            }
        }
    };

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!oTableAssetCategory || settings.nTable.id !== 'dtActAssetCategory') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = oTableAssetCategory.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        return String(rowData['assetCategoryStatus']) === statusFilterValue;
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const handleGroupSelectChange = function () {
        setGroupFilter($(this).val() || '', true);
    };

    const populateGroupFilter = function () {
        const $select = $('#optActGroupId');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Groups</option>'];
        const groups = [];
        $.each(refAssetGroup, function (key, group) {
            if (!group || typeof group !== 'object') {
                return true;
            }
            groups.push({
                id: group['assetGroupId'] || key,
                name: group['assetGroupName'] || ''
            });
            return true;
        });
        groups.sort(function (a, b) {
            return (a.name || '').localeCompare(b.name || '');
        });
        groups.forEach(function (group) {
            options.push(`<option value="${group.id}">${group.name}</option>`);
        });
        $select.html(options.join(''));
        initMaterialSelect('#optActGroupId');
        $select.off('change').on('change', handleGroupSelectChange);
    };

    this.init = function () {
        $.fn.dataTable.ext.search.push(statusFilterFn);

        initMaterialSelect('#optActStatus');
        populateGroupFilter();

        oTableAssetCategory = $('#dtActAssetCategory').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 25,
            dom: "<'row d-none'<'col-sm-12'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
            columnDefs: [
                {targets: [0, 4, 5], orderable: false, className: 'text-center'},
                {targets: [1, 2], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAssetCategory.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkActAssetCategoryEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId, 10)).data();
                        modalAssetCategoryClass.edit(currentRow['assetCategoryId'], rowId);
                    }
                });
                $('.lnkActAssetCategoryDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId, 10)).data();
                        modalAssetCategoryClass.deactivate(currentRow['assetCategoryId'], rowId);
                    }
                });
                $('.lnkActAssetCategoryActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId, 10)).data();
                        modalAssetCategoryClass.activate(currentRow['assetCategoryId'], rowId);
                    }
                });
                $('.lnkActAssetCategoryDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetCategory.row(parseInt(rowId, 10)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetCategoryId'], modalAssetCategoryClass);
                    }
                });
                applyTableDataLabels('#dtActAssetCategory', tableHeaders);
                refreshListSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: 'assetGroupId', mRender: function (data) {
                        if (!refAssetGroup || !refAssetGroup[data]) {
                            return 'Unknown Group';
                        }
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'assetCategoryName'},
                {mData: 'assetCategoryDesc'},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['assetCategoryStatus'];
                        if (!status || !refStatus[status]) {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center action-cell', mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        label += `<button type="button" class="btn-action btn-edit lnkActAssetCategoryEdit" id="lnkActAssetCategoryEdit_${meta.row}" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></button>`;
                        if (row['assetCategoryStatus'] === '1') {
                            label += `<button type="button" class="btn-action btn-delete lnkActAssetCategoryDeactivate" id="lnkActAssetCategoryDeactivate_${meta.row}" data-toggle="tooltip" title="Deactivate"><i class="fas fa-toggle-off"></i></button>`;
                        } else {
                            label += `<button type="button" class="btn-action btn-edit lnkActAssetCategoryActivate" id="lnkActAssetCategoryActivate_${meta.row}" data-toggle="tooltip" title="Activate"><i class="fas fa-toggle-on"></i></button>`;
                        }
                        label += `<button type="button" class="btn-action btn-delete lnkActAssetCategoryDelete" id="lnkActAssetCategoryDelete_${meta.row}" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
                        label += '</div>';
                        return label;
                    }},
                {mData: 'assetGroupId', visible: false},
                {mData: 'assetCategoryId', visible: false}
            ]
        });
        $('#dtActAssetCategory_filter').hide();

        $('#txtActAssetCategorySearch').on('keyup change', function () {
            oTableAssetCategory.search($(this).val()).draw();
        });

        $('#optActStatus').on('change', handleStatusSelectChange);

        const btnAssetCategoryOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                btnAssetCategoryOpt._counter = 1;
                            }
                            btnAssetCategoryOpt._counter = (btnAssetCategoryOpt._counter || 1);
                            return btnAssetCategoryOpt._counter++;
                        }
                        if (column === 4) {
                            const idx = data.indexOf('">');
                            if (idx >= 0) {
                                const content = data.substr(idx + 2);
                                return content.replace('</span></h6>', '');
                            }
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAssetCategory, {
            buttons: [
                $.extend(true, {}, btnAssetCategoryOpt, { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - Asset Category List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetCategoryOpt, { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - Asset Category List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetCategoryOpt, { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - Asset Category List', titleAttr: 'PDF', className: 'btn btn-outline-white btn-rounded btn-sm px-2' })
            ]
        }).container().appendTo($('#btnDtActAssetCategoryExport'));

        $('#btnActAssetCategoryAdd').on('click', function () {
            modalAssetCategoryClass.add();
        });

        $('#btnDtActAssetCategoryRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAct(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);
        setGroupFilter('', true);

        self.genTableAct(0);
    };

    this.genTableAct = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetCategory = mzGetLocalRaw('gems_assetCategory', versionLocal, [], 'asset_category');
        assetCategoryDataCache = Array.isArray(refAssetCategory) ? refAssetCategory : [];
        oTableAssetCategory.clear().rows.add(assetCategoryDataCache).draw();
        lastListUpdatedText = getNowStamp();
        updateAssetCategoryMetrics(assetCategoryDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setGroupFilter(groupFilterValue || '', true);
    };

    this.addTableAct = function (_dataAdd) {
        oTableAssetCategory.row.add(_dataAdd).draw();
        assetCategoryDataCache = oTableAssetCategory.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateAssetCategoryMetrics(assetCategoryDataCache);
        refreshListSummary();
    };

    this.updateTableAct = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetCategory.row(_rowEdit).data();
        if (typeof _dataEdit['assetCategoryName'] !== 'undefined') {
            currentRow['assetCategoryName'] = _dataEdit['assetCategoryName'];
        }
        if (typeof _dataEdit['assetCategoryDesc'] !== 'undefined') {
            currentRow['assetCategoryDesc'] = _dataEdit['assetCategoryDesc'];
        }
        if (typeof _dataEdit['assetCategoryStatus'] !== 'undefined') {
            currentRow['assetCategoryStatus'] = _dataEdit['assetCategoryStatus'];
        }
        if (typeof _dataEdit['assetGroupId'] !== 'undefined') {
            currentRow['assetGroupId'] = _dataEdit['assetGroupId'];
        }
        oTableAssetCategory.row(_rowEdit).data(currentRow).draw();
        assetCategoryDataCache = oTableAssetCategory.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateAssetCategoryMetrics(assetCategoryDataCache);
        refreshListSummary();
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setModalAssetCategoryClass = function (_modalAssetCategoryClass) {
        modalAssetCategoryClass = _modalAssetCategoryClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}