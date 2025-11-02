function MainClient() {

    const className = 'MainClient';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refSeverity;
    let refFailureCode;
    let oTableClient;
    let modalClientClass;
    let modalSeverityHourClass;
    let clientDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';

    const statusChipMap = {
        '': '#linkClnAll',
        '1': '#linkClnActive',
        '2': '#linkClnInactive',
        '5': '#linkClnArchived'
    };

    const tableHeaders = ['#', 'Client Name', 'Description', 'Severity KPI', 'Failure Codes', 'Status', 'Actions'];

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

    const getNowStamp = function () {
        return (typeof moment !== 'undefined' && moment) ? moment().format('MMM D, YYYY h:mm A') : new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableClient) {
            return;
        }
        const info = oTableClient.page.info();
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblClnFilterCount').text(summaryText);
        $('#lblClnListCount').text(summaryText);
        $('#lblClnFilterUpdated').text(lastListUpdatedText);
        $('#lblClnListUpdated').text(lastListUpdatedText);
    };

    const updateStatusChips = function (counts) {
        const labelMap = {
            '': 'All',
            '1': refStatus && refStatus[1] ? refStatus[1]['statusDesc'] : 'Active',
            '2': refStatus && refStatus[2] ? refStatus[2]['statusDesc'] : 'Inactive',
            '5': refStatus && refStatus[5] ? refStatus[5]['statusDesc'] : 'Archived'
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(`${labelMap[status]} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
    };

    const updateClientMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let configuredKpi = 0;

        dataSet.forEach(function (item) {
            total += 1;
            const status = String(item['clientStatus']);
            switch (status) {
                case '1':
                    active += 1;
                    break;
                case '2':
                    inactive += 1;
                    break;
                case '5':
                    archived += 1;
                    break;
                default:
                    break;
            }
            if (item['severities'] && item['severities'] !== '' && item['severityHours'] && item['severityHours'] !== '') {
                configuredKpi += 1;
            }
        });

        $('#metricClnTotal').text(mzFormatNumber(total, 0));
        $('#metricClnActive').text(mzFormatNumber(active, 0));
        $('#metricClnInactive').text(mzFormatNumber(inactive, 0)).toggleClass('text-warning', inactive > 0);
        $('#metricClnWithKpi').text(mzFormatNumber(configuredKpi, 0)).toggleClass('text-success', configuredKpi > 0);

        updateStatusChips({
            '': total,
            '1': active,
            '2': inactive,
            '5': archived
        });
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

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableClient) {
            oTableClient.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optClnStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusSelectChange);
            }
        }
    };

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!oTableClient || settings.nTable.id !== 'dtClnClient') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = oTableClient.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        return String(rowData['clientStatus']) === statusFilterValue;
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const renderSeverityColumn = function (row, metaRow) {
        const severity = row['severities'] || '';
        const severityHour = row['severityHours'] || '';
        const severityRespondTime = row['severityRespondTime'] || '';
        if (!severity || !severityHour) {
            return '<span class="text-muted">Not configured</span>';
        }
        const severitySplit = severity.split(',');
        const hourSplit = severityHour.split(',');
        const respondSplit = severityRespondTime.split(',');
        const listItems = [];
        for (let j = 0; j < severitySplit.length; j++) {
            const severityId = severitySplit[j];
            const severityName = refSeverity && refSeverity[severityId] ? refSeverity[severityId]['severityName'] : `Severity ${severityId}`;
            const hourValue = hourSplit[j] || '0';
            const respondValue = respondSplit[j] || '0';
            const linkId = `lnkClnClientHourEdit_${metaRow}_${severityId}__${hourValue}___${respondValue}`;
            listItems.push(`<li>${severityName} - ${respondValue}-minute/${hourValue}-hour <button type="button" class="btn-action btn-edit lnkClnClientHourEdit" id="${linkId}" data-severity-id="${severityId}" data-severity-hour="${hourValue}" data-severity-respond="${respondValue}" data-toggle="tooltip" data-placement="top" title="Edit KPI hours"><i class="fas fa-pen-alt"></i></button></li>`);
        }
        return `<ul class="list-unstyled mb-0">${listItems.join('')}</ul>`;
    };

    const getSeverityText = function (row) {
        const severity = row['severities'] || '';
        const severityHour = row['severityHours'] || '';
        const severityRespondTime = row['severityRespondTime'] || '';
        if (!severity || !severityHour) {
            return 'Not configured';
        }
        const severitySplit = severity.split(',');
        const hourSplit = severityHour.split(',');
        const respondSplit = severityRespondTime ? severityRespondTime.split(',') : [];
        const parts = [];
        for (let j = 0; j < severitySplit.length; j++) {
            const severityId = severitySplit[j];
            const severityName = refSeverity && refSeverity[severityId] ? refSeverity[severityId]['severityName'] : `Severity ${severityId}`;
            const hourValue = hourSplit[j] || '0';
            const respondValue = respondSplit[j] || '0';
            parts.push(`${severityName}: ${respondValue} min / ${hourValue} hr`);
        }
        return parts.join('; ');
    };

    const renderFailureCodesColumn = function (row) {
        const failureCode = row['failureCodes'] || '';
        if (!failureCode) {
            return '<span class="text-muted">No failure codes</span>';
        }
        const failureCodeSplit = failureCode.split(',');
        const listItems = [];
        for (let j = 0; j < failureCodeSplit.length; j++) {
            const failureCodeId = failureCodeSplit[j];
            const failureCodeName = refFailureCode && refFailureCode[failureCodeId] ? refFailureCode[failureCodeId]['failureCodeName'] : `Failure Code ${failureCodeId}`;
            listItems.push(`<li>${failureCodeName}</li>`);
        }
        return `<ul class="list-unstyled mb-0">${listItems.join('')}</ul>`;
    };

    const getFailureCodesText = function (row) {
        const failureCode = row['failureCodes'] || '';
        if (!failureCode) {
            return 'No failure codes';
        }
        const failureCodeSplit = failureCode.split(',');
        const names = [];
        for (let j = 0; j < failureCodeSplit.length; j++) {
            const failureCodeId = failureCodeSplit[j];
            const failureCodeName = refFailureCode && refFailureCode[failureCodeId] ? refFailureCode[failureCodeId]['failureCodeName'] : `Failure Code ${failureCodeId}`;
            names.push(failureCodeName);
        }
        return names.join(', ');
    };

    const bindRowActions = function () {
        $('.lnkClnClientEdit').off('click').on('click', function () {
            const row = oTableClient.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalClientClass.edit(rowData['clientId'], row.index());
        });
        $('.lnkClnClientDeactivate').off('click').on('click', function () {
            const row = oTableClient.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalClientClass.deactivate(rowData['clientId'], row.index());
        });
        $('.lnkClnClientActivate').off('click').on('click', function () {
            const row = oTableClient.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalClientClass.activate(rowData['clientId'], row.index());
        });
        $('.lnkClnClientDelete').off('click').on('click', function () {
            const row = oTableClient.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalConfirmDeleteClass.delete(rowData['clientId'], modalClientClass);
        });
        $('.lnkClnClientHourEdit').off('click').on('click', function () {
            const row = oTableClient.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            const passParam = {
                clientName: rowData['clientName'],
                severityId: $(this).data('severity-id'),
                severityRespondTime: $(this).data('severity-respond'),
                severityHour: $(this).data('severity-hour')
            };
            modalSeverityHourClass.edit(rowData['clientId'], row.index(), passParam);
        });
    };

    this.init = function () {
        $.fn.dataTable.ext.search.push(statusFilterFn);

        initMaterialSelect('#optClnStatus');

        oTableClient = $('#dtClnClient').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            pagingType: 'simple_numbers',
            autoWidth: false,
            columnDefs: [
                {targets: [0, 5, 6], orderable: false, className: 'text-center'},
                {targets: [1], className: 'text-nowrap'}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableClient.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                applyTableDataLabels('#dtClnClient', tableHeaders);
                bindRowActions();
                refreshListSummary();
            },
            aoColumns: [
                {mData: null},
                {mData: 'clientName'},
                {mData: 'clientDesc'},
                {mData: null, mRender: function (data, type, row, meta) {
                        if (type !== 'display') {
                            return getSeverityText(row);
                        }
                        return renderSeverityColumn(row, meta.row);
                    }},
                {mData: null, mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return getFailureCodesText(row);
                        }
                        return renderFailureCodesColumn(row);
                    }},
                {mData: null, mRender: function (data, type, row) {
                        const status = row['clientStatus'];
                        if (type !== 'display') {
                            if (!status || !refStatus[status]) {
                                return 'Unknown';
                            }
                            return refStatus[status]['statusDesc'];
                        }
                        if (!status || !refStatus[status]) {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return `<h6 class="mb-0"><span class="badge badge-pill ${refStatus[status]['statusColor']} z-depth-2">${refStatus[status]['statusDesc']}</span></h6>`;
                    }},
                {mData: null, bSortable: false, sClass: 'text-center action-cell', mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        label += '<button type="button" class="btn-action btn-edit lnkClnClientEdit" id="lnkClnClientEdit_' + meta.row + '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></button>';
                        if (row['clientStatus'] === '1') {
                            label += '<button type="button" class="btn-action btn-delete lnkClnClientDeactivate" id="lnkClnClientDeactivate_' + meta.row + '" data-toggle="tooltip" title="Deactivate"><i class="fas fa-toggle-off"></i></button>';
                        } else {
                            label += '<button type="button" class="btn-action btn-edit lnkClnClientActivate" id="lnkClnClientActivate_' + meta.row + '" data-toggle="tooltip" title="Activate"><i class="fas fa-toggle-on"></i></button>';
                        }
                        label += '<button type="button" class="btn-action btn-delete lnkClnClientDelete" id="lnkClnClientDelete_' + meta.row + '" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                        label += '</div>';
                        return label;
                    }},
                {mData: 'clientStatus', visible: false},
                {mData: 'clientId', visible: false}
            ]
        });
        $('#dtClnClient_filter').hide();

        $('#txtClnClientSearch').on('keyup change', function () {
            oTableClient.search($(this).val()).draw();
        });

        $('#optClnStatus').on('change', handleStatusSelectChange);

        let exportCounter = 1;
        const btnClientOpt = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportCounter = 1;
                            }
                            return exportCounter++;
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableClient, {
            buttons: [
                $.extend(true, {}, btnClientOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Client List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnClientOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Client List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnClientOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Client List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtClnClientExport'));

        $('#btnClnClientAdd').on('click', function () {
            modalClientClass.add();
        });

        $('#btnDtClnClientRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCln();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);

        self.genTableCln();
    };

    this.genTableCln = function () {
        const refClient = mzAjaxRequest('client.php?type=with_severity', 'GET');
        clientDataCache = Array.isArray(refClient) ? refClient : [];
        oTableClient.clear().rows.add(clientDataCache).draw();
        lastListUpdatedText = getNowStamp();
        updateClientMetrics(clientDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.addTableCln = function (_dataAdd) {
        oTableClient.row.add(_dataAdd).draw();
        clientDataCache = oTableClient.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateClientMetrics(clientDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.updateTableCln = function (_dataEdit, _rowEdit) {
        const currentRow = oTableClient.row(_rowEdit).data();
        if (!currentRow) {
            return;
        }
        if (typeof _dataEdit['clientName'] !== 'undefined') {
            currentRow['clientName'] = _dataEdit['clientName'];
        }
        if (typeof _dataEdit['clientDesc'] !== 'undefined') {
            currentRow['clientDesc'] = _dataEdit['clientDesc'];
        }
        if (typeof _dataEdit['clientStatus'] !== 'undefined') {
            currentRow['clientStatus'] = _dataEdit['clientStatus'];
        }
        if (typeof _dataEdit['severities'] !== 'undefined') {
            currentRow['severities'] = _dataEdit['severities'];
        }
        if (typeof _dataEdit['severityHours'] !== 'undefined') {
            currentRow['severityHours'] = _dataEdit['severityHours'];
        }
        if (typeof _dataEdit['severityRespondTime'] !== 'undefined') {
            currentRow['severityRespondTime'] = _dataEdit['severityRespondTime'];
        }
        if (typeof _dataEdit['failureCodes'] !== 'undefined') {
            currentRow['failureCodes'] = _dataEdit['failureCodes'];
        }
        oTableClient.row(_rowEdit).data(currentRow).draw();
        clientDataCache = oTableClient.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateClientMetrics(clientDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };

    this.setRefFailureCode = function (_refFailureCode) {
        refFailureCode = _refFailureCode;
    };

    this.setModalClientClass = function (_modalClientClass) {
        modalClientClass = _modalClientClass;
    };

    this.setModalSeverityHourClass = function (_modalSeverityHourClass) {
        modalSeverityHourClass = _modalSeverityHourClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}