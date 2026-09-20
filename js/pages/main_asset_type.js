function MainAssetType() {

    const className = 'MainAssetType';
    const momentAvailable = (typeof moment === 'function');
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
    let lastTypeUpdated = null;
    let lastModelUpdated = null;
    let statusFilterValue = '';
    let groupFilterValue = '';
    let categoryFilterValue = '';
    let statusFilterFn;

    const statusChipMap = {
        '': '#linkAtyAll',
        '1': '#linkAtyActive',
        '2': '#linkAtyInactive',
        '5': '#linkAtyArchived'
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

    function assetGroupRows() {
        const rows = [];
        $.each(refAssetGroup, function (key, group) {
            if (!group || typeof group !== 'object') {
                return true;
            }
            rows.push(group);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['assetGroupName'] || '').localeCompare(b['assetGroupName'] || '');
        });
        return rows;
    }

    function assetCategoryRows(groupId) {
        const rows = [];
        $.each(refAssetCategory, function (key, category) {
            if (!category || typeof category !== 'object') {
                return true;
            }
            if (groupId && String(category['assetGroupId']) !== String(groupId)) {
                return true;
            }
            rows.push(category);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['assetCategoryName'] || '').localeCompare(b['assetCategoryName'] || '');
        });
        return rows;
    }

    function populateGroupFilter() {
        GemsUI.fillSelect(
            'optAtyGroupId',
            assetGroupRows(),
            'assetGroupId',
            function (row) {
                return row['assetGroupName'] || '';
            },
            'All Groups',
            groupFilterValue
        );
    }

    function populateCategoryFilter(groupId, selectedValue) {
        const categories = assetCategoryRows(groupId);
        const valueToSet = categories.some(function (category) {
            return String(category['assetCategoryId']) === String(selectedValue);
        }) ? selectedValue : '';
        GemsUI.fillSelect(
            'optAtyCategoryId',
            categories,
            'assetCategoryId',
            function (row) {
                return row['assetCategoryName'] || '';
            },
            'All Categories',
            valueToSet
        );
        return valueToSet;
    }

    function groupName(groupId) {
        if (refAssetGroup && refAssetGroup[groupId] && refAssetGroup[groupId]['assetGroupName']) {
            return refAssetGroup[groupId]['assetGroupName'];
        }
        return 'Unknown Group';
    }

    function categoryName(categoryId) {
        if (refAssetCategory && refAssetCategory[categoryId] && refAssetCategory[categoryId]['assetCategoryName']) {
            return refAssetCategory[categoryId]['assetCategoryName'];
        }
        return 'Unknown Category';
    }

    function brandName(brandId) {
        if (refAssetBrand && refAssetBrand[brandId] && refAssetBrand[brandId]['assetBrandName']) {
            return refAssetBrand[brandId]['assetBrandName'];
        }
        return 'Unknown Brand';
    }

    function displayText(value, type) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        return GemsUI.escape(text);
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
                return fallback || '';
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

    function updateTypeMetrics(dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let models = 0;

        (dataSet || []).forEach(function (item) {
            if (!item) {
                return;
            }
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

        $('#metricAtyTotal').text(total.toLocaleString());
        $('#metricAtyActive').text(active.toLocaleString());
        $('#metricAtyInactive').text(inactive.toLocaleString());
        $('#metricAtyModels').text(models.toLocaleString());

        updateStatusChips({
            '': total,
            '1': active,
            '2': inactive,
            '5': archived
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
            $(selector).html(GemsUI.escape(labelMap[status]) + ' <span class="badge bg-secondary-lt ms-1">' + count.toLocaleString() + '</span>');
        });
        setActiveStatusChip(statusFilterValue);
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

    function refreshTypeListSummary() {
        if (!oTableAssetType) {
            return;
        }
        const info = (typeof oTableAssetType.page === 'function' && typeof oTableAssetType.page.info === 'function')
            ? oTableAssetType.page.info()
            : null;
        const summaryText = info
            ? ('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal)
            : 'Showing 0 of 0';
        const updatedText = formatTimestamp(lastTypeUpdated);
        $('#lblAtyFilterCount').text(summaryText);
        $('#lblAtyListCount').text(summaryText + ' records');
        $('#lblAtyFilterUpdated').text(updatedText);
        $('#lblAtyListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
    }

    function refreshModelSummary() {
        if (!oTableAssetModel) {
            return;
        }
        const info = (typeof oTableAssetModel.page === 'function' && typeof oTableAssetModel.page.info === 'function')
            ? oTableAssetModel.page.info()
            : null;
        const summaryText = info
            ? ('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal)
            : 'Showing 0 of 0';
        $('#lblAtyModelCount').text(summaryText);
        $('#lblAtyModelUpdated').html('<i class="far fa-clock me-1"></i>' + formatTimestamp(lastModelUpdated));
    }

    function applyGroupFilter() {
        if (!oTableAssetType) {
            return;
        }
        if (groupFilterValue) {
            oTableAssetType.column(8).search('^' + groupFilterValue + '$', true, false, true);
        } else {
            oTableAssetType.column(8).search('');
        }
    }

    function applyCategoryFilter() {
        if (!oTableAssetType) {
            return;
        }
        if (categoryFilterValue) {
            oTableAssetType.column(9).search('^' + categoryFilterValue + '$', true, false, true);
        } else {
            oTableAssetType.column(9).search('');
        }
    }

    function setStatusFilter(value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableAssetType) {
            oTableAssetType.draw();
            refreshTypeListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            $('#optAtyStatus').val(statusFilterValue);
        }
    }

    function setGroupFilter(value, skipSelect) {
        groupFilterValue = value || '';
        if (!skipSelect) {
            $('#optAtyGroupId').val(groupFilterValue);
        }
        categoryFilterValue = populateCategoryFilter(groupFilterValue, categoryFilterValue);
        if (oTableAssetType) {
            applyGroupFilter();
            applyCategoryFilter();
            oTableAssetType.draw();
            refreshTypeListSummary();
        }
    }

    function setCategoryFilter(value, skipSelect) {
        categoryFilterValue = value || '';
        if (!skipSelect) {
            $('#optAtyCategoryId').val(categoryFilterValue);
        }
        if (oTableAssetType) {
            applyCategoryFilter();
            oTableAssetType.draw();
            refreshTypeListSummary();
        }
    }

    function handleStatusSelectChange() {
        setStatusFilter($(this).val() || '', true);
    }

    function handleGroupSelectChange() {
        categoryFilterValue = '';
        setGroupFilter($(this).val() || '', true);
        setCategoryFilter('', true);
    }

    function handleCategorySelectChange() {
        setCategoryFilter($(this).val() || '', true);
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el, table) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !table) {
            return null;
        }
        return { rowId: rowId, data: table.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        $('#sectionAtyModel').hide();

        populateGroupFilter();
        populateCategoryFilter('', '');

        let cntAssetType;
        const exportTypeOpt = {
            columns: [0, 1, 2, 3, 4, 5, 6],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntAssetType = 1;
                    }
                    if (column === 0) {
                        return cntAssetType++;
                    }
                    return data;
                }
            }
        };
        const dtTypeButtons = GemsUI.dtButtons('GEMS 2.0 - Asset Type List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportTypeOpt;
            return btn;
        });

        oTableAssetType = $('#dtAtyAssetType').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            pageLength: 25,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtTypeButtons,
            language: GemsUI.dtEmpty('fa-sitemap', 'No asset types recorded yet.', 'No asset types match the current search or filters.'),
            columnDefs: [
                {targets: [0, 5, 6, 7], orderable: false, className: 'text-center'},
                {targets: [1, 2, 3], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableAssetType && oTableAssetType.page && typeof oTableAssetType.page.info === 'function')
                    ? oTableAssetType.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                refreshTypeListSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'assetGroupId', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(groupName(data), type);
                    }
                },
                {mData: 'assetCategoryId', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(categoryName(data), type);
                    }
                },
                {mData: 'assetTypeName', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'assetTypeDesc',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'totalModel', bSortable: false, sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        const total = parseInt(data || 0, 10);
                        const safeTotal = isNaN(total) ? 0 : total;
                        if (type !== 'display') {
                            return safeTotal;
                        }
                        return '<button type="button" class="btn btn-sm btn-outline-primary lnkAtyAssetTypeModel" id="lnkAtyAssetTypeTotal_' + meta.row + '" data-toggle="tooltip" title="View models" aria-label="View models">' + safeTotal.toLocaleString() + '</button>';
                    }
                },
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['assetTypeStatus'], type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkAtyAssetTypeEdit',
                            id: 'lnkAtyAssetTypeEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-view',
                            cls: 'lnkAtyAssetTypeModel',
                            id: 'lnkAtyAssetTypeModel_' + meta.row,
                            title: 'Model list',
                            icon: 'fas fa-list-ul'
                        });
                        if (row['assetTypeStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkAtyAssetTypeDeactivate',
                                id: 'lnkAtyAssetTypeDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkAtyAssetTypeActivate',
                                id: 'lnkAtyAssetTypeActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkAtyAssetTypeDelete',
                            id: 'lnkAtyAssetTypeDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'assetGroupId', visible: false, sClass: 'noVis'},
                {mData: 'assetCategoryId', visible: false, sClass: 'noVis'},
                {mData: 'assetTypeId', visible: false, sClass: 'noVis'},
                {mData: 'assetTypeStatus', visible: false, sClass: 'noVis'}
            ]
        });

        oTableAssetType.buttons().container().appendTo($('#btnDtAtyAssetTypeExport'));
        GemsUI.bindDtTooltips('#dtAtyAssetType');

        const typeBody = $('#dtAtyAssetType tbody');
        typeBody.on('click', '.lnkAtyAssetTypeEdit', function () {
            const current = rowDataFromLink(this, oTableAssetType);
            if (current && current.data) {
                modalAssetTypeClass.edit(current.data['assetTypeId'], current.rowId);
            }
        });
        typeBody.on('click', '.lnkAtyAssetTypeDeactivate', function () {
            const current = rowDataFromLink(this, oTableAssetType);
            if (current && current.data) {
                modalAssetTypeClass.deactivate(current.data['assetTypeId'], current.rowId);
            }
        });
        typeBody.on('click', '.lnkAtyAssetTypeActivate', function () {
            const current = rowDataFromLink(this, oTableAssetType);
            if (current && current.data) {
                modalAssetTypeClass.activate(current.data['assetTypeId'], current.rowId);
            }
        });
        typeBody.on('click', '.lnkAtyAssetTypeDelete', function () {
            const current = rowDataFromLink(this, oTableAssetType);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['assetTypeId'], modalAssetTypeClass);
            }
        });
        typeBody.on('click', '.lnkAtyAssetTypeModel', function () {
            const current = rowDataFromLink(this, oTableAssetType);
            if (!current || !current.data) {
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAtyModel(0, current.data['assetTypeId'], current.rowId, current.data['assetTypeName']);
                    const section = document.getElementById('sectionAtyModel');
                    if (section && typeof section.scrollIntoView === 'function') {
                        section.scrollIntoView({behavior: 'smooth', block: 'start'});
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtAtyAssetType') {
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
        $.fn.dataTable.ext.search.push(statusFilterFn);

        $('#txtAtyAssetTypeSearch').on('keyup change', function () {
            oTableAssetType.search($(this).val()).draw();
        });
        $('#optAtyStatus').on('change', handleStatusSelectChange);
        $('#optAtyGroupId').on('change', handleGroupSelectChange);
        $('#optAtyCategoryId').on('change', handleCategorySelectChange);

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

        let cntAssetModel;
        const exportModelOpt = {
            columns: [0, 1, 2, 3, 4],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntAssetModel = 1;
                    }
                    if (column === 0) {
                        return cntAssetModel++;
                    }
                    return data;
                }
            }
        };
        const dtModelButtons = GemsUI.dtButtons('GEMS 2.0 - Asset Model List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportModelOpt;
            return btn;
        });

        oTableAssetModel = $('#dtAtyAssetModel').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            pageLength: 25,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtModelButtons,
            language: GemsUI.dtEmpty('fa-cubes', 'No asset models recorded yet.', 'No asset models match the current search.'),
            columnDefs: [
                {targets: [0, 4, 5], orderable: false, className: 'text-center'},
                {targets: [1, 2], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableAssetModel && oTableAssetModel.page && typeof oTableAssetModel.page.info === 'function')
                    ? oTableAssetModel.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                refreshModelSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'assetBrandId', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(brandName(data), type);
                    }
                },
                {mData: 'assetModelName', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'assetModelDesc',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['assetModelStatus'], type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkAtyAssetModelEdit',
                            id: 'lnkAtyAssetModelEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['assetModelStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkAtyAssetModelDeactivate',
                                id: 'lnkAtyAssetModelDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkAtyAssetModelActivate',
                                id: 'lnkAtyAssetModelActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkAtyAssetModelDelete',
                            id: 'lnkAtyAssetModelDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'assetModelId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableAssetModel.buttons().container().appendTo($('#btnDtAtyAssetModelExport'));
        GemsUI.bindDtTooltips('#dtAtyAssetModel');

        const modelBody = $('#dtAtyAssetModel tbody');
        modelBody.on('click', '.lnkAtyAssetModelEdit', function () {
            const current = rowDataFromLink(this, oTableAssetModel);
            if (current && current.data) {
                modalAssetModelClass.edit(current.data['assetModelId'], current.rowId);
            }
        });
        modelBody.on('click', '.lnkAtyAssetModelDeactivate', function () {
            const current = rowDataFromLink(this, oTableAssetModel);
            if (current && current.data) {
                modalAssetModelClass.deactivate(current.data['assetModelId'], current.rowId);
            }
        });
        modelBody.on('click', '.lnkAtyAssetModelActivate', function () {
            const current = rowDataFromLink(this, oTableAssetModel);
            if (current && current.data) {
                modalAssetModelClass.activate(current.data['assetModelId'], current.rowId);
            }
        });
        modelBody.on('click', '.lnkAtyAssetModelDelete', function () {
            const current = rowDataFromLink(this, oTableAssetModel);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['assetModelId'], modalAssetModelClass);
            }
        });

        $('#txtAtyAssetModelSearch').on('keyup change', function () {
            oTableAssetModel.search($(this).val()).draw();
        });

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
        lastTypeUpdated = new Date();
        oTableAssetType.clear().rows.add(assetTypeDataCache);
        applyGroupFilter();
        applyCategoryFilter();
        oTableAssetType.draw();
        updateTypeMetrics(assetTypeDataCache);
        refreshTypeListSummary();
        setStatusFilter(statusFilterValue, true);
        setGroupFilter(groupFilterValue, true);
        setCategoryFilter(categoryFilterValue, true);
    };

    this.addTableAty = function (_dataAdd) {
        oTableAssetType.row.add(_dataAdd).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdated = new Date();
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
        lastTypeUpdated = new Date();
        oTableAssetType.row(_rowEdit).data(currentRow).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
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
        lastModelUpdated = new Date();
        refreshModelSummary();
    };

    this.addTableAtyModel = function (_dataAdd) {
        oTableAssetModel.row.add(_dataAdd).draw();
        assetModelDataCache = oTableAssetModel.rows().data().toArray();
        lastModelUpdated = new Date();
        refreshModelSummary();
        const currentRow = oTableAssetType.row(rowIdModel).data();
        currentRow['totalModel'] = parseInt(currentRow['totalModel'] || 0, 10) + 1;
        oTableAssetType.row(rowIdModel).data(currentRow).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdated = new Date();
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
        lastModelUpdated = new Date();
        oTableAssetModel.row(_rowEdit).data(currentRow).draw();
        assetModelDataCache = oTableAssetModel.rows().data().toArray();
        refreshModelSummary();
    };

    this.deleteTableAtyModel = function () {
        self.genTableAtyModel(1, assetTypeId, rowIdModel);
        const currentRow = oTableAssetType.row(rowIdModel).data();
        currentRow['totalModel'] = Math.max(0, parseInt(currentRow['totalModel'] || 0, 10) - 1);
        oTableAssetType.row(rowIdModel).data(currentRow).draw();
        assetTypeDataCache = oTableAssetType.rows().data().toArray();
        lastTypeUpdated = new Date();
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
        if (oTableAssetType) {
            oTableAssetType.rows().invalidate();
            oTableAssetType.draw(false);
        }
        if (oTableAssetModel) {
            oTableAssetModel.rows().invalidate();
            oTableAssetModel.draw(false);
        }
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
        if ($('#optAtyGroupId').length) {
            populateGroupFilter();
        }
        if (oTableAssetType) {
            oTableAssetType.rows().invalidate();
            oTableAssetType.draw(false);
        }
    };

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
        if ($('#optAtyCategoryId').length) {
            populateCategoryFilter(groupFilterValue, categoryFilterValue);
        }
        if (oTableAssetType) {
            oTableAssetType.rows().invalidate();
            oTableAssetType.draw(false);
        }
    };

    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
        if (oTableAssetModel) {
            oTableAssetModel.rows().invalidate();
            oTableAssetModel.draw(false);
        }
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
