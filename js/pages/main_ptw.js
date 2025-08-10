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
                                    console.log('PTW PDF generation failed:', resultRequest.error);
                                    toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
                                }
                            } catch (e) {
                                console.log('PTW PDF generation error caught:', e.message, e);
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
        console.log('DEBUG: refreshPtwData called');
        ShowLoader();
        
        try {
            console.log('DEBUG: About to call mzAjaxRequest for PTW data...');
            const arrPtwData = mzAjaxRequest('ptw.php', 'GET', {action: 'list'});
            console.log('DEBUG: PTW API returned data directly:', arrPtwData);
            console.log('DEBUG: Data type:', typeof arrPtwData);
            console.log('DEBUG: Is array?', Array.isArray(arrPtwData));
            console.log('DEBUG: Length:', arrPtwData ? arrPtwData.length : 'N/A');
            
            // Show what we got
            alert('PTW API Direct Response: ' + JSON.stringify({
                dataType: typeof arrPtwData,
                isArray: Array.isArray(arrPtwData),
                length: arrPtwData ? arrPtwData.length : 'N/A',
                firstRecord: arrPtwData && arrPtwData.length > 0 ? JSON.stringify(arrPtwData[0]) : 'None'
            }, null, 2));
            
            console.log('DEBUG: PTW API call successful - clearing DataTable...');
            oTablePtw.clear();
            console.log('DEBUG: DataTable cleared');
            
            // Try adding a single minimal test row first
            try {
                console.log('DEBUG: Adding minimal test row...');
                const testRow = ['1', 'Test Date', 'TEST-001', 'Test Description', 'Test Site', 'Test Area', 'Test Type', 'Test Applicant', 'Low', 'Active', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
                console.log('DEBUG: Test row data:', testRow);
                console.log('DEBUG: Test row length:', testRow.length);
                oTablePtw.row.add(testRow);
                console.log('DEBUG: Test row added successfully');
            } catch (testError) {
                console.error('DEBUG: Error adding test row:', testError);
            }
            
            let rowsAdded = 0;
            
            console.log('DEBUG: Checking PTW data conditions...');
            console.log('DEBUG: arrPtwData exists?', !!arrPtwData);
            console.log('DEBUG: arrPtwData is array?', Array.isArray(arrPtwData));
            console.log('DEBUG: arrPtwData length > 0?', arrPtwData && arrPtwData.length > 0);
            console.log('DEBUG: arrPtwData actual length:', arrPtwData ? arrPtwData.length : 'N/A');
            
            if (arrPtwData && Array.isArray(arrPtwData) && arrPtwData.length > 0) {
                console.log('DEBUG: ✅ PTW data conditions met - Processing', arrPtwData.length, 'PTW records');
                console.log('DEBUG: Full PTW array:', arrPtwData);
                
                // Just try the first record for now
                const firstPtw = arrPtwData[0];
                console.log('DEBUG: Processing first PTW record:', firstPtw);
                console.log('DEBUG: First PTW record keys:', Object.keys(firstPtw || {}));
                
                try {
                    const rowData = [
                        '2', // Row number
                        firstPtw.created_date || '',
                        firstPtw.ptw_permit_number || '',
                        firstPtw.ptw_permit_description || '',
                        'Site Name', // Site name - simplified
                        firstPtw.ptw_work_area || '',
                        firstPtw.ptw_work_type || '',
                        firstPtw.ptw_applicant_name || '',
                        firstPtw.ptw_risk_level || '',
                        firstPtw.ptw_status || '',
                        firstPtw.ptw_valid_from || '',
                        firstPtw.ptw_valid_to || '',
                        '0', // Worker count
                        firstPtw.ptw_contractor_company || '',
                        'Creator Name', // Created by name
                        '<i class="fas fa-eye"></i>', // Action buttons
                        '', // PDF ID
                        firstPtw.ptw_permit_id || '',
                        firstPtw.ptw_status || '',
                        firstPtw.ptw_risk_level || '',
                        firstPtw.created_by || '',
                        '', // Approved supervisor
                        '', // Approved SHE
                        '', // Approved FM
                        firstPtw.approved_supervisor_date || '',
                        firstPtw.approved_she_date || '',
                        firstPtw.approved_fm_date || '',
                        firstPtw.created_date || ''
                    ];
                    
                    console.log('DEBUG: Adding first record row data:', rowData);
                    console.log('DEBUG: Row data length:', rowData.length);
                    oTablePtw.row.add(rowData);
                    rowsAdded++;
                    console.log('DEBUG: First record added successfully');
                    
                } catch (e) {
                    console.error('DEBUG: Error processing first PTW record:', e);
                    console.error('DEBUG: Record data:', firstPtw);
                }
            } else {
                console.log('DEBUG: ❌ PTW data conditions NOT met');
                console.log('DEBUG: arrPtwData exists?', !!arrPtwData);
                console.log('DEBUG: arrPtwData is array?', Array.isArray(arrPtwData));
                console.log('DEBUG: arrPtwData length:', arrPtwData ? arrPtwData.length : 'N/A');
                console.log('DEBUG: arrPtwData value:', arrPtwData);
                console.log('DEBUG: typeof arrPtwData:', typeof arrPtwData);
                
                if (arrPtwData && !Array.isArray(arrPtwData)) {
                    console.log('DEBUG: arrPtwData is not an array, trying to convert...');
                    if (typeof arrPtwData === 'object') {
                        console.log('DEBUG: arrPtwData object keys:', Object.keys(arrPtwData));
                    }
                }
            }
            
            console.log('DEBUG: About to draw DataTable...');
            console.log('DEBUG: DataTable rows count before draw:', oTablePtw.rows().count());
            oTablePtw.draw();
            console.log('DEBUG: DataTable draw completed');
            console.log('DEBUG: DataTable rows count after draw:', oTablePtw.rows().count());
            
            console.log('DEBUG: About to update status links...');
            self.updateStatusLinks(arrPtwData || []);
            console.log('DEBUG: Status links updated');
            console.log('DEBUG: Table draw completed');
            
        } catch (error) {
            console.log('DEBUG: PTW API call failed with error:', error);
            console.log('DEBUG: Error message:', error.message);
            alert('PTW API Error: ' + error.message);
            toastr['error'](error.message, _ALERT_TITLE_ERROR);
        }
        
        HideLoader();
        console.log('DEBUG: refreshPtwData completed');
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
        
        if (ptw.ptw_status === 'DRAFT') {
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
            console.log('PTW delete failed:', resultRequest.error);
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
        console.log('PTW details view failed:', resultRequest.error);
        toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
    }
}
