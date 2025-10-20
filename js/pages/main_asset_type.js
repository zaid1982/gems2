function MainAssetType() {

    const className = 'MainAssetType';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetBrand;
    let oTableAssetType;
    let modalAssetTypeClass;
    let oTableAssetModel;
    let modalAssetModelClass;
    let assetTypeId;
    let rowIdModel;
    let assetTypeDataCache = [];
    let assetModelDataCache = [];
    let lastTypeUpdatedText = '—';
    let lastModelUpdatedText = '—';
    let statusFilterValue = '';
    let groupFilterValue = '';
    let categoryFilterValue = '';

    const statusChipMap = {
        '': '#linkAtyAll',
        '1': '#linkAtyActive',
        '2': '#linkAtyInactive',
        '5': '#linkAtyArchived'
    };

    const typeTableHeaders = ['#', 'Asset Group', 'Asset Category', 'Asset Type', 'Description', 'Total Models', 'Status', 'Actions'];
    const modelTableHeaders = ['#', 'Asset Brand', 'Asset Model', 'Description', 'Status', 'Actions'];

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy errors for first-run initialisation
        }
        const $wrapper = $element.parent('.select-wrapper');
        if ($wrapper.length) {
            $wrapper.before($element);
            $wrapper.remove();
        }
        $element.siblings('.select-dropdown').remove();
        $element.materialSelect();
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

    const refreshTypeListSummary = function () {
        if (!oTableAssetType) {
            return;
        }
        const info = oTableAssetType.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblAtyFilterCount').text(summaryText);
        $('#lblAtyListCount').text(summaryText);
        $('#lblAtyFilterUpdated').text(lastTypeUpdatedText);
        $('#lblAtyListUpdated').text(lastTypeUpdatedText);
    };

    const refreshModelSummary = function () {
        if (!oTableAssetModel) {
            return;
        }
        const info = oTableAssetModel.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblAtyModelCount').text(summaryText);
        $('#lblAtyModelUpdated').text(lastModelUpdatedText);
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

    const updateTypeMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let models = 0;

        dataSet.forEach(function (item) {
            total += 1;
            const status = String(item['assetTypeStatus']);
            const modelCount = parseInt(item['totalModel'] || 0, 10);
            const safeModels = isNaN(modelCount) ? 0 : modelCount;
            switch (status) {
                case '1':
                    active += 1;
                    models += safeModels;
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

        $('#metricAtyTotal').text(mzFormatNumber(total, 0));
        $('#metricAtyActive').text(mzFormatNumber(active, 0));
        $('#metricAtyInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricAtyModels').text(mzFormatNumber(models, 0));

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
        if (oTableAssetType) {
            oTableAssetType.draw();
            refreshTypeListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optAtyStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy issues
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusSelectChange);
            }
        }
    };

    const setGroupFilter = function (value, skipSelect) {
        groupFilterValue = value || '';
        if (oTableAssetType) {
            if (groupFilterValue) {
                oTableAssetType.column(8).search(`^${groupFilterValue}$`, true, false, true);
            } else {
                oTableAssetType.column(8).search('');
            }
            oTableAssetType.draw();
            refreshTypeListSummary();
        }
        if (!skipSelect) {
            const $groupSelect = $('#optAtyGroupId');
            if ($groupSelect.length) {
                try {
                    $groupSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore
                }
                $groupSelect.val(groupFilterValue);
                $groupSelect.materialSelect();
                $groupSelect.off('change').on('change', handleGroupSelectChange);
            }
        }
        populateCategoryFilter(groupFilterValue, categoryFilterValue);
    };

    const setCategoryFilter = function (value, skipSelect) {
        categoryFilterValue = value || '';
        if (oTableAssetType) {
            if (categoryFilterValue) {
                oTableAssetType.column(9).search(`^${categoryFilterValue}$`, true, false, true);
            } else {
                oTableAssetType.column(9).search('');
            }
            oTableAssetType.draw();
            refreshTypeListSummary();
        }
        if (!skipSelect) {
            const $categorySelect = $('#optAtyCategoryId');
            if ($categorySelect.length) {
                try {
                    $categorySelect.materialSelect('destroy');
                } catch (e) {
                    // ignore
                }
                $categorySelect.val(categoryFilterValue);
                $categorySelect.materialSelect();
                $categorySelect.off('change').on('change', handleCategorySelectChange);
            }
        }
    };

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!oTableAssetType || settings.nTable.id !== 'dtAtyAssetType') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = oTableAssetType.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        return String(rowData['assetTypeStatus']) === statusFilterValue;
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const handleGroupSelectChange = function () {
        const value = $(this).val() || '';
        categoryFilterValue = '';
        setGroupFilter(value, true);
        setCategoryFilter('', true);
    };

    const handleCategorySelectChange = function () {
        setCategoryFilter($(this).val() || '', true);
    };

    const populateGroupFilter = function () {
        const $select = $('#optAtyGroupId');
        if (!$select.length) {
            return;
        }
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
        const options = ['<option value="" selected>All Groups</option>'];
        groups.forEach(function (group) {
            options.push(`<option value="${group.id}">${group.name}</option>`);
        });
        $select.html(options.join(''));
        initMaterialSelect('#optAtyGroupId');
        $select.off('change').on('change', handleGroupSelectChange);
    };

    const populateCategoryFilter = function (groupId, selectedValue) {
        const $select = $('#optAtyCategoryId');
        if (!$select.length) {
            return;
        }
        const categories = [];
        $.each(refAssetCategory, function (key, category) {
            if (!category || typeof category !== 'object') {
                return true;
            }
            if (groupId && String(category['assetGroupId']) !== String(groupId)) {
                return true;
            }
            categories.push({
                id: category['assetCategoryId'] || key,
                name: category['assetCategoryName'] || ''
            });
            return true;
        });
        categories.sort(function (a, b) {
            return (a.name || '').localeCompare(b.name || '');
        });
        const options = ['<option value="" selected>All Categories</option>'];
        categories.forEach(function (category) {
            options.push(`<option value="${category.id}">${category.name}</option>`);
        });
        $select.html(options.join(''));
        const valueToSet = categories.some(function (category) { return String(category.id) === String(selectedValue); }) ? selectedValue : '';
        initMaterialSelect('#optAtyCategoryId');
        $select.val(valueToSet);
        try {
            $select.materialSelect();
        } catch (e) {
            // ignore
        }
        $select.off('change').on('change', handleCategorySelectChange);
    };

    const bindTypeRowActions = function () {
        $('.lnkAtyAssetTypeEdit').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetType.row(parseInt(rowId, 10)).data();
            modalAssetTypeClass.edit(currentRow['assetTypeId'], rowId);
        });
        $('.lnkAtyAssetTypeDeactivate').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetType.row(parseInt(rowId, 10)).data();
            modalAssetTypeClass.deactivate(currentRow['assetTypeId'], rowId);
        });
        $('.lnkAtyAssetTypeActivate').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetType.row(parseInt(rowId, 10)).data();
            modalAssetTypeClass.activate(currentRow['assetTypeId'], rowId);
        });
        $('.lnkAtyAssetTypeDelete').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetType.row(parseInt(rowId, 10)).data();
            modalConfirmDeleteClass.delete(currentRow['assetTypeId'], modalAssetTypeClass);
        });
        $('.lnkAtyAssetTypeModel').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetType.row(parseInt(rowId, 10)).data();
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAtyModel(0, currentRow['assetTypeId'], rowId, currentRow['assetTypeName']);
                    document.getElementById('sectionAtyModel').scrollIntoView({behavior: 'smooth', block: 'start'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    const bindModelRowActions = function () {
        $('.lnkAtyAssetModelEdit').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetModel.row(parseInt(rowId, 10)).data();
            modalAssetModelClass.edit(currentRow['assetModelId'], rowId);
        });
        $('.lnkAtyAssetModelDeactivate').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetModel.row(parseInt(rowId, 10)).data();
            modalAssetModelClass.deactivate(currentRow['assetModelId'], rowId);
        });
        $('.lnkAtyAssetModelActivate').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetModel.row(parseInt(rowId, 10)).data();
            modalAssetModelClass.activate(currentRow['assetModelId'], rowId);
        });
        $('.lnkAtyAssetModelDelete').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex <= 0) {
                return;
            }
            const rowId = linkId.substr(linkIndex + 1);
            const currentRow = oTableAssetModel.row(parseInt(rowId, 10)).data();
            modalConfirmDeleteClass.delete(currentRow['assetModelId'], modalAssetModelClass);
        });
    };

    this.init = function () {
        $('#sectionAtyModel').hide();
        $.fn.dataTable.ext.search.push(statusFilterFn);

        populateGroupFilter();
        populateCategoryFilter('', '');
        initMaterialSelect('#optAtyStatus');

        oTableAssetType = $('#dtAtyAssetType').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't',
            columnDefs: [
                {targets: [0, 5, 6, 7], orderable: false, className: 'text-center'},
                {targets: [1, 2, 3], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAssetType.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                applyTableDataLabels('#dtAtyAssetType', typeTableHeaders);
                bindTypeRowActions();
                refreshTypeListSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: null, mRender: function (data, type, row) {
                        const groupId = row['assetGroupId'];
                        if (!refAssetGroup || !refAssetGroup[groupId]) {
                            return 'Unknown Group';
                        }
                        return refAssetGroup[groupId]['assetGroupName'];
                    }},
                {mData: null, mRender: function (data, type, row) {
                        const categoryId = row['assetCategoryId'];
                        if (!refAssetCategory || !refAssetCategory[categoryId]) {
                            return 'Unknown Category';
                        }
                        return refAssetCategory[categoryId]['assetCategoryName'];
                    }},
                {mData: 'assetTypeName'},
                {mData: 'assetTypeDesc'},
                {mData: 'totalModel', mRender: function (data, type, row, meta) {
                        const total = mzFormatNumber(parseInt(data || 0, 10), 0);
                        return `<a class="trigger cyan accent-4 text-white lnkAtyAssetTypeModel" id="lnkAtyAssetTypeTotal_${meta.row}" data-toggle="tooltip" data-placement="top" title="View models">${total}</a>`;
                    }},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['assetTypeStatus'];
                        if (!status || !refStatus[status]) {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        let label = `<a><i class="fas fa-edit lnkAtyAssetTypeEdit" id="lnkAtyAssetTypeEdit_${meta.row}" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;`;
                        label += `<a><i class="fas fa-list-ul lnkAtyAssetTypeModel" id="lnkAtyAssetTypeModel_${meta.row}" data-toggle="tooltip" data-placement="top" title="Model list"></i></a>&nbsp;&nbsp;`;
                        if (row['assetTypeStatus'] === '1') {
                            label += `<a><i class="fas fa-toggle-off lnkAtyAssetTypeDeactivate" id="lnkAtyAssetTypeDeactivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;`;
                        } else {
                            label += `<a><i class="fas fa-toggle-on lnkAtyAssetTypeActivate" id="lnkAtyAssetTypeActivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;`;
                        }
                        label += `<a><i class="fas fa-trash-alt lnkAtyAssetTypeDelete" id="lnkAtyAssetTypeDelete_${meta.row}" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>`;
                        return label;
                    }},
                {mData: 'assetGroupId', visible: false},
                {mData: 'assetCategoryId', visible: false},
                {mData: 'assetTypeId', visible: false},
                {mData: 'assetTypeStatus', visible: false}
            ]
        });
        $('#dtAtyAssetType_filter').hide();

        $('#txtAtyAssetTypeSearch').on('keyup change', function () {
            oTableAssetType.search($(this).val()).draw();
        });

        $('#optAtyStatus').on('change', handleStatusSelectChange);

        let exportCounter = 1;
        const btnAssetTypeOpt = {
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
                            return data.replace(/<[^>]+>/g, '');
                        }
                        if (column === 6) {
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

        new $.fn.dataTable.Buttons(oTableAssetType, {
            buttons: [
                $.extend(true, {}, btnAssetTypeOpt, { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - Asset Type List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetTypeOpt, { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - Asset Type List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetTypeOpt, { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - Asset Type List', titleAttr: 'PDF', className: 'btn btn-outline-white btn-rounded btn-sm px-2' })
            ]
        }).container().appendTo($('#btnDtAtyAssetTypeExport'));

        $('#btnAtyAssetTypeAdd').on('click', function () {
            modalAssetTypeClass.add();
        });

        $('#btnDtAtyAssetTypeRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAty(1);
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

        oTableAssetModel = $('#dtAtyAssetModel').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't',
            columnDefs: [
                {targets: [0, 4, 5], orderable: false, className: 'text-center'},
                {targets: [1, 2], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAssetModel.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                applyTableDataLabels('#dtAtyAssetModel', modelTableHeaders);
                bindModelRowActions();
                refreshModelSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: null, mRender: function (data, type, row) {
                        const brandId = row['assetBrandId'];
                        if (!refAssetBrand || !refAssetBrand[brandId]) {
                            return 'Unknown Brand';
                        }
                        return refAssetBrand[brandId]['assetBrandName'];
                    }},
                {mData: 'assetModelName'},
                {mData: 'assetModelDesc'},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['assetModelStatus'];
                        if (!status || !refStatus[status]) {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        let label = `<a><i class="fas fa-edit lnkAtyAssetModelEdit" id="lnkAtyAssetModelEdit_${meta.row}" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;`;
                        if (row['assetModelStatus'] === '1') {
                            label += `<a><i class="fas fa-toggle-off lnkAtyAssetModelDeactivate" id="lnkAtyAssetModelDeactivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;`;
                        } else {
                            label += `<a><i class="fas fa-toggle-on lnkAtyAssetModelActivate" id="lnkAtyAssetModelActivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;`;
                        }
                        label += `<a><i class="fas fa-trash-alt lnkAtyAssetModelDelete" id="lnkAtyAssetModelDelete_${meta.row}" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>`;
                        return label;
                    }},
                {mData: 'assetModelId', visible: false}
            ]
        });
        $('#dtAtyAssetModel_filter').hide();

        $('#txtAtyAssetModelSearch').on('keyup change', function () {
            oTableAssetModel.search($(this).val()).draw();
        });

        let exportModelCounter = 1;
        const btnAssetModelOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportModelCounter = 1;
                            }
                            return exportModelCounter++;
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

        new $.fn.dataTable.Buttons(oTableAssetModel, {
            buttons: [
                $.extend(true, {}, btnAssetModelOpt, { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - Asset Model List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetModelOpt, { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - Asset Model List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetModelOpt, { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - Asset Model List', titleAttr: 'PDF', className: 'btn btn-outline-white btn-rounded btn-sm px-2' })
            ]
        }).container().appendTo($('#btnDtAtyAssetModelExport'));

        $('#btnAtyAssetModelAdd').on('click', function () {
            modalAssetModelClass.add();
        });

        $('#btnDtAtyAssetModelRefresh').on('click', function () {
            if (!assetTypeId) {
                toastr['info']('Select an asset type first.', 'Info');
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAtyModel(1, assetTypeId, rowIdModel);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        setStatusFilter('', true);
        setGroupFilter('', true);
        setCategoryFilter('', true);

        self.genTableAty(0);
    };

    this.genTableAty = function (_type) {
        $('#sectionAtyModel').hide();
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetType = mzGetLocalRaw('gems_assetType', versionLocal, [], 'asset_type');
        assetTypeDataCache = Array.isArray(refAssetType) ? refAssetType : [];
        oTableAssetType.clear().rows.add(assetTypeDataCache).draw();
        lastTypeUpdatedText = getNowStamp();
        updateTypeMetrics(assetTypeDataCache);
        refreshTypeListSummary();
        setStatusFilter(statusFilterValue, true);
        setGroupFilter(groupFilterValue, true);
        setCategoryFilter(categoryFilterValue, true);
    };

    this.addTableAty = function (_dataAdd) {
        oTableAssetType.row.add(_dataAdd).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdatedText = getNowStamp();
        updateTypeMetrics(assetTypeDataCache);
        refreshTypeListSummary();
    };

    this.updateTableAty = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetType.row(_rowEdit).data();
        if (typeof _dataEdit['assetTypeName'] !== 'undefined') {
            currentRow['assetTypeName'] = _dataEdit['assetTypeName'];
        }
        if (typeof _dataEdit['assetTypeDesc'] !== 'undefined') {
            currentRow['assetTypeDesc'] = _dataEdit['assetTypeDesc'];
        }
        if (typeof _dataEdit['assetTypeStatus'] !== 'undefined') {
            currentRow['assetTypeStatus'] = _dataEdit['assetTypeStatus'];
        }
        if (typeof _dataEdit['assetGroupId'] !== 'undefined') {
            currentRow['assetGroupId'] = _dataEdit['assetGroupId'];
        }
        if (typeof _dataEdit['assetCategoryId'] !== 'undefined') {
            currentRow['assetCategoryId'] = _dataEdit['assetCategoryId'];
        }
        if (typeof _dataEdit['totalModel'] !== 'undefined') {
            currentRow['totalModel'] = _dataEdit['totalModel'];
        }
        oTableAssetType.row(_rowEdit).data(currentRow).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdatedText = getNowStamp();
        updateTypeMetrics(assetTypeDataCache);
        refreshTypeListSummary();
    };

    this.genTableAtyModel = function (_type, _assetTypeId, _rowIdModel, _assetTypeName) {
        assetTypeId = _assetTypeId;
        rowIdModel = _rowIdModel;
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetModel = mzGetLocalRaw('gems_assetModel', versionLocal, {assetTypeId: assetTypeId}, 'asset_model');
        assetModelDataCache = Array.isArray(refAssetModel) ? refAssetModel : [];
        oTableAssetModel.clear().rows.add(assetModelDataCache).draw();
        modalAssetModelClass.setAssetTypeId(assetTypeId);
        $('#lblAtyAssetModelTitle').text(_assetTypeName || '—');
        $('#sectionAtyModel').show();
        lastModelUpdatedText = getNowStamp();
        refreshModelSummary();
    };

    this.addTableAtyModel = function (_dataAdd) {
        oTableAssetModel.row.add(_dataAdd).draw();
        assetModelDataCache = oTableAssetModel.rows().data().toArray();
        lastModelUpdatedText = getNowStamp();
        refreshModelSummary();
        const currentRow = oTableAssetType.row(rowIdModel).data();
        currentRow['totalModel'] = parseInt(currentRow['totalModel'] || 0, 10) + 1;
        oTableAssetType.row(rowIdModel).data(currentRow).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdatedText = getNowStamp();
        updateTypeMetrics(assetTypeDataCache);
        refreshTypeListSummary();
    };

    this.updateTableAtyModel = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetModel.row(_rowEdit).data();
        if (typeof _dataEdit['assetModelName'] !== 'undefined') {
            currentRow['assetModelName'] = _dataEdit['assetModelName'];
        }
        if (typeof _dataEdit['assetModelDesc'] !== 'undefined') {
            currentRow['assetModelDesc'] = _dataEdit['assetModelDesc'];
        }
        if (typeof _dataEdit['assetModelStatus'] !== 'undefined') {
            currentRow['assetModelStatus'] = _dataEdit['assetModelStatus'];
        }
        oTableAssetModel.row(_rowEdit).data(currentRow).draw();
        assetModelDataCache = oTableAssetModel.rows().data().toArray();
        lastModelUpdatedText = getNowStamp();
        refreshModelSummary();
    };

    this.deleteTableAtyModel = function () {
        self.genTableAtyModel(1, assetTypeId, rowIdModel);
        const currentRow = oTableAssetType.row(rowIdModel).data();
        currentRow['totalModel'] = Math.max(0, parseInt(currentRow['totalModel'] || 0, 10) - 1);
        oTableAssetType.row(rowIdModel).data(currentRow).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdatedText = getNowStamp();
        updateTypeMetrics(assetTypeDataCache);
        refreshTypeListSummary();
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

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
    };

    this.setModalAssetTypeClass = function (_modalAssetTypeClass) {
        modalAssetTypeClass = _modalAssetTypeClass;
    };

    this.setModalAssetModelClass = function (_modalAssetModelClass) {
        modalAssetModelClass = _modalAssetModelClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}