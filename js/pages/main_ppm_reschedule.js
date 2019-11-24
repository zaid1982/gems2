function MainPpmReschedule() {

    const className = 'MainPpmReschedule';
    let self = this;
    let refSite;
    let refPpmGroup;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refStatus;
    let modalPpmRescheduleClass;
    let yearId;
    let userClient;
    let currentRole = '';
    let oTablePpm;
    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    this.init = function () {
        yearId = '2019';
        userClient = mzGetUserInfoByParam('clientId');
        if (mzIsRoleExist('1')) {
            currentRole = 1;
        } else if (mzIsRoleExist('2')) {
            currentRole = 2;
        }

        let arrYear = [];
        let arrMonth = [];
        let dt = new Date();
        const currentYear = dt.getFullYear();
        const currentMonth = dt.getMonth();

        for (let year = 2019; year <= currentYear+3; year++) {
            arrYear.push({yearId:year.toString(), yearDesc:year.toString()});
        }
        for (let i = 0; i <= monthFull.length; i++) {
            arrMonth.push({monthId:i, monthDesc:monthFull[i]});
        }
        for (let i = 0; i <= refSite.length; i++) {
            if (typeof refSite[i] !== 'undefined' && refSite[i]['clientId'] === userClient) {
                currentSite = refSite[i]['siteId'];
                break;
            }
        }

        let filterSite = {siteStatus:'1'};
        if (currentRole === '2') {
            filterSite['clientId'] = userClient;
        }
        mzOption('optPrsYear', arrYear, 'Select Year', 'yearId', 'yearDesc', {}, 'required');
        mzOption('optPrsMonth', arrMonth, 'Select Month', 'monthId', 'monthDesc', {}, 'required', false);
        mzOption('optPrsSiteId', refSite, 'Select Site', 'siteId', 'siteName', filterSite, 'required');

        $('#optPrsYear').val(yearId);
        $('#optPrsMonth').val(currentMonth);
        $('#optPrsSiteId').val(currentSite);

        oTablePpm =  $('#dtPrsList').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [6, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTablePpm.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPrsPpmPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTablePpm.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfId'];
                                if (currentRow['pdfId'] === '') {
                                    pdfId = mzAjaxRequest('ppm.php', 'POST', {action: 'generate_pdf', ppmTaskId:currentRow['ppmTaskId']});
                                }
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
                $('.lnkPrsPpmReschedule').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTablePpm.row(parseInt(rowId)).data();
                                const passParam = {
                                    ppmTaskNo:currentRow['ppmTaskNo'],
                                    ppmTaskStartDate:currentRow['ppmTaskStartDate'],
                                    ppmTaskScheduleDate:currentRow['ppmTaskScheduleDate'],
                                    frequency:currentRow['frequency'],
                                    frequencyIds:currentRow['frequencyIds']
                                };
                                modalPpmRescheduleClass.edit(currentRow['ppmTaskId'], rowId, passParam);
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
                    {mData: 'assetNo'},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                        }},
                    {mData: 'ppmTaskStartDate'},
                    {mData: 'ppmTaskScheduleDate'},
                    {mData: 'frequency'},
                    {mData: null, mRender: function (data, type, row){
                            return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : '';
                        }},
                    {mData: 'ppmTaskIsScheduled', sClass: 'text-center', mRender: function (data, type, row){
                            const frequency = row['frequency'];
                            if (data === '1') {
                                return 'Rescheduled';
                            } else if (frequency === 'Daily' || frequency.search(', ') !== -1) {
                                return 'Disallowed';
                            } else {
                                return 'Not Yet';
                            }
                        }},
                    {mData: null, sClass: 'text-center',
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmTaskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmTaskStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="far fa-file-pdf lnkPrsPpmPdf" id="lnkPrsPpmPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="PPM PDF"></i></a>';
                            const frequency = row['frequencyIds'];
                            if (frequency === '1' || frequency === '2' || frequency === '3' || frequency === '4' || frequency === '6') {
                                label += '&nbsp;&nbsp;<a><i class="far fa-calendar-check lnkPrsPpmReschedule" id="lnkPrsPpmReschedule_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Reschedule PPM Date"></i></a>';
                            }
                            return label;
                        }
                    },
                    {mData: 'ppmTaskId', visible: false},
                    {mData: 'siteId', visible: false},
                    {mData: 'ppmTaskStatus', visible: false},
                    {mData: 'pdfId', visible: false},
                    {mData: 'frequencyIds', visible: false}
                ]
        });
        $("#dtPrsList_filter").hide();
        $('#txtPrsListSearch').on('keyup change', function () {
            oTablePpm.search($(this).val()).draw();
        });

        $('#optPrsRescheduleStatus').on('change', function () {
            oTablePpm.column(10).search($(this).val(), false, true, false).draw();
        });

        let cntPpm;
        let btnPpmOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntPpm = 1;
                        }
                        if (column === 11) {
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
        }).container().appendTo($('#btnDtPrsListExport'));

        $('#btnDtPrsListRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePrsPpm();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#optPrsSiteId, #optPrsYear, #optPrsMonth').on('change', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePrsPpm();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTablePrsPpm();
    };

    this.genTablePrsPpm = function () {
        const dataPpm = mzAjaxRequest('ppm.php?type=dashboard_list&clientId=&siteId='+$('#optPrsSiteId').val()+'&year='+$('#optPrsYear').val()+'&month='+$('#optPrsMonth').val(), 'GET');
        oTablePpm.clear().rows.add(dataPpm).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
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

    this.setModalPpmRescheduleClass = function (_modalPpmRescheduleClass) {
        modalPpmRescheduleClass = _modalPpmRescheduleClass;
    };
}