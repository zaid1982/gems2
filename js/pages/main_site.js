function MainSite() {

        const className = 'MainSite';
        let self = this;
        let versionLocal;
        let refStatus;
        let refClient;
        let oTableSite;
        let modalSiteClass;
        let modalConfirmDeleteClass;
        let siteDataCache = [];
        let lastListUpdatedText = '—';
        let statusFilterValue = '';
        let clientFilterValue = '';

        const statusChipMap = {
            '': '#linkSteAll',
            '1': '#linkSteActive',
            '2': '#linkSteInactive',
            '5': '#linkSteArchived'
        };

        const tableHeaders = ['#', 'Client', 'Site Name', 'Site Code', 'Work Request', 'Public QR', 'Status', 'Actions'];

        const initMaterialSelect = function (selector) {
            const $element = $(selector);
            if (!$element.length || typeof $element.materialSelect !== 'function') {
                return;
            }
            try {
                $element.materialSelect('destroy');
            } catch (e) {
                // ignore destroy errors when reinitialising
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
            if (!oTableSite) {
                return;
            }
            const info = oTableSite.page.info();
            const showing = info ? (info.end - info.start) : 0;
            const total = info ? info.recordsDisplay : 0;
            const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
            $('#lblSteFilterCount').text(summaryText);
            $('#lblSteListCount').text(summaryText);
            $('#lblSteFilterUpdated').text(lastListUpdatedText);
            $('#lblSteListUpdated').text(lastListUpdatedText);
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

        const formatYesNo = function (value) {
            return value === '1' || value === 1 ? 'Yes' : 'No';
        };

        const updateSiteMetrics = function (dataSet) {
            let total = 0;
            let active = 0;
            let inactive = 0;
            let archived = 0;
            let wrEnabled = 0;
            let publicEnabled = 0;

            dataSet.forEach(function (item) {
                total += 1;
                const status = String(item['siteStatus']);
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
                if (item['siteIsWr'] === '1' || item['siteIsWr'] === 1) {
                    wrEnabled += 1;
                }
                if (item['siteIsPublic'] === '1' || item['siteIsPublic'] === 1) {
                    publicEnabled += 1;
                }
            });

            $('#metricSteTotal').text(mzFormatNumber(total, 0));
            $('#metricSteActive').text(mzFormatNumber(active, 0));
            $('#metricSteWr').text(mzFormatNumber(wrEnabled, 0)).toggleClass('text-success', wrEnabled > 0);
            $('#metricStePublic').text(mzFormatNumber(publicEnabled, 0)).toggleClass('text-success', publicEnabled > 0);

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

        const statusFilterFn = function (settings, data, dataIndex) {
            if (!oTableSite || settings.nTable.id !== 'dtSteSite') {
                return true;
            }
            if (!statusFilterValue) {
                return true;
            }
            const rowData = oTableSite.row(dataIndex).data();
            if (!rowData) {
                return true;
            }
            return String(rowData['siteStatus']) === statusFilterValue;
        };

        const getClientName = function (clientId) {
            if (refClient && refClient[clientId]) {
                return refClient[clientId]['clientName'];
            }
            return 'Unknown Client';
        };

        const populateClientFilter = function () {
            const $select = $('#optSteClientId');
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
            initMaterialSelect('#optSteClientId');
            if (clientFilterValue) {
                try {
                    $select.materialSelect('destroy');
                } catch (e) {
                    // ignore destroy errors
                }
                $select.val(clientFilterValue);
                $select.materialSelect();
            }
            $select.off('change').on('change', handleClientSelectChange);
        };

        const setStatusFilter = function (value, fromSelect) {
            statusFilterValue = value || '';
            if (oTableSite) {
                oTableSite.draw();
                refreshListSummary();
            }
            setActiveStatusChip(statusFilterValue);
            if (!fromSelect) {
                const $statusSelect = $('#optSteStatus');
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
            clientFilterValue = value || '';
            if (oTableSite) {
                if (clientFilterValue) {
                    oTableSite.column(8).search(`^${clientFilterValue}$`, true, false, true);
                } else {
                    oTableSite.column(8).search('');
                }
                oTableSite.draw();
                refreshListSummary();
            }
            if (!skipSelect) {
                const $clientSelect = $('#optSteClientId');
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

        const handleStatusSelectChange = function () {
            setStatusFilter($(this).val() || '', true);
        };

        const handleClientSelectChange = function () {
            setClientFilter($(this).val() || '', true);
        };

        const bindRowActions = function () {
            $('.lnkSteSiteEdit').off('click').on('click', function () {
                const row = oTableSite.row($(this).closest('tr'));
                const rowData = row.data();
                if (!rowData) {
                    return;
                }
                modalSiteClass.edit(rowData['siteId'], row.index());
            });
            $('.lnkSteSiteDeactivate').off('click').on('click', function () {
                const row = oTableSite.row($(this).closest('tr'));
                const rowData = row.data();
                if (!rowData) {
                    return;
                }
                modalSiteClass.deactivate(rowData['siteId'], row.index());
            });
            $('.lnkSteSiteActivate').off('click').on('click', function () {
                const row = oTableSite.row($(this).closest('tr'));
                const rowData = row.data();
                if (!rowData) {
                    return;
                }
                modalSiteClass.activate(rowData['siteId'], row.index());
            });
            $('.lnkSteSiteDelete').off('click').on('click', function () {
                const row = oTableSite.row($(this).closest('tr'));
                const rowData = row.data();
                if (!rowData) {
                    return;
                }
                modalConfirmDeleteClass.delete(rowData['siteId'], modalSiteClass);
            });
            $('.lnkSteSiteVisitorPublic').off('click').on('click', function () {
                const row = oTableSite.row($(this).closest('tr'));
                const rowData = row.data();
                if (!rowData || typeof window.modalSiteVisitorPublicGlobal === 'undefined') {
                    return;
                }
                window.modalSiteVisitorPublicGlobal.openForSite(rowData['siteId']);
            });
            $('.lnkSteSitePtwPublic').off('click').on('click', function () {
                const row = oTableSite.row($(this).closest('tr'));
                const rowData = row.data();
                if (!rowData || typeof window.modalSitePtwPublicGlobal === 'undefined') {
                    return;
                }
                window.modalSitePtwPublicGlobal.openForSite(rowData['siteId']);
            });
        };

        this.init = function () {
            $.fn.dataTable.ext.search.push(statusFilterFn);

            initMaterialSelect('#optSteStatus');
            populateClientFilter();

            oTableSite = $('#dtSteSite').DataTable({
                bLengthChange: false,
                bFilter: true,
                aaSorting: [[1, 'asc'], [2, 'asc']],
                language: _DATATABLE_LANGUAGE,
                dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
                pagingType: 'simple_numbers',
                autoWidth: false,
                columnDefs: [
                    { targets: [0, 4, 5, 6, 7], orderable: false, className: 'text-center' },
                    { targets: [1, 2, 3], className: 'text-nowrap' }
                ],
                fnRowCallback: function (nRow, aData, iDisplayIndex) {
                    const info = oTableSite.page.info();
                    $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                },
                drawCallback: function () {
                    $('[data-toggle="tooltip"]').tooltip();
                    applyTableDataLabels('#dtSteSite', tableHeaders);
                    bindRowActions();
                    refreshListSummary();
                },
                aoColumns: [
                    { mData: null },
                    { mData: 'clientId', mRender: function (data, type) {
                            const clientName = getClientName(data);
                            return type !== 'display' ? clientName : clientName;
                        } },
                    { mData: 'siteName' },
                    { mData: 'siteCode' },
                    { mData: 'siteIsWr', mRender: function (data, type) {
                            const yesNo = formatYesNo(data);
                            if (type !== 'display') {
                                return yesNo;
                            }
                            return mzRowYesNo(data);
                        } },
                    { mData: 'siteIsPublic', mRender: function (data, type) {
                            const yesNo = formatYesNo(data);
                            if (type !== 'display') {
                                return yesNo;
                            }
                            return mzRowYesNo(data);
                        } },
                    { mData: null, mRender: function (data, type, row) {
                            const status = row['siteStatus'];
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
                        } },
                    { mData: null, bSortable: false, sClass: 'text-center', mRender: function (data, type, row, meta) {
                            let label = `<a><i class="fas fa-qrcode lnkSteSiteVisitorPublic" id="lnkSteSiteVisitorPublic_${meta.row}" data-toggle="tooltip" data-placement="top" title="Visitor Public Link / QR"></i></a>&nbsp;&nbsp;`;
                            label += `<a><i class="fas fa-qrcode lnkSteSitePtwPublic" id="lnkSteSitePtwPublic_${meta.row}" data-toggle="tooltip" data-placement="top" title="PTW Public Link / QR"></i></a>&nbsp;&nbsp;`;
                            label += `<a><i class="fas fa-edit lnkSteSiteEdit" id="lnkSteSiteEdit_${meta.row}" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;`;
                            if (row['siteStatus'] === '1') {
                                label += `<a><i class="fas fa-toggle-off lnkSteSiteDeactivate" id="lnkSteSiteDeactivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;`;
                            } else {
                                label += `<a><i class="fas fa-toggle-on lnkSteSiteActivate" id="lnkSteSiteActivate_${meta.row}" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;`;
                            }
                            label += `<a><i class="fas fa-trash-alt lnkSteSiteDelete" id="lnkSteSiteDelete_${meta.row}" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>`;
                            return label;
                        } },
                    { mData: 'clientId', visible: false },
                    { mData: 'siteStatus', visible: false },
                    { mData: 'siteId', visible: false }
                ]
            });
            $('#dtSteSite_filter').hide();

            $('#txtSteSiteSearch').on('keyup change', function () {
                oTableSite.search($(this).val()).draw();
            });

            $('#optSteStatus').on('change', handleStatusSelectChange);
            $('#optSteClientId').on('change', handleClientSelectChange);

            let exportCounter = 1;
            const btnSiteOpt = {
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
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

            new $.fn.dataTable.Buttons(oTableSite, {
                buttons: [
                    $.extend(true, {}, btnSiteOpt, {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i>',
                        title: 'GEMS 2.0 - Site List',
                        titleAttr: 'Print',
                        className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                    }),
                    $.extend(true, {}, btnSiteOpt, {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel"></i>',
                        title: 'GEMS 2.0 - Site List',
                        titleAttr: 'Excel',
                        className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                    }),
                    $.extend(true, {}, btnSiteOpt, {
                        extend: 'pdfHtml5',
                        text: '<i class="fas fa-file-pdf"></i>',
                        title: 'GEMS 2.0 - Site List',
                        titleAttr: 'Pdf',
                        className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                    })
                ]
            }).container().appendTo($('#btnDtSteSiteExport'));

            $('#btnSteSiteAdd').on('click', function () {
                modalSiteClass.add();
            });

            $('#btnDtSteSiteRefresh').on('click', function () {
                ShowLoader();
                setTimeout(function () {
                    try {
                        self.genTableSte(1);
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
            setClientFilter('', true);

            self.genTableSte(0);
        };

        this.genTableSte = function (_type) {
            if (_type === 1) {
                versionLocal = mzGetDataVersion();
            }
            const refSite = mzGetLocalRaw('gems_site', versionLocal, [], 'site');
            siteDataCache = Array.isArray(refSite) ? refSite : [];
            oTableSite.clear().rows.add(siteDataCache).draw();
            lastListUpdatedText = getNowStamp();
            updateSiteMetrics(siteDataCache);
            refreshListSummary();
            setStatusFilter(statusFilterValue || '', true);
            setClientFilter(clientFilterValue || '', true);
        };

        this.addTableSte = function (_dataAdd) {
            oTableSite.row.add(_dataAdd).draw();
            siteDataCache = oTableSite.rows().data().toArray();
            lastListUpdatedText = getNowStamp();
            updateSiteMetrics(siteDataCache);
            refreshListSummary();
            setStatusFilter(statusFilterValue || '', true);
            setClientFilter(clientFilterValue || '', true);
        };

        this.updateTableSte = function (_dataEdit, _rowEdit) {
            const currentRow = oTableSite.row(_rowEdit).data();
            if (!currentRow) {
                return;
            }
            if (typeof _dataEdit['siteName'] !== 'undefined') {
                currentRow['siteName'] = _dataEdit['siteName'];
            }
            if (typeof _dataEdit['siteCode'] !== 'undefined') {
                currentRow['siteCode'] = _dataEdit['siteCode'];
            }
            if (typeof _dataEdit['siteDesc'] !== 'undefined') {
                currentRow['siteDesc'] = _dataEdit['siteDesc'];
            }
            if (typeof _dataEdit['siteIsWr'] !== 'undefined') {
                currentRow['siteIsWr'] = _dataEdit['siteIsWr'];
            }
            if (typeof _dataEdit['siteIsPublic'] !== 'undefined') {
                currentRow['siteIsPublic'] = _dataEdit['siteIsPublic'];
            }
            if (typeof _dataEdit['siteStatus'] !== 'undefined') {
                currentRow['siteStatus'] = _dataEdit['siteStatus'];
            }
            if (typeof _dataEdit['clientId'] !== 'undefined') {
                currentRow['clientId'] = _dataEdit['clientId'];
            }
            oTableSite.row(_rowEdit).data(currentRow).draw();
            siteDataCache = oTableSite.rows().data().toArray();
            lastListUpdatedText = getNowStamp();
            updateSiteMetrics(siteDataCache);
            refreshListSummary();
            setStatusFilter(statusFilterValue || '', true);
            setClientFilter(clientFilterValue || '', true);
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

        this.setModalSiteClass = function (_modalSiteClass) {
            modalSiteClass = _modalSiteClass;
        };

        this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
            modalConfirmDeleteClass = _modalConfirmDeleteClass;
        };
    }