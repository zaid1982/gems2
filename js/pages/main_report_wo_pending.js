function MainReportWoPending() {

    const className = 'MainReportWoPending';
    let self = this;
    let refClient;
    let refSite;
    let refStatus;
    let refUser;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTableWoPending;
    let clientId;
    let siteId;
    let selectedYear;
    let selectedMonth;
    let userSite;
    let tableDataCache = [];
    let lastUpdated = null;
    const momentAvailable = (typeof moment === 'function');

    function refreshMaterialSelect(selector) {
        const el = $(selector);
        if (!el.length) {
            return;
        }
        try {
            el.materialSelect('destroy');
        } catch (err) {
            // ignore when component not initialised yet
        }
        el.materialSelect();
    }

    function getUserName(userId) {
        if (!userId || !refUser) {
            return '';
        }
        const key = String(userId);
        if (refUser[key]) {
            return refUser[key]['userFullName'] || refUser[key]['userFirstName'] || '';
        }
        if (Array.isArray(refUser)) {
            const match = refUser.find(function (row) {
                return row && String(row.userId) === key;
            });
            if (match) {
                return match.userFullName || match.userFirstName || '';
            }
        }
        return '';
    }

    function resolveStatus(statusId) {
        if (statusId === undefined || statusId === null || !refStatus) {
            return null;
        }
        const key = String(statusId);
        if (refStatus[key]) {
            return refStatus[key];
        }
        if (Array.isArray(refStatus)) {
            return refStatus.find(function (row) {
                return row && String(row.statusId) === key;
            }) || null;
        }
        return null;
    }

    function getStatusBadge(statusId) {
        const status = resolveStatus(statusId);
        if (!status) {
            return '';
        }
        const color = status.statusColor || 'badge-secondary';
        const label = status.statusDesc || status.statusAction || status.statusName || statusId;
        return '<span class="badge badge-pill ' + color + ' z-depth-2">' + label + '</span>';
    }

    function setDataLabels(row, apiInstance) {
        const api = apiInstance || oTableWoPending;
        if (!api) {
            return;
        }
        const visibleIndexes = api.columns(':visible').indexes().toArray();
        $('td', row).each(function (cellIdx) {
            const columnIndex = visibleIndexes[cellIdx];
            if (columnIndex === undefined) {
                return;
            }
            const headerText = $(api.column(columnIndex).header()).text().trim();
            $(this).attr('data-label', headerText);
        });
    }

    function updateSummary() {
        if (!oTableWoPending) {
            return;
        }
        const info = typeof oTableWoPending.page === 'function' ? oTableWoPending.page.info() : null;
        if (!info) {
            return;
        }
        $('#lblRwpCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
    }

    function updateTimestamp() {
        const el = $('#lblRwpUpdated');
        if (!el.length) {
            return;
        }
        if (!lastUpdated) {
            el.text('Updated —');
            return;
        }
        if (momentAvailable && moment.isMoment(lastUpdated)) {
            el.text('Updated ' + lastUpdated.format('DD MMM YYYY, hh:mm A'));
        } else {
            el.text('Updated ' + new Date(lastUpdated).toLocaleString());
        }
    }

    function updatePeriodLabel() {
        const el = $('#lblRwpPeriod');
        if (!el.length) {
            return;
        }
        const yearInt = parseInt(selectedYear, 10);
        const monthInt = parseInt(selectedMonth, 10);
        if (momentAvailable && !isNaN(yearInt) && !isNaN(monthInt)) {
            const label = moment({ year: yearInt, month: monthInt - 1, day: 1 }).format('MMM YYYY');
            el.text('Reporting window: ' + label);
        } else if (!isNaN(yearInt) && !isNaN(monthInt)) {
            el.text('Reporting window: ' + monthInt + '/' + yearInt);
        } else {
            el.text('Reporting window: --');
        }
    }

    function updateMetrics(data) {
        const metrics = {
            total: 0,
            older: 0,
            avgAge: 0,
            monthCount: 0
        };
        let totalAge = 0;
        const now = momentAvailable ? moment() : null;
        const yearInt = parseInt(selectedYear, 10);
        const monthInt = parseInt(selectedMonth, 10);

        if (!Array.isArray(data)) {
            data = [];
        }

        metrics.total = data.length;

        data.forEach(function (row) {
            const createdRaw = row && row.woTaskTimeCreated ? row.woTaskTimeCreated : null;
            if (!createdRaw) {
                return;
            }
            let createdMoment = null;
            if (momentAvailable) {
                createdMoment = moment(createdRaw);
            }
            if (createdMoment && createdMoment.isValid()) {
                const diffDays = now ? now.diff(createdMoment, 'days') : 0;
                totalAge += diffDays;
                if (diffDays > 7) {
                    metrics.older += 1;
                }
                if (!isNaN(yearInt) && !isNaN(monthInt) && createdMoment.year() === yearInt && createdMoment.month() === (monthInt - 1)) {
                    metrics.monthCount += 1;
                }
            } else {
                // Fallback when moment is unavailable
                const createdDate = new Date(createdRaw);
                if (!isNaN(createdDate.getTime())) {
                    const diffMs = Date.now() - createdDate.getTime();
                    const diffDaysFallback = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                    totalAge += diffDaysFallback;
                    if (diffDaysFallback > 7) {
                        metrics.older += 1;
                    }
                    if (!isNaN(yearInt) && !isNaN(monthInt) && createdDate.getFullYear() === yearInt && (createdDate.getMonth() + 1) === monthInt) {
                        metrics.monthCount += 1;
                    }
                }
            }
        });

        metrics.avgAge = metrics.total > 0 ? (totalAge / metrics.total) : 0;

        $('#metricRwpTotal').text(metrics.total.toLocaleString());
        $('#metricRwpOlder').text(metrics.older.toLocaleString());
        $('#metricRwpAvgAge').text(metrics.avgAge.toFixed(1));
        $('#metricRwpThisMonth').text(metrics.monthCount.toLocaleString());
    }

    this.init = function () {
        userSite = mzGetUserInfoByParam('siteId');

        mzOption('optRwpClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');

        const siteFilter = !mzIsRoleExist('1,10') ? {clientId: '1', siteId: userSite, siteStatus: '1'} : {clientId: '1', siteStatus: '1'};
        mzOption('optRwpSiteId', refSite, 'Choose Site', 'siteId', 'siteDesc', siteFilter, 'required');

        clientId = '1';
        siteId = !mzIsRoleExist('1,10') ? userSite : '1';

        $('#optRwpClientId').val(clientId);
        $('#optRwpSiteId').val(siteId);

        if (!mzIsRoleExist('1,10')) {
            $('#optRwpSiteId').prop('disabled', true);
        }

        const dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth() + 1;
        selectedYear = dateCurrent.getFullYear();

        mzOption('optRwpYearId', yearArr, 'Choose Year', 'yearId', 'yearName', {}, 'required', false);
        $('#optRwpYearId').val(selectedYear);

        mzOption('optRwpMonthId', monthArr, 'Choose Month', 'monthId', 'monthName', {}, 'required', false);
        $('#optRwpMonthId').val(selectedMonth);

        refreshMaterialSelect('#optRwpClientId');
        refreshMaterialSelect('#optRwpSiteId');
        refreshMaterialSelect('#optRwpYearId');
        refreshMaterialSelect('#optRwpMonthId');

        $('#optRwpClientId').on('change', function () {
            clientId = $(this).val();
            mzOptionStop('optRwpSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: clientId, siteStatus: '1'}, 'required');
            if (!mzIsRoleExist('1,10')) {
                $('#optRwpSiteId').val(userSite);
            }
            refreshMaterialSelect('#optRwpSiteId');
        });

        const validationFields = [
            {
                field_id: 'optRwpClientId',
                type: 'select',
                name: 'Client',
                validator: { notEmpty: true }
            },
            {
                field_id: 'optRwpMonthId',
                type: 'select',
                name: 'Month',
                validator: { notEmpty: true }
            },
            {
                field_id: 'optRwpYearId',
                type: 'select',
                name: 'Year',
                validator: { notEmpty: true }
            }
        ];

        const formValidate = new MzValidate('formRwpSearch');
        formValidate.registerFields(validationFields);

        $('#btnRwpSearch').attr('disabled', !formValidate.validateForm());
        $('#formRwpSearch').on('change keyup', 'select, input', function () {
            $('#btnRwpSearch').attr('disabled', !formValidate.validateForm());
        });

        const exportOptions = {
            columns: [0, 1, 2, 3, 4],
            format: {
                body: function (data, row, column) {
                    if (column === 4) {
                        const stripped = $('<div>').html(data).text();
                        return stripped.trim();
                    }
                    return data;
                }
            }
        };

        oTableWoPending = $('#dtRwpWoPending').DataTable({
            bLengthChange: false,
            bFilter: false,
            autoWidth: false,
            aaSorting: [[3, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: "Brt<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
            buttons: [
                {
                    extend: 'print',
                    text: '<i class="fas fa-print text-dark"></i>',
                    title: 'GEMS 2.0 - Outstanding Work Order List',
                    titleAttr: 'Print',
                    className: 'btn btn-light btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel text-dark"></i>',
                    title: 'GEMS 2.0 - Outstanding Work Order List',
                    titleAttr: 'Excel',
                    className: 'btn btn-light btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf text-dark"></i>',
                    title: 'GEMS 2.0 - Outstanding Work Order List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-light btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ],
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-nowrap', targets: [1, 3] }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const api = $(this).DataTable();
                const info = api.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                setDataLabels(nRow, api);
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateSummary();
            },
            aoColumns: [
                { mData: null },
                { mData: 'woTaskCreatedBy', mRender: function (data) {
                        return getUserName(data);
                    } },
                { mData: 'woTaskComplaint' },
                { mData: 'woTaskTimeCreated', mRender: function (data) {
                        return data ? data.substr(0, 10) : '';
                    } },
                { mData: 'woTaskStatus', mRender: function (data) {
                        return getStatusBadge(data);
                    } }
            ]
        });

        oTableWoPending.buttons().container().appendTo('#btnDtRwpWoPendingExport');

        $('#txtRwpSearch').on('keyup change', function () {
            const term = $(this).val();
            oTableWoPending.search(term).draw();
        });

        $('#btnRwpWoPendingRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoPending();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnRwpSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    siteId = $('#optRwpSiteId').val() || siteId;
                    selectedYear = $('#optRwpYearId').val();
                    selectedMonth = $('#optRwpMonthId').val();
                    updatePeriodLabel();
                    self.genTableWoPending();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        updatePeriodLabel();
        self.genTableWoPending();
    };

    this.genTableWoPending = function () {
    const monthIndex = parseInt(selectedMonth, 10);
    const monthParam = isNaN(monthIndex) ? 0 : (monthIndex - 1);
        const dataWoPending = mzAjaxRequest('wo.php?type=report_wo_pending_list&siteId=' + siteId + '&year=' + selectedYear + '&month=' + monthParam, 'GET') || [];
        tableDataCache = Array.isArray(dataWoPending) ? dataWoPending : [];
        oTableWoPending.clear().rows.add(tableDataCache).draw();
        updateMetrics(tableDataCache);
        lastUpdated = momentAvailable ? moment() : new Date();
        updateTimestamp();
        updateSummary();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}