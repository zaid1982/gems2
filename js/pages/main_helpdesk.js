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

    function lookupName(ref, id, field) {
        if (id === '' || id === null || typeof id === 'undefined') {
            return '';
        }
        if (ref && ref[id] && ref[id][field]) {
            return ref[id][field];
        }
        return String(id);
    }

    function displayText(data, type) {
        if (type === 'display') {
            return GemsUI.escape(data || '');
        }
        return data || '';
    }

    function progressBadge(statusId, type) {
        const label = refStatus[statusId] ? refStatus[statusId]['statusDesc'] : String(statusId);
        if (type !== 'display') {
            return label;
        }
        const statusMap = {
            '1': 'warning',
            '2': 'warning',
            '3': 'primary',
            '4': 'primary',
            '5': 'success',
            '6': 'danger'
        };
        return GemsUI.badge(statusMap[String(statusId)] || 'secondary', GemsUI.escape(label));
    }

    function stripHtml(data) {
        const tmp = document.createElement('div');
        tmp.innerHTML = data;
        return tmp.textContent || tmp.innerText || '';
    }

    function statusChip(linkId, label, count, kind) {
        $('#' + linkId).html(GemsUI.escape(label) + ' ' + GemsUI.badge(kind, mzFormatNumber(count)));
    }

    this.init = function () {
        if (!mzIsRoleExist('11')) {
            $('#btnHdkWoAdd').hide();
        }

        $('#lblHdkSiteName').html(GemsUI.escape(refSite[userSite]['siteName']));

        oTableWo = $('#dtHdkWo').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [1, 'desc'],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableWo.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                try {
                    const info = oTableWo.page.info();
                    $('#lblHelpdeskCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal);
                    $('#lblHelpdeskUpdated').text(new Date().toLocaleString());
                } catch (e) { /* noop */ }
            },
            language: GemsUI.dtEmpty('fa-headset', 'No complaints loaded.'),
            dom: GemsUI.dtDom,
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskTimeCreated'},
                {mData: 'woTaskRequestNo'},
                {mData: 'woTaskNo'},
                {mData: null, mRender: function (data, type, row) {
                    return displayText(lookupName(refSite, row['siteId'], 'siteDesc'), type);
                }},
                {mData: 'woTaskLocation', mRender: function (data, type) {
                    return displayText(data, type);
                }},
                {mData: 'woTaskTypeDesc'},
                {mData: null, mRender: function (data, type, row) {
                    return displayText(lookupName(refUser, row['woTaskCreatedBy'], 'userFirstName'), type);
                }},
                {mData: 'woTaskComplaint', mRender: function (data, type) {
                    return displayText(data, type);
                }},
                {mData: 'woTaskSeverity'},
                {mData: null, mRender: function (data, type, row) {
                    const name = row['ppmGroupId'] !== '' ? lookupName(refPpmGroup, row['ppmGroupId'], 'ppmGroupName') : '';
                    return displayText(name, type);
                }},
                {mData: null, mRender: function (data, type, row) {
                    const name = row['woTaskAssignedTo'] !== '' ? lookupName(refUser, row['woTaskAssignedTo'], 'userFirstName') : '';
                    return displayText(name, type);
                }},
                {mData: 'woTaskRepairDesc', mRender: function (data, type) {
                    return displayText(data, type);
                }},
                {mData: 'woTaskRate'},
                {mData: null, mRender: function (data, type, row) {
                    return progressBadge(row['woTaskStatus'], type);
                }},
                {mData: null, bSortable: false, sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return '';
                        }
                        if (!mzIsRoleExist('1')) {
                            return '';
                        }
                        return GemsUI.actionBtn({id: 'lnkHdkWoDelete_' + meta.row, cls: 'lnkHdkWoDelete', icon: 'fas fa-trash-alt', title: 'Delete'});
                    }
                },
                {mData: 'siteId', visible: false},
                {mData: 'ppmGroupId', visible: false},
                {mData: 'woTaskStatus', visible: false},
                {mData: 'woTaskType', visible: false},
                {mData: 'woTaskId', visible: false},
                {mData: null, visible: false, mRender: function (data, type, row) {
                    return row['woTaskFixedBy'] !== '' ? lookupName(refUser, row['woTaskFixedBy'], 'userFirstName') : '';
                }},
                {mData: 'woTaskAssignedBy', visible: false, mRender: function (data, type, row) {
                    return row['woTaskAssignedBy'] !== '' ? lookupName(refUser, row['woTaskAssignedBy'], 'userFirstName') : '';
                }},
                {mData: 'woTaskVerifiedBy', visible: false, mRender: function (data, type, row) {
                    return row['woTaskVerifiedBy'] !== '' ? lookupName(refUser, row['woTaskVerifiedBy'], 'userFirstName') : '';
                }},
                {mData: 'woTaskTimeResponded', visible: false},
                {mData: 'woTaskTimeAssigned', visible: false},
                {mData: 'woTaskTimeExecuted', visible: false},
                {mData: 'woTaskTimeVerified', visible: false},
                {mData: 'woTaskFixedBy', visible: false}
            ]
        });
        $('#dtHdkWo_filter').hide();
        GemsUI.bindDtTooltips('#dtHdkWo');

        $('#dtHdkWo').on('click', '.lnkHdkWoDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableWo.row(parseInt(rowId, 10)).data();
                modalConfirmDeleteClass.delete(currentRow['woTaskId'], self);
            }
        });
        $('#dtHdkWo').on('click', '.lnkHdkWoPdf', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableWo.row(parseInt(rowId, 10)).data();
                        let pdfId = currentRow['pdfId'];
                        const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf', woTaskId: currentRow['woTaskId']});
                        pdfId = resultRequest['pdfId'];
                        const pdfSrc = mzAjaxRequest('pdf.php?pdfId=' + pdfId, 'GET');
                        $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Order Report: ' + currentRow['woTaskNo']);
                        $('#mpdf_iframe').attr('src', pdfSrc);
                        $('#modal_pdf').modal('show');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
        $('#dtHdkWo').on('click', '.lnkHdkWoPdfWr', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableWo.row(parseInt(rowId, 10)).data();
                        let pdfId = currentRow['pdfIdWr'];
                        const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf_wr', woTaskId: currentRow['woTaskId']});
                        pdfId = resultRequest['pdfId'];
                        const pdfSrc = mzAjaxRequest('pdf.php?pdfId=' + pdfId, 'GET');
                        $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Request Report: ' + currentRow['woTaskNo']);
                        $('#mpdf_iframe').attr('src', pdfSrc);
                        $('#modal_pdf').modal('show');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#txtHdkWoSearch').on('keyup change', function () {
            oTableWo.search($(this).val()).draw();
        });

        oTableWo.column(4).visible(false);
        oTableWo.column(8).visible(false);
        oTableWo.column(12).visible(false);
        oTableWo.column(13).visible(false);

        $('#optHdkWoColumns').on('change', function () {
            for (let i = 1; i <= 14; i++) {
                oTableWo.column(i).visible(false);
            }
            const selectedColumns = $(this).val();
            $.each(selectedColumns, function (n, u) {
                oTableWo.column(parseInt(u, 10)).visible(true);
            });
        });

        let cntWo;
        let btnWoOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 21, 22, 23, 24, 25, 26, 27],
                format: {
                    body: function (data, row, column) {
                        if (row === 0 && column === 0) {
                            cntWo = 1;
                        }
                        if (column === 14) {
                            return stripHtml(data);
                        }
                        return column === 0 ? cntWo++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableWo, {
            buttons: [
                $.extend(true, {}, btnWoOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Complaint List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend(true, {}, btnWoOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Complaint List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-secondary btn-sm'
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

        for (let i = 0; i < 31; i++) {
            arrTotal[i] = 0;
        }

        const tableData = oTableWo.data();
        $.each(tableData, function (n, u) {
            const status = parseInt(u['woTaskStatus'], 10);
            arrTotal[status]++;
            totalAll++;
        });

        statusChip('linkHdkAll', 'All Status', totalAll, 'primary');
        statusChip('linkHdk24', refStatus[24]['statusDesc'], arrTotal[24], 'warning');
        statusChip('linkHdk27', refStatus[27]['statusDesc'], arrTotal[27], 'warning');
        statusChip('linkHdk28', refStatus[28]['statusDesc'], arrTotal[28], 'warning');
        statusChip('linkHdk29', refStatus[29]['statusDesc'], arrTotal[29], 'warning');
        statusChip('linkHdk13', refStatus[13]['statusDesc'], arrTotal[13], 'warning');
        statusChip('linkHdk15', refStatus[15]['statusDesc'], arrTotal[15], 'warning');
        statusChip('linkHdk26', refStatus[26]['statusDesc'], arrTotal[26], 'warning');
        statusChip('linkHdk16', refStatus[16]['statusDesc'], arrTotal[16], 'success');
        statusChip('linkHdk25', refStatus[25]['statusDesc'], arrTotal[25], 'danger');
        statusChip('linkHdk30', refStatus[30]['statusDesc'], arrTotal[30], 'danger');

        const chartData = [
            {name: refStatus[24]['statusDesc'], y: arrTotal[24]},
            {name: refStatus[27]['statusDesc'], y: arrTotal[27]},
            {name: refStatus[28]['statusDesc'], y: arrTotal[28]},
            {name: refStatus[29]['statusDesc'], y: arrTotal[29]},
            {name: refStatus[13]['statusDesc'], y: arrTotal[13]},
            {name: refStatus[15]['statusDesc'], y: arrTotal[15]},
            {name: refStatus[26]['statusDesc'], y: arrTotal[26]}
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
            credits: {
                enabled: false
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.y}'
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
