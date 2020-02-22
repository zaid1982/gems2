function MainHome() {

    const className = 'MainHome';
    let self = this;
    let modalConfirmDeleteClass;
    let refClient;
    let refSite;
    let refContract;
    let refUser;
    let refPpmGroup;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refStatus;
    let userClient;
    let clientId = '1';
    let siteId = '0';
    let currentMonth;
    let currentYear;
    let reportId = '2';
    let reportType = 'Work Order';
    let oTableWo;
    let oTablePpm;
    let totalPpm = 0;
    let totalLate = 0;
    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    this.init = function () {
        $('.divHmeTopStats_ppm, #divHmeTable_ppm').hide();
        userClient = mzGetUserInfoByParam('clientId');
        refSite[0] = {clientId:'1', siteDesc:'Overall'};
        $('#lnkHmeReportType_2').addClass('active').addClass('text-white');

        $.each(refClient, function (_clientId, _client) {
            if (typeof _client !== 'undefined') {
                if (mzIsRoleExist('1,10')) {
                    $('#divHmeClient').append('<a class="dropdown-item lnkHmeClient" href="#" id="lnkHmeClient_'+_clientId+'">'+_client['clientName']+'</a>');
                }
                else if (userClient == _clientId) {
                    clientId = userClient;
                    refSite[0] = {clientId:clientId, siteDesc:'Overall'};
                    $('#divHmeClient').append('<a class="dropdown-item lnkHmeClient" href="#" id="lnkHmeClient_'+_clientId+'">'+_client['clientName']+'</a>');
                    return false;
                }
            }
        });
        $('#lnkHmeClient_'+clientId).addClass('active').addClass('text-white');

        $('.lnkHmeClient').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        $('#lnkHmeClient_'+clientId).removeClass('active').removeClass('text-white');
                        clientId = linkId.substr(linkIndex + 1);
                        $('#lnkHmeClient_'+clientId).addClass('active').addClass('text-white');
                        $('#lnkHmeSite_'+siteId).removeClass('active').removeClass('text-white');
                        siteId = '0';
                        $('#lnkHmeSite_'+siteId).addClass('active').addClass('text-white');
                        self.setOptionSite();
                        self.runChart();}
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        let dateEarliest = new Date();
        dateEarliest.setFullYear(2019, 2, 1);
        let dateCtr = new Date();
        currentMonth = dateCtr.getMonth();
        currentYear = dateCtr.getFullYear();
        dateCtr.setDate(1);
        for (let i = 0; i < 100; i++) {
            if (dateCtr < dateEarliest) {
                break;
            }
            const isActive = (dateCtr.getMonth() === currentMonth && dateCtr.getFullYear() === currentYear) ? 'active text-white' : '';
            $('#divHmeMonth').append('<a class="dropdown-item lnkHmeMonth '+isActive+'" href="#" id="lnkHmeMonth_'+dateCtr.getFullYear()+dateCtr.getMonth()+'">'+monthFull[dateCtr.getMonth()] + ' ' + dateCtr.getFullYear() + '</a>');
            dateCtr.setMonth(dateCtr.getMonth() - 1);
        }

        $('.lnkHmeMonth').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        const year = linkId.substr(linkIndex + 1, 4);
                        const month = linkId.substr(linkIndex + 1 + 4);
                        $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('active').removeClass('text-white');
                        $('#lnkHmeMonth_'+year+month).addClass('active').addClass('text-white');
                        currentMonth = month;
                        currentYear = year;
                        self.runChart();
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('.lnkHmeReportType').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        $('#lnkHmeReportType_'+reportId).removeClass('active').removeClass('text-white');
                        reportId = linkId.substr(linkIndex + 1);
                        $('#lnkHmeReportType_'+reportId).addClass('active').addClass('text-white');
                        if (reportId === '1') {
                            reportType = 'PPM';
                        } else if (reportId === '2') {
                            reportType = 'Work Order';
                        }
                        self.runChart();
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.setOptionSite();

        oTableWo =  $('#dtHmeDataWo').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'desc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableWo.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkHmeDataWoDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWo.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['woTaskId'], self);
                    }
                });
                $('.lnkHmeDataWoPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableWo.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfId'];
                                //if (currentRow['pdfId'] === '') {
                                    const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf', woTaskId:currentRow['woTaskId']});
                                    pdfId = resultRequest['pdfId'];
                                //}
                                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Order Report: '+currentRow['woTaskNo']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
                $('.lnkHmeDataWoPdfWr').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableWo.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfIdWr'];
                                //if (currentRow['pdfId'] === '') {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf_wr', woTaskId:currentRow['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                                //}
                                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Request Report: '+currentRow['woTaskNo']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'woTaskTimeCreated', mRender: function (data){
                            return data.substr(0, 10);
                        }},
                    {mData: 'woTaskRequestNo'},
                    {mData: 'woTaskNo'},
                    {mData: null, mRender: function (data, type, row){
                            return refSite[row['siteId']]['siteDesc'];
                        }},
                    {mData: 'woTaskLocation'},
                    {mData: 'woTaskTypeDesc'},
                    {mData: null, mRender: function (data, type, row){
                            return refUser[row['woTaskCreatedBy']]['userFirstName'];
                        }},
                    {mData: 'woTaskComplaint'},
                    {mData: 'woTaskSeverity'},
                    {mData: null, mRender: function (data, type, row){
                            return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['woTaskAssignedTo'] !== '' ? refUser[row['woTaskAssignedTo']]['userFirstName'] : '';
                        }},
                    {mData: 'woTaskRepairDesc'},
                    {mData: 'woTaskRate'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['woTaskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['woTaskStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '';
                            if (mzIsRoleExist('1')) {
                                label += '&nbsp;<a><i class="fas fa-trash-alt lnkHmeDataWoDelete" id="lnkHmeDataWoDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>&nbsp;';
                            }
                            if (row['woTaskIsWr'] === '1') {
                                label += '&nbsp;<a><i class="fas fa-file-signature lnkHmeDataWoPdfWr" id="lnkHmeDataWoPdfWr_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Work Request PDF"></i></a>&nbsp;';
                            }
                            if (row['woTaskIsWr'] !== '1' || row['woTaskTimeWrVerified'] !== '') {
                                label += '&nbsp;<a><i class="far fa-file-pdf lnkHmeDataWoPdf" id="lnkHmeDataWoPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Work Order PDF"></i></a>&nbsp;';
                            }
                            return label;
                        }
                    },
                    {mData: 'siteId', visible: false},
                    {mData: 'ppmGroupId', visible: false},
                    {mData: 'woTaskStatus', visible: false},
                    {mData: 'woTaskType', visible: false},
                    {mData: 'woTaskId', visible: false},
                    {mData: null, visible: false, mRender: function (data, type, row){
                            return row['woTaskFixedBy'] !== '' ? refUser[row['woTaskFixedBy']]['userFirstName'] : '';
                        }},
                    {mData: 'woTaskAssignedBy', visible: false, mRender: function (data, type, row){
                            return row['woTaskAssignedBy'] !== '' ? refUser[row['woTaskAssignedBy']]['userFirstName'] : '';
                        }},
                    {mData: 'woTaskVerifiedBy', visible: false, mRender: function (data, type, row){
                            return row['woTaskVerifiedBy'] !== '' ? refUser[row['woTaskVerifiedBy']]['userFirstName'] : '';
                        }},
                    {mData: 'woTaskTimeCreated', visible: false},
                    {mData: 'woTaskTimeResponded', visible: false},
                    {mData: 'woTaskTimeAssigned', visible: false},
                    {mData: 'woTaskTimeExecuted', visible: false},
                    {mData: 'woTaskTimeVerified', visible: false},
                    {mData: 'woTaskFixedBy', visible: false}
                ]
        });
        $("#dtHmeDataWo_filter").hide();
        $('#txtHmeDataWoSearch').on('keyup change', function () {
            oTableWo.search($(this).val()).draw();
        });

        oTableWo.column(4).visible(false);
        oTableWo.column(11).visible(false);
        oTableWo.column(12).visible(false);

        $('#optHmeDataWoColumns').on('change', function () {
            for (let i=1; i<=14; i++) {
                oTableWo.column(i).visible(false);
            }
            const selectedColumns = $(this).val();
            $.each(selectedColumns, function (n, u) {
                oTableWo.column(parseInt(u)).visible(true);
            });
        });

        let cntWo;
        let btnWoOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 20, 21, 22, 23, 24, 25, 26, 27, 28],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntWo = 1;
                        }
                        if (column === 14) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntWo++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableWo, {
            buttons: [
                $.extend( true, {}, btnWoOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Work Order List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Work Order List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtHmeDataWoExport'));

        $('#btnDtHmeDataWoRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableHmeDataWo();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTablePpm =  $('#dtHmeDataPpm').DataTable({
            bLengthChange: false,
            bFilter: true,
            //"aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTablePpm.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkHmeDataPpmPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTablePpm.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfId'];
                                //if (currentRow['pdfId'] === '') {
                                    pdfId = mzAjaxRequest('ppm.php', 'POST', {action: 'generate_pdf', ppmTaskId:currentRow['ppmTaskId']});
                                //}
                                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;PPM Report: '+currentRow['ppmTaskNo']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmTaskNo'},
                    {mData: null, mRender: function (data, type, row){
                            return refSite[row['siteId']]['siteDesc'];
                        }},
                    {mData: 'ppmTaskStartDate'},
                    {mData: 'frequency'},
                    {mData: 'documentNo'},
                    {mData: 'assetNo'},
                    {mData: 'assetName'},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                        }},
                    {mData: 'assetLocationCode'},
                    {mData: 'assetLocationDesc'},
                    {mData: 'assetBlock'},
                    {mData: 'assetLevel'},
                    {mData: 'ppmTaskRemark'},
                    {mData: null, mRender: function (data, type, row){
                            return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['executor'] !== '' ? refUser[row['executor']]['userFirstName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['reviewer'] !== '' ? refUser[row['reviewer']]['userFirstName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['verifier'] !== '' ? refUser[row['verifier']]['userFirstName'] : '';
                        }},
                    {mData: 'ppmTaskTimeStart'},
                    {mData: 'ppmTaskTimeServiced'},
                    {mData: 'ppmTaskTimeChecked'},
                    {mData: 'ppmTaskTimeVerified'},
                    {mData: 'lateness'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmTaskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmTaskStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            return '<a><i class="far fa-file-pdf lnkHmeDataPpmPdf" id="lnkHmeDataPpmPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="PPM PDF"></i></a>';
                        }
                    },
                    {mData: 'siteId', visible: false},
                    {mData: 'ppmGroupId', visible: false},
                    {mData: 'assetGroupId', visible: false},
                    {mData: 'ppmTaskStatus', visible: false},
                    {mData: 'pdfId', visible: false},
                    {mData: 'ppmTaskServicedBy', visible: false}
                ]
        });
        $("#dtHmeDataPpm_filter").hide();
        $('#txtHmeDataPpmSearch').on('keyup change', function () {
            oTablePpm.search($(this).val()).draw();
        });

        oTablePpm.column(5).visible(false);
        oTablePpm.column(7).visible(false);
        oTablePpm.column(11).visible(false);
        oTablePpm.column(12).visible(false);
        oTablePpm.column(13).visible(false);
        oTablePpm.column(14).visible(false);
        oTablePpm.column(15).visible(false);
        oTablePpm.column(16).visible(false);
        oTablePpm.column(18).visible(false);
        oTablePpm.column(19).visible(false);
        oTablePpm.column(20).visible(false);
        oTablePpm.column(21).visible(false);
        oTablePpm.column(22).visible(false);
        oTablePpm.column(23).visible(false);
        oTablePpm.column(24).visible(false);

        $('#optHmeDataPpmColumns').on('change', function () {
            for (let i=1; i<=25; i++) {
                oTablePpm.column(i).visible(false);
            }
            const selectedColumns = $(this).val();
            $.each(selectedColumns, function (n, u) {
                oTablePpm.column(parseInt(u)).visible(true);
            });
        });

        let cntPpm;
        let btnPpmOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntPpm = 1;
                        }
                        if (column === 25) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntPpm++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTablePpm, {
            buttons: [
                $.extend( true, {}, btnPpmOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - PPM List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnPpmOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - PPM List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtHmeDataPpmExport'));

        $('#btnDtHmeDataPpmRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableHmeDataPpm();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        self.runChart();
    };

    this.setOptionSite = function () {
        siteId = '0';
        $('#divHmeSite').html('');
        $.each(refSite, function (_siteId, _site) {
            if (typeof _site !== 'undefined') {
                if (_site['clientId'] === clientId) {
                    $('#divHmeSite').append('<a class="dropdown-item lnkHmeSite" href="#" id="lnkHmeSite_'+_siteId+'">'+_site['siteDesc']+'</a>');
                }
            }
        });
        $('#lnkHmeSite_'+siteId).addClass('active').addClass('text-white');

        $('.lnkHmeSite').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        $('#lnkHmeSite_'+siteId).removeClass('active').removeClass('text-white');
                        siteId = linkId.substr(linkIndex + 1);
                        $('#lnkHmeSite_'+siteId).addClass('active').addClass('text-white');
                        self.runChart();
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.runChart = function () {
        if (reportId === '1') {
            $('.divHmeTopStats_ppm, #divHmeTable_ppm').show();
            $('.divHmeTopStats_wo, #divHmeTable_wo').hide();
            self.generateTotalAsset();
            self.generateTotalPpmTask();
            self.generateTotalPpmLate();
            self.generatePercPpmDone();
            self.generateChartPpmBySite();
            self.generateChartPpmByTrade();
            self.generateChartPpmTop5Execute();
            self.generateChartPpmBottom5Execute();
            self.generateChartPpmAverageExecuteByTrade();
            self.genTableHmeDataPpm();
        } else if (reportId === '2') {
            $('.divHmeTopStats_ppm, #divHmeTable_ppm').hide();
            $('.divHmeTopStats_wo, #divHmeTable_wo').show();
            self.generateChartWoBySite();
            self.generateChartWoByCategory();
            self.generateChartWoByTrade();
            self.generateChartWoAverageExecuteByTrade();
            self.generateChartWoTop5Execute();
            self.generateChartWoBottom5Execute();
            self.genTableHmeDataWo();
        }
        $('#lblHmeSelected').html(reportType+' <i>('+refClient[clientId]['clientName']+' - '+refSite[siteId]['siteDesc']+', '+monthFull[currentMonth]+' '+currentYear+')</i>');
    };

    this.genTableHmeDataWo = function () {
        const dataWo = mzAjaxRequest('wo.php?type=dashboard_list&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        oTableWo.clear().rows.add(dataWo).draw();
    };

    this.genTableHmeDataPpm = function () {
        const dataPpm = mzAjaxRequest('ppm.php?type=dashboard_list&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        oTablePpm.clear().rows.add(dataPpm).draw();
    };

    this.generateTotalAsset = function () {
        $.ajax({
            url: 'api/asset.php?type=total_asset&clientId='+clientId+'&siteId='+siteId,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    $('#lblHmeTotalAsset').html(mzFormatNumber(resp.result));
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateTotalPpmTask = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_ppm_task&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    $('#lblHmeTotalPpm').html(mzFormatNumber(resp.result));
                    $('#lblHmeTotalPpmTitle').html('Total PPM <br/><strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
                    totalPpm = parseInt(resp.result);
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateTotalPpmLate = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_ppm_late&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    $('#lblHmeTotalPpmLate').html(mzFormatNumber(resp.result));
                    $('#lblHmeTotalPpmLateTitle').html('Total Late PPM <br/><strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
                    totalLate = parseInt(resp.result);
                    self.generateChartPpmByLateness();
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generatePercPpmDone = function () {
        $.ajax({
            url: 'api/ppm.php?type=perc_ppm_done&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    const percPpmDone = resp.result;
                    const percDone = mzFormatNumber(percPpmDone,2)+'%';
                    $('#lblHmePercPpmDone').html(percDone);
                    $('#divBarHmePercAttendance').css('width', percDone);
                    $('#lblHmePercPpmDoneTitle').html('PPM done for <strong>'+monthFull[currentMonth]+' '+currentYear+'</strong>');
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoBySite = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_site_status&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    let siteDescs = [];
                    let siteIds = resp.result.categories;
                    siteIds.forEach(function(key){
                        siteDescs.push(refSite[key]['siteDesc']);
                    });
                    Highcharts.chart('chartHme1', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'WO By Site'
                        },
                        subtitle: {
                            text: 'Total Work Order Status by Site'
                        },
                        xAxis: {
                            categories: siteDescs,
                            title: {
                                text: ''
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total Work Order'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function(event) {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(16).search(siteIds[siteDescs.indexOf(this.category)], false, true, false);
                                            oTableWo.column(18).search(event.point.series.userOptions.woTaskStatus, true, false).draw();
                                        }
                                    }
                                }
                            }
                            /*series: {
                                events: {
                                    legendItemClick: function () {
                                        const visibility = this.visible ? 'visible' : 'hidden';
                                        if (!confirm('The series is currently ' +
                                            visibility + '. Do you want to change that?')) {
                                            return false;
                                        }
                                    }
                                }
                            }*/
                        },
                        /*legend: {
                            layout: 'vertical',
                            align: 'right',
                            verticalAlign: 'top',
                            x: -10,
                            y: 40,
                            floating: true,
                            borderWidth: 1,
                            backgroundColor:
                                Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
                            shadow: true
                        },*/
                        credits: {
                            enabled: false
                        },
                        series: resp.result.series
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByCategory = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_site_type&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    let siteDescs = [];
                    let siteIds = resp.result.categories;
                    siteIds.forEach(function(key){
                        siteDescs.push(refSite[key]['siteDesc']);
                    });
                    Highcharts.chart('chartHme2', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'WO By Category'
                        },
                        subtitle: {
                            text: 'Total Work Order by Category'
                        },
                        xAxis: {
                            categories: siteDescs,
                            crosshair: true
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total Work Order'
                            }
                        },
                        tooltip: {
                        },
                        /*tooltip: {
                            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                                '<td style="padding:0"><b>{point.y}</b></td></tr>',
                            footerFormat: '</table>',
                            shared: true,
                            useHTML: true
                        },*/
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderWidth: 0,
                                borderRadius: 3
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function(event) {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(16).search(siteIds[siteDescs.indexOf(this.category)], false, true, false);
                                            oTableWo.column(19).search(event.point.series.userOptions.woTaskType, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: resp.result.series
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByType = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_type&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme4', {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Work Order Type'
                        },
                        subtitle: {
                            text: 'Total Work Order by Type'
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    formatter: function() {
                                        if (this.y !== 0) {
                                            return this.y;
                                        } else {
                                            return null;
                                        }
                                    },
                                    //format: '<b>{point.name}</b><br>{point.y}',
                                    distance: -20
                                },
                                showInLegend: true,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(19).search(this.woTaskType, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            colorByPoint: true,
                            innerSize: '30%',
                            data: resp.result
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByProgress = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_status&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme5', {
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'Work Order Progress'
                        },
                        subtitle: {
                            text: 'Total Work Order by Current Progress'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total WO',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(18).search(this.woTaskStatus, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data,
                            color: '#f45b5b'
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoByTrade = function () {
        $.ajax({
            url: 'api/wo.php?type=total_by_group&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme3', {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Trade'
                        },
                        subtitle: {
                            text: 'Total Work Order by Trade'
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    formatter: function() {
                                        if (this.y !== 0) {
                                            return this.y;
                                        } else {
                                            return null;
                                        }
                                    },
                                    //format: '<b>{point.name}</b><br>{point.y}',
                                    distance: -20
                                },
                                showInLegend: true,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(17).search(this.ppmGroupId, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            colorByPoint: true,
                            data: resp.result
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoAverageExecuteByTrade = function () {
        $.ajax({
            url: 'api/wo.php?type=average_execute_by_trade&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme4', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'Execution Time'
                        },
                        subtitle: {
                            text: 'Average Execution Time by Trade'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Average WO Execution Time (minutes)'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.display} ({point.y:.2f} minutes)</b>'
                        },
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.display}</b>',
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(10).search(this.ppmGroupName, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Average Time',
                            data: resp.result.data,
                            color: '#00b8d4'
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoTop5Execute = function () {
        $.ajax({
            url: 'api/wo.php?type=top5_execute&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme5', {
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'Top 5 Executor'
                        },
                        subtitle: {
                            text: 'Total Work Order Executed'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total WO Executed',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(29).search(this.woTaskFixedBy, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartWoBottom5Execute = function () {
        $.ajax({
            url: 'api/wo.php?type=bottom5_execute&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme6', {
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'Bottom 5 Executor'
                        },
                        subtitle: {
                            text: 'Total Work Order Executed'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total WO Executed',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                    //format: '<b>{point.woTaskFixedBy}</b>',
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTableWo.search('').columns().search('').draw();
                                            oTableWo.column(29).search(this.woTaskFixedBy, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmBySite = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_by_site_status&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    let siteDescs = [];
                    let siteIds = resp.result.categories;
                    siteIds.forEach(function(key){
                        siteDescs.push(refSite[key]['siteDesc']);
                    });
                    Highcharts.chart('chartHme1', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'PPM By Site'
                        },
                        subtitle: {
                            text: 'Total PPM Status by Site'
                        },
                        xAxis: {
                            categories: siteDescs,
                            title: {
                                text: ''
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total PPM'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function (event) {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(27).search(siteIds[siteDescs.indexOf(this.category)], false, true, false);
                                            oTablePpm.column(30).search(event.point.series.userOptions.ppmTaskStatus, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: resp.result.series
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmByTrade = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_by_site_trade&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    let siteDescs = [];
                    let siteIds = resp.result.categories;
                    siteIds.forEach(function(key){
                        siteDescs.push(refSite[key]['siteDesc']);
                    });
                    Highcharts.chart('chartHme2', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'PPM By Trade'
                        },
                        subtitle: {
                            text: 'Total PPM by Trade'
                        },
                        xAxis: {
                            categories: siteDescs,
                            crosshair: true
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total PPM'
                            }
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderWidth: 0,
                                borderRadius: 3
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function(event) {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(27).search(siteIds[siteDescs.indexOf(this.category)], false, true, false);
                                            oTablePpm.column(29).search(event.point.series.userOptions.assetGroupId, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: resp.result.series
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmByTradePerSite = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_by_trade&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme3', {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'PPM Trade'
                        },
                        subtitle: {
                            text: 'Total PPM by Trade'
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    formatter: function() {
                                        if (this.y !== 0) {
                                            return this.y;
                                        } else {
                                            return null;
                                        }
                                    },
                                    distance: -20
                                },
                                showInLegend: true,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(29).search(this.assetGroupId, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            colorByPoint: true,
                            innerSize: '30%',
                            data: resp.result
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmByProgress = function () {
        $.ajax({
            url: 'api/ppm.php?type=total_by_status&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme4', {
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'PPM Progress'
                        },
                        subtitle: {
                            text: 'Total PPM by Current Progress'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total PPM',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(30).search(this.ppmTaskStatus, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data,
                            color: '#f45b5b'
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmByLateness = function () {
        if (totalPpm >= totalLate) {
            Highcharts.chart('chartHme3', {
                chart: {
                    type: 'pie'
                },
                title: {
                    text: 'PPM Lateness'
                },
                subtitle: {
                    text: 'Total PPM Execution by Lateness Status'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y} - {point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            formatter: function () {
                                if (this.y !== 0) {
                                    return this.y;
                                } else {
                                    return null;
                                }
                            },
                            //format: '<b>{point.name}</b><br>{point.y}',
                            distance: -20
                        },
                        showInLegend: true,
                        borderWidth: 0
                    },
                    series: {
                        point: {
                            events: {
                                click: function () {
                                    oTablePpm.search('').columns().search('').draw();
                                    oTablePpm.column(24).search(this.name, true, false).draw();
                                }
                            }
                        }
                    }
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Total',
                    colorByPoint: true,
                    data: [
                        {
                            name: 'Late',
                            y: totalLate,
                            sliced: true,
                            selected: true,
                            color: '#f45b5b'
                        }, {
                            name: 'On-time',
                            y: totalPpm - totalLate
                        }]
                }]
            });
        }
    };

    this.generateChartPpmTop5Execute = function () {
        $.ajax({
            url: 'api/ppm.php?type=top5_execute&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme5', {
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'Top 5 Executor'
                        },
                        subtitle: {
                            text: 'Total PPM Executed'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total PPM Executed',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(32).search(this.ppmTaskServicedBy, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmBottom5Execute = function () {
        $.ajax({
            url: 'api/ppm.php?type=bottom5_execute&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme6', {
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'Bottom 5 Executor'
                        },
                        subtitle: {
                            text: 'Total PPM Executed'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Total PPM Executed',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true
                                    //format: '<b>{point.woTaskFixedBy}</b>',
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(32).search(this.ppmTaskServicedBy, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Total',
                            data: resp.result.data
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.generateChartPpmAverageExecuteByTrade = function () {
        $.ajax({
            url: 'api/ppm.php?type=average_execute_by_trade&clientId='+clientId+'&siteId='+siteId+'&year='+currentYear+'&month='+currentMonth,
            type: 'GET', headers: {'Authorization': 'Bearer ' + sessionStorage.getItem('token')},
            dataType: 'json', async: true,
            success: function (resp) {
                if (resp.success) {
                    Highcharts.chart('chartHme4', {
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'Execution Time'
                        },
                        subtitle: {
                            text: 'Average Execution Time by Trade'
                        },
                        xAxis: {
                            categories: resp.result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Average PPM Execution Time (minutes)'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        legend: {
                            enabled: false
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.display} ({point.y:.2f} minutes)</b>'
                        },
                        plotOptions: {
                            column: {
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.display}</b>',
                                },
                                borderRadius: 3,
                                borderWidth: 0
                            },
                            series: {
                                point: {
                                    events: {
                                        click: function() {
                                            oTablePpm.search('').columns().search('').draw();
                                            oTablePpm.column(29).search(this.assetGroupId, true, false).draw();
                                        }
                                    }
                                }
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            name: 'Average Time',
                            data: resp.result.data,
                            color: '#00b8d4'
                        }]
                    });
                } else {
                    throw new Error(_ALERT_MSG_ERROR_DEFAULT);
                }
            },
            error: function () {
                throw new Error(_ALERT_MSG_ERROR_DEFAULT);
            }
        });
    };

    this.deleteWo = function (_woTaskId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_woTaskId]);
                mzAjaxRequest('wo.php?woTaskId='+_woTaskId, 'DELETE');
                self.genTableHmeDataWo();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
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

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}