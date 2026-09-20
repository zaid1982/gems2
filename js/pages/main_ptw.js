function MainPtw() {

    const className = 'MainPtw';
    let self = this;
    let oTablePtw;
    let refSite;
    let refUser;
    let refPpmGroup;
    let refStatus;
    let userSite;
    let modalCreatePtwClass;

    function exportCellText(data) {
        const tmp = document.createElement('div');
        tmp.innerHTML = data;
        return tmp.textContent || tmp.innerText || '';
    }

    this.init = function () {
        if (!mzIsRoleExist('11')) {
            $('#btnPtwAdd').hide();
        }

        const siteName = refSite && refSite[userSite] ? refSite[userSite]['siteName'] : '';
        $('#lblPtwSiteName').html('Site Name : <b>' + GemsUI.escape(siteName) + '</b>');

        oTablePtw = $('#dtPtw').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [1, 'desc'],
            language: GemsUI.dtEmpty('fa-file-signature', 'No PTW permits recorded.', 'No permits match the current search or filter.'),
            dom: GemsUI.dtDom,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTablePtw.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            }
        });

        GemsUI.bindDtTooltips('#dtPtw');

        $('#dtPtw').on('click', '.lnkPtwDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTablePtw.row(parseInt(rowId)).data();
                modalConfirmDeleteClass.delete(currentRow['ptwPermitId'], self);
            }
        });
        $('#dtPtw').on('click', '.lnkPtwPdf', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTablePtw.row(parseInt(rowId)).data();
                        let pdfId = currentRow['pdfId'];
                        const resultRequest = mzAjaxRequest('ptw.php', 'POST', {action: 'generate_pdf', ptwPermitId: currentRow['ptwPermitId']});
                        if (resultRequest.success) {
                            const resultPdf = resultRequest.result;
                            pdfId = resultPdf.pdfId;
                            oTablePtw.cell(parseInt(rowId), 16).data(pdfId).draw();
                            mzOpenPdfModal('/' + resultPdf.pdfFullPath);
                        } else {
                            toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
                        }
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
        $('#dtPtw').on('click', '.lnkPtwEdit', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTablePtw.row(parseInt(rowId)).data();
                window.location.href = 'ptw_form.html?id=' + currentRow['ptwPermitId'];
            }
        });
        $('#dtPtw').on('click', '.lnkPtwView', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTablePtw.row(parseInt(rowId)).data();
                viewPtwDetails(currentRow['ptwPermitId']);
            }
        });

        let cntExport;
        const btnOpt = {
            exportOptions: {
                columns: ':visible',
                format: {
                    body: function (data, row, column) {
                        if (row === 0 && column === 0) {
                            cntExport = 1;
                        }
                        return column === 0 ? cntExport++ : exportCellText(data);
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTablePtw, {
            buttons: [
                $.extend(true, {}, btnOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'PTW Permits',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend(true, {}, btnOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'PTW Permits',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend(true, {}, btnOpt, {
                    extend: 'csvHtml5',
                    text: '<i class="fas fa-file-csv"></i>',
                    title: 'PTW Permits',
                    titleAttr: 'CSV',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend(true, {}, btnOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'PTW Permits',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-secondary btn-sm'
                })
            ]
        }).container().appendTo($('#btnDtPtwExport'));

        $('#optPtwColumns').on('change', function () {
            const selectedColumns = $(this).val();
            for (let i = 1; i < oTablePtw.columns()[0].length - 1; i++) {
                oTablePtw.column(i).visible(false);
            }
            if (selectedColumns) {
                selectedColumns.forEach(function (col) {
                    oTablePtw.column(parseInt(col)).visible(true);
                });
            }
        });

        $('#txtPtwSearch').on('keyup', function () {
            oTablePtw.search(this.value).draw();
        });

        $('#btnDtPtwRefresh').off('click').on('click', function () {
            self.refreshPtwData();
        });

        $('#btnPtwAdd').off('click').on('click', function () {
            modalCreatePtwClass.show();
        });

        $('.linkPtwStatus').off('click').on('click', function () {
            const statusFilter = $(this).attr('id').replace('linkPtw', '');
            self.filterByStatus(statusFilter);
        });

        this.refreshPtwData();
        this.loadPtwStatusSummary();
        this.loadPtwChart();
    };

    this.refreshPtwData = function () {
        ShowLoader();

        try {
            const arrPtwData = mzAjaxRequest('ptw.php', 'GET', {action: 'list'});

            oTablePtw.clear();

            if (arrPtwData && Array.isArray(arrPtwData) && arrPtwData.length > 0) {
                const firstPtw = arrPtwData[0];
                try {
                    oTablePtw.row.add([
                        '2',
                        firstPtw.created_date || '',
                        firstPtw.ptw_permit_number || '',
                        firstPtw.ptw_permit_description || '',
                        'Site Name',
                        firstPtw.ptw_work_area || '',
                        firstPtw.ptw_work_type || '',
                        firstPtw.ptw_applicant_name || '',
                        firstPtw.ptw_risk_level || '',
                        firstPtw.ptw_status || '',
                        firstPtw.ptw_valid_from || '',
                        firstPtw.ptw_valid_to || '',
                        '0',
                        firstPtw.ptw_contractor_company || '',
                        'Creator Name',
                        '<i class="fas fa-eye"></i>',
                        '',
                        firstPtw.ptw_permit_id || '',
                        firstPtw.ptw_status || '',
                        firstPtw.ptw_risk_level || '',
                        firstPtw.created_by || '',
                        '',
                        '',
                        '',
                        firstPtw.approved_supervisor_date || '',
                        firstPtw.approved_she_date || '',
                        firstPtw.approved_fm_date || '',
                        firstPtw.created_date || ''
                    ]);
                } catch (e) {
                    console.error('Error processing PTW record:', e);
                }
            }

            oTablePtw.draw();
            self.updateStatusLinks(arrPtwData || []);
        } catch (error) {
            toastr['error'](error.message, _ALERT_TITLE_ERROR);
        }

        HideLoader();
    };

    this.getStatusBadge = function (status) {
        const kinds = {
            DRAFT: 'secondary',
            PENDING_SUPERVISOR: 'warning',
            PENDING_SHE: 'warning',
            PENDING_FM: 'warning',
            APPROVED: 'info',
            ACTIVE: 'success',
            COMPLETED: 'secondary',
            CANCELLED: 'danger'
        };
        const labels = {
            DRAFT: 'Draft',
            PENDING_SUPERVISOR: 'Pending Supervisor',
            PENDING_SHE: 'Pending SHE',
            PENDING_FM: 'Pending FM',
            APPROVED: 'Approved',
            ACTIVE: 'Active',
            COMPLETED: 'Completed',
            CANCELLED: 'Cancelled'
        };
        return GemsUI.badge(kinds[status] || 'secondary', GemsUI.escape(labels[status] || status));
    };

    this.getRiskBadge = function (risk) {
        const kinds = {
            LOW: 'success',
            MEDIUM: 'warning',
            HIGH: 'danger',
            CRITICAL: 'danger'
        };
        const labels = {
            LOW: 'Low',
            MEDIUM: 'Medium',
            HIGH: 'High',
            CRITICAL: 'Critical'
        };
        let label = GemsUI.escape(labels[risk] || risk);
        if (risk === 'CRITICAL') {
            label = '<i class="fas fa-exclamation-triangle"></i> ' + label;
        }
        return GemsUI.badge(kinds[risk] || 'secondary', label);
    };

    this.getActionButtons = function (ptw, index) {
        let buttons = '';
        buttons += GemsUI.actionBtn({id: 'lnkPtwView_' + index, cls: 'lnkPtwView', icon: 'fas fa-eye', title: 'View Details'});
        if (ptw.ptw_status === 'DRAFT') {
            buttons += GemsUI.actionBtn({id: 'lnkPtwEdit_' + index, cls: 'lnkPtwEdit', icon: 'fas fa-edit', title: 'Edit'});
            buttons += GemsUI.actionBtn({id: 'lnkPtwDelete_' + index, cls: 'lnkPtwDelete', icon: 'fas fa-trash', title: 'Delete'});
        }
        buttons += GemsUI.actionBtn({id: 'lnkPtwPdf_' + index, cls: 'lnkPtwPdf', icon: 'far fa-file-pdf', title: 'Generate PDF'});
        return buttons;
    };

    this.filterByStatus = function (status) {
        if (status === 'All') {
            oTablePtw.column(18).search('').draw();
        } else {
            const statusMap = {
                Draft: 'DRAFT',
                PendingSupervisor: 'PENDING_SUPERVISOR',
                PendingShe: 'PENDING_SHE',
                PendingFm: 'PENDING_FM',
                Approved: 'APPROVED',
                Active: 'ACTIVE',
                Completed: 'COMPLETED',
                Cancelled: 'CANCELLED'
            };
            oTablePtw.column(18).search(statusMap[status] || status).draw();
        }
    };

    this.updateStatusLinks = function (data) {
        const statusCounts = {
            All: data.length,
            DRAFT: 0,
            PENDING_SUPERVISOR: 0,
            PENDING_SHE: 0,
            PENDING_FM: 0,
            APPROVED: 0,
            ACTIVE: 0,
            COMPLETED: 0,
            CANCELLED: 0
        };

        data.forEach(function (ptw) {
            if (statusCounts.hasOwnProperty(ptw.ptw_status)) {
                statusCounts[ptw.ptw_status]++;
            }
        });

        $('#linkPtwAll').html('All (' + statusCounts.All + ')');
        $('#linkPtwDraft').html('Draft (' + statusCounts.DRAFT + ')');
        $('#linkPtwPendingSupervisor').html('Pending Supervisor (' + statusCounts.PENDING_SUPERVISOR + ')');
        $('#linkPtwPendingShe').html('Pending SHE (' + statusCounts.PENDING_SHE + ')');
        $('#linkPtwPendingFm').html('Pending FM (' + statusCounts.PENDING_FM + ')');
        $('#linkPtwApproved').html('Approved (' + statusCounts.APPROVED + ')');
        $('#linkPtwActive').html('Active (' + statusCounts.ACTIVE + ')');
        $('#linkPtwCompleted').html('Completed (' + statusCounts.COMPLETED + ')');
        $('#linkPtwCancelled').html('Cancelled (' + statusCounts.CANCELLED + ')');
    };

    this.loadPtwStatusSummary = function () {
        const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'statistics'});
        if (resultRequest.success) {
            const stats = resultRequest.result;
        }
    };

    this.loadPtwChart = function () {
        const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'chart_data'});
        if (resultRequest.success) {
            const chartData = resultRequest.result;
            self.renderPtwChart(chartData);
        }
    };

    this.renderPtwChart = function (data) {
        Highcharts.chart('chartPtwByStatus', {
            chart: { type: 'pie' },
            title: { text: 'PTW Permits by Status' },
            tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>' },
            accessibility: { point: { valueSuffix: '%' } },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                    }
                }
            },
            series: [{
                name: 'Status',
                colorByPoint: true,
                data: data
            }]
        });
    };

    this.deleteRecord = function (ptwPermitId) {
        ShowLoader();
        const resultRequest = mzAjaxRequest('ptw.php', 'DELETE', {permit_id: ptwPermitId});
        if (resultRequest.success) {
            toastr['success']('PTW permit deleted successfully', _ALERT_TITLE_SUCCESS);
            self.refreshPtwData();
        } else {
            toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.getClassName = function () {
        return className;
    };

    this.setUserSite = function (value) { userSite = value; };
    this.setRefSite = function (value) { refSite = value; };
    this.setRefUser = function (value) { refUser = value; };
    this.setRefPpmGroup = function (value) { refPpmGroup = value; };
    this.setRefStatus = function (value) { refStatus = value; };
    this.setModalCreatePtw = function (value) { modalCreatePtwClass = value; };
}

function viewPtwDetails(permitId) {
    const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'details', permit_id: permitId});
    if (resultRequest.success) {
        const permit = resultRequest.result;
        let content =
            '<div class="row">' +
                '<div class="col-6"><strong>Permit Number:</strong> ' + GemsUI.escape(permit.ptwPermitNumber) + '</div>' +
                '<div class="col-6"><strong>Status:</strong> ' + GemsUI.escape(permit.ptwStatus) + '</div>' +
            '</div>' +
            '<div class="row mt-2">' +
                '<div class="col-12"><strong>Description:</strong> ' + GemsUI.escape(permit.ptwPermitDescription) + '</div>' +
            '</div>' +
            '<div class="row mt-2">' +
                '<div class="col-6"><strong>Work Area:</strong> ' + GemsUI.escape(permit.ptwWorkArea) + '</div>' +
                '<div class="col-6"><strong>Work Type:</strong> ' + GemsUI.escape(permit.ptwWorkType) + '</div>' +
            '</div>' +
            '<div class="row mt-2">' +
                '<div class="col-6"><strong>Risk Level:</strong> ' + GemsUI.escape(permit.ptwRiskLevel) + '</div>' +
                '<div class="col-6"><strong>Valid From:</strong> ' + GemsUI.escape(permit.ptwValidFrom) + '</div>' +
            '</div>' +
            '<div class="row mt-2">' +
                '<div class="col-6"><strong>Valid To:</strong> ' + GemsUI.escape(permit.ptwValidTo) + '</div>' +
                '<div class="col-6"><strong>Applicant:</strong> ' + GemsUI.escape(permit.ptwApplicantName) + '</div>' +
            '</div>';

        if (permit.workers && permit.workers.length > 0) {
            content += '<div class="row mt-3"><div class="col-12"><strong>Workers:</strong></div></div>';
            content += '<div class="table-responsive"><table class="table table-sm">';
            content += '<thead><tr><th>Name</th><th>IC Number</th><th>Company</th></tr></thead><tbody>';
            permit.workers.forEach(function (worker) {
                content += '<tr><td>' + GemsUI.escape(worker.workerName) + '</td><td>' + GemsUI.escape(worker.workerIcNumber) + '</td><td>' + GemsUI.escape(worker.workerCompany) + '</td></tr>';
            });
            content += '</tbody></table></div>';
        }

        mzShowAlert('PTW Permit Details', content);
    } else {
        toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
    }
}
