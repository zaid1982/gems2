function MainAssetBrand() {

    const className = 'MainAssetBrand';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAssetBrand;
    let modalAssetBrandClass;
    let assetBrandDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';

    const statusChipMap = {
        '': '#linkAbrAll',
        '1': '#linkAbrActive',
        '2': '#linkAbrInactive',
        '5': '#linkAbrArchived'
    };

    const tableHeaders = ['#', 'Asset Brand', 'Description', 'Status', 'Actions'];

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
        if (!oTableAssetBrand) {
            return;
        }
        const info = oTableAssetBrand.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblAbrFilterCount').text(summaryText);
        $('#lblAbrListCount').text(summaryText);
        $('#lblAbrFilterUpdated').text(lastListUpdatedText);
        $('#lblAbrListUpdated').text(lastListUpdatedText);
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

    const updateAssetBrandMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;

        dataSet.forEach(function (item) {
            total += 1;
            switch (String(item['assetBrandStatus'])) {
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

        $('#metricAbrTotal').text(mzFormatNumber(total, 0));
        $('#metricAbrActive').text(mzFormatNumber(active, 0));
        $('#metricAbrInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricAbrArchived').text(mzFormatNumber(archived, 0)).toggleClass('text-danger', archived > 0);

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
        if (oTableAssetBrand) {
            oTableAssetBrand.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optAbrStatus');
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

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!oTableAssetBrand || settings.nTable.id !== 'dtAbrAssetBrand') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = oTableAssetBrand.row(dataIndex).data();
        if (!rowData) {
            return true;
    }
        return String(rowData['assetBrandStatus']) === statusFilterValue;
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    this.init = function () {
        $.fn.dataTable.ext.search.push(statusFilterFn);

        initMaterialSelect('#optAbrStatus');

        oTableAssetBrand = $('#dtAbrAssetBrand').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 25,
            dom: "<'row d-none'<'col-sm-12'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
            columnDefs: [
                {targets: [0, 3, 4], orderable: false, className: 'text-center'},
                {targets: [1], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAssetBrand.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAbrAssetBrandEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId, 10)).data();
                        modalAssetBrandClass.edit(currentRow['assetBrandId'], rowId);
                    }
                });
                $('.lnkAbrAssetBrandDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId, 10)).data();
                        modalAssetBrandClass.deactivate(currentRow['assetBrandId'], rowId);
                    }
                });
                $('.lnkAbrAssetBrandActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId, 10)).data();
                        modalAssetBrandClass.activate(currentRow['assetBrandId'], rowId);
                    }
                });
                $('.lnkAbrAssetBrandDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAssetBrand.row(parseInt(rowId, 10)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetBrandId'], modalAssetBrandClass);
                    }
                });
                applyTableDataLabels('#dtAbrAssetBrand', tableHeaders);
                refreshListSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: 'assetBrandName'},
                {mData: 'assetBrandDesc'},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['assetBrandStatus'];
                        if (!status || !refStatus[status]) {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center action-cell', mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        label += `<button type="button" class="btn-action btn-edit lnkAbrAssetBrandEdit" id="lnkAbrAssetBrandEdit_${meta.row}" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></button>`;
                        if (row['assetBrandStatus'] === '1') {
                            label += `<button type="button" class="btn-action btn-delete lnkAbrAssetBrandDeactivate" id="lnkAbrAssetBrandDeactivate_${meta.row}" data-toggle="tooltip" title="Deactivate"><i class="fas fa-toggle-off"></i></button>`;
                        } else {
                            label += `<button type="button" class="btn-action btn-edit lnkAbrAssetBrandActivate" id="lnkAbrAssetBrandActivate_${meta.row}" data-toggle="tooltip" title="Activate"><i class="fas fa-toggle-on"></i></button>`;
                        }
                        label += `<button type="button" class="btn-action btn-delete lnkAbrAssetBrandDelete" id="lnkAbrAssetBrandDelete_${meta.row}" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
                        label += '</div>';
                        return label;
                    }},
                {mData: 'assetBrandId', visible: false}
            ]
        });
        $('#dtAbrAssetBrand_filter').hide();

        $('#txtAbrAssetBrandSearch').on('keyup change', function () {
            oTableAssetBrand.search($(this).val()).draw();
        });

        $('#optAbrStatus').on('change', handleStatusSelectChange);

        let exportCounter = 1;
        const btnAssetBrandOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportCounter = 1;
                            }
                            return exportCounter++;
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

        new $.fn.dataTable.Buttons(oTableAssetBrand, {
            buttons: [
                $.extend(true, {}, btnAssetBrandOpt, { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - Asset Brand List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetBrandOpt, { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - Asset Brand List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetBrandOpt, { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - Asset Brand List', titleAttr: 'PDF', className: 'btn btn-outline-white btn-rounded btn-sm px-2' })
            ]
        }).container().appendTo($('#btnDtAbrAssetBrandExport'));

        $('#btnAbrAssetBrandAdd').on('click', function () {
            modalAssetBrandClass.add();
        });

        $('#btnDtAbrAssetBrandRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAbr(1);
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

        self.genTableAbr(0);
    };

    this.genTableAbr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetBrand = mzGetLocalRaw('gems_assetBrand', versionLocal, [], 'asset_brand');
        assetBrandDataCache = Array.isArray(refAssetBrand) ? refAssetBrand : [];
        oTableAssetBrand.clear().rows.add(assetBrandDataCache).draw();
        lastListUpdatedText = getNowStamp();
        updateAssetBrandMetrics(assetBrandDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.addTableAbr = function (_dataAdd) {
        oTableAssetBrand.row.add(_dataAdd).draw();
        assetBrandDataCache = oTableAssetBrand.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateAssetBrandMetrics(assetBrandDataCache);
        refreshListSummary();
    };

    this.updateTableAbr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetBrand.row(_rowEdit).data();
        if (typeof _dataEdit['assetBrandName'] !== 'undefined') {
            currentRow['assetBrandName'] = _dataEdit['assetBrandName'];
        }
        if (typeof _dataEdit['assetBrandDesc'] !== 'undefined') {
            currentRow['assetBrandDesc'] = _dataEdit['assetBrandDesc'];
        }
        if (typeof _dataEdit['assetBrandStatus'] !== 'undefined') {
            currentRow['assetBrandStatus'] = _dataEdit['assetBrandStatus'];
        }
        oTableAssetBrand.row(_rowEdit).data(currentRow).draw();
        assetBrandDataCache = oTableAssetBrand.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateAssetBrandMetrics(assetBrandDataCache);
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

    this.setModalAssetBrandClass = function (_modalAssetBrandClass) {
        modalAssetBrandClass = _modalAssetBrandClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}