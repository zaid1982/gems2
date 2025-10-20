function MainAttendance () {

    const className = 'MainAttendance';
    let self = this;
    let refStatus;
    let refClient;
    let oTableAtc;
    let modalConfirmSubmitClass;
    let sectionAttendanceSiteClass;
    let attendanceDataCache = [];
    let lastUpdatedText = '—';
    let clientFilterValue = '';
    let statusFilterValue = '';
    let attendanceFeatureValue = '';

    const statusChipMap = {
        '': '#linkAttendanceAll',
        '1': '#linkAttendanceActive',
        '2': '#linkAttendanceInactive'
    };

    const tableHeaders = ['#', 'Client', 'Site Name', 'Site Code', 'Description', 'Status', 'Total Groups', 'Total Participants', 'Attendance', 'Actions'];

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy warnings
        }
        const $wrapper = $element.parent('.select-wrapper');
        if ($wrapper.length) {
            $wrapper.before($element);
            $wrapper.remove();
        }
        $element.siblings('.select-dropdown').remove();
        $element.materialSelect();
    };

    const applyTableDataLabels = function () {
        $('#dtAtcData tbody tr').each(function () {
            $('td', this).each(function (index) {
                if (tableHeaders[index]) {
                    $(this).attr('data-label', tableHeaders[index]);
                }
            });
        });
    };

    const stripHtml = function (value) {
        if (value === null || value === undefined) {
            return '';
        }
        if (typeof value !== 'string') {
            return value;
        }
        return value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
    };

    const getNowStamp = function () {
        if (typeof moment !== 'undefined' && moment) {
            return moment().format('MMM D, YYYY h:mm A');
        }
        return new Date().toLocaleString();
    };

    const getClientName = function (clientId) {
        const key = clientId !== undefined && clientId !== null ? String(clientId) : '';
        if (key && refClient && refClient[key]) {
            return refClient[key]['clientName'] || '—';
        }
        if (Array.isArray(refClient)) {
            const match = refClient.find(function (item) {
                return String(item['clientId']) === key;
            });
            if (match) {
                return match['clientName'] || '—';
            }
        }
        return '—';
    };

    const getStatusDesc = function (statusId) {
        const key = statusId !== undefined && statusId !== null && statusId !== '' ? String(statusId) : '';
        if (!key) {
            return 'Unknown';
        }
        if (refStatus && refStatus[key]) {
            return refStatus[key]['statusDesc'] || 'Unknown';
        }
        if (Array.isArray(refStatus)) {
            const match = refStatus.find(function (item) {
                return String(item['statusId']) === key;
            });
            if (match) {
                return match['statusDesc'] || 'Unknown';
            }
        }
        return 'Unknown';
    };

    const getStatusBadge = function (statusId) {
        const key = statusId !== undefined && statusId !== null && statusId !== '' ? String(statusId) : '';
        if (key && refStatus && refStatus[key]) {
            const desc = refStatus[key]['statusDesc'] || 'Unknown';
            const badgeClass = refStatus[key]['statusColor'] || 'badge-secondary';
            return `<span class="badge badge-pill ${badgeClass} z-depth-2">${desc}</span>`;
        }
        return '<span class="badge badge-pill badge-secondary">Unknown</span>';
    };

    const getAttendanceBadge = function (flag) {
        if (parseInt(flag, 10) === 1) {
            return '<span class="badge badge-pill badge-success z-depth-2">Enabled</span>';
        }
        return '<span class="badge badge-pill badge-secondary z-depth-2">Disabled</span>';
    };

    const setActiveStatusChip = function (value) {
        $.each(statusChipMap, function (status, selector) {
            if (status === value) {
                $(selector).addClass('active');
            } else {
                $(selector).removeClass('active');
            }
        });
    };

    const updateStatusChips = function (counts) {
        const activeLabel = (function () {
            const desc = getStatusDesc('1');
            return desc === 'Unknown' ? 'Active' : desc;
        })();
        const inactiveLabel = (function () {
            const desc = getStatusDesc('2');
            return desc === 'Unknown' ? 'Inactive' : desc;
        })();
        const labelMap = {
            '': 'All',
            '1': activeLabel,
            '2': inactiveLabel
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(`${labelMap[status] || 'Status'} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
        setActiveStatusChip(statusFilterValue || '');
    };

    const updateMetrics = function (dataSet) {
        let totalSites = 0;
        let enabledSites = 0;
        let totalGroups = 0;
        let totalParticipants = 0;
        const statusCounts = { '': 0, '1': 0, '2': 0 };

        dataSet.forEach(function (item) {
            totalSites += 1;
            statusCounts[''] += 1;

            const statusKey = item['siteStatus'] !== undefined && item['siteStatus'] !== null ? String(item['siteStatus']) : '';
            if (statusKey) {
                statusCounts[statusKey] = (statusCounts[statusKey] || 0) + 1;
            }

            if (parseInt(item['siteIsAttendance'], 10) === 1) {
                enabledSites += 1;
            }

            const groupTotal = parseInt(item['totalGroup'], 10);
            if (!isNaN(groupTotal)) {
                totalGroups += groupTotal;
            }

            const participantTotal = parseInt(item['totalParticipant'], 10);
            if (!isNaN(participantTotal)) {
                totalParticipants += participantTotal;
            }
        });

        $('#metricAttendanceTotal').text(mzFormatNumber(totalSites, 0));
        $('#metricAttendanceEnabled').text(mzFormatNumber(enabledSites, 0));
        $('#metricAttendanceGroups').text(mzFormatNumber(totalGroups, 0));
        $('#metricAttendanceParticipants').text(mzFormatNumber(totalParticipants, 0));

        updateStatusChips(statusCounts);
    };

    const refreshListSummary = function () {
        if (!oTableAtc) {
            return;
        }
        const info = oTableAtc.page() ? oTableAtc.page.info() : null;
        const showing = info ? info.end - info.start : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblAttendanceFilterCount').text(summaryText);
        $('#lblAttendanceFilterUpdated').text(lastUpdatedText);
        $('#lblAttendanceListCount').text(summaryText);
        $('#lblAttendanceListUpdated').text(lastUpdatedText);
    };

    const populateClientFilter = function () {
        const $select = $('#optAttendanceClient');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Clients</option>'];
        if (refClient) {
            const keys = Array.isArray(refClient) ? refClient.map(function (item) {
                return String(item['clientId']);
            }) : Object.keys(refClient);

            keys.sort(function (a, b) {
                const nameA = (getClientName(a) || '').toLowerCase();
                const nameB = (getClientName(b) || '').toLowerCase();
                return nameA.localeCompare(nameB);
            }).forEach(function (key) {
                const name = getClientName(key) || '—';
                options.push(`<option value="${key}">${name}</option>`);
            });
        }
        $select.html(options.join(''));
        initMaterialSelect('#optAttendanceClient');
        clientFilterValue = '';
    };

    const populateStatusFilter = function () {
        const $select = $('#optAttendanceStatus');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Status</option>'];
        if (refStatus) {
            const keys = Array.isArray(refStatus) ? refStatus.map(function (item) {
                return String(item['statusId']);
            }) : Object.keys(refStatus);
            keys.sort(function (a, b) {
                const descA = (getStatusDesc(a) || '').toLowerCase();
                const descB = (getStatusDesc(b) || '').toLowerCase();
                return descA.localeCompare(descB);
            }).forEach(function (key) {
                if (!key) {
                    return;
                }
                options.push(`<option value="${key}">${getStatusDesc(key)}</option>`);
            });
        }
        $select.html(options.join(''));
        initMaterialSelect('#optAttendanceStatus');
        statusFilterValue = '';
    };

    const populateAttendanceFeatureFilter = function () {
        const $select = $('#optAttendanceFeature');
        if (!$select.length) {
            return;
        }
        $select.val('');
        initMaterialSelect('#optAttendanceFeature');
        attendanceFeatureValue = '';
    };

    const handleClientChange = function () {
        clientFilterValue = $('#optAttendanceClient').val() || '';
        if (oTableAtc) {
            oTableAtc.draw();
        }
        refreshListSummary();
    };

    const handleStatusChange = function () {
        setStatusFilter($('#optAttendanceStatus').val() || '', true);
    };

    const handleAttendanceFeatureChange = function () {
        setAttendanceFeatureFilter($('#optAttendanceFeature').val() || '', true);
    };

    const bindSearchField = function () {
        $('#txtAttendanceSearch').off('input change keyup').on('input change keyup', function () {
            if (!oTableAtc) {
                return;
            }
            oTableAtc.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableAtc) {
            oTableAtc.draw();
        }
        refreshListSummary();
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optAttendanceStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy warnings
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusChange);
            }
        }
    };

    const setAttendanceFeatureFilter = function (value, fromSelect) {
        attendanceFeatureValue = value || '';
        if (oTableAtc) {
            oTableAtc.draw();
        }
        refreshListSummary();
        if (!fromSelect) {
            const $featureSelect = $('#optAttendanceFeature');
            if ($featureSelect.length) {
                try {
                    $featureSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy warnings
                }
                $featureSelect.val(attendanceFeatureValue);
                $featureSelect.materialSelect();
                $featureSelect.off('change').on('change', handleAttendanceFeatureChange);
            }
        }
    };

    const attendanceFilterFn = function (settings, data, dataIndex) {
        if (!oTableAtc || settings.nTable.id !== 'dtAtcData') {
            return true;
        }
        const rowData = oTableAtc.row(dataIndex).data();
        if (!rowData) {
            return true;
        }

        if (clientFilterValue) {
            if (String(rowData['clientId']) !== String(clientFilterValue)) {
                return false;
            }
        }

        if (statusFilterValue) {
            if (String(rowData['siteStatus']) !== String(statusFilterValue)) {
                return false;
            }
        }

        if (attendanceFeatureValue === 'enabled' && parseInt(rowData['siteIsAttendance'], 10) !== 1) {
            return false;
        }

        if (attendanceFeatureValue === 'disabled' && parseInt(rowData['siteIsAttendance'], 10) === 1) {
            return false;
        }

        return true;
    };

    $.fn.dataTable.ext.search.push(attendanceFilterFn);

    const bindActionButtons = function () {
        $('.btn-attendance-toggle').off('click').on('click', function (evt) {
            evt.preventDefault();
            evt.stopPropagation();
            const rowIndex = parseInt($(this).attr('data-row-index'), 10);
            const action = $(this).attr('data-action');
            const rowData = oTableAtc ? oTableAtc.row(rowIndex).data() : null;
            if (!rowData) {
                return;
            }
            const siteName = rowData['siteName'] || 'this site';
            const actionText = action === 'Activate' ? 'ACTIVATE' : 'DEACTIVATE';
            modalConfirmSubmitClass.setClassFrom(self);
            modalConfirmSubmitClass.load(rowData['siteId'], action, `Are you sure to <b>${actionText}</b> attendance for <b>${siteName}</b>?`);
        });
    };

    const bindRowInteractions = function () {
        const $tbody = $('#dtAtcData tbody');
        $tbody.off('click', 'tr').on('click', function (evt) {
            const $button = $(evt.target).closest('button');
            if ($button.length) {
                return;
            }
            const $cell = $(evt.target).closest('td');
            if ($cell.length && $cell.index() === 9) {
                return;
            }
            const rowData = oTableAtc ? oTableAtc.row(this).data() : null;
            if (!rowData) {
                return;
            }
            sectionAttendanceSiteClass.load(rowData['siteId']);
        });

        $tbody.off('mouseenter', 'tr').on('mouseenter', function (evt) {
            if ($(evt.target).closest('button').length) {
                return;
            }
            const rowData = oTableAtc ? oTableAtc.row(this).data() : null;
            if (!rowData) {
                return;
            }
            const $cell = $(evt.target).closest('td');
            if (!$cell.length || $cell.index() >= 9) {
                return;
            }
            $cell.css('cursor', 'pointer');
            $cell.attr('data-toggle', 'tooltip');
            $cell.attr('title', `Click to configure ${rowData['siteName'] || 'this site'}`);
            $('[data-toggle="tooltip"]').tooltip();
        });
    };

    const attachQuickStatusHandlers = function () {
        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });
    };

    this.init = function () {
        populateClientFilter();
        populateStatusFilter();
        populateAttendanceFeatureFilter();
        bindSearchField();

        $('#optAttendanceClient').off('change').on('change', handleClientChange);
        $('#optAttendanceStatus').off('change').on('change', handleStatusChange);
        $('#optAttendanceFeature').off('change').on('change', handleAttendanceFeatureChange);

        oTableAtc = $('#dtAtcData').DataTable({
            bLengthChange: false,
            searching: true,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 20,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            columnDefs: [
                { orderable: false, targets: [0, 9] },
                { className: 'text-center align-middle', targets: [0, 5, 8, 9] },
                { className: 'text-right align-middle', targets: [6, 7] },
                { className: 'align-middle', targets: [1, 2, 3, 4] }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAtc.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels();
                refreshListSummary();
                bindActionButtons();
                $('[data-toggle="tooltip"]').tooltip();
            },
            aoColumns: [
                { mData: null },
                { mData: 'clientId', mRender: function (data, type) {
                        const value = getClientName(data);
                        return type !== 'display' ? value : (value || '<span class="text-muted">—</span>');
                    } },
                { mData: 'siteName', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">No site name</span>';
                    } },
                { mData: 'siteCode', mRender: function (data, type) {
                        const value = data || '';
                        return type !== 'display' ? value : (value || '<span class="text-muted">—</span>');
                    } },
                { mData: 'siteDesc', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">No description</span>';
                    } },
                { mData: 'siteStatus', mRender: function (data, type) {
                        if (type !== 'display') {
                            return getStatusDesc(data);
                        }
                        return `<h6 class="mb-0">${getStatusBadge(data)}</h6>`;
                    } },
                { mData: 'totalGroup', mRender: function (data, type) {
                        const value = mzFormatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'totalParticipant', mRender: function (data, type) {
                        const value = mzFormatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'siteIsAttendance', mRender: function (data, type) {
                        if (type !== 'display') {
                            return parseInt(data, 10) === 1 ? 'Enabled' : 'Disabled';
                        }
                        return getAttendanceBadge(data);
                    } },
                { mData: null, orderable: false, width: '90px', mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return '';
                        }
                        const isEnabled = parseInt(row['siteIsAttendance'], 10) === 1;
                        const action = isEnabled ? 'Deactivate' : 'Activate';
                        const icon = isEnabled ? 'fa-toggle-off text-danger' : 'fa-toggle-on text-success';
                        const tooltip = isEnabled ? 'Disable attendance' : 'Enable attendance';
                        return `<div class="table-actions d-flex justify-content-center">
                                    <button type="button" class="btn btn-link btn-sm px-2 btn-attendance-toggle" data-row-index="${meta.row}" data-action="${action}" data-toggle="tooltip" title="${tooltip}">
                                        <i class="fas ${icon}"></i>
                                    </button>
                                </div>`;
                    } }
            ]
        });

        $('#dtAtcData_filter').hide();

        let exportCounter = 1;
        const exportOptions = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
            format: {
                body: function (data, row, column) {
                    if (column === 0) {
                        if (row === 0) {
                            exportCounter = 1;
                        }
                        return exportCounter++;
                    }
                    if (column === 1) {
                        const rowData = oTableAtc.row(row).data();
                        return rowData ? getClientName(rowData['clientId']) : stripHtml(data);
                    }
                    if (column === 5) {
                        const rowData = oTableAtc.row(row).data();
                        return rowData ? getStatusDesc(rowData['siteStatus']) : stripHtml(data);
                    }
                    if (column === 8) {
                        const rowData = oTableAtc.row(row).data();
                        if (!rowData) {
                            return stripHtml(data);
                        }
                        return parseInt(rowData['siteIsAttendance'], 10) === 1 ? 'Enabled' : 'Disabled';
                    }
                    return stripHtml(data);
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableAtc, {
            buttons: [
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Attendance Site List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Attendance Site List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Attendance Site List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ]
        }).container().appendTo($('#btnDtAttendanceExport'));

        bindRowInteractions();
        attachQuickStatusHandlers();

        $('#btnAttendanceRefresh').off('click').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTable();
        setStatusFilter('', true);
        setAttendanceFeatureFilter('', true);
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function () {
        $('.sectionAtcMain').show();
    };

    this.hideMain = function () {
        $('.sectionAtcMain').hide();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('att_group/site', 'GET');
        const dataSet = Array.isArray(dataDb) ? dataDb : [];
        attendanceDataCache = dataSet.slice();
        if (oTableAtc) {
            oTableAtc.clear().rows.add(dataSet).draw();
        }
        lastUpdatedText = getNowStamp();
        updateMetrics(attendanceDataCache);
        setStatusFilter(statusFilterValue, true);
        setAttendanceFeatureFilter(attendanceFeatureValue, true);
        refreshListSummary();
    };

    this.confirmSubmit = function (_siteId, _flag) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_siteId, _flag]);
                    if (_flag === 'Activate') {
                        mzAjaxRequest2('att_group/activate_site/' + _siteId, 'PUT');
                        self.genTable();
                    } else if (_flag === 'Deactivate') {
                        mzAjaxRequest2('att_group/deactivate_site/' + _siteId, 'PUT');
                        self.genTable();
                    } else {
                        toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setModalConfirmSubmitClass = function (_modalConfirmSubmitClass) {
        modalConfirmSubmitClass = _modalConfirmSubmitClass;
    };

    this.setSectionAttendanceSiteClass = function (_sectionAttendanceSiteClass) {
        sectionAttendanceSiteClass = _sectionAttendanceSiteClass;
    };
}