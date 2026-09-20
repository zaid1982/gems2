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

    const statusColorMap = {
        'badge-primary': 'info',
        'badge-info': 'info',
        'badge-success': 'success',
        'badge-danger': 'danger',
        'badge-warning': 'warning',
        'badge-secondary': 'secondary'
    };

    function displayText(data, type) {
        if (type === 'display') {
            return GemsUI.escape(data || '');
        }
        return data || '';
    }

    function statusBadge(statusId, type) {
        const rec = refStatus && refStatus[statusId];
        const label = rec ? rec['statusDesc'] : String(statusId);
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusColorMap[rec && rec['statusColor']] || 'secondary', GemsUI.escape(label));
    }

    function setWvrTab(activeId) {
        $('.btnWvrTab').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('#' + activeId).removeClass('btn-outline-secondary').addClass('btn-primary');
    }

    function openPdf(woTask, pdfId, isWr) {
        ShowLoader(); setTimeout(function () {
            try {
                if (pdfId === null || (isWr ? woTask['woTaskIsPdfWr'] === 1 : woTask['woTaskIsPdf'] === 1)) {
                    const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: isWr ? 'generate_pdf_wr' : 'generate_pdf', woTaskId: woTask['woTaskId']});
                    pdfId = resultRequest['pdfId'];
                }
                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                const title = isWr ? 'Work Request Report: ' : 'Work Order Report: ';
                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;' + title + woTask['woTaskNo']);
                $('#mpdf_iframe').attr('src', pdfSrc);
                $('#modal_pdf').modal('show');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    }

    function pendingActions(row, meta) {
        let label = '';
        if (row['woTaskIsWr'] === 1) {
            label += GemsUI.actionBtn({id: 'lnkWvrPendingPdfWr_' + meta.row, cls: 'lnkWvrPendingPdfWr', icon: 'far fa-file-alt', title: 'Work Request PDF'});
        }
        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
            label += GemsUI.actionBtn({id: 'lnkWvrPendingPdf_' + meta.row, cls: 'lnkWvrPendingPdf', icon: 'far fa-file-pdf', title: 'Work Order PDF'});
        }
        label += GemsUI.actionBtn({id: 'lnkWvrPendingEdit_' + meta.row, cls: 'lnkWvrPendingEdit', icon: 'fas fa-clipboard-check', title: 'Verify'});
        return label;
    }

    function submittedActions(row, meta) {
        let label = '';
        if (row['woTaskIsWr'] === 1) {
            label += GemsUI.actionBtn({id: 'lnkWvrSubmittedPdfWr_' + meta.row, cls: 'lnkWvrSubmittedPdfWr', icon: 'far fa-file-alt', title: 'Work Request PDF'});
        }
        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
            label += GemsUI.actionBtn({id: 'lnkWvrSubmittedPdf_' + meta.row, cls: 'lnkWvrSubmittedPdf', icon: 'far fa-file-pdf', title: 'Work Order PDF'});
        }
        label += GemsUI.actionBtn({id: 'lnkWvrSubmittedInfo_' + meta.row, cls: 'lnkWvrSubmittedInfo', icon: 'fas fa-info-circle', title: 'View Details'});
        return label;
    }

    this.init = function () {
        userSite = mzGetUserInfoByParam('siteId');
        currentTab = 'Pending';
        self.showMain(false);

        oTableWvrPending = $('#dtWvrPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[7, 'desc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-clipboard-list', 'No pending verifications.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { bSortable: false, targets: [0, 13] },
                { visible: false, targets: [4, 7, 8] },
                { className: 'text-center', targets: [0, 1, 7, 8, 9, 10, 11, 12, 13] },
                { className: 'noVis', targets: [0, 13] }
            ],
            buttons: GemsUI.dtButtons('GEMS - Assign WO or WR Pending List').concat([
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-secondary btn-sm', attr: { id: 'btnWvrPendingRefresh', 'aria-label': 'Refresh data' }, titleAttr: 'Refresh'}
            ]),
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('#btnWvrPendingRefresh').off('click').on('click', function () {
                    runPending = true;
                    self.genTablePending();
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'woTaskNo', mRender: function (data, type) { return displayText(data, type); }},
                { mData: 'woTaskLocation', mRender: function (data, type) { return displayText(data, type); }},
                { mData: 'woTaskType', mRender: function (data, type) {
                        return displayText(data !== null ? refWoType[data] : '', type);
                    }},
                { mData: 'woTaskCreatedBy', mRender: function (data, type) {
                        return displayText(data !== null ? refUser[data]['userFirstName'] : '', type);
                    }},
                { mData: 'woTaskSeverity', mRender: function (data, type) {
                        return displayText(data !== null ? refSeverity[data]['severityName'] : '', type);
                    }},
                { mData: 'woTaskFixedBy', mRender: function (data, type) {
                        return displayText(data !== null ? refUser[data]['userFirstName'] : '', type);
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
                { mData: 'woTaskStatus', mRender: function (data, type) {
                        return statusBadge(data, type);
                    }},
                { mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        if (type !== 'display') { return ''; }
                        return pendingActions(row, meta);
                    }}
            ]
        });

        oTableWvrSubmitted = $('#dtWvrSubmitted').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[7, 'desc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-check', 'No submitted verifications.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { bSortable: false, targets: [0, 14] },
                { visible: false, targets: [4, 7, 9, 10] },
                { className: 'text-center', targets: [0, 1, 7, 8, 9, 10, 11, 12, 13, 14] },
                { className: 'noVis', targets: [0, 14] }
            ],
            buttons: GemsUI.dtButtons('GEMS - Assign WO or WR Submitted List').concat([
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-secondary btn-sm', attr: { id: 'btnWvrSubmittedRefresh', 'aria-label': 'Refresh data' }, titleAttr: 'Refresh'}
            ]),
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('#btnWvrSubmittedRefresh').off('click').on('click', function () {
                    runSubmitted = true;
                    self.genTableSubmitted();
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'woTaskNo', mRender: function (data, type) { return displayText(data, type); }},
                { mData: 'woTaskLocation', mRender: function (data, type) { return displayText(data, type); }},
                { mData: 'woTaskType', mRender: function (data, type) {
                        return displayText(data !== null ? refWoType[data] : '', type);
                    }},
                { mData: 'woTaskCreatedBy', mRender: function (data, type) {
                        return displayText(data !== null ? refUser[data]['userFirstName'] : '', type);
                    }},
                { mData: 'woTaskSeverity', mRender: function (data, type) {
                        return displayText(data !== null ? refSeverity[data]['severityName'] : '', type);
                    }},
                { mData: 'woTaskFixedBy', mRender: function (data, type) {
                        return displayText(data !== null ? refUser[data]['userFirstName'] : '', type);
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
                { mData: 'woTaskStatus', mRender: function (data, type) {
                        return statusBadge(data, type);
                    }},
                { mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        if (type !== 'display') { return ''; }
                        return submittedActions(row, meta);
                    }}
            ]
        });

        oTableWvrPending.buttons().container().appendTo('#btnWvrPendingExport');
        oTableWvrSubmitted.buttons().container().appendTo('#btnWvrSubmittedExport');
        GemsUI.bindDtTooltips('#dtWvrPending');
        GemsUI.bindDtTooltips('#dtWvrSubmitted');

        $('#dtWvrPending').on('click', '.lnkWvrPendingEdit', function () {
            const woTaskId = mzGetLinkId($(this), oTableWvrPending, 'woTaskId');
            sectionWoClass.verify(woTaskId);
        });
        $('#dtWvrPending').on('click', '.lnkWvrPendingPdf', function () {
            const woTask = mzGetLinkRow($(this), oTableWvrPending);
            openPdf(woTask, woTask['pdfId'], false);
        });
        $('#dtWvrPending').on('click', '.lnkWvrPendingPdfWr', function () {
            const woTask = mzGetLinkRow($(this), oTableWvrPending);
            openPdf(woTask, woTask['pdfIdWr'], true);
        });
        $('#dtWvrSubmitted').on('click', '.lnkWvrSubmittedInfo', function () {
            const woTaskId = mzGetLinkId($(this), oTableWvrSubmitted, 'woTaskId');
            sectionWoClass.view(woTaskId);
        });
        $('#dtWvrSubmitted').on('click', '.lnkWvrSubmittedPdf', function () {
            const woTask = mzGetLinkRow($(this), oTableWvrSubmitted);
            openPdf(woTask, woTask['pdfId'], false);
        });
        $('#dtWvrSubmitted').on('click', '.lnkWvrSubmittedPdfWr', function () {
            const woTask = mzGetLinkRow($(this), oTableWvrSubmitted);
            openPdf(woTask, woTask['pdfIdWr'], true);
        });

        $('#btnWvrPending').on('click', function () {
            $('.sectionWvrTask').hide();
            $('.sectionWvrPending').show();
            setWvrTab('btnWvrPending');
            self.genTablePending();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        $('#btnWvrSubmitted').on('click', function () {
            $('.sectionWvrTask').hide();
            $('.sectionWvrSubmitted').show();
            setWvrTab('btnWvrSubmitted');
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
