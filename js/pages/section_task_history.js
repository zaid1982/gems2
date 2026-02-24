function SectionTaskHistory() {

    const className = 'SectionTaskHistory';
    const columnLabels = ['#', 'Checkpoint', 'User', 'Role', 'Due Date', 'Time Created', 'Time Submitted', 'Status', ''];

    const self = this;
    let transactionId = '';
    let classFrom;
    let refStatus;
    let refRole;
    let refUser;
    let refFlow;
    let refCheckpoint;
    let taskDetails;
    let oTableTransaction;
    let $modal;

    const safeText = function (value, fallback) {
        const replacement = typeof fallback !== 'undefined' ? fallback : '-';
        return value !== null && value !== undefined && value !== '' ? value : replacement;
    };

    const formatNumber = function (value) {
        const numeric = Number(value);
        return Number.isFinite(numeric) ? numeric.toLocaleString() : '0';
    };

    const getStatusMeta = function (statusId) {
        if (!statusId || !refStatus || !refStatus[statusId]) {
            return null;
        }
        return refStatus[statusId];
    };

    const classifyStatusColor = function (colorClass) {
        if (!colorClass) {
            return 'neutral';
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
        return 'neutral';
    };

    const setStatusChip = function (selector, statusMeta) {
        const $element = $(selector);
        if (!$element.length) {
            return;
        }
        if (statusMeta) {
            $element.text(statusMeta['statusDesc']);
            $element.attr('data-state', classifyStatusColor(statusMeta['statusColor']));
        } else {
            $element.text('-');
            $element.attr('data-state', 'neutral');
        }
    };

    const setDetailValue = function (id, value) {
        $('#' + id).text(safeText(value));
    };

    const normalizeIdentifier = function (value) {
        if (value === null || value === undefined) {
            return '';
        }
        const str = String(value).trim();
        if (str === '' || str === '-' || str === '—') {
            return '';
        }
        return str;
    };

    const updateTaskNumberDisplays = function () {
        let wrNo = normalizeIdentifier(taskDetails ? taskDetails['woTaskRequestNo'] : '');
        const woNoRaw = normalizeIdentifier(taskDetails ? taskDetails['woTaskNo'] : '');
        const transactionNo = normalizeIdentifier(taskDetails ? taskDetails['transactionNo'] : '');
        if (!wrNo && transactionNo && /^RQ/i.test(transactionNo)) {
            wrNo = transactionNo;
        }
        let woNo = woNoRaw;
        if (!woNo && transactionNo && /^WO/i.test(transactionNo)) {
            woNo = transactionNo;
        }

        $('#summarySthWrNo').text(safeText(wrNo));
        $('#summarySthWoNo').text(safeText(woNo));

        setDetailValue('detailSthWrNo', wrNo);
        setDetailValue('detailSthWoNo', woNo);
    };

    const updateHeaderSummary = function (flowDesc) {
        $('#summarySthFlow').text(safeText(flowDesc));
    };

    const updateHistoryTimestamp = function () {
        const stamp = (typeof moment === 'function') ? moment().format('DD MMM YYYY, HH:mm') : new Date().toLocaleString();
        const $label = $('#lblSthHistoryUpdated .font-weight-bold');
        if ($label.length) {
            $label.text(stamp);
        }
    };

    this.init = function () {
        $modal = $('#modalSectionTaskHistory');
        if (!$modal.length) {
            throw new Error('Task history modal is missing from the page.');
        }

        $('#btnSthBack').on('click', function () {
            $modal.modal('hide');
        });

        oTableTransaction = $('#dtSthList').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo: false,
            aaSorting: [5, 'asc'],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableTransaction.page.info();
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
                if (oTableTransaction && typeof oTableTransaction.page === 'function') {
                    const info = oTableTransaction.page.info();
                    if (info) {
                        $('#lblSthHistoryCount').text('Showing ' + formatNumber(info.recordsDisplay || 0) + ' of ' + formatNumber(info.recordsTotal || 0) + ' transitions');
                    }
                }
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns: [
                { mData: null, bSortable: false },
                { mData: null, mRender: function (data, type, row) {
                        const checkpointId = row['checkpointId'];
                        return checkpointId && refCheckpoint && refCheckpoint[checkpointId] ? refCheckpoint[checkpointId]['checkpointDesc'] : '';
                    } },
                { mData: 'taskClaimedUser', mRender: function (data) {
                        return data && refUser && refUser[data] ? refUser[data]['userFullName'] : '';
                    } },
                { mData: 'roleId', mRender: function (data) {
                        return data && refRole && refRole[data] ? refRole[data]['roleDesc'] : '';
                    } },
                { mData: 'taskDateDue' },
                { mData: 'taskTimeCreated' },
                { mData: 'taskTimeSubmit' },
                { mData: null, bSortable: false, mRender: function (data, type, row) {
                        const statusMeta = getStatusMeta(row['taskStatus']);
                        if (!statusMeta) {
                            return '-';
                        }
                        return '<span class="status-chip" data-state="' + classifyStatusColor(statusMeta['statusColor']) + '">' + statusMeta['statusDesc'] + '</span>';
                    } },
                { mData: null, bSortable: false, sClass: 'text-center', mRender: function () {
                        return '';
                    } }
            ]
        });

        $('#dtSthList_filter').hide();

        const btnHistoryOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7],
                format: {
                    body: function (data, row, column) {
                        if (column === 7) {
                            const marker = data.indexOf('>');
                            if (marker > -1) {
                                const content = data.substr(marker + 1);
                                return content.replace('</span>', '');
                            }
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableTransaction, {
            buttons: [
                $.extend(true, {}, btnHistoryOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Transaction History',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-primary btn-sm px-3'
                }),
                $.extend(true, {}, btnHistoryOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Transaction History',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-sm px-3'
                }),
                $.extend(true, {}, btnHistoryOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Transaction History',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-primary btn-sm px-3'
                })
            ]
        }).container().appendTo($('#btnDtSthListExport'));

        $('#btnDtSthListRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSthList();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $modal.on('shown.bs.modal', function () {
            if (oTableTransaction && typeof oTableTransaction.columns === 'function') {
                oTableTransaction.columns.adjust();
            }
        });
    };

    this.view = function (_transactionId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_transactionId]);
                transactionId = _transactionId;

                const flowDesc = taskDetails && taskDetails['flowId'] && refFlow && refFlow[taskDetails['flowId']] ? refFlow[taskDetails['flowId']]['flowDesc'] : '';
                const checkpointDesc = taskDetails && taskDetails['checkpointId'] && refCheckpoint && refCheckpoint[taskDetails['checkpointId']] ? refCheckpoint[taskDetails['checkpointId']]['checkpointDesc'] : '';
                const initiatedBy = taskDetails && taskDetails['transUser'] && refUser && refUser[taskDetails['transUser']] ? refUser[taskDetails['transUser']]['userFullName'] : '';
                const currentOwner = taskDetails && taskDetails['userId'] && refUser && refUser[taskDetails['userId']] ? refUser[taskDetails['userId']]['userFullName'] : '';
                const flowStatusMeta = getStatusMeta(taskDetails ? taskDetails['transactionStatus'] : '');
                const taskStatusMeta = getStatusMeta(taskDetails ? taskDetails['taskStatus'] : '');

                setDetailValue('detailSthFlow', flowDesc);
                setDetailValue('detailSthCheckpoint', checkpointDesc);
                setDetailValue('detailSthInitiatedBy', initiatedBy);
                setDetailValue('detailSthInitiatedTime', taskDetails ? taskDetails['transactionTimeCreated'] : '');
                setDetailValue('detailSthOwner', currentOwner);
                setDetailValue('detailSthReceivedTime', taskDetails ? taskDetails['taskTimeCreated'] : '');
                setDetailValue('detailSthDueDate', taskDetails ? taskDetails['transactionDateDue'] : '');

                setStatusChip('#detailSthFlowStatus', flowStatusMeta);
                setStatusChip('#detailSthTaskStatus', taskStatusMeta);
                setStatusChip('#metricSthStatus', flowStatusMeta);

                $('#metricSthCheckpoint').text(safeText(checkpointDesc));

                updateTaskNumberDisplays();
                updateHeaderSummary(flowDesc);

                self.genTableSthList();

                $modal.modal('show');
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTableSthList = function () {
        const dataTransaction = mzAjaxRequest('track_monitoring.php?type=transaction_history&transactionId=' + transactionId, 'GET');
        oTableTransaction.clear().rows.add(dataTransaction).draw();

        const totalRecords = Array.isArray(dataTransaction) ? dataTransaction.length : 0;
        const completedRecords = Array.isArray(dataTransaction) ? dataTransaction.filter(function (item) {
            return item && item['taskTimeSubmit'] !== null && item['taskTimeSubmit'] !== '';
        }).length : 0;

        $('#metricSthTotal').text(formatNumber(totalRecords));
        $('#metricSthCompleted').text(formatNumber(completedRecords));

        const summaryText = totalRecords ? 'Showing ' + formatNumber(totalRecords) + ' recorded transitions' : 'No transitions recorded yet';
        $('#lblSthHistoryCount').text(summaryText);
        updateHistoryTimestamp();

        let trainStationFlag = '';
        if (taskDetails && taskDetails['flowId'] === '2') {
            const woTask = mzAjaxRequest('wo.php?type=wo_by_transaction&transactionId=' + transactionId, 'GET');
            if (woTask['woTaskTypeInit'] === '1' || woTask['woTaskTypeInit'] === '6') {
                // Self-Finding (1) and Public Complaint (6) both start at CP 11
                trainStationFlag = woTask['woTaskIsWr'] === '1' ? '3' : '1';
            } else {
                // Internal/Client Complaint starts at CP 10
                trainStationFlag = '2';
            }

            if (woTask && typeof woTask === 'object') {
                if (normalizeIdentifier(woTask['woTaskNo'])) {
                    taskDetails['woTaskNo'] = normalizeIdentifier(woTask['woTaskNo']);
                }
                if (normalizeIdentifier(woTask['woTaskRequestNo'])) {
                    taskDetails['woTaskRequestNo'] = normalizeIdentifier(woTask['woTaskRequestNo']);
                }
                updateTaskNumberDisplays();
            }
        }

        const dataLength = dataTransaction.length;
        const currentCheckpoint = dataLength > 0 ? dataTransaction[dataLength - 1]['checkpointId'] : '';
        const trainStations = mzAjaxRequest('track_monitoring.php?type=transaction_history_train_station&transactionId=' + transactionId + '&flag=' + trainStationFlag, 'GET');
        self.updateTimeline(trainStations, currentCheckpoint);

        if ($modal && $modal.hasClass('show') && oTableTransaction && typeof oTableTransaction.columns === 'function') {
            oTableTransaction.columns.adjust();
        }
    };

    this.updateTimeline = function (trainStations, currentCheckpoint) {
        const $list = $('#ulSthStep');
        $list.empty();
        if (!Array.isArray(trainStations) || !taskDetails) {
            return;
        }

        let beforeCurrent = true;
        let currentMatched = false;

        for (let i = 0; i < trainStations.length; i++) {
            const station = trainStations[i];
            if (!station || station['flowId'] !== taskDetails['flowId']) {
                continue;
            }

            const checkpointId = station['checkpointId'];
            let stateClass = 'upcoming';
            let stateLabel = 'Pending checkpoint';

            if (checkpointId === currentCheckpoint) {
                stateClass = 'current';
                stateLabel = 'Current checkpoint';
                beforeCurrent = false;
                currentMatched = true;
            } else if (beforeCurrent) {
                stateClass = 'complete';
                stateLabel = 'Completed';
            }

            const itemHtml = '<li class="history-step ' + stateClass + '">' +
                '<div class="step-icon"><i class="' + safeText(station['checkpointIcon'], 'fas fa-flag') + '"></i></div>' +
                '<div class="step-content">' +
                '<span class="step-title">' + safeText(station['checkpointDesc']) + '</span>' +
                '<span class="step-meta">' + stateLabel + '</span>' +
                '</div>' +
                '</li>';
            $list.append(itemHtml);
        }

        if (!currentMatched && $list.children().length > 0) {
            const $last = $list.children().last();
            $last.removeClass('upcoming').addClass('current');
            $last.find('.step-meta').text('Current checkpoint');
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
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

    this.setTaskDetails = function (_taskDetails) {
        taskDetails = (_taskDetails && typeof _taskDetails === 'object') ? _taskDetails : {};
    };
}
