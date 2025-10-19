function MainHelpdesk() {

    const className = 'MainHelpdesk';
    let self = this;
    let oTableWo;
    let refSite;
    let refUser;
    let refPpmGroup;
    let refStatus;
    let userSite;
    let modalCreateComplaintClass;

    this.init = function () {
        if (!mzIsRoleExist('11')) {
            $('#btnHdkWoAdd').hide();
        }

        $('#lblHdkSiteName').html('Site Name : <b>'+refSite[userSite]['siteName']+'</b>');

        oTableWo =  $('#dtHdkWo').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'desc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableWo.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkHdkWoDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWo.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['woTaskId'], self);
                    }
                });
                $('.lnkHdkWoPdf').off('click').on('click', function () {
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
                $('.lnkHdkWoPdfWr').off('click').on('click', function () {
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
                    {mData: 'woTaskTimeCreated'},
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
                                label += '&nbsp;<a><i class="fas fa-trash-alt lnkHdkWoDelete" id="lnkHdkWoDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>&nbsp;';
                            }
                            //if (row['woTaskIsWr'] === '1') {
                            //    label += '&nbsp;<a><i class="fas fa-file-signature lnkHdkWoPdfWr" id="lnkHdkWoPdfWr_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Work Request PDF"></i></a>&nbsp;';
                            //}
                            //if (row['woTaskIsWr'] !== '1' || row['woTaskTimeWrVerified'] !== '') {
                            //    label += '&nbsp;<a><i class="far fa-file-pdf lnkHdkWoPdf" id="lnkHdkWoPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Work Order PDF"></i></a>&nbsp;';
                            //}
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
                    {mData: 'woTaskTimeResponded', visible: false},
                    {mData: 'woTaskTimeAssigned', visible: false},
                    {mData: 'woTaskTimeExecuted', visible: false},
                    {mData: 'woTaskTimeVerified', visible: false},
                    {mData: 'woTaskFixedBy', visible: false}
                ]
        });
        $("#dtHdkWo_filter").hide();
        $('#txtHdkWoSearch').on('keyup change', function () {
            oTableWo.search($(this).val()).draw();
        });

        oTableWo.column(4).visible(false);
        oTableWo.column(8).visible(false);
        oTableWo.column(12).visible(false);
        oTableWo.column(13).visible(false);

        $('#optHdkWoColumns').on('change', function () {
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
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 21, 22, 23, 24, 25, 26, 27],
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
                    title:     'GEMS 2.0 - Complaint List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnWoOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Complaint List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtHdkWoExport'));

        $('.linkHdkStatus').on('click', function () {
            const linkId = $(this).attr('id');
            const statusId = linkId.substr(7);
            if (statusId === 'All') {
                oTableWo.column(18).search('', false, true, false).draw();
            } else {
                oTableWo.column(18).search(statusId, false, true, false).draw();
            }
        });

        $('#btnDtHdkWoRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableHdkWo();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnHdkWoAdd').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    modalCreateComplaintClass.add(userSite);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        self.genTableHdkWo();
    };

    this.genTableHdkWo = function () {
        const dataWo = mzAjaxRequest('wo.php?type=helpdesk_list&isPending=1', 'GET');
        oTableWo.clear().rows.add(dataWo).draw();
        if (refSite[userSite]['siteIsWr'] !== '1') {
            oTableWo.column(2).visible(false);
        }
        self.displayStatsChart();
    };

    this.displayStatsChart = function () {
        let totalAll = 0;
        let arrTotal = [];

        for (let i=0; i<31; i++) {
            arrTotal[i] = 0;
        }

        const tableData = oTableWo.data();
        $.each(tableData, function (n, u) {
            const status = parseInt(u['woTaskStatus']);
            arrTotal[status]++;
            totalAll++;
        });

    $('#linkHdkAll').html('<span class="bullet blue"></span> All Status <span class="badge blue">'+mzFormatNumber(totalAll)+'</span>');
    $('#linkHdk24').html('<span class="bullet orange"></span> '+refStatus[24]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[24])+'</span>');
    $('#linkHdk27').html('<span class="bullet orange"></span> '+refStatus[27]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[27])+'</span>');
    $('#linkHdk28').html('<span class="bullet orange"></span> '+refStatus[28]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[28])+'</span>');
    $('#linkHdk29').html('<span class="bullet orange"></span> '+refStatus[29]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[29])+'</span>');
    $('#linkHdk13').html('<span class="bullet orange"></span> '+refStatus[13]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[13])+'</span>');
    $('#linkHdk15').html('<span class="bullet orange"></span> '+refStatus[15]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[15])+'</span>');
    $('#linkHdk26').html('<span class="bullet orange"></span> '+refStatus[26]['statusDesc']+' <span class="badge orange">'+mzFormatNumber(arrTotal[26])+'</span>');
    $('#linkHdk16').html('<span class="bullet green"></span> '+refStatus[16]['statusDesc']+' <span class="badge green">'+mzFormatNumber(arrTotal[16])+'</span>');
    $('#linkHdk25').html('<span class="bullet red"></span> '+refStatus[25]['statusDesc']+' <span class="badge red">'+mzFormatNumber(arrTotal[25])+'</span>');
    $('#linkHdk30').html('<span class="bullet red"></span> '+refStatus[30]['statusDesc']+' <span class="badge red">'+mzFormatNumber(arrTotal[30])+'</span>');

        const chartData = [
            {name:refStatus[24]['statusDesc'], y:arrTotal[24]},
            {name:refStatus[27]['statusDesc'], y:arrTotal[27]},
            {name:refStatus[28]['statusDesc'], y:arrTotal[28]},
            {name:refStatus[29]['statusDesc'], y:arrTotal[29]},
            {name:refStatus[13]['statusDesc'], y:arrTotal[13]},
            {name:refStatus[15]['statusDesc'], y:arrTotal[15]},
            {name:refStatus[26]['statusDesc'], y:arrTotal[26]}
        ];

        Highcharts.chart('chartHdkWoByStatus', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'Total Pending Complaint Status'
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
                        color: 'white'
                    }
                }
            },
            series: [{
                name: 'Status',
                data: chartData
            }]
        });
    };

    this.getClassName = function () {
        return className;
    };

    this.setUserSite = function (_userSite) {
        userSite = _userSite;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
    
    this.setModalCreateComplaint = function (_modalCreateComplaintClass) {
        modalCreateComplaintClass = _modalCreateComplaintClass;
    };
}