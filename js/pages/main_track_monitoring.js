function MainTrackMonitoring() {

    const className = 'MainTrackMonitoring';
    const columnLabels = ['#', 'Task No', 'Flow', 'Checkpoint', 'User', 'Role', 'Created On', 'Status', 'Details'];

    const self = this;
    let refStatus;
    let refRole;
    let refUser;
    let refFlow;
    let refCheckpoint;
    let refSite;
    let oTableTrack;
    let sectionTaskHistoryClass;
    let siteCode;
    let flowId;
    let yearId;
    let userSite;

    const formatNumber = function (value) {
        if (typeof mzFormatNumber === 'function') {
            return mzFormatNumber(value || 0);
        }
        const numeric = Number(value) || 0;
        return numeric.toLocaleString();
    };

    const classifyStatusColor = function (colorClass) {
        if (!colorClass) {
            return '';
        }
        const normalized = colorClass.toLowerCase();
        if (normalized.indexOf('success') > -1 || normalized.indexOf('green') > -1) {
            return 'success';
        }
        if (normalized.indexOf('warning') > -1 || normalized.indexOf('orange') > -1 || normalized.indexOf('amber') > -1) {
            return 'warning';
        }
        if (normalized.indexOf('danger') > -1 || normalized.indexOf('red') > -1 || normalized.indexOf('error') > -1) {
            return 'danger';
        }
        return '';
    };

    this.init = function () {
        flowId = '1';
        const dateCtr = new Date();
        yearId = dateCtr.getFullYear();
        userSite = mzGetUserInfoByParam('siteId');

        const arrYear = [];
        const dt = new Date();
        for (let year = 2019; year <= dt.getFullYear() + 3; year++) {
            arrYear.push({ yearId: year.toString(), yearDesc: year.toString() });
        }

        mzOption('optTnmYear', arrYear, 'Select Year', 'yearId', 'yearDesc', {}, 'required');
        mzOption('optTnmSiteCode', refSite, 'Select Site', 'siteCode', 'siteName', {}, 'required');
        mzOption('optTnmFlowId', refFlow, 'Select Flow', 'flowId', 'flowDesc', {}, 'required');
        mzOption('optTnmCheckpointId', refCheckpoint, 'All Checkpoint', 'checkpointId', 'checkpointDesc', { flowId: flowId });

        if (mzIsRoleExist('1,10')) {
            siteCode = 'BNMDC';
        } else {
            siteCode = refSite[userSite]['siteCode'];
            $('#optTnmSiteCode').prop('disabled', true);
        }

        $('#optTnmYear').val(yearId);
        $('#optTnmSiteCode').val(siteCode);
        $('#optTnmFlowId').val(flowId);

        oTableTrack = $('#dtTnmList').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [6, 'asc'],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableTrack.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
                $('td', nRow).each(function (index) {
                    const label = columnLabels[index];
                    if (label) {
                        $(this).attr('data-label', label);
                    } else {
                        $(this).removeAttr('data-label');
                    }
                });
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkTnmListView').off('click').on('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex + 1);
                        const currentRow = oTableTrack.row(parseInt(rowId, 10)).data();
                        sectionTaskHistoryClass.setTaskDetails(currentRow);
                        sectionTaskHistoryClass.view(currentRow['transactionId']);
                    }
                });

                if (oTableTrack && typeof oTableTrack.rows === 'function') {
                    const appliedData = oTableTrack.rows({ search: 'applied' }).data();
                    const dataSet = appliedData && typeof appliedData.toArray === 'function' ? appliedData.toArray() : [];
                    self.updateMetrics(dataSet);
                } else {
                    self.updateMetrics([]);
                }

                self.updateResultsSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns: [
                { mData: null, bSortable: false },
                { mData: 'transactionNo' },
                {
                    mData: null,
                    mRender: function (data, type, row) {
                        return row['flowId'] !== '' ? refFlow[row['flowId']]['flowDesc'] : '';
                    }
                },
                {
                    mData: null,
                    mRender: function (data, type, row) {
                        return row['checkpointId'] !== '' ? refCheckpoint[row['checkpointId']]['checkpointDesc'] : '';
                    }
                },
                {
                    mData: 'userId',
                    mRender: function (data) {
                        return data !== '' ? refUser[data]['userFullName'] : '';
                    }
                },
                {
                    mData: 'roleId',
                    mRender: function (data) {
                        return data !== '' ? refRole[data]['roleDesc'] : '';
                    }
                },
                { mData: 'taskTimeCreated' },
                {
                    mData: null,
                    mRender: function (data, type, row) {
                        const statusId = row['transactionStatus'];
                        if (!statusId || !refStatus || !refStatus[statusId]) {
                            return '';
                        }
                        const statusMeta = refStatus[statusId];
                        return '<h6><span class="badge badge-pill ' + statusMeta['statusColor'] + ' z-depth-2">' + statusMeta['statusDesc'] + '</span></h6>';
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        label += '<button type="button" class="btn-action btn-view lnkTnmListView" id="lnkTnmListView_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Details"><i class="fas fa-info-circle"></i></button>';
                        label += '</div>';
                        return label;
                    }
                },
                { mData: 'flowId', visible: false },
                { mData: 'checkpointId', visible: false }
            ]
        });

        $('#dtTnmList tbody').on('click', 'tr', function () {
            const rowData = oTableTrack.row(this).data();
            if (!rowData) {
                return;
            }
            sectionTaskHistoryClass.setTaskDetails(rowData);
            sectionTaskHistoryClass.view(rowData['transactionId']);
        });

        $('#dtTnmList_filter').hide();
        $('#txtTnmListSearch').on('keyup change', function () {
            oTableTrack.search($(this).val()).draw();
        });

        $('#optTnmYear').on('change', function () {
            yearId = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableTrack();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#optTnmSiteCode').on('change', function () {
            siteCode = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableTrack();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#optTnmFlowId').on('change', function () {
            mzOptionStop('optTnmCheckpointId', refCheckpoint, 'All Checkpoint', 'checkpointId', 'checkpointDesc', { flowId: $(this).val() });
            oTableTrack.column(10).search('', false, true, false).draw();
            flowId = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableTrack();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#optTnmCheckpointId').on('change', function () {
            oTableTrack.column(10).search($(this).val(), false, true, false).draw();
        });

        let cntTrack;
        const btnTrackOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7],
                format: {
                    body: function (data, row, column) {
                        if (row === 0 && column === 0) {
                            cntTrack = 1;
                        }
                        if (column === 7) {
                            const n = data.search('">');
                            const k = data.substr(n + 2);
                            return k.replace('</span></h6>', '');
                        }
                        return column === 0 ? cntTrack++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableTrack, {
            buttons: [
                $.extend(true, {}, btnTrackOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Transaction List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnTrackOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Transaction List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnTrackOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Transaction List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtTnmListExport'));

        $('#btnDtTnmListRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableTrack();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        self.genTableTrack();
    };

    this.genTableTrack = function () {
        const dataTrack = mzAjaxRequest('track_monitoring.php?type=track_monitoring_list&siteCode=' + siteCode + '&flowId=' + flowId + '&year=' + yearId, 'GET');
        oTableTrack.clear().rows.add(dataTrack).draw();
        self.updateRefreshTimestamp();
    };

    this.getClassName = function () {
        return className;
    };

    this.updateMetrics = function (dataSet) {
        if (!$('#metricTnmTotal').length) {
            return;
        }

        const totals = {
            total: Array.isArray(dataSet) ? dataSet.length : 0,
            success: 0,
            warning: 0,
            danger: 0
        };

        if (Array.isArray(dataSet)) {
            dataSet.forEach(function (item) {
                const statusKey = item && item.transactionStatus !== undefined ? item.transactionStatus : '';
                const statusMeta = (statusKey !== '' && refStatus) ? refStatus[statusKey] : null;
                const colorClass = statusMeta ? statusMeta['statusColor'] : '';
                const bucket = classifyStatusColor(colorClass);
                if (bucket === 'success') {
                    totals.success += 1;
                } else if (bucket === 'warning') {
                    totals.warning += 1;
                } else if (bucket === 'danger') {
                    totals.danger += 1;
                }
            });
        }

        $('#metricTnmTotal').text(formatNumber(totals.total));
        $('#metricTnmOnTrack').text(formatNumber(totals.success));
        $('#metricTnmAttention').text(formatNumber(totals.warning));
        $('#metricTnmBlocked').text(formatNumber(totals.danger));
    };

    this.updateResultsSummary = function () {
        if (!oTableTrack || typeof oTableTrack.page !== 'function' || typeof oTableTrack.page.info !== 'function') {
            return;
        }
        const info = oTableTrack.page.info();
        if (!info) {
            return;
        }
        const totalRecords = info.recordsTotal || 0;
        const visibleRecords = info.recordsDisplay || 0;
        const $summary = $('#lblTnmCount');
        if ($summary.length) {
            $summary.text(`Showing ${formatNumber(visibleRecords)} of ${formatNumber(totalRecords)} transactions`);
        }
    };

    this.updateRefreshTimestamp = function () {
        const stamp = (typeof moment === 'function') ? moment().format('DD MMM YYYY, HH:mm') : new Date().toLocaleString();
        const $label = $('#lblTnmUpdated .font-weight-bold');
        if ($label.length) {
            $label.text(stamp);
        } else {
            $('#lblTnmUpdated').text(`Last refreshed: ${stamp}`);
        }
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefFlow = function (_refFlow) {
        refFlow = _refFlow;
    };

    this.setRefCheckpoint = function (_refCheckpoint) {
        refCheckpoint = _refCheckpoint;
    };

    this.setSectionTaskHistoryClass = function (_sectionTaskHistoryClass) {
        sectionTaskHistoryClass = _sectionTaskHistoryClass;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

}
