function MainPpmManagement() {

    const className = 'MainPpmManagement';
    let self = this;
    let modalConfirmDeleteClass;
    let refStatus;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let refLocationCode;
    let refUser;
    let refPpmGroup;
    let oTableAsset;
    let oTableScheduled;
    let sectionAssetClass;
    let contractId;
    let versionLocal;
    let modalPpmClass;
    let ppmIdSelected;
    let userSite;
    let userIsAdmin = false;

    this.init = function () {
        $('#divPmgScheduled').hide();
        userSite = mzGetUserInfoByParam('siteId');
        userIsAdmin = mzIsRoleExist('1,19');

        const filterSite = !mzIsRoleExist('1,10') ? {siteId: userSite} : {};
        mzOption('optPmgContractId', refContract, 'Choose Contract', 'contractId', 'contractName', filterSite, 'required');
        mzOption('optPmgGroupId', refAssetGroup, 'All Asset Group', 'assetGroupId', 'assetGroupName', {});

        for(let contract of refContract) {
            if (typeof contract !== 'undefined' && contract['contractId'] === '20') {
                contractId = contract['contractId'];
                $('#optPmgContractId').val(contractId);
                break;
            }
        }

        const updateAssetSummary = function () {
            if (!oTableAsset) {
                return;
            }
            try {
                const info = oTableAsset.page.info();
                $('#lblPmgAssetCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal);
                $('#lblPmgAssetUpdated').text(new Date().toLocaleString());
            } catch (err) {
                // noop
            }
        };

        const applyAssetDataLabels = function () {
            const headers = [];
            $('#dtPmgAsset thead th').each(function () {
                headers.push($(this).text().trim());
            });
            $('#dtPmgAsset tbody tr').each(function () {
                $('td', this).each(function (i) {
                    $(this).attr('data-label', headers[i] || '');
                });
            });
        };

        const applyScheduledDataLabels = function () {
            const headers = [];
            $('#dtPmgScheduled thead th').each(function () {
                headers.push($(this).text().trim());
            });
            $('#dtPmgScheduled tbody tr').each(function () {
                $('td', this).each(function (i) {
                    $(this).attr('data-label', headers[i] || '');
                });
            });
        };

        oTableAsset =  $('#dtPmgAsset').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [2, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableAsset.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPmgAssetView').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        sectionAssetClass.view(currentRow['assetId']);
                    }
                });
                $('.lnkPmgAssetPpmAssign').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        modalPpmClass.setSingle(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkPmgAssetDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        sectionAssetClass.deactivate(currentRow['assetId'], rowId);
                    }
                });
                $('.lnkPmgPpmListExpand').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAsset.row(parseInt(rowId)).data();
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                self.genTablePmgScheduled(currentRow['ppmId'], currentRow['assetNo']);
                                const elmnt = document.getElementById("divPmgScheduled");
                                elmnt.scrollIntoView();
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 300);
                    }
                });
                updateAssetSummary();
                applyAssetDataLabels();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: null, mRender: function (data, type, row){
                            return typeof row['assetName'] !== 'undefined' ? row['assetName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return typeof row['assetNo'] !== 'undefined' ? row['assetNo'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return typeof row['ppmTaskNo'] !== 'undefined' ? row['ppmTaskNo'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return typeof row['assetSerialNo'] !== 'undefined' ? row['assetSerialNo'] : '';
                        }}, // 4
                    {mData: null, mRender: function (data, type, row){
                            return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return typeof row['assetBrandId'] !== 'undefined' &&  row['assetBrandId'] !== '' ? refAssetBrand[row['assetBrandId']]['assetBrandName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return typeof row['assetModelId'] !== 'undefined' &&  row['assetModelId'] !== '' ? refAssetModel[row['assetModelId']]['assetModelName'] : '';
                        }}, // 9
                    {mData: null, mRender: function (data, type, row){
                            let ppmGroupId = row['ppmGroupIdPpm'];
                            if (ppmGroupId == '') {
                                ppmGroupId = row['ppmGroupId'];
                            }
                            return ppmGroupId !== '' ? refPpmGroup[ppmGroupId]['ppmGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['locationCodeId'] !== '' ? refLocationCode[row['locationCodeId']]['locationCodeName'] : '';
                        }},
                    {mData: null,
                        mRender: function (data, type, row) {
                            if (row['ppmStatus'] === '6') {
                                return '<h6><span class="badge badge-pill '+refStatus[row['ppmStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmStatus']]['statusDesc']+'</span></h6>';
                            }
                            return '<h6><span class="badge badge-pill '+refStatus[row['assignedStatus']]['statusColor']+' z-depth-2">'+refStatus[row['assignedStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<div class="action-btn-group">';
                            if (row['assignedStatus'] === '10') {
                                label += '<button type="button" class="btn-action btn-view lnkPmgPpmListExpand" id="lnkPmgPpmListExpand_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Scheduled PPM List"><i class="fas fa-list-ul"></i></button>';
                                // label += '<button type="button" class="btn-action btn-deactivate lnkPmgAssetDeactivate" id="lnkPmgAssetDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"><i class="fas fa-toggle-off"></i></button>';
                            }
                            if (userIsAdmin) {
                                label += '<button type="button" class="btn-action btn-edit lnkPmgAssetPpmAssign" id="lnkPmgAssetPpmAssign_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Assign PPM"><i class="fas fa-calendar-plus"></i></button>';
                            }
                            label += '<button type="button" class="btn-action btn-view lnkPmgAssetView" id="lnkPmgAssetView_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Asset Information"><i class="fas fa-qrcode"></i></button>';
                            label += '</div>';
                            return label;
                        }
                    },
                    {mData: 'assetId', visible: false}, // 14
                    {mData: 'assetGroupId', visible: false},
                    {mData: 'assetCategoryId', visible: false},
                    {mData: 'assetTypeId', visible: false},
                    {mData: 'assetBrandId', visible: false},
                    {mData: 'assetModelId', visible: false}, // 19
                    {mData: 'assignedStatus', visible: false}
                ]
        });
        $("#dtPmgAsset_filter").hide();
        $('#txtPmgAssetSearch').on('keyup change', function () {
            oTableAsset.search($(this).val()).draw();
        });
        const setActiveStatusChip = function (target) {
            $('#linkPmgAll, #linkPmg1, #linkPmg2').removeClass('active');
            $(target).addClass('active');
        };

        $('#linkPmgAll').on('click', function () {
            setActiveStatusChip(this);
            oTableAsset.column(20).search('').draw();
        });
        $('#linkPmg1').on('click', function () {
            setActiveStatusChip(this);
            oTableAsset.column(20).search('10', false, true, false).draw();
        });
        $('#linkPmg2').on('click', function () {
            setActiveStatusChip(this);
            oTableAsset.column(20).search('11', false, true, false).draw();
        });

        setActiveStatusChip('#linkPmgAll');

        $('#optPmgGroupId').on('change', function () {
            mzOptionStop('optPmgCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: $(this).val()});
            mzOptionStopClear('optPmgTypeId', 'All Asset Type');
            mzOptionStopClear('optPmgBrandId', 'All Asset Brand');
            mzOptionStopClear('optPmgModelId', 'All Asset Model');
            //mzOptionStop('optPmgTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: '0'});
            oTableAsset.column(15).search("^" + $(this).val() + "$", true, false, true);
            oTableAsset.column(16).search('');
            oTableAsset.column(17).search('');
            oTableAsset.column(18).search('');
            oTableAsset.column(19).search('').draw();
        });

        $('#optPmgCategoryId').on('change', function () {
            mzOptionStop('optPmgTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val()});
            mzOptionStopClear('optPmgBrandId', 'All Asset Brand');
            mzOptionStopClear('optPmgModelId', 'All Asset Model');
            oTableAsset.column(16).search($(this).val());
            oTableAsset.column(17).search('');
            oTableAsset.column(18).search('');
            oTableAsset.column(19).search('').draw();
        });

        $('#optPmgTypeId').on('change', function () {
            const refAssetBrandGroup = mzGetLocalArray('gems_assetBrandGroup', versionLocal, 'assetBrandId', {assetTypeId: $(this).val()});
            mzOptionStop('optPmgBrandId', refAssetBrandGroup, 'All Asset Brand', 'assetBrandId', 'assetBrandName');
            mzOptionStopClear('optPmgModelId', 'All Asset Model');
            oTableAsset.column(17).search("^" + $(this).val() + "$", true, false, true);
            oTableAsset.column(18).search('');
            oTableAsset.column(19).search('').draw();
        });

        $('#optPmgBrandId').on('change', function () {
            mzOptionStop('optPmgModelId', refAssetModel, 'All Asset Model', 'assetModelId', 'assetModelName', {assetBrandId: $(this).val(), assetTypeId: $('#optPmgTypeId').val()});
            oTableAsset.column(18).search("^" + $(this).val() + "$", true, false, true);
            oTableAsset.column(19).search('').draw();
        });

        $('#optPmgModelId').on('change', function () {
            oTableAsset.column(19).search("^" + $(this).val() + "$", true, false, true).draw();
        });

        let cntAsset;
        let btnAssetOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntAsset = 1;
                        }
                        if (column === 12) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntAsset++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAsset, {
            buttons: [
                $.extend( true, {}, btnAssetOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Asset List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Asset List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnAssetOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Asset List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtPmgAssetExport'));

        oTableAsset.column(1).visible(false);
        oTableAsset.column(4).visible(false);
        oTableAsset.column(8).visible(false);
        oTableAsset.column(9).visible(false);

        $('#optPmgColumns').on('change', function () {
            for (let i=1; i<=10; i++) {
                oTableAsset.column(i).visible(false);
            }
            const selectedColumns = $(this).val();
            $.each(selectedColumns, function (n, u) {
                oTableAsset.column(parseInt(u)).visible(true);
            });
        });

        updateAssetSummary();
        applyAssetDataLabels();

        $('#optPmgContractId').on('change', function () {
            $('#optPmgGroupId').val(null);
            mzOption('optPmgCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: '0'});
            mzOption('optPmgTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: '0'});
            oTableAsset.column(14).search('', false, true, false).draw();
            oTableAsset.column(15).search('', false, true, false).draw();
            oTableAsset.column(16).search('', false, true, false).draw();
            contractId = $(this).val();
            self.genTablePmg();
            self.displayStatsChart();
        });

        $('#btnPmgAssignBatch').on('click', function () {
            const assetTypeId = $('#optPmgTypeId').val();
            if (assetTypeId === '' || assetTypeId === null) {
                toastr['error']('To perform PPM Bulk Assign, please make sure Asset Type selected in Search section!', _ALERT_TITLE_ERROR);
            }
            modalPpmClass.setBulk(contractId, assetTypeId);
        });

        $('#btnDtPmgAssetRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePmg();
                    self.displayStatsChart();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableScheduled =  $('#dtPmgScheduled').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            "aaSorting": [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableScheduled.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPmgScheduledPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableScheduled.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfId'];
                                if (currentRow['pdfId'] === '') {
                                    pdfId = mzAjaxRequest('ppm.php', 'POST', {action: 'generate_pdf', ppmTaskId:currentRow['ppmTaskId']});
                                }
                                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Order Report: '+currentRow['ppmTaskNo']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
                applyScheduledDataLabels();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmTaskNo'},
                    {mData: 'ppmTaskScheduleDate'},
                    {mData: 'frequency'},
                    {mData: 'ppmTaskAssignedTo',
                        mRender: function (data) {
                            return data !== '' ? refUser[data]['userFullName'] : '';
                        }
                    },
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmTaskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmTaskStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<div class="action-btn-group">';
                            label += '<button type="button" class="btn-action btn-view lnkPmgScheduledPdf" id="lnkPmgScheduledPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Preventive Maintenance PDF"><i class="far fa-file-pdf"></i></button>';
                            label += '</div>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPmgScheduled_filter").hide();
        $('#txtPmgScheduledSearch').on('keyup change', function () {
            oTableScheduled.search($(this).val()).draw();
        });

        applyScheduledDataLabels();

        let cntScheduled;
        let btnScheduledOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntScheduled = 1;
                        }
                        if (column === 5) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntScheduled++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableScheduled, {
            buttons: [
                $.extend( true, {}, btnScheduledOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Scheduled PPM List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnScheduledOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Scheduled PPM List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnScheduledOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Scheduled PPM List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtPmgScheduledExport'));

        $('#btnDtPmgScheduledRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePmgScheduled(ppmIdSelected);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        self.genTablePmg();
        self.displayStatsChart();
    };

    this.genTablePmg = function () {
        const dataAsset = mzAjaxRequest('ppm.php?type=asset_with_ppm&contractId='+contractId, 'GET');
        oTableAsset.clear().rows.add(dataAsset).draw();
    };

    this.getTablePmgRow = function (_rowId) {
        return oTableAsset.row(parseInt(_rowId)).data();
    };

    this.updateTablePmg = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAsset.row(_rowEdit).data();
        if (typeof _dataEdit['action'] !== 'undefined') {
            if (_dataEdit['action'] === 'assign_ppm_single') {
                currentRow['checklistId'] = _dataEdit['checklistId'];
                currentRow['ppmDateStart'] = _dataEdit['ppmDateStart'];
                currentRow['ppmId'] = _dataEdit['ppmId'];
                currentRow['ppmTaskNo'] = _dataEdit['ppmTaskNo'];
                currentRow['ppmGroupId'] = _dataEdit['ppmGroupId'];
                currentRow['ppmStatus'] = _dataEdit['ppmStatus'];
                currentRow['assignedStatus'] = _dataEdit['assignedStatus'];
            }
        }
        oTableAsset.row(_rowEdit).data(currentRow).draw();
        self.displayStatsChart();
    };

    this.genTablePmgScheduled = function (_ppmId, _assetNo) {
        ppmIdSelected = _ppmId;
        const dataScheduled = mzAjaxRequest('ppm.php?type=scheduled_ppm&ppmId='+_ppmId, 'GET');
        oTableScheduled.clear().rows.add(dataScheduled).draw();

        if (typeof _assetNo !== 'undefined') {
            $('#lblPmgScheduledTitle').html(_assetNo);
        }
        $('#divPmgScheduled').show();
    };

    this.displayStatsChart = function () {
        let totalAll = 0;
        let total1 = 0;
        let total2 = 0;

        const tableData = oTableAsset.data();
        $.each(tableData, function (n, u) {
            switch (u['assignedStatus']) {
                case '10':
                    total1++;
                    totalAll++;
                    break;
                case '11':
                    total2++;
                    totalAll++;
                    break;
                default:
                    totalAll++;
            }
        });

    $('#linkPmgAll').html('<span>All Status</span><span class="badge-soft badge-soft-muted">'+mzFormatNumber(totalAll)+'</span>');
    $('#linkPmg1').html('<span>'+refStatus[10]['statusDesc']+'</span><span class="badge '+refStatus[10]['statusColor']+'">'+mzFormatNumber(total1)+'</span>');
    $('#linkPmg2').html('<span>'+refStatus[11]['statusDesc']+'</span><span class="badge '+refStatus[11]['statusColor']+'">'+mzFormatNumber(total2)+'</span>');

        const chartData = [
            {name:refStatus[10]['statusDesc'], y:total1},
            {name:refStatus[11]['statusDesc'], y:total2}
        ];

        $('#overlayChartPmg').show();

        if (typeof Highcharts === 'undefined' || typeof Highcharts.chart !== 'function') {
            $('#overlayChartPmg').hide();
            return;
        }

        Highcharts.chart('chartPmgAssetByStatus', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'Total PPM Status'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.y} ({point.percentage:.1f}%)</b>'
            },
            credits:{
                enabled:false
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.y}',
                        color: '#243746'
                    }
                }
            },
            series: [{
                name: 'Status',
                data: chartData
            }]
        });

        $('#overlayChartPmg').hide();
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

    this.setRefLocationCode = function (_refLocationCode) {
        refLocationCode = _refLocationCode;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setSectionAssetClass = function (_sectionAssetClass) {
        sectionAssetClass = _sectionAssetClass;
    };

    this.setModalPpmClass = function (_modalPpmClass) {
        modalPpmClass = _modalPpmClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };
}