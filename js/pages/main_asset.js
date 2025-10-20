function MainAsset() {

    const className = 'MainAsset';
    let self = this;
    let modalConfirmDeleteClass;
    let refStatus;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let refPpmGroup;
    let oTableAsset;
    let sectionAssetClass;
    let contractId = null;
    let userSite;
    let assetDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';

    const assetTableHeaders = ['#', 'Asset Name', 'Asset No', 'Asset Serial No', 'Asset Group', 'Asset Category', 'Asset Type', 'Brand', 'Model', 'PPM Group', 'Location Code', 'Status', 'Actions'];
    const statusChipMap = {
        '': '#linkAszAll',
        '1': '#linkAsz1',
        '2': '#linkAsz2',
        '5': '#linkAsz5'
    };

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // Ignore destroy errors for first-run initialisation.
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

    const refreshListSummary = function () {
        if (!oTableAsset) {
            return;
        }
        const info = oTableAsset.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblAszFilterCount').text(summaryText);
        $('#lblAszListCount').text(summaryText);
        $('#lblAszFilterUpdated').text(lastListUpdatedText);
        $('#lblAszListUpdated').text(lastListUpdatedText);
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

    const updateAssetMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        dataSet.forEach(function (item) {
            total += 1;
            switch (item['assetStatus']) {
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

        $('#metricAszTotal').text(mzFormatNumber(total, 0));
        $('#metricAszActive').text(mzFormatNumber(active, 0));
        $('#metricAszInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricAszArchived').text(mzFormatNumber(archived, 0)).toggleClass('text-danger', archived > 0);

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

    const setStatusFilter = function (value) {
        statusFilterValue = value || '';
        if (!oTableAsset) {
            return;
        }
        if (statusFilterValue === '') {
            oTableAsset.column(17).search('').draw();
        } else {
            oTableAsset.column(17).search(`^${statusFilterValue}$`, true, false, true).draw();
        }
        setActiveStatusChip(statusFilterValue);
        refreshListSummary();
    };

    const findContractById = function (id) {
        let match = null;
        if (!refContract) {
            return match;
        }
        $.each(refContract, function (idx, contract) {
            if (typeof contract === 'undefined' || contract === null) {
                return true;
            }
            if (String(contract['contractId']) === String(id)) {
                match = contract;
                return false;
            }
            return true;
        });
        return match;
    };

    const updateContractBadge = function () {
        const contract = findContractById(contractId);
        $('#lblAszContractName').text(contract ? contract['contractName'] : '—');
    };

    this.init = function () {
        userSite = mzGetUserInfoByParam('siteId');
        if (!mzIsRoleExist('1,19')) {
            $('#btnAszAssetAdd').hide();
        }

        const filterSite = !mzIsRoleExist('1,10') ? {siteId: userSite} : {};
        mzOption('optAszContractId', refContract, 'Choose Contract', 'contractId', 'contractName', filterSite, 'required');
        mzOption('optAszGroupId', refAssetGroup, 'All Asset Group', 'assetGroupId', 'assetGroupName', {});
        mzOptionStopClear('optAszCategoryId', 'All Asset Category');
        mzOptionStopClear('optAszTypeId', 'All Asset Type');

        initMaterialSelect('#optAszContractId');
        initMaterialSelect('#optAszGroupId');
        initMaterialSelect('#optAszCategoryId');
        initMaterialSelect('#optAszTypeId');
        initMaterialSelect('#optAszColumns');

        if (!contractId) {
            $.each(refContract, function (index, contract) {
                if (typeof contract === 'undefined' || contract === null) {
                    return true;
                }
                if (filterSite.siteId && String(contract['siteId']) !== String(filterSite.siteId)) {
                    return true;
                }
                contractId = contract['contractId'];
                $('#optAszContractId').val(contractId);
                return false;
            });
        }

        if (contractId) {
            initMaterialSelect('#optAszContractId');
        }
        updateContractBadge();

        oTableAsset = $('#dtAszAsset').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: 't',
            columnDefs: [
                {targets: [0], orderable: false, className: 'text-center'},
                {targets: [11], className: 'text-center'},
                {targets: [12], orderable: false, className: 'text-center'},
                {targets: [2, 3, 4, 5, 6, 7, 8, 9, 10], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAsset.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAszAssetEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAsset.row(parseInt(rowId, 10)).data();
                        sectionAssetClass.edit(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAsset.row(parseInt(rowId, 10)).data();
                        sectionAssetClass.deactivate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAsset.row(parseInt(rowId, 10)).data();
                        sectionAssetClass.activate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkAszAssetDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableAsset.row(parseInt(rowId, 10)).data();
                        modalConfirmDeleteClass.delete(currentRow['assetId'], sectionAssetClass);
                    }
                });
                applyTableDataLabels('#dtAszAsset', assetTableHeaders);
                refreshListSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: null, mRender: function (data, type, row) { return row['assetName'] || ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetNo'] || ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetSerialNo'] || ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetBrandId'] ? refAssetBrand[row['assetBrandId']]['assetBrandName'] : ''; }},
                {mData: null, mRender: function (data, type, row) { return row['assetModelId'] ? refAssetModel[row['assetModelId']]['assetModelName'] : ''; }},
                {mData: null, mRender: function (data, type, row) { return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : ''; }},
                {mData: 'assetLocationCode'},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['assetStatus'];
                        if (!status || typeof refStatus[status] === 'undefined') {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        let label = `<a><i class="fas fa-edit lnkAszAssetEdit" id="lnkAszAssetEdit_${meta.row}" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;`;
                        if (row['assetStatus'] === '1') {
                            label += `<a><i class="fas fa-toggle-off lnkAszAssetDeactivate" id="lnkAszAssetDeactivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>`;
                        } else if (row['assetStatus'] === '2') {
                            label += `<a><i class="fas fa-toggle-on lnkAszAssetActivate" id="lnkAszAssetActivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>`;
                        } else if (row['assetStatus'] === '5') {
                            label += `<a><i class="fas fa-trash-alt lnkAszAssetDelete" id="lnkAszAssetDelete_${meta.row}" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>`;
                        }
                        return label;
                    }},
                {mData: 'assetId', visible: false},
                {mData: 'assetGroupId', visible: false},
                {mData: 'assetCategoryId', visible: false},
                {mData: 'assetTypeId', visible: false},
                {mData: 'assetStatus', visible: false}
            ]
        });
        $('#dtAszAsset_filter').hide();

        $('#txtAszAssetSearch').on('keyup change', function () {
            oTableAsset.search($(this).val()).draw();
        });

        $('#optAszGroupId').on('change', function () {
            mzOptionStop('optAszCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: $(this).val()});
            mzOptionStopClear('optAszTypeId', 'All Asset Type');
            initMaterialSelect('#optAszCategoryId');
            initMaterialSelect('#optAszTypeId');
            oTableAsset.column(14).search(`^${$(this).val()}$`, true, false, true).draw();
            oTableAsset.column(15).search('', false, true, false).draw();
            oTableAsset.column(16).search('', false, true, false).draw();
        });

        $('#optAszCategoryId').on('change', function () {
            mzOptionStop('optAszTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val()});
            initMaterialSelect('#optAszTypeId');
            oTableAsset.column(15).search(`^${$(this).val()}$`, true, false, true).draw();
            oTableAsset.column(16).search('', false, true, false).draw();
        });

        $('#optAszTypeId').on('change', function () {
            oTableAsset.column(16).search(`^${$(this).val()}$`, true, false, true).draw();
        });

        const btnAssetOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                format: {
                    body: function (data, row, column) {
                        if (column === 11) {
                            const start = data.indexOf('">');
                            if (start >= 0) {
                                const trimmed = data.substr(start + 2);
                                return trimmed.replace('</span></h6>', '');
                            }
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAsset, {
            buttons: [
                $.extend(true, {}, btnAssetOpt, { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - Asset List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetOpt, { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - Asset List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2' }),
                $.extend(true, {}, btnAssetOpt, { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - Asset List', titleAttr: 'PDF', orientation: 'landscape', className: 'btn btn-outline-white btn-rounded btn-sm px-2' })
            ]
        }).container().appendTo($('#btnDtAszAssetExport'));

        oTableAsset.column(1).visible(false);
        oTableAsset.column(3).visible(false);
        oTableAsset.column(7).visible(false);
        oTableAsset.column(8).visible(false);

        $('#optAszColumns').on('change', function () {
            for (let i = 1; i <= 10; i++) {
                oTableAsset.column(i).visible(false);
            }
            const selectedColumns = $(this).val() || [];
            $.each(selectedColumns, function (n, u) {
                oTableAsset.column(parseInt(u, 10)).visible(true);
            });
        });

        $('#optAszContractId').on('change', function () {
            contractId = $(this).val();
            updateContractBadge();
            setStatusFilter('');
            ShowLoader();
            setTimeout(function () {
                try {
                    $('#optAszGroupId').val(null);
                    initMaterialSelect('#optAszGroupId');
                    mzOptionStopClear('optAszCategoryId', 'All Asset Category');
                    mzOptionStopClear('optAszTypeId', 'All Asset Type');
                    initMaterialSelect('#optAszCategoryId');
                    initMaterialSelect('#optAszTypeId');
                    oTableAsset.column(14).search('', false, true, false).draw();
                    oTableAsset.column(15).search('', false, true, false).draw();
                    oTableAsset.column(16).search('', false, true, false).draw();
                    self.genTableAsz();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnAszAssetAdd').on('click', function () {
            sectionAssetClass.add(contractId, $('#optAszGroupId').val(), $('#optAszCategoryId').val(), $('#optAszTypeId').val());
        });

        $('#btnDtAszAssetRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAsz();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status);
            });
        });
        setStatusFilter('');

        self.genTableAsz();
    };

    this.genTableAsz = function () {
        if (!contractId) {
            oTableAsset.clear().draw();
            assetDataCache = [];
            updateAssetMetrics(assetDataCache);
            lastListUpdatedText = '—';
            refreshListSummary();
            self.displayChart();
            return;
        }
        const dataAsset = mzAjaxRequest('asset.php?contractId=' + contractId, 'GET');
        assetDataCache = Array.isArray(dataAsset) ? dataAsset : [];
        oTableAsset.clear().rows.add(assetDataCache).draw();
        lastListUpdatedText = getNowStamp();
        updateAssetMetrics(assetDataCache);
        refreshListSummary();
        self.displayChart();
    };

    this.displayStats = function () {
        const tableData = oTableAsset ? oTableAsset.rows().data().toArray() : [];
        updateAssetMetrics(tableData);
        refreshListSummary();
    };

    this.displayChart = function () {
        if (typeof Highcharts === 'undefined') {
            return;
        }
        const chartData = [];
        const chartDrilldown = [];
        const drilldowns = {};
        const assetGroups = {};

        assetDataCache.forEach(function (item) {
            if (item['assetStatus'] === '1' && item['assetGroupId'] !== '' && item['assetCategoryId'] !== '' && item['assetTypeId'] !== '') {
                const assetGroupId = item['assetGroupId'];
                const assetCategoryId = item['assetCategoryId'];
                const assetTypeId = item['assetTypeId'];
                const assetGroupName = refAssetGroup[assetGroupId] ? refAssetGroup[assetGroupId]['assetGroupName'] : 'Unknown Group';
                const assetCategoryName = refAssetCategory[assetCategoryId] ? refAssetCategory[assetCategoryId]['assetCategoryName'] : 'Unknown Category';
                const assetTypeName = refAssetType[assetTypeId] ? refAssetType[assetTypeId]['assetTypeName'] : 'Unknown Type';

                if (assetGroups['group' + assetGroupId]) {
                    assetGroups['group' + assetGroupId]['y'] += 1;
                } else {
                    assetGroups['group' + assetGroupId] = {name: assetGroupName, y: 1, drilldown: 'group' + assetGroupId};
                }

                if (!drilldowns['group' + assetGroupId]) {
                    drilldowns['group' + assetGroupId] = {name: 'Asset Category', data: [], datas: {}, id: 'group' + assetGroupId};
                }
                if (drilldowns['group' + assetGroupId]['datas']['category' + assetCategoryId]) {
                    drilldowns['group' + assetGroupId]['datas']['category' + assetCategoryId]['y'] += 1;
                } else {
                    drilldowns['group' + assetGroupId]['datas']['category' + assetCategoryId] = {name: assetCategoryName, y: 1, drilldown: 'category' + assetCategoryId};
                }

                if (!drilldowns['category' + assetCategoryId]) {
                    drilldowns['category' + assetCategoryId] = {name: 'Asset Type', data: [], datas: {}, id: 'category' + assetCategoryId};
                }
                if (drilldowns['category' + assetCategoryId]['datas']['type' + assetTypeId]) {
                    drilldowns['category' + assetCategoryId]['datas']['type' + assetTypeId]['y'] += 1;
                } else {
                    drilldowns['category' + assetCategoryId]['datas']['type' + assetTypeId] = {name: assetTypeName, y: 1};
                }
            }
        });

        $.each(assetGroups, function (key, value) {
            chartData.push(value);
        });

        $.each(drilldowns, function (key, value) {
            const temp = [];
            $.each(value['datas'], function (key2, value2) {
                temp.push(value2);
            });
            value['data'] = temp;
            delete value['datas'];
            chartDrilldown.push(value);
        });

        Highcharts.chart('chartAszAssetByType', {
            chart: { type: 'pie' },
            title: { text: 'Total Asset by Category' },
            tooltip: { pointFormat: '{series.name}: <b>{point.y} ({point.percentage:.1f}%)</b>' },
            credits: { enabled: false },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: { enabled: true, format: '<b>{point.name}</b>: {point.y}', color: 'white' }
                }
            },
            series: [{ name: 'Asset Group', data: chartData }],
            drilldown: { series: chartDrilldown }
        });
    };

    this.addTableAsz = function (_dataAdd) {
        oTableAsset.row.add(_dataAdd).draw();
        assetDataCache = oTableAsset.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        self.displayStats();
        self.displayChart();
    };

    this.updateTableAsz = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAsset.row(_rowEdit).data();
        if (typeof _dataEdit['action'] !== 'undefined') {
            if (_dataEdit['action'] === 'save' || _dataEdit['action'] === 'submit') {
                currentRow['assetName'] = _dataEdit['assetName'];
                currentRow['assetNo'] = _dataEdit['assetNo'];
                currentRow['assetSerialNo'] = _dataEdit['assetSerialNo'];
                currentRow['assetGroupId'] = _dataEdit['assetGroupId'];
                currentRow['assetCategoryId'] = _dataEdit['assetCategoryId'];
                currentRow['assetTypeId'] = _dataEdit['assetTypeId'];
                currentRow['assetBrandId'] = _dataEdit['assetBrandId'];
                currentRow['assetModelId'] = _dataEdit['assetModelId'];
                currentRow['assetLocationCode'] = _dataEdit['assetLocationCode'];
                currentRow['assetLocationDesc'] = _dataEdit['assetLocationDesc'];
                currentRow['ppmGroupId'] = _dataEdit['ppmGroupId'];
                currentRow['assetCapacity'] = _dataEdit['assetCapacity'];
            }
            if (_dataEdit['action'] === 'submit') {
                currentRow['assetStatus'] = '1';
            }
            if (_dataEdit['action'] === 'update') {
                currentRow['assetName'] = _dataEdit['assetName'];
                currentRow['assetNo'] = _dataEdit['assetNo'];
                currentRow['assetSerialNo'] = _dataEdit['assetSerialNo'];
                currentRow['assetLocationCode'] = _dataEdit['assetLocationCode'];
                currentRow['assetLocationDesc'] = _dataEdit['assetLocationDesc'];
                currentRow['ppmGroupId'] = _dataEdit['ppmGroupId'];
                currentRow['assetCapacity'] = _dataEdit['assetCapacity'];
            }
        }
        if (typeof _dataEdit['assetStatus'] !== 'undefined') {
            currentRow['assetStatus'] = _dataEdit['assetStatus'];
        }
        oTableAsset.row(_rowEdit).data(currentRow).draw();
        assetDataCache = oTableAsset.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        self.displayStats();
        self.displayChart();
    };

    this.deleteTableAsz = function () {
        self.genTableAsz();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };
    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };
    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };
    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };
    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
    };
    this.setRefAssetModel = function (_refAssetModel) {
        refAssetModel = _refAssetModel;
    };
    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
    this.setSectionAssetClass = function (_sectionAssetClass) {
        sectionAssetClass = _sectionAssetClass;
    };
    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}