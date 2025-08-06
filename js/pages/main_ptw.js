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

    this.init = function () {
        if (!mzIsRoleExist('11')) {
            $('#btnPtwAdd').hide();
        }

        $('#lblPtwSiteName').html('Site Name : <b>'+refSite[userSite]['siteName']+'</b>');

        oTablePtw =  $('#dtPtw').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'desc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTablePtw.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPtwDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTablePtw.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['ptwPermitId'], self);
                    }
                });
                $('.lnkPtwPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTablePtw.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfId'];
                                const resultRequest = mzAjaxRequest('ptw.php', 'POST', {action: 'generate_pdf', ptwPermitId:currentRow['ptwPermitId']});
                                if (resultRequest.success) {
                                    const resultPdf = resultRequest.result;
                                    pdfId = resultPdf.pdfId;
                                    oTablePtw.cell(parseInt(rowId), 16).data(pdfId).draw();
                                    mzOpenPdfModal('/'+resultPdf.pdfFullPath);
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
                $('.lnkPtwEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTablePtw.row(parseInt(rowId)).data();
                        window.location.href = 'ptw_form.html?id=' + currentRow['ptwPermitId'];
                    }
                });
                $('.lnkPtwView').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTablePtw.row(parseInt(rowId)).data();
                        viewPtwDetails(currentRow['ptwPermitId']);
                    }
                });
            },
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'PTW Permits',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csvHtml5',
                    title: 'PTW Permits'
                },
                {
                    extend: 'pdfHtml5',
                    title: 'PTW Permits',
                    orientation: 'landscape',
                    pageSize: 'A4'
                },
                {
                    extend: 'print',
                    title: 'PTW Permits'
                }
            ]
        });

        new $.fn.dataTable.Buttons(oTablePtw, {
            buttons: [
                {
                    extend: 'collection',
                    text: '<i class="fas fa-download"></i>',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    buttons: ['excelHtml5', 'csvHtml5', 'pdfHtml5', 'print']
                }
            ]
        });

        oTablePtw.buttons().container().appendTo('#btnDtPtwExport');

        // Column visibility
        $('#optPtwColumns').on('change', function() {
            var selectedColumns = $(this).val();
            
            // Hide all columns first (except first and action columns)
            for (var i = 1; i < oTablePtw.columns()[0].length - 1; i++) {
                oTablePtw.column(i).visible(false);
            }
            
            // Show selected columns
            if (selectedColumns) {
                selectedColumns.forEach(function(col) {
                    oTablePtw.column(parseInt(col)).visible(true);
                });
            }
        });

        // Search functionality
        $('#txtPtwSearch').on('keyup', function() {
            oTablePtw.search(this.value).draw();
        });

        // Refresh button
        $('#btnDtPtwRefresh').off('click').on('click', function () {
            self.refreshPtwData();
        });

        // Add new PTW button
        $('#btnPtwAdd').off('click').on('click', function () {
            modalCreatePtwClass.show();
        });

        // Status filter links
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
        const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'list'});
        if (resultRequest.success) {
            oTablePtw.clear();
            const arrPtwData = resultRequest.result;
            
            arrPtwData.forEach(function(ptw, index) {
                const statusBadge = self.getStatusBadge(ptw.ptwStatus);
                const riskBadge = self.getRiskBadge(ptw.ptwRiskLevel);
                const actionButtons = self.getActionButtons(ptw, index);
                
                oTablePtw.row.add([
                    '', // Row number (auto-generated)
                    ptw.createdDate,
                    ptw.ptwPermitNumber,
                    ptw.ptwPermitDescription,
                    ptw.siteName,
                    ptw.ptwWorkArea,
                    ptw.ptwWorkType,
                    ptw.ptwApplicantName,
                    riskBadge,
                    statusBadge,
                    ptw.ptwValidFrom,
                    ptw.ptwValidTo,
                    ptw.workerCount || 0,
                    ptw.ptwContractorCompany,
                    ptw.createdByName,
                    actionButtons,
                    ptw.pdfId || '',
                    ptw.ptwPermitId,
                    ptw.ptwStatus,
                    ptw.ptwRiskLevel,
                    ptw.createdBy,
                    ptw.approvedSupervisorName,
                    ptw.approvedSheName,
                    ptw.approvedFmName,
                    ptw.approvedSupervisorDate,
                    ptw.approvedSheDate,
                    ptw.approvedFmDate,
                    ptw.createdDate
                ]);
            });
            
            oTablePtw.draw();
            self.updateStatusLinks(arrPtwData);
        } else {
            toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
        }
        HideLoader();
    };

    this.getStatusBadge = function(status) {
        const badges = {
            'DRAFT': '<span class="badge badge-secondary">Draft</span>',
            'PENDING_SUPERVISOR': '<span class="badge badge-warning">Pending Supervisor</span>',
            'PENDING_SHE': '<span class="badge badge-warning">Pending SHE</span>',
            'PENDING_FM': '<span class="badge badge-warning">Pending FM</span>',
            'APPROVED': '<span class="badge badge-info">Approved</span>',
            'ACTIVE': '<span class="badge badge-success">Active</span>',
            'COMPLETED': '<span class="badge badge-dark">Completed</span>',
            'CANCELLED': '<span class="badge badge-danger">Cancelled</span>'
        };
        return badges[status] || '<span class="badge badge-light">' + status + '</span>';
    };

    this.getRiskBadge = function(risk) {
        const badges = {
            'LOW': '<span class="badge badge-success">Low</span>',
            'MEDIUM': '<span class="badge badge-warning">Medium</span>',
            'HIGH': '<span class="badge badge-danger">High</span>',
            'CRITICAL': '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Critical</span>'
        };
        return badges[risk] || '<span class="badge badge-light">' + risk + '</span>';
    };

    this.getActionButtons = function(ptw, index) {
        let buttons = '';
        buttons += '<a href="javascript:void(0);" class="lnkPtwView" id="lnkPtwView_' + index + '" data-toggle="tooltip" title="View Details"><i class="fas fa-eye text-info"></i></a> ';
        
        if (ptw.ptwStatus === 'DRAFT') {
            buttons += '<a href="javascript:void(0);" class="lnkPtwEdit" id="lnkPtwEdit_' + index + '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit text-primary"></i></a> ';
            buttons += '<a href="javascript:void(0);" class="lnkPtwDelete" id="lnkPtwDelete_' + index + '" data-toggle="tooltip" title="Delete"><i class="fas fa-trash text-danger"></i></a> ';
        }
        
        buttons += '<a href="javascript:void(0);" class="lnkPtwPdf" id="lnkPtwPdf_' + index + '" data-toggle="tooltip" title="Generate PDF"><i class="fas fa-file-pdf text-danger"></i></a>';
        
        return buttons;
    };

    this.filterByStatus = function(status) {
        if (status === 'All') {
            oTablePtw.column(18).search('').draw(); // Clear status filter
        } else {
            const statusMap = {
                'Draft': 'DRAFT',
                'PendingSupervisor': 'PENDING_SUPERVISOR',
                'PendingShe': 'PENDING_SHE',
                'PendingFm': 'PENDING_FM',
                'Approved': 'APPROVED',
                'Active': 'ACTIVE',
                'Completed': 'COMPLETED',
                'Cancelled': 'CANCELLED'
            };
            oTablePtw.column(18).search(statusMap[status] || status).draw();
        }
    };

    this.updateStatusLinks = function(data) {
        const statusCounts = {
            'All': data.length,
            'DRAFT': 0,
            'PENDING_SUPERVISOR': 0,
            'PENDING_SHE': 0,
            'PENDING_FM': 0,
            'APPROVED': 0,
            'ACTIVE': 0,
            'COMPLETED': 0,
            'CANCELLED': 0
        };

        data.forEach(function(ptw) {
            if (statusCounts.hasOwnProperty(ptw.ptwStatus)) {
                statusCounts[ptw.ptwStatus]++;
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

    this.loadPtwStatusSummary = function() {
        const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'statistics'});
        if (resultRequest.success) {
            const stats = resultRequest.result;
            // Update any status summary displays if needed
        }
    };

    this.loadPtwChart = function() {
        const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'chart_data'});
        if (resultRequest.success) {
            const chartData = resultRequest.result;
            self.renderPtwChart(chartData);
        }
    };

    this.renderPtwChart = function(data) {
        Highcharts.chart('chartPtwByStatus', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'PTW Permits by Status'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
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

    // Setters
    this.setUserSite = function(value) { userSite = value; };
    this.setRefSite = function(value) { refSite = value; };
    this.setRefUser = function(value) { refUser = value; };
    this.setRefPpmGroup = function(value) { refPpmGroup = value; };
    this.setRefStatus = function(value) { refStatus = value; };
    this.setModalCreatePtw = function(value) { modalCreatePtwClass = value; };
}

function viewPtwDetails(permitId) {
    const resultRequest = mzAjaxRequest('ptw.php', 'GET', {action: 'details', permit_id: permitId});
    if (resultRequest.success) {
        const permit = resultRequest.result;
        let content = `
            <div class="row">
                <div class="col-6"><strong>Permit Number:</strong> ${permit.ptwPermitNumber}</div>
                <div class="col-6"><strong>Status:</strong> ${permit.ptwStatus}</div>
            </div>
            <div class="row mt-2">
                <div class="col-12"><strong>Description:</strong> ${permit.ptwPermitDescription}</div>
            </div>
            <div class="row mt-2">
                <div class="col-6"><strong>Work Area:</strong> ${permit.ptwWorkArea}</div>
                <div class="col-6"><strong>Work Type:</strong> ${permit.ptwWorkType}</div>
            </div>
            <div class="row mt-2">
                <div class="col-6"><strong>Risk Level:</strong> ${permit.ptwRiskLevel}</div>
                <div class="col-6"><strong>Valid From:</strong> ${permit.ptwValidFrom}</div>
            </div>
            <div class="row mt-2">
                <div class="col-6"><strong>Valid To:</strong> ${permit.ptwValidTo}</div>
                <div class="col-6"><strong>Applicant:</strong> ${permit.ptwApplicantName}</div>
            </div>
        `;
        
        if (permit.workers && permit.workers.length > 0) {
            content += '<div class="row mt-3"><div class="col-12"><strong>Workers:</strong></div></div>';
            content += '<div class="table-responsive"><table class="table table-sm">';
            content += '<thead><tr><th>Name</th><th>IC Number</th><th>Company</th></tr></thead><tbody>';
            permit.workers.forEach(function(worker) {
                content += `<tr><td>${worker.workerName}</td><td>${worker.workerIcNumber}</td><td>${worker.workerCompany}</td></tr>`;
            });
            content += '</tbody></table></div>';
        }
        
        // Show in modal or dedicated view
        mzShowAlert('PTW Permit Details', content);
    } else {
        toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
    }
}
