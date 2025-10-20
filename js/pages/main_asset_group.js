function MainAssetGroup() {

    const className = 'MainAssetGroup';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAssetGroup;
    let modalAssetGroupClass;
    let assetGroupDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const statusChipMap = {
        '': '#linkAgrAll',
        '1': '#linkAgrActive',
        '2': '#linkAgrInactive',
        '5': '#linkAgrArchived'
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
        if (!oTableAssetGroup) {
            return;
        }
        const info = oTableAssetGroup.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblAgrFilterCount').text(summaryText);
        $('#lblAgrListCount').text(summaryText);
        $('#lblAgrFilterUpdated').text(lastListUpdatedText);
        $('#lblAgrListUpdated').text(lastListUpdatedText);
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

    const updateAssetGroupMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;

        dataSet.forEach(function (item) {
            total += 1;
            switch (String(item['assetGroupStatus'])) {
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

        $('#metricAgrTotal').text(mzFormatNumber(total, 0));
        $('#metricAgrActive').text(mzFormatNumber(active, 0));
        $('#metricAgrInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricAgrArchived').text(mzFormatNumber(archived, 0)).toggleClass('text-danger', archived > 0);

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

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy errors for first run
        }
        const $wrapper = $element.parent('.select-wrapper');
        if ($wrapper.length) {
            $wrapper.before($element);
            $wrapper.remove();
        }
        $element.siblings('.select-dropdown').remove();
        $element.materialSelect();
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableAssetGroup) {
            oTableAssetGroup.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optAgrStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors when not yet initialised
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusSelectChange);
            }
        }
    };

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!oTableAssetGroup || settings.nTable.id !== 'dtAgrAssetGroup') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = oTableAssetGroup.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        return String(rowData['assetGroupStatus']) === statusFilterValue;
    };

    const toArray = function (raw) {
        const result = [];
        $.each(raw, function (idx, item) {
            if (typeof item !== 'undefined' && item !== null) {
                result.push(item);
            }
        });
        return result;
    };

    this.init = function () {
        $.fn.dataTable.ext.search.push(statusFilterFn);

        initMaterialSelect('#optAgrStatus');

        oTableAssetGroup = $('#dtAgrAssetGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't',
            columnDefs: [
                {targets: [0, 4], orderable: false, className: 'text-center'},
                {targets: [3], className: 'text-center'},
                {targets: [1, 2], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAssetGroup.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAgrAssetGroupEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId, 10)).data();
                        modalAssetGroupClass.edit(currentRow['assetGroupId'], rowId);
                    }
                });
                $('.lnkAgrAssetGroupDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId, 10)).data();
                        modalAssetGroupClass.deactivate(currentRow['assetGroupId'], rowId);
                    }
                });
                $('.lnkAgrAssetGroupActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId, 10)).data();
                        modalAssetGroupClass.activate(currentRow['assetGroupId'], rowId);
                    }
                });
                $('.lnkAgrAssetGroupDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetGroup.row(parseInt(rowId, 10)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetGroupId'], modalAssetGroupClass);
                    }
                });
                applyTableDataLabels('#dtAgrAssetGroup', ['#', 'Asset Group', 'Description', 'Status', 'Actions']);
                refreshListSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: 'assetGroupName'},
                {mData: 'assetGroupDesc'},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['assetGroupStatus'];
                        if (!status || typeof refStatus[status] === 'undefined') {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        let label = `<a><i class="fas fa-edit lnkAgrAssetGroupEdit" id="lnkAgrAssetGroupEdit_${meta.row}" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;`;
                        if (row['assetGroupStatus'] === '1') {
                            label += `<a><i class="fas fa-toggle-off lnkAgrAssetGroupDeactivate" id="lnkAgrAssetGroupDeactivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;`;
                        } else {
                            label += `<a><i class="fas fa-toggle-on lnkAgrAssetGroupActivate" id="lnkAgrAssetGroupActivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;`;
                        }
                        label += `<a><i class="fas fa-trash-alt lnkAgrAssetGroupDelete" id="lnkAgrAssetGroupDelete_${meta.row}" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>`;
                        return label;
                    }},
                {mData: 'assetGroupId', visible: false}
            ]
        });
        $('#dtAgrAssetGroup_filter').hide();

        $('#txtAgrAssetGroupSearch').on('keyup change', function () {
            oTableAssetGroup.search($(this).val()).draw();
        });

        $('#optAgrStatus').on('change', handleStatusSelectChange);

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        let exportCount = 1;
        const btnAssetGroupOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportCount = 1;
                            }
                            return exportCount++;
                        }
                        if (column === 3) {
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

        new $.fn.dataTable.Buttons(oTableAssetGroup, {
            buttons: [
                $.extend(true, {}, btnAssetGroupOpt, { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - Asset Group List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetGroupOpt, { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - Asset Group List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetGroupOpt, { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - Asset Group List', titleAttr: 'PDF', className: 'btn btn-outline-white btn-rounded btn-sm px-2' })
            ]
        }).container().appendTo($('#btnDtAgrAssetGroupExport'));

        $('#btnAgrAssetGroupAdd').on('click', function () {
            modalAssetGroupClass.add();
        });

        $('#btnDtAgrAssetGroupRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAgr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableAgr(0);
    };

    this.genTableAgr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const rawAssetGroup = mzGetLocalRaw('gems_assetGroup', versionLocal, [], 'asset_group');
        assetGroupDataCache = toArray(rawAssetGroup);
        oTableAssetGroup.clear().rows.add(assetGroupDataCache).draw();
        lastListUpdatedText = getNowStamp();
        updateAssetGroupMetrics(assetGroupDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.addTableAgr = function (_dataAdd) {
        oTableAssetGroup.row.add(_dataAdd).draw();
        assetGroupDataCache = oTableAssetGroup.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateAssetGroupMetrics(assetGroupDataCache);
        refreshListSummary();
    };

    this.updateTableAgr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetGroup.row(_rowEdit).data();
        if (typeof _dataEdit['assetGroupName'] !== 'undefined') {
            currentRow['assetGroupName'] = _dataEdit['assetGroupName'];
        }
        if (typeof _dataEdit['assetGroupDesc'] !== 'undefined') {
            currentRow['assetGroupDesc'] = _dataEdit['assetGroupDesc'];
        }
        if (typeof _dataEdit['assetGroupStatus'] !== 'undefined') {
            currentRow['assetGroupStatus'] = _dataEdit['assetGroupStatus'];
        }
        oTableAssetGroup.row(_rowEdit).data(currentRow).draw();
        assetGroupDataCache = oTableAssetGroup.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateAssetGroupMetrics(assetGroupDataCache);
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

    this.setModalAssetGroupClass = function (_modalAssetGroupClass) {
        modalAssetGroupClass = _modalAssetGroupClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}