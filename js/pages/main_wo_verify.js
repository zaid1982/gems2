function MainWoVerify () {

    const className = 'MainWoVerify';
    let self = this;
    let oTableWvrPending;
    let oTableWvrSubmitted;
    let sectionWoClass;
    let refStatus;
    let refUser;
    let refSeverity;
    let refWoType = {
        1: 'Client Complaint',
        2: 'Self Finding',
        3: 'Request',
        4: 'Breakdown',
        5: 'Defect',
        6: 'Public Complaint'
    };
    let currentTab;
    let runPending = true;
    let runSubmitted = true;
    let userSite;

    this.init = function () {
        userSite = mzGetUserInfoByParam('siteId');
        currentTab = 'Pending';
        self.showMain(false);

        oTableWvrPending = $('#dtWvrPending').DataTable({
            bLengthChange: false,
            bFilter: false,
            aaSorting: [[7, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 13] },
                { visible: false, targets: [4, 7, 8] },
                { className: 'text-center', targets: [0, 1, 7, 8, 9, 10, 11, 12, 13] },
                { className: 'noVis', targets: [0, 13] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-2 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Assign WO or WR Pending List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0', attr: { id: 'btnWvrPendingRefresh' }, titleAttr: 'Refresh'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnWvrPendingRefresh').off('click').on('click', function () {
                    runPending = true;
                    self.genTablePending();
                });
                $('.lnkWvrPendingEdit').off('click').on('click', function () {
                    const woTaskId = mzGetLinkId($(this), oTableWvrPending, 'woTaskId');
                    sectionWoClass.verify(woTaskId);
                });
                $('.lnkWvrPendingPdf').off('click').on('click', function () {
                    const woTask = mzGetLinkRow($(this), oTableWvrPending);
                    ShowLoader(); setTimeout(function () {
                        try {
                            let pdfId = woTask['pdfId'];
                            if (woTask['pdfId'] === null || woTask['woTaskIsPdf'] === 1) {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf', woTaskId:woTask['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Order Report: '+woTask['woTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
                $('.lnkWvrPendingPdfWr').off('click').on('click', function () {
                    const woTask = mzGetLinkRow($(this), oTableWvrPending);
                    ShowLoader(); setTimeout(function () {
                        try {
                            let pdfId = woTask['pdfIdWr'];
                            if (woTask['pdfIdWr'] === null || woTask['woTaskIsPdfWr'] === 1) {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf_wr', woTaskId:woTask['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Request Report: '+woTask['woTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'woTaskNo'},
                { mData: 'woTaskLocation'},
                { mData: 'woTaskType', mRender: function (data) {
                        return data !== null ? refWoType[data] : '';
                    }},
                { mData: 'woTaskCreatedBy', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'woTaskSeverity', mRender: function (data) {
                        return data !== null ? refSeverity[data]['severityName'] : '';
                    }},
                { mData: 'woTaskFixedBy', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'taskTimeCreated'},
                { mData: 'clientSeverityRespondTime', mRender: function (data) {
                        if (data === null) {
                            return null;
                        } else if (data === 1) {
                            return '1 minute';
                        }
                        return data + ' minutes';
                    }},
                { mData: null, mRender: function (data, type, row) {
                        return mzDurationStr(type, row['woTaskTimeCreated'], row['woTaskTimeAssigned']);
                    }},
                { mData: 'clientSeverityHour', mRender: function (data) {
                        if (data === null) {
                            return null;
                        } else if (data === 1) {
                            return '1 hour';
                        }
                        return data + ' hour';
                    }},
                { mData: null, mRender: function (data, type, row) {
                        return mzDurationStr(type, row[row['woTaskIsWr'] === 1 ? 'woTaskTimeWrVerified' : 'woTaskTimeAssigned'], row['woTaskTimeExecuted']);
                    }},
                { mData: 'woTaskStatus', mRender: function (data) {
                        const statusMap = {
                            'badge-primary': 'pending',
                            'badge-info': 'in-progress',
                            'badge-success': 'completed',
                            'badge-danger': 'cancelled'
                        };
                        const statusClass = statusMap[refStatus[data]['statusColor']] || 'pending';
                        return '<span class="status-badge ' + statusClass + '">' + refStatus[data]['statusDesc'] + '</span>';
                    }},
                { mData: null, bSortable: false, sClass: 'text-center action-cell', mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        if (row['woTaskIsWr'] === 1) {
                            label += '<button type="button" class="btn-action btn-view lnkWvrPendingPdfWr" id="lnkWvrPendingPdfWr_' + meta.row + '" data-toggle="tooltip" title="Work Request PDF">' +
                                     '<i class="far fa-file-alt"></i></button>';
                        }
                        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
                            label += '<button type="button" class="btn-action btn-view lnkWvrPendingPdf" id="lnkWvrPendingPdf_' + meta.row + '" data-toggle="tooltip" title="Work Order PDF">' +
                                     '<i class="far fa-file-pdf"></i></button>';
                        }
                        label += '<button type="button" class="btn-action btn-edit lnkWvrPendingEdit" id="lnkWvrPendingEdit_' + meta.row + '" data-toggle="tooltip" title="Verify">' +
                                 '<i class="fas fa-clipboard-check"></i></button>';
                        label += '</div>';
                        return label;
                    }}
            ]
        });

        oTableWvrSubmitted = $('#dtWvrSubmitted').DataTable({
            bLengthChange: false,
            bFilter: false,
            aaSorting: [[7, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 14] },
                { visible: false, targets: [4, 7, 9, 10] },
                { className: 'text-center', targets: [0, 1, 7, 8, 9, 10, 11, 12, 13, 14] },
                { className: 'noVis', targets: [0, 14] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Assign WO or WR Submitted List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Assign WO or WR Submitted List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Assign WO or WR Submitted List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-2 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Assign WO or WR Submitted List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0', attr: { id: 'btnWvrSubmittedRefresh' }, titleAttr: 'Refresh'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnWvrSubmittedRefresh').off('click').on('click', function () {
                    runSubmitted = true;
                    self.genTableSubmitted();
                });
                $('.lnkWvrSubmittedInfo').off('click').on('click', function () {
                    const woTaskId = mzGetLinkId($(this), oTableWvrSubmitted, 'woTaskId');
                    sectionWoClass.view(woTaskId);
                });
                $('.lnkWvrSubmittedPdf').off('click').on('click', function () {
                    const woTask = mzGetLinkRow($(this), oTableWvrSubmitted);
                    ShowLoader(); setTimeout(function () {
                        try {
                            let pdfId = woTask['pdfId'];
                            if (woTask['pdfId'] === null || woTask['woTaskIsPdf'] === 1) {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf', woTaskId:woTask['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Order Report: '+woTask['woTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
                $('.lnkWvrSubmittedPdfWr').off('click').on('click', function () {
                    const woTask = mzGetLinkRow($(this), oTableWvrSubmitted);
                    ShowLoader(); setTimeout(function () {
                        try {
                            let pdfId = woTask['pdfIdWr'];
                            if (woTask['pdfIdWr'] === null || woTask['woTaskIsPdfWr'] === 1) {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf_wr', woTaskId:woTask['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Request Report: '+woTask['woTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'woTaskNo'},
                { mData: 'woTaskLocation'},
                { mData: 'woTaskType', mRender: function (data) {
                        return data !== null ? refWoType[data] : '';
                    }},
                { mData: 'woTaskCreatedBy', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'woTaskSeverity', mRender: function (data) {
                        return data !== null ? refSeverity[data]['severityName'] : '';
                    }},
                { mData: 'woTaskFixedBy', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'taskTimeCreated'},
                { mData: 'taskTimeSubmit'},
                { mData: 'clientSeverityRespondTime', mRender: function (data) {
                        if (data === null) {
                            return null;
                        } else if (data === 1) {
                            return '1 minute';
                        }
                        return data + ' minutes';
                    }},
                { mData: null, mRender: function (data, type, row) {
                        return mzDurationStr(type, row['woTaskTimeCreated'], row['woTaskTimeAssigned']);
                    }},
                { mData: 'clientSeverityHour', mRender: function (data) {
                        if (data === null) {
                            return null;
                        } else if (data === 1) {
                            return '1 hour';
                        }
                        return data + ' hour';
                    }},
                { mData: null, mRender: function (data, type, row) {
                        return mzDurationStr(type, row[row['woTaskIsWr'] === 1 ? 'woTaskTimeWrVerified' : 'woTaskTimeAssigned'], row['woTaskTimeExecuted']);
                    }},
                { mData: 'woTaskStatus', mRender: function (data) {
                        const statusMap = {
                            'badge-primary': 'pending',
                            'badge-info': 'in-progress',
                            'badge-success': 'completed',
                            'badge-danger': 'cancelled'
                        };
                        const statusClass = statusMap[refStatus[data]['statusColor']] || 'pending';
                        return '<span class="status-badge ' + statusClass + '">' + refStatus[data]['statusDesc'] + '</span>';
                    }},
                { mData: null, bSortable: false, sClass: 'text-center action-cell', mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        if (row['woTaskIsWr'] === 1) {
                            label += '<button type="button" class="btn-action btn-view lnkWvrSubmittedPdfWr" id="lnkWvrSubmittedPdfWr_' + meta.row + '" data-toggle="tooltip" title="Work Request PDF">' +
                                     '<i class="far fa-file-alt"></i></button>';
                        }
                        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
                            label += '<button type="button" class="btn-action btn-view lnkWvrSubmittedPdf" id="lnkWvrSubmittedPdf_' + meta.row + '" data-toggle="tooltip" title="Work Order PDF">' +
                                     '<i class="far fa-file-pdf"></i></button>';
                        }
                        label += '<button type="button" class="btn-action btn-view lnkWvrSubmittedInfo" id="lnkWvrSubmittedInfo_' + meta.row + '" data-toggle="tooltip" title="View Details">' +
                                 '<i class="fas fa-info-circle"></i></button>';
                        label += '</div>';
                        return label;
                    }}
            ]
        });

        oTableWvrPending.buttons().container().appendTo('#btnWvrPendingExport');
        oTableWvrSubmitted.buttons().container().appendTo('#btnWvrSubmittedExport');

        $('#btnWvrPending').on('click', function () {
            $('.sectionWvrTask').hide();
            $('.sectionWvrPending').show();
            $('.btnWvrTab').removeClass('lighten-2').addClass('lighten-2');
            $('#btnWvrPending').removeClass('lighten-2');
            self.genTablePending();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        $('#btnWvrSubmitted').on('click', function () {
            $('.sectionWvrTask').hide();
            $('.sectionWvrSubmitted').show();
            $('.btnWvrTab').removeClass('lighten-2').addClass('lighten-2');
            $('#btnWvrSubmitted').removeClass('lighten-2');
            self.genTableSubmitted();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        $('#txtWvrSearch').on('input', function () {
            const term = $(this).val();
            oTableWvrPending.search(term).draw();
            oTableWvrSubmitted.search(term).draw();
        });
    };

    this.genTablePending = function () {
        if (runPending) {
            ShowLoader(); setTimeout(function () { 
                const apiUrl = !mzIsRoleExist('1,10') ? `wo_v3/pending_verify/site/${userSite}` : 'wo_v3/pending_verify';
                mzFetch(apiUrl).then(res => {
                    $('#badgeWvrTotalPending').text(res.length);
                    oTableWvrPending.clear().rows.add(res).draw();
                    runPending = false;
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); 
            }, 200);
        }
    };

    this.genTableSubmitted = function () {
        if (runSubmitted) {
            ShowLoader(); setTimeout(function () { 
                const apiUrl = !mzIsRoleExist('1,10') ? `wo_v3/submitted_verify/site/${userSite}` : 'wo_v3/submitted_verify';
                mzFetch(apiUrl).then(res => {
                    $('#badgeWvrTotalSubmitted').text(res.length);
                    oTableWvrSubmitted.clear().rows.add(res).draw();
                    runSubmitted = false;
                    currentTab = 'Submitted';
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); 
            }, 200);
        }
    };

    this.assignTotalSubmitted = function () {
        ShowLoader(); setTimeout(function () { 
            const apiUrl = !mzIsRoleExist('1,10') ? `wo_v3/submitted_verify_total/site/${userSite}` : 'wo_v3/submitted_verify_total';
            mzFetch(apiUrl).then(res => {
                $('#badgeWvrTotalSubmitted').text(res);
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); 
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function (_runPending) {
        $('.sectionWvrMain').show();
        $('.sectionWvrTask').hide();
        $('.sectionWvr'+currentTab).show();
        if (_runPending) {
            runPending = true;
            runSubmitted = true;
            self.genTablePending();
        }
    };

    this.hideMain = function () {
        $('.sectionWvrMain').hide();
    };

    this.setSectionWoClass = function (_sectionWoClass) {
        sectionWoClass = _sectionWoClass;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };
}