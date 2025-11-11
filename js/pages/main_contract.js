function MainContract() {

    const className = 'MainContract';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refClient;
    let refSite;
    let oTableContract;
    let modalContractClass;
    let sectionContractClass;
    let contractDataCache = [];
    let lastListUpdatedText = '—';
    let statusFilterValue = '';
    let clientFilterValue = '';
    let siteFilterValue = '';

    const statusChipMap = {
        '': '#linkCcrAll',
        '1': '#linkCcrActive',
        '2': '#linkCcrInactive',
        '5': '#linkCcrArchived'
    };

    const tableHeaders = ['#', 'Client', 'Site', 'Contract', 'Description', 'Start Date', 'End Date', 'Status', 'Actions'];

    const initMaterialSelect = function (selector) {
        // const $element = $(selector);
        // if (!$element.length || typeof $element.materialSelect !== 'function') {
        //     return;
        // }
        // try {
        //     $element.materialSelect('destroy');
        // } catch (e) {
        //     // ignore destroy errors when reinitialising
        // }
        // const $wrapper = $element.parent('.select-wrapper');
        // if ($wrapper.length) {
        //     $wrapper.before($element);
        //     $wrapper.remove();
        // }
        // $element.siblings('.select-dropdown').remove();
        // $element.materialSelect();
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
        if (!oTableContract) {
            return;
        }
        const info = oTableContract.page.info();
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblCcrFilterCount').text(summaryText);
        $('#lblCcrListCount').text(summaryText);
        $('#lblCcrFilterUpdated').text(lastListUpdatedText);
        $('#lblCcrListUpdated').text(lastListUpdatedText);
    };

    const getStatusDesc = function (status) {
        if (!status || !refStatus || !refStatus[status]) {
            return 'Unknown';
        }
        return refStatus[status]['statusDesc'] || 'Unknown';
    };

    const updateStatusChips = function (counts) {
        const labelMap = {
            '': 'All',
            '1': getStatusDesc('1'),
            '2': getStatusDesc('2'),
            '5': getStatusDesc('5')
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(`${labelMap[status]} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
    };

    const getClientName = function (clientId) {
        if (refClient && refClient[clientId]) {
            return refClient[clientId]['clientName'];
        }
        return 'Unknown Client';
    };

    const getSiteName = function (siteId) {
        if (refSite && refSite[siteId]) {
            return refSite[siteId]['siteName'];
        }
        return 'Unknown Site';
    };

    const getStatusBadge = function (status) {
        if (!status || !refStatus || !refStatus[status]) {
            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
        }
        const statusObj = refStatus[status];
        return `<span class="badge badge-pill ${statusObj['statusColor']} z-depth-2">${statusObj['statusDesc']}</span>`;
    };

    const getMoment = function () {
        return (typeof moment !== 'undefined' && moment) ? moment : null;
    };

    const updateContractMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let expiringSoon = 0;
        let ended = 0;
        const now = getMoment() ? getMoment()() : null;

        dataSet.forEach(function (item) {
            total += 1;
            const status = String(item['contractStatus']);
            if (status === '1') {
                active += 1;
            }
            const endDate = item['contractDateEnd'];
            if (endDate) {
                if (now) {
                    const diffDays = getMoment()(endDate, 'YYYY-MM-DD').diff(now, 'days');
                    if (diffDays < 0) {
                        ended += 1;
                    } else if (diffDays <= 30) {
                        expiringSoon += 1;
                    }
                } else {
                    const end = new Date(endDate);
                    const today = new Date();
                    const diff = Math.floor((end - today) / (1000 * 60 * 60 * 24));
                    if (diff < 0) {
                        ended += 1;
                    } else if (diff <= 30) {
                        expiringSoon += 1;
                    }
                }
            }
        });

        $('#metricCcrTotal').text(mzFormatNumber(total, 0));
        $('#metricCcrActive').text(mzFormatNumber(active, 0));
        $('#metricCcrExpiring').text(mzFormatNumber(expiringSoon, 0)).toggleClass('text-warning', expiringSoon > 0);
        $('#metricCcrEnded').text(mzFormatNumber(ended, 0)).toggleClass('text-danger', ended > 0);

        const inactive = dataSet.filter(function (item) {
            const status = String(item['contractStatus']);
            return status === '2';
        }).length;
        const archived = dataSet.filter(function (item) {
            const status = String(item['contractStatus']);
            return status === '5';
        }).length;

        updateStatusChips({
            '': total,
            '1': active,
            '2': inactive,
            '5': archived
        });
    };

    const populateStatusFilter = function () {
        const $select = $('#optCcrStatus');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Status</option>'];
        ['1', '2', '5'].forEach(function (statusId) {
            if (refStatus && refStatus[statusId]) {
                options.push(`<option value="${statusId}">${refStatus[statusId]['statusDesc']}</option>`);
            }
        });
        $select.html(options.join(''));
        initMaterialSelect('#optCcrStatus');
        $select.off('change').on('change', handleStatusSelectChange);
    };

    const populateClientFilter = function () {
        const $select = $('#optCcrClientId');
        if (!$select.length) {
            return;
        }
        const clients = [];
        if (refClient) {
            $.each(refClient, function (key, client) {
                if (!client || typeof client !== 'object') {
                    return true;
                }
                const id = client['clientId'] || key;
                const name = client['clientName'] || '';
                clients.push({ id: id, name: name });
                return true;
            });
        }
        clients.sort(function (a, b) {
            return (a.name || '').localeCompare(b.name || '');
        });
        const options = ['<option value="" selected>All Clients</option>'];
        clients.forEach(function (client) {
            options.push(`<option value="${client.id}">${client.name}</option>`);
        });
        $select.html(options.join(''));
        initMaterialSelect('#optCcrClientId');
        if (clientFilterValue) {
            $select.val(clientFilterValue);
            $select.materialSelect();
        }
        $select.off('change').on('change', handleClientSelectChange);
    };

    const populateSiteFilter = function () {
        const $select = $('#optCcrSiteId');
        if (!$select.length) {
            return;
        }
        const sites = [];
        if (refSite) {
            $.each(refSite, function (key, site) {
                if (!site || typeof site !== 'object') {
                    return true;
                }
                const siteId = site['siteId'] || key;
                const siteName = site['siteName'] || '';
                const siteClientId = site['clientId'];
                if (clientFilterValue && String(siteClientId) !== String(clientFilterValue)) {
                    return true;
                }
                sites.push({ id: siteId, name: siteName });
                return true;
            });
        }
        sites.sort(function (a, b) {
            return (a.name || '').localeCompare(b.name || '');
        });
        const options = ['<option value="" selected>All Sites</option>'];
        sites.forEach(function (site) {
            options.push(`<option value="${site.id}">${site.name}</option>`);
        });
        if (siteFilterValue && !sites.some(function (site) { return String(site.id) === String(siteFilterValue); })) {
            siteFilterValue = '';
        }
        $select.html(options.join(''));
        initMaterialSelect('#optCcrSiteId');
        if (siteFilterValue) {
            $select.val(siteFilterValue);
            $select.materialSelect();
        }
        $select.off('change').on('change', handleSiteSelectChange);
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
        if (oTableContract) {
            oTableContract.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optCcrStatus');
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

    const setClientFilter = function (value, skipSelect) {
        const newValue = value || '';
        clientFilterValue = newValue;
        if (clientFilterValue && siteFilterValue && refSite && refSite[siteFilterValue]) {
            const siteClientId = refSite[siteFilterValue]['clientId'];
            if (String(siteClientId) !== String(clientFilterValue)) {
                siteFilterValue = '';
            }
        }
        if (oTableContract) {
            oTableContract.draw();
            refreshListSummary();
        }
        populateSiteFilter();
        if (!skipSelect) {
            const $clientSelect = $('#optCcrClientId');
            if ($clientSelect.length) {
                try {
                    $clientSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $clientSelect.val(clientFilterValue);
                $clientSelect.materialSelect();
                $clientSelect.off('change').on('change', handleClientSelectChange);
            }
        }
    };

    const setSiteFilter = function (value, skipSelect) {
        siteFilterValue = value || '';
        if (oTableContract) {
            oTableContract.draw();
            refreshListSummary();
        }
        if (!skipSelect) {
            const $siteSelect = $('#optCcrSiteId');
            if ($siteSelect.length) {
                try {
                    $siteSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $siteSelect.val(siteFilterValue);
                $siteSelect.materialSelect();
                $siteSelect.off('change').on('change', handleSiteSelectChange);
            }
        }
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const handleClientSelectChange = function () {
        setClientFilter($(this).val() || '', true);
    };

    const handleSiteSelectChange = function () {
        setSiteFilter($(this).val() || '', true);
    };

    const bindRowActions = function () {
        $('.lnkCcrContractEdit').off('click').on('click', function () {
            const row = oTableContract.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalContractClass.edit(rowData['contractId'], row.index());
        });
        $('.lnkCcrContractDetails').off('click').on('click', function () {
            const row = oTableContract.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            sectionContractClass.load(rowData['contractId'], row.index());
        });
        $('.lnkCcrContractDeactivate').off('click').on('click', function () {
            const row = oTableContract.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalContractClass.deactivate(rowData['contractId'], row.index());
        });
        $('.lnkCcrContractActivate').off('click').on('click', function () {
            const row = oTableContract.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalContractClass.activate(rowData['contractId'], row.index());
        });
        $('.lnkCcrContractDelete').off('click').on('click', function () {
            const row = oTableContract.row($(this).closest('tr'));
            const rowData = row.data();
            if (!rowData) {
                return;
            }
            modalConfirmDeleteClass.delete(rowData['contractId'], modalContractClass);
        });
    };

    const contractFilterFn = function (settings, data, dataIndex) {
        if (!oTableContract || settings.nTable.id !== 'dtCcrContract') {
            return true;
        }
        const rowData = oTableContract.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        if (statusFilterValue && String(rowData['contractStatus']) !== statusFilterValue) {
            return false;
        }
        if (clientFilterValue && String(rowData['clientId']) !== clientFilterValue) {
            return false;
        }
        if (siteFilterValue && String(rowData['siteId']) !== siteFilterValue) {
            return false;
        }
        return true;
    };

    this.init = function () {
        $.fn.dataTable.ext.search.push(contractFilterFn);

        populateStatusFilter();
        populateClientFilter();
        populateSiteFilter();

        oTableContract = $('#dtCcrContract').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            pagingType: 'simple_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: [0, 7, 8], orderable: false, className: 'text-center' },
                { targets: [1, 2, 3], className: 'text-nowrap' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableContract.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                applyTableDataLabels('#dtCcrContract', tableHeaders);
                bindRowActions();
                refreshListSummary();
            },
            aoColumns: [
                { mData: null },
                { mData: 'clientId', mRender: function (data, type) {
                        const name = getClientName(data);
                        return type !== 'display' ? name : name;
                    } },
                { mData: 'siteId', mRender: function (data, type) {
                        const name = getSiteName(data);
                        return type !== 'display' ? name : name;
                    } },
                { mData: 'contractName', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data || '<span class="text-muted">—</span>';
                    } },
                { mData: 'contractDesc', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data && data.trim() !== '' ? data : '<span class="text-muted">No description</span>';
                    } },
                { mData: 'contractDateStart', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data || '<span class="text-muted">—</span>';
                    } },
                { mData: 'contractDateEnd', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data || '<span class="text-muted">—</span>';
                    } },
                { mData: 'contractStatus', mRender: function (data, type) {
                        if (type !== 'display') {
                            return getStatusDesc(data);
                        }
                        return `<h6 class="mb-0">${getStatusBadge(data)}</h6>`;
                    } },
                { mData: null, bSortable: false, sClass: 'text-center action-cell', mRender: function (data, type, row, meta) {
                        let label = '<div class="action-btn-group">';
                        label += '<button type="button" class="btn-action btn-edit lnkCcrContractEdit" id="lnkCcrContractEdit_' + meta.row + '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></button>';
                        label += '<button type="button" class="btn-action btn-view lnkCcrContractDetails" id="lnkCcrContractDetails_' + meta.row + '" data-toggle="tooltip" title="Details"><i class="fas fa-search-plus"></i></button>';
                        if (row['contractStatus'] === '1') {
                            label += '<button type="button" class="btn-action btn-delete lnkCcrContractDeactivate" id="lnkCcrContractDeactivate_' + meta.row + '" data-toggle="tooltip" title="Deactivate"><i class="fas fa-toggle-off"></i></button>';
                        } else {
                            label += '<button type="button" class="btn-action btn-edit lnkCcrContractActivate" id="lnkCcrContractActivate_' + meta.row + '" data-toggle="tooltip" title="Activate"><i class="fas fa-toggle-on"></i></button>';
                        }
                        label += '<button type="button" class="btn-action btn-delete lnkCcrContractDelete" id="lnkCcrContractDelete_' + meta.row + '" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                        label += '</div>';
                        return label;
                    } }
            ]
        });
        $('#dtCcrContract_filter').hide();

        $('#txtCcrContractSearch').on('keyup change', function () {
            oTableContract.search($(this).val()).draw();
        });

        let exportCounter = 1;
        const btnContractOpt = {
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
                        if (column === 7) {
                            const rowData = oTableContract.row(row).data();
                            const statusVal = rowData ? rowData['contractStatus'] : '';
                            return getStatusDesc(statusVal);
                        }
                        return stripHtml(data);
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableContract, {
            buttons: [
                $.extend(true, {}, btnContractOpt, {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Contract List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnContractOpt, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Contract List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnContractOpt, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Contract List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtCcrContractExport'));

        $('#btnCcrContractAdd').on('click', function () {
            modalContractClass.add();
        });

        $('#btnDtCcrContractRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCcr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);
        setClientFilter('', true);
        setSiteFilter('', true);

        self.genTableCcr(0);
    };

    this.genTableCcr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refContract = mzGetLocalRaw('gems_contract', versionLocal, [], 'contract');
        contractDataCache = Array.isArray(refContract) ? refContract : [];
        oTableContract.clear().rows.add(contractDataCache).draw();
        contractDataCache = oTableContract.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateContractMetrics(contractDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
    };

    this.addTableCcr = function (_dataAdd) {
        oTableContract.row.add(_dataAdd).draw();
        contractDataCache = oTableContract.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateContractMetrics(contractDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
    };

    this.updateTableCcr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableContract.row(_rowEdit).data();
        if (!currentRow) {
            return;
        }
        if (typeof _dataEdit['contractName'] !== 'undefined') {
            currentRow['contractName'] = _dataEdit['contractName'];
        }
        if (typeof _dataEdit['contractDesc'] !== 'undefined') {
            currentRow['contractDesc'] = _dataEdit['contractDesc'];
        }
        if (typeof _dataEdit['contractDateStart'] !== 'undefined') {
            currentRow['contractDateStart'] = _dataEdit['contractDateStart'];
        }
        if (typeof _dataEdit['contractDateEnd'] !== 'undefined') {
            currentRow['contractDateEnd'] = _dataEdit['contractDateEnd'];
        }
        if (typeof _dataEdit['contractStatus'] !== 'undefined') {
            currentRow['contractStatus'] = _dataEdit['contractStatus'];
        }
        if (typeof _dataEdit['clientId'] !== 'undefined') {
            currentRow['clientId'] = _dataEdit['clientId'];
        }
        if (typeof _dataEdit['siteId'] !== 'undefined') {
            currentRow['siteId'] = _dataEdit['siteId'];
        }
        oTableContract.row(_rowEdit).data(currentRow).draw();
        contractDataCache = oTableContract.rows().data().toArray();
        lastListUpdatedText = getNowStamp();
        updateContractMetrics(contractDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        setClientFilter(clientFilterValue || '', true);
        setSiteFilter(siteFilterValue || '', true);
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

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalContractClass = function (_modalContractClass) {
        modalContractClass = _modalContractClass;
    };

    this.setSectionContractClass = function (_sectionContractClass) {
        sectionContractClass = _sectionContractClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}