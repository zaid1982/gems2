function MainMrf () {

    const className = 'MainMrf';
    let self = this;
    let oTableMrfList;
    let userSite;
    let userClient;
    let refStatus;
    let refClient;
    let refSite;
    let refUser;
    const arrYear = [];
    const currentYear = (new Date()).getFullYear();
    let formValidate;
    let mrfDataCache = [];
    let lastUpdatedText = '—';
    let selectedStatusId = '';
    let statusChipMap = { '': '#linkMrfAll' };
    const tableHeaders = ['#', 'Time Request', 'Request No', 'Work Order No', 'Requester', 'Remark', 'Status', 'MRF'];

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy errors
        }
        const $wrapper = $element.parent('.select-wrapper');
        if ($wrapper.length) {
            $wrapper.before($element);
            $wrapper.remove();
        }
        $element.siblings('.select-dropdown').remove();
        $element.materialSelect();
    };

    const applyTableDataLabels = function (tableSelector, headers) {
        $(`${tableSelector} tbody tr`).each(function () {
            $('td', this).each(function (index) {
                if (headers[index]) {
                    $(this).attr('data-label', headers[index]);
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
        return (typeof moment !== 'undefined' && moment) ? moment().format('MMM D, YYYY h:mm A') : new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableMrfList) {
            return;
        }
        const info = oTableMrfList.page.info();
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblMrfFilterCount').text(summaryText);
        $('#lblMrfListCount').text(summaryText);
        $('#lblMrfFilterUpdated').text(lastUpdatedText);
        $('#lblMrfListUpdated').text(lastUpdatedText);
    };

    const getStatusDesc = function (statusId) {
        if (!statusId && statusId !== 0) {
            return 'Unknown';
        }
        const statusKey = String(statusId);
        if (refStatus && refStatus[statusKey] && refStatus[statusKey]['statusDesc']) {
            return refStatus[statusKey]['statusDesc'];
        }
        return 'Unknown';
    };

    const getStatusBadge = function (statusId) {
        if (!statusId && statusId !== 0) {
            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
        }
        const statusKey = String(statusId);
        if (refStatus && refStatus[statusKey]) {
            const statusObj = refStatus[statusKey];
            const badgeClass = statusObj['statusColor'] ? statusObj['statusColor'] : 'badge-secondary';
            const desc = statusObj['statusDesc'] || 'Unknown';
            return `<span class="badge badge-pill ${badgeClass} z-depth-2">${desc}</span>`;
        }
        return '<span class="badge badge-pill badge-secondary">Unknown</span>';
    };

    const getRequesterName = function (userId) {
        if (!userId && userId !== 0) {
            return '—';
        }
        if (refUser && refUser[userId]) {
            const user = refUser[userId];
            if (user['userFirstName'] && user['userLastName']) {
                return `${user['userFirstName']} ${user['userLastName']}`;
            }
            if (user['userFirstName']) {
                return user['userFirstName'];
            }
            if (user['userLastName']) {
                return user['userLastName'];
            }
        }
        return '—';
    };

    const bindPdfLinks = function () {
        $('#dtMrfList tbody .lnkMrfListPdf').off('click').on('click', function () {
            const rowIndex = parseInt($(this).attr('data-row-index'), 10);
            if (isNaN(rowIndex)) {
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    const currentRow = oTableMrfList.row(rowIndex).data();
                    if (!currentRow) {
                        throw new Error('Unable to locate the selected request.');
                    }
                    const pdfId = currentRow['woTaskRequestMrfPdf'];
                    let pdfSrc;
                    if (pdfId !== null && currentRow['woTaskRequestMrfGenerate'] === 0) {
                        pdfSrc = mzAjaxRequest2(`wo_task_request/get_mrf_pdf_link/${pdfId}`, 'GET');
                    } else {
                        pdfSrc = mzAjaxRequest2(`wo_task_request/preview_mrf_pdf/${currentRow['woTaskRequestId']}`, 'GET');
                    }
                    $('#mpdf_title').html(`<i class="far fa-file-pdf text-white"></i> &nbsp;Material Request Form (MRF): ${currentRow['woTaskNo']}`);
                    $('#mpdf_iframe').attr('src', pdfSrc);
                    $('#modal_pdf').modal('show');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    const updateActiveStatusChip = function () {
        $('#quickMrfStatus .gems-status-chip').removeClass('active');
        const selector = statusChipMap[selectedStatusId || ''] || '#linkMrfAll';
        $(selector).addClass('active');
    };

    const setStatusFilter = function (statusId) {
        selectedStatusId = statusId || '';
        if (oTableMrfList) {
            oTableMrfList.draw();
            refreshListSummary();
        }
        updateActiveStatusChip();
    };

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!oTableMrfList || settings.nTable.id !== 'dtMrfList') {
            return true;
        }
        if (!selectedStatusId) {
            return true;
        }
        const rowData = oTableMrfList.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        const statusId = rowData['woTaskRequestStatus'];
        return String(statusId) === String(selectedStatusId);
    };

    const renderStatusChips = function (statusCounts, total) {
        const $container = $('#quickMrfStatus');
        if (!$container.length) {
            return;
        }
        const chips = [];
        statusChipMap = { '': '#linkMrfAll' };
        chips.push(`<a href="javascript:void(0);" class="gems-status-chip" id="linkMrfAll" data-status="">All <span class="chip-count">${mzFormatNumber(total, 0)}</span></a>`);
        const sortedStatuses = Object.keys(statusCounts).sort(function (a, b) {
            return statusCounts[b] - statusCounts[a];
        });
        sortedStatuses.forEach(function (statusId) {
            const chipId = `linkMrfStatus_${statusId}`;
            chips.push(`<a href="javascript:void(0);" class="gems-status-chip" id="${chipId}" data-status="${statusId}">${getStatusDesc(statusId)} <span class="chip-count">${mzFormatNumber(statusCounts[statusId], 0)}</span></a>`);
            statusChipMap[statusId] = `#${chipId}`;
        });
        $container.html(chips.join(''));
        $container.find('.gems-status-chip').off('click').on('click', function () {
            const status = $(this).attr('data-status') || '';
            setStatusFilter(status);
        });
        updateActiveStatusChip();
    };

    const updateStatusLegend = function (dataSet) {
        const statusCounts = {};
        dataSet.forEach(function (item) {
            const statusId = item['woTaskRequestStatus'];
            if (statusId === null || statusId === undefined || statusId === '') {
                return;
            }
            const key = String(statusId);
            statusCounts[key] = (statusCounts[key] || 0) + 1;
        });
        renderStatusChips(statusCounts, dataSet.length);
    };

    const updateMrfMetrics = function (dataSet) {
        const total = dataSet.length;
        const requesterMap = {};
        let pdfReady = 0;

        dataSet.forEach(function (item) {
            if (item['woTaskRequestOrderBy']) {
                requesterMap[item['woTaskRequestOrderBy']] = true;
            }
            if (item['woTaskRequestMrfPdf']) {
                pdfReady += 1;
            }
        });

        const pdfPending = Math.max(total - pdfReady, 0);

        $('#metricMrfTotal').text(mzFormatNumber(total, 0));
        $('#metricMrfRequesters').text(mzFormatNumber(Object.keys(requesterMap).length, 0));
        $('#metricMrfPdfReady').text(mzFormatNumber(pdfReady, 0));
        $('#metricMrfPdfPending').text(mzFormatNumber(pdfPending, 0)).toggleClass('text-warning', pdfPending > 0);
    };

    const bindSearchField = function (selector) {
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).off('input change keyup').on('input change keyup', function () {
            if (!oTableMrfList) {
                return;
            }
            oTableMrfList.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const triggerSearch = function () {
        if (!formValidate || !formValidate.validateNow()) {
            toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            return;
        }
        ShowLoader();
        setTimeout(function () {
            try {
                self.genMrfTable();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.init = function () {
        $('#mrfTableCard').hide();
        userSite = mzGetUserInfoByParam('siteId');
        userClient = mzGetUserInfoByParam('clientId');
        arrYear.length = 0;
        for (let year = 2021; year <= currentYear; year++) {
            arrYear.push({ yearId: year.toString(), yearDesc: year.toString() });
        }

        if (mzIsRoleExist('1,10')) {
            mzOption('optMrfClient', refClient, 'Select Client', 'clientId', 'clientName', { clientStatus: '1' }, 'required');
            initMaterialSelect('#optMrfClient');
            $('#optMrfClient').off('change').on('change', function () {
                mzOptionStop('optMrfSite', refSite, 'Select Site', 'siteId', 'siteName', { siteStatus: '1', clientId: $(this).val() }, 'required');
                initMaterialSelect('#optMrfSite');
            });
        } else {
            mzOption('optMrfClient', refClient, 'Select Client', 'clientId', 'clientName', { clientId: userClient, clientStatus: '1' }, 'required');
            mzOption('optMrfSite', refSite, 'Select Site', 'siteId', 'siteName', { siteId: userSite, siteStatus: '1' }, 'required');
            mzSetFieldValue('MrfClient', userClient, 'select', '', true);
            mzSetFieldValue('MrfSite', userSite, 'select', '', true);
            initMaterialSelect('#optMrfClient');
            initMaterialSelect('#optMrfSite');
        }

        mzOption('optMrfYear', arrYear, 'Select Year', 'yearId', 'yearDesc', {}, 'required');
        initMaterialSelect('#optMrfYear');
        mzOption('optMrfMonth', mzGetMonthArray(), 'Select Month', 'monthId', 'monthName', {}, 'required', false);
        initMaterialSelect('#optMrfMonth');

        const vData = [
            { field_id: 'optMrfClient', type: 'select', name: 'Client', validator: { notEmpty: true } },
            { field_id: 'optMrfSite', type: 'select', name: 'Site', validator: { notEmpty: true } },
            { field_id: 'optMrfYear', type: 'select', name: 'Year', validator: { notEmpty: true } },
            { field_id: 'optMrfMonth', type: 'select', name: 'Month', validator: { notEmpty: true } }
        ];

        formValidate = new MzValidate('formMrf');
        formValidate.registerFields(vData);

        $('#btnMrfSearch').off('click').on('click', function (evt) {
            evt.preventDefault();
            triggerSearch();
        });

        $('#btnMrfRefresh').off('click').on('click', function () {
            triggerSearch();
        });

        $.fn.dataTable.ext.search.push(statusFilterFn);

        oTableMrfList = $('#dtMrfList').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            pagingType: 'simple_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: [0, 7], orderable: false, className: 'text-center align-middle' },
                { targets: [1, 2, 3, 4, 5, 6], className: 'align-middle' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableMrfList.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels('#dtMrfList', tableHeaders);
                refreshListSummary();
                bindPdfLinks();
            },
            aoColumns: [
                { mData: null },
                { mData: 'woTaskRequestTimeOrdered', mRender: function (data, type) {
                        if (!data) {
                            return type === 'display' ? '<span class="text-muted">—</span>' : '';
                        }
                        if (type !== 'display') {
                            return data;
                        }
                        if (typeof moment !== 'undefined' && moment(data).isValid()) {
                            return moment(data).format('DD MMM YYYY, h:mm A');
                        }
                        return data;
                    } },
                { mData: 'woTaskRequestNo', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? `<span class="font-weight-500">${data}</span>` : '<span class="text-muted">—</span>';
                    } },
                { mData: 'woTaskNo', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">—</span>';
                    } },
                { mData: 'woTaskRequestOrderBy', mRender: function (data, type) {
                        const name = getRequesterName(data);
                        return type !== 'display' ? name : name;
                    } },
                { mData: 'woTaskRequestRemark', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">—</span>';
                    } },
                { mData: 'woTaskRequestStatus', mRender: function (data, type) {
                        if (type !== 'display') {
                            return getStatusDesc(data);
                        }
                        const statusMap = {
                            '1': 'pending',
                            '2': 'in-progress', 
                            '3': 'completed',
                            '4': 'cancelled'
                        };
                        const statusClass = statusMap[data] || 'pending';
                        const statusText = getStatusDesc(data);
                        return `<span class="status-badge ${statusClass}">${statusText}</span>`;
                    } },
                { mData: null, mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return 'View PDF';
                        }
                        return `<div class="action-btn-group">
                            <button type="button" class="btn-action btn-view lnkMrfListPdf" data-row-index="${meta.row}" title="View PDF">
                                <i class="fas fa-file-pdf"></i>
                                <span>View PDF</span>
                            </button>
                        </div>`;
                    } }
            ]
        });

        $('#dtMrfList_filter').hide();
        bindSearchField('#txtMrfSearch');

        let exportCounter = 1;
        const btnExportOptions = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportCounter = 1;
                            }
                            return exportCounter++;
                        }
                        if (column === 6) {
                            const rowData = oTableMrfList.row(row).data();
                            return rowData ? getStatusDesc(rowData['woTaskRequestStatus']) : stripHtml(data);
                        }
                        if (column === 7) {
                            return 'View PDF';
                        }
                        return stripHtml(data);
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableMrfList, {
            buttons: [
                $.extend(true, {}, btnExportOptions, {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnExportOptions, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - MRF List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnExportOptions, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - MRF List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnExportOptions, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - MRF List',
                    titleAttr: 'PDF',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtMrfExport'));

        updateActiveStatusChip();
    };

    this.genMrfTable = function () {
        const siteId = $('#optMrfSite').val();
        const yearVal = $('#optMrfYear').val();
        const monthVal = $('#optMrfMonth').val();
        const dataDb = mzAjaxRequest2(`wo_task_request/list_mrf/${siteId}/${yearVal}/${monthVal}`, 'GET');
        mrfDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTableMrfList.clear().rows.add(mrfDataCache).draw();
        mrfDataCache = oTableMrfList.rows().data().toArray();
        lastUpdatedText = getNowStamp();
        updateMrfMetrics(mrfDataCache);
        updateStatusLegend(mrfDataCache);
        refreshListSummary();
        setStatusFilter('');
        $('#mrfTableCard').show();
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.getClassName = function () {
        return className;
    };
}