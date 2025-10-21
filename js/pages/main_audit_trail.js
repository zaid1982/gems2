function MainAuditTrail() {

    const className = 'MainAuditTrail';
    let self = this;
    let refUser;
    let refAuditModule;
    let refAuditAction;
    let oTableAdt;
    let formValidateSearch;
    let dataCache = [];
    let lastUpdated = '--';

    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const tableHeaders = ['#', 'Timestamp', 'User', 'Module', 'Action', 'IP Address', 'Remarks'];

    const toStr = function (value) {
        return value === null || typeof value === 'undefined' ? '' : String(value);
    };

    const numberFormat = function (value) {
        if (typeof mzFormatNumber === 'function') {
            return mzFormatNumber(value, 0);
        }
        const num = Number(value);
        if (!Number.isFinite(num)) {
            return value;
        }
        return num.toLocaleString();
    };

    const getNowStamp = function () {
        if (typeof moment !== 'undefined' && typeof moment === 'function') {
            return moment().format('DD MMM YYYY, h:mm A');
        }
        return new Date().toLocaleString();
    };

    const refreshMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (err) {
            // ignore when component not initialised yet
        }
        $element.materialSelect();
    };

    const applyRowLabels = function (nRow) {
        $('td', nRow).each(function (index) {
            if (tableHeaders[index]) {
                $(this).attr('data-label', tableHeaders[index]);
            }
        });
    };

    const getModuleDesc = function (moduleId) {
        const key = toStr(moduleId);
        if (!key || key === '0') {
            return 'All Modules';
        }
        if (refAuditModule && refAuditModule[key]) {
            return refAuditModule[key]['auditModuleDesc'] || `Module ${key}`;
        }
        return `Module ${key}`;
    };

    const getActionDesc = function (actionId) {
        const key = toStr(actionId);
        if (!key || key === '0') {
            return 'All Actions';
        }
        if (refAuditAction && refAuditAction[key]) {
            return refAuditAction[key]['auditActionDesc'] || `Action ${key}`;
        }
        return `Action ${key}`;
    };

    const deriveModuleId = function (row) {
        if (!row) {
            return '';
        }
        const actionKey = toStr(row['auditActionId']);
        if (actionKey && refAuditAction && refAuditAction[actionKey] && refAuditAction[actionKey]['auditModuleId']) {
            const moduleId = toStr(refAuditAction[actionKey]['auditModuleId']);
            return moduleId === '0' ? '' : moduleId;
        }
        const moduleFallback = toStr(row['auditModuleId']);
        return moduleFallback === '0' ? '' : moduleFallback;
    };

    const updateMetrics = function () {
        const total = dataCache.length;
        const uniqueUsers = new Set();
        const uniqueModules = new Set();
        const uniqueActions = new Set();

        dataCache.forEach(function (row) {
            const userKey = toStr(row['userId']);
            if (userKey) {
                uniqueUsers.add(userKey);
            }
            const moduleKey = deriveModuleId(row);
            if (moduleKey) {
                uniqueModules.add(moduleKey);
            }
            const actionKey = toStr(row['auditActionId']);
            if (actionKey && actionKey !== '0') {
                uniqueActions.add(actionKey);
            }
        });

        $('#metricAdtTotal').text(numberFormat(total));
        $('#metricAdtUsers').text(numberFormat(uniqueUsers.size));
        $('#metricAdtModules').text(numberFormat(uniqueModules.size));
        $('#metricAdtActions').text(numberFormat(uniqueActions.size));
    };

    const updateResultsSummary = function () {
        if (!oTableAdt) {
            $('#lblAdtCount').text('No entries loaded');
            return;
        }
        const filtered = oTableAdt.rows({filter: 'applied'}).count();
        const label = filtered ? `Showing ${numberFormat(filtered)} entr${filtered === 1 ? 'y' : 'ies'}` : 'No entries loaded';
        $('#lblAdtCount').text(label);
    };

    const updateScopeSummary = function () {
        const yearVal = toStr($('#optAdtYear').val());
        const monthIdx = Number($('#optAdtMonth').val());
        const monthName = Number.isInteger(monthIdx) && monthIdx >= 0 && monthIdx < monthFull.length ? monthFull[monthIdx] : 'Month';
    const moduleValRaw = toStr($('#optAdtModule').val());
        const moduleDesc = getModuleDesc(moduleValRaw);
        const actionDesc = getActionDesc($('#optAdtAction').val());

        const primaryScope = `${monthName} ${yearVal || ''}`.trim();
        const detailScope = actionDesc !== 'All Actions' ? `${moduleDesc} • ${actionDesc}` : moduleDesc;

        $('#lblAdtScope').text(`${primaryScope} • ${detailScope}`);
        $('#lblAdtSummary').text(`Filtered by ${primaryScope} / ${detailScope}`);
    };

    const bindSearchButtonState = function () {
        const isValid = formValidateSearch ? formValidateSearch.validateForm() : false;
        $('#btnAdtSearch').attr('disabled', !isValid);
    };

    this.init = function () {
        const arrYear = [];
        const arrMonth = [];
        const dt = new Date();
        const currentYear = dt.getFullYear();
        const currentMonth = dt.getMonth();

        for (let year = 2019; year <= currentYear; year++) {
            arrYear.push({yearId: year.toString(), yearDesc: year.toString()});
        }
        for (let i = 0; i < monthFull.length; i++) {
            arrMonth.push({monthId: i.toString(), monthDesc: monthFull[i]});
        }

        mzOption('optAdtYear', arrYear, 'Select Year', 'yearId', 'yearDesc', {}, 'required');
        mzOption('optAdtMonth', arrMonth, 'Select Month', 'monthId', 'monthDesc', {}, 'required', false);
        mzOption('optAdtModule', refAuditModule, 'All Module', 'auditModuleId', 'auditModuleDesc', {}, '', false);
        mzOption('optAdtAction', refAuditAction, 'All Action', 'auditActionId', 'auditActionDesc', {auditModuleId: '0'}, '', false);

        $('#optAdtYear').val(currentYear.toString());
        $('#optAdtMonth').val(currentMonth.toString());

        ['#optAdtYear', '#optAdtMonth', '#optAdtModule', '#optAdtAction'].forEach(function (selector) {
            refreshMaterialSelect(selector);
        });

        $('#optAdtModule').on('change', function () {
            const moduleVal = toStr($(this).val());
            const filterModule = moduleVal === '' ? '0' : moduleVal;
            mzOptionStop('optAdtAction', refAuditAction, 'All Action', 'auditActionId', 'auditActionDesc', {auditModuleId: filterModule}, '', false);
            refreshMaterialSelect('#optAdtAction');
            updateScopeSummary();
            bindSearchButtonState();
        });

        $('#optAdtYear, #optAdtMonth, #optAdtAction').on('change', function () {
            updateScopeSummary();
            bindSearchButtonState();
        });

        const vData = [
            { field_id: 'optAdtModule', type: 'select', name: 'Module', validator: {} },
            { field_id: 'optAdtAction', type: 'select', name: 'Action', validator: {} },
            { field_id: 'optAdtYear', type: 'select', name: 'Year', validator: { notEmpty: true } },
            { field_id: 'optAdtMonth', type: 'select', name: 'Month', validator: { notEmpty: true } }
        ];

        formValidateSearch = new MzValidate('formAdtSearch');
        formValidateSearch.registerFields(vData);

        $('#formAdtSearch').on('keyup change', function () {
            bindSearchButtonState();
        });

        oTableAdt = $('#dtAdtList').DataTable({
            bLengthChange: false,
            bFilter: true,
            pageLength: 100,
            aaSorting: [[1, 'asc']],
            autoWidth: false,
            language: _DATATABLE_LANGUAGE,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableAdt.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                applyRowLabels(nRow);
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateResultsSummary();
            },
            aoColumns: [
                { mData: null, bSortable: false },
                { mData: 'auditTimestamp' },
                {
                    mData: 'userId',
                    mRender: function (data) {
                        const key = toStr(data);
                        if (key && refUser && refUser[key]) {
                            return refUser[key]['userFirstName'] || refUser[key]['userFullName'] || key;
                        }
                        return key;
                    }
                },
                {
                    mData: null,
                    mRender: function (data, type, row) {
                        const moduleId = deriveModuleId(row);
                        return getModuleDesc(moduleId);
                    }
                },
                {
                    mData: 'auditActionId',
                    mRender: function (data) {
                        return getActionDesc(data);
                    }
                },
                { mData: 'auditIp' },
                { mData: 'auditRemark' }
            ]
        });

        $('#dtAdtList_filter').hide();

        $('#txtAdtSearch').on('keyup change', function () {
            const term = $(this).val() || '';
            oTableAdt.search(term).draw();
        });

        const btnAdtOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6],
                format: {
                    body: function (data, row, column, node) {
                        if (column === 0) {
                            return row + 1;
                        }
                        return typeof data === 'string' ? data.replace(/\s+/g, ' ').trim() : data;
                    }
                }
            }
        };

        $('#btnAdtExport').empty();
        new $.fn.dataTable.Buttons(oTableAdt, {
            buttons: [
                $.extend(true, {}, btnAdtOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Audit Trail List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-primary btn-sm btn-rounded waves-effect'
                }),
                $.extend(true, {}, btnAdtOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Audit Trail List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-sm btn-rounded waves-effect'
                }),
                $.extend(true, {}, btnAdtOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Audit Trail List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-primary btn-sm btn-rounded waves-effect'
                })
            ]
        }).container().appendTo($('#btnAdtExport'));

        $('#btnAdtSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAdt();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnAdtRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAdt();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        updateScopeSummary();
        bindSearchButtonState();
        self.genTableAdt();
    };

    this.genTableAdt = function () {
        const params = [
            'year=' + encodeURIComponent(toStr($('#optAdtYear').val())),
            'month=' + encodeURIComponent(toStr($('#optAdtMonth').val())),
            'moduleId=' + encodeURIComponent(toStr($('#optAdtModule').val())),
            'actionId=' + encodeURIComponent(toStr($('#optAdtAction').val()))
        ].join('&');

        const dataAdt = mzAjaxRequest('audit.php?' + params, 'GET');
        dataCache = Array.isArray(dataAdt) ? dataAdt : [];
        oTableAdt.clear().rows.add(dataCache).draw();
        updateMetrics();
        updateResultsSummary();
        updateScopeSummary();
        lastUpdated = getNowStamp();
        $('#lblAdtUpdated').text(`Updated ${lastUpdated}`);
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefAuditModule = function (_refAuditModule) {
        refAuditModule = _refAuditModule;
    };

    this.setRefAuditAction = function (_refAuditAction) {
        refAuditAction = _refAuditAction;
    };
}