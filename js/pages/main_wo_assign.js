function MainWoAssign () {

    const className = 'MainWoAssign';
    let self = this;
    let oTableWssPending;
    let oTableWssSubmitted;
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

    function setWssTab(activeId) {
        $('.btnWssTab').removeClass('btn-primary').addClass('btn-outline-secondary');
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
            label += GemsUI.actionBtn({id: 'lnkWssPendingPdfWr_' + meta.row, cls: 'lnkWssPendingPdfWr', icon: 'far fa-file-alt', title: 'Work Request PDF'});
        }
        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
            label += GemsUI.actionBtn({id: 'lnkWssPendingPdf_' + meta.row, cls: 'lnkWssPendingPdf', icon: 'far fa-file-pdf', title: 'Work Order PDF'});
        }
        label += GemsUI.actionBtn({id: 'lnkWssPendingEdit_' + meta.row, cls: 'lnkWssPendingEdit', icon: 'fas fa-user-check', title: 'Assign'});
        return label;
    }

    function submittedActions(row, meta) {
        let label = '';
        if (row['woTaskIsWr'] === 1) {
            label += GemsUI.actionBtn({id: 'lnkWssSubmittedPdfWr_' + meta.row, cls: 'lnkWssSubmittedPdfWr', icon: 'far fa-file-alt', title: 'Work Request PDF'});
        }
        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
            label += GemsUI.actionBtn({id: 'lnkWssSubmittedPdf_' + meta.row, cls: 'lnkWssSubmittedPdf', icon: 'far fa-file-pdf', title: 'Work Order PDF'});
        }
        label += GemsUI.actionBtn({id: 'lnkWssSubmittedInfo_' + meta.row, cls: 'lnkWssSubmittedInfo', icon: 'fas fa-info-circle', title: 'View Details'});
        return label;
    }

    this.init = function () {
        userSite = mzGetUserInfoByParam('siteId');
        currentTab = 'Pending';
        self.showMain(false);

        oTableWssPending = $('#dtWssPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[5, 'desc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-tasks', 'No pending assignments.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { bSortable: false, targets: [0, 7] },
                { className: 'text-center', targets: [0, 1, 5, 6, 7] },
                { className: 'noVis', targets: [0, 7] }
            ],
            buttons: GemsUI.dtButtons('GEMS - Assign WO or WR Pending List').concat([
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-secondary btn-sm', attr: { id: 'btnWssPendingRefresh', 'aria-label': 'Refresh data' }, titleAttr: 'Refresh'}
            ]),
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('#btnWssPendingRefresh').off('click').on('click', function () {
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
                        const name = (data !== null && refUser && refUser[data]) ? refUser[data]['userFirstName'] : (data ? 'User ID: ' + data : '');
                        return displayText(name, type);
                    }},
                { mData: 'taskTimeCreated'},
                { mData: 'woTaskStatus', mRender: function (data, type) {
                        return statusBadge(data, type);
                    }},
                { mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                        if (type !== 'display') { return ''; }
                        return pendingActions(row, meta);
                    }}
            ]
        });

        oTableWssSubmitted = $('#dtWssSubmitted').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[7, 'desc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-check', 'No submitted assignments.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { bSortable: false, targets: [0, 11] },
                { visible: false, targets: [6] },
                { className: 'text-center', targets: [0, 1, 6, 7, 8, 9, 10, 11] },
                { className: 'noVis', targets: [0, 11] }
            ],
            buttons: GemsUI.dtButtons('GEMS - Assign WO or WR Submitted List').concat([
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-secondary btn-sm', attr: { id: 'btnWssSubmittedRefresh', 'aria-label': 'Refresh data' }, titleAttr: 'Refresh'}
            ]),
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('#btnWssSubmittedRefresh').off('click').on('click', function () {
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
                        const name = (data !== null && refUser && refUser[data]) ? refUser[data]['userFirstName'] : (data ? 'User ID: ' + data : '');
                        return displayText(name, type);
                    }},
                { mData: 'woTaskSeverity', mRender: function (data, type) {
                        return displayText(data !== null ? refSeverity[data]['severityName'] : '', type);
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
                        return mzDurationStr(type, row['taskTimeCreated'], row['taskTimeSubmit']);
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

        oTableWssPending.buttons().container().appendTo('#btnWssPendingExport');
        oTableWssSubmitted.buttons().container().appendTo('#btnWssSubmittedExport');
        GemsUI.bindDtTooltips('#dtWssPending');
        GemsUI.bindDtTooltips('#dtWssSubmitted');

        $('#dtWssPending').on('click', '.lnkWssPendingEdit', function () {
            const woTaskId = mzGetLinkId($(this), oTableWssPending, 'woTaskId');
            sectionWoClass.assign(woTaskId);
        });
        $('#dtWssPending').on('click', '.lnkWssPendingPdf', function () {
            openPdf(mzGetLinkRow($(this), oTableWssPending), mzGetLinkRow($(this), oTableWssPending)['pdfId'], false);
        });
        $('#dtWssPending').on('click', '.lnkWssPendingPdfWr', function () {
            const woTask = mzGetLinkRow($(this), oTableWssPending);
            openPdf(woTask, woTask['pdfIdWr'], true);
        });
        $('#dtWssSubmitted').on('click', '.lnkWssSubmittedInfo', function () {
            const woTaskId = mzGetLinkId($(this), oTableWssSubmitted, 'woTaskId');
            sectionWoClass.view(woTaskId);
        });
        $('#dtWssSubmitted').on('click', '.lnkWssSubmittedPdf', function () {
            const woTask = mzGetLinkRow($(this), oTableWssSubmitted);
            openPdf(woTask, woTask['pdfId'], false);
        });
        $('#dtWssSubmitted').on('click', '.lnkWssSubmittedPdfWr', function () {
            const woTask = mzGetLinkRow($(this), oTableWssSubmitted);
            openPdf(woTask, woTask['pdfIdWr'], true);
        });

        $('#btnWssPending').on('click', function () {
            $('.sectionWssTask').hide();
            $('.sectionWssPending').show();
            setWssTab('btnWssPending');
            currentTab = 'Pending';
            self.genTablePending();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        $('#btnWssSubmitted').on('click', function () {
            $('.sectionWssTask').hide();
            $('.sectionWssSubmitted').show();
            setWssTab('btnWssSubmitted');
            currentTab = 'Submitted';
            self.genTableSubmitted();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        $('#txtWssSearch').on('input', function () {
            const term = $(this).val();
            oTableWssPending.search(term).draw();
            oTableWssSubmitted.search(term).draw();
        });
    };

    this.genTablePending = function () {
        if (runPending) {
            ShowLoader(); setTimeout(function () {
                const apiUrl = !mzIsRoleExist('1,10') ? `wo_v3/pending_assign/site/${userSite}` : 'wo_v3/pending_assign';
                mzFetch(apiUrl).then(res => {
                    $('#badgeWssTotalPending').text(res.length);

                    if (!refUser || Object.keys(refUser).length === 0) {
                        console.warn('refUser is empty, attempting to refresh reference data');
                        self.refreshRefData();
                    }

                    oTableWssPending.clear().rows.add(res).draw();
                    runPending = false;
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);
        }
    };

    this.genTableSubmitted = function () {
        if (runSubmitted) {
            ShowLoader(); setTimeout(function () {
                const apiUrl = !mzIsRoleExist('1,10') ? `wo_v3/submitted_assign/site/${userSite}` : 'wo_v3/submitted_assign';
                mzFetch(apiUrl).then(res => {
                    $('#badgeWssTotalSubmitted').text(res.length);
                    oTableWssSubmitted.clear().rows.add(res).draw();
                    runSubmitted = false;
                    currentTab = 'Submitted';
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
        }
    };

    this.assignTotalSubmitted = function () {
        ShowLoader(); setTimeout(function () { mzFetch('wo_v3/submitted_assign_total').then(res => {
            $('#badgeWssTotalSubmitted').text(res);
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function (_runPending) {
        $('.sectionWssMain').show();
        $('.sectionWssTask').hide();
        $('.sectionWss'+currentTab).show();
        if (_runPending) {
            runPending = true;
            runSubmitted = true;
            self.genTablePending();
        }
    };

    this.hideMain = function () {
        $('.sectionWssMain').hide();
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

    this.refreshRefData = function () {
        try {
            const versionLocal = mzGetDataVersion();
            const refUser_ = mzGetLocalArrayV2('gems_user2', versionLocal, 'user/ref');
            const refStatus_ = mzGetLocalArrayV2('gems_status2', versionLocal, 'status/ref');
            const refSeverity_ = mzGetLocalArray('gems_severity', versionLocal, 'severityId', [], 'severity');

            refUser = refUser_;
            refStatus = refStatus_;
            refSeverity = refSeverity_;

            if (oTableWssPending) {
                oTableWssPending.draw();
            }
            if (oTableWssSubmitted) {
                oTableWssSubmitted.draw();
            }

            console.log('Reference data refreshed successfully');
            return true;
        } catch (e) {
            console.error('Failed to refresh reference data:', e.message);
            toastr['error']('Failed to refresh reference data: ' + e.message, _ALERT_TITLE_ERROR);
            return false;
        }
    };

    this.debugRefData = function () {
        console.log('=== Debug Reference Data ===');
        console.log('refUser:', refUser);
        console.log('refStatus:', refStatus);
        console.log('refSeverity:', refSeverity);
        console.log('refUser keys count:', refUser ? Object.keys(refUser).length : 'null/undefined');

        const testUserIds = [1, 1388, 1389];
        testUserIds.forEach(userId => {
            if (refUser && refUser[userId]) {
                console.log(`User ${userId}:`, refUser[userId]);
            } else {
                console.log(`User ${userId}: NOT FOUND`);
            }
        });

        return {
            refUser: refUser,
            refStatus: refStatus,
            refSeverity: refSeverity,
            userCount: refUser ? Object.keys(refUser).length : 0
        };
    };
}
