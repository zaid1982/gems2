function MainPurchaseRequest () {

    const className = 'MainPurchaseRequest';
    let self = this;
    let refUser;
    let sectionPrClass;
    let oTablePcrNew;
    let purchaseDataCache = [];
    let lastUpdatedText = '—';

    const tableHeaders = ['#', 'Request No.', 'Site', 'Quotation No.', 'Supplier', 'Total Price', 'Requested By', 'Request Time'];

    const getNowStamp = function () {
        if (typeof moment !== 'undefined' && moment) {
            return moment().format('MMM D, YYYY h:mm A');
        }
        return new Date().toLocaleString();
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

    const refreshListSummary = function () {
        if (!oTablePcrNew) {
            return;
        }
        const info = oTablePcrNew.page.info();
        const showing = info ? info.end - info.start : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblPcrFilterCount').text(summaryText);
        $('#lblPcrFilterUpdated').text(lastUpdatedText);
        $('#lblPcrNewCount').text(summaryText);
        $('#lblPcrNewUpdated').text(lastUpdatedText);
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

    const bindSearchField = function (selector) {
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).off('input change keyup').on('input change keyup', function () {
            if (!oTablePcrNew) {
                return;
            }
            oTablePcrNew.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const bindRowInteractions = function () {
        if (!oTablePcrNew) {
            return;
        }
        const $rows = $('#dtPcrNew tbody tr');
        $rows.off('click').on('click', function () {
            const rowData = oTablePcrNew.row(this).data();
            if (!rowData) {
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    sectionPrClass.load(false, rowData['prId']);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        }).off('mouseenter').on('mouseenter', function () {
            $(this).find('td').css('cursor', 'pointer');
        });
    };

    const updatePurchaseMetrics = function (dataSet) {
        const totalPrepare = dataSet.length;
        $('#metricPcrPrepare').text(mzFormatNumber(totalPrepare, 0));
        $('#metricPcrApproval').text('0');
        $('#metricPcrSap').text('0');
        $('#metricPcrPo').text('0');

        $('#badgePcrNew').text(mzFormatNumber(totalPrepare, 0));
        $('#badgePcrApproval').text('0');
        $('#badgePcrSap').text('0');
        $('#badgePcrPo').text('0');
        $('#badgePcrAll').text(mzFormatNumber(totalPrepare, 0));
    };

    const formatCurrencyCell = function (data, type) {
        const amount = parseFloat(data);
        if (type !== 'display') {
            return isNaN(amount) ? data : amount;
        }
        if (isNaN(amount)) {
            return '<span class="text-muted">—</span>';
        }
        return `<span class="text-monospace">${mzFormatNumber(amount, 2)}</span>`;
    };

    const formatDateCell = function (data, type) {
        if (type !== 'display') {
            return data || '';
        }
        if (!data) {
            return '<span class="text-muted">—</span>';
        }
        if (typeof moment !== 'undefined' && moment) {
            return `<span class="text-nowrap">${moment(data).format('MMM D, YYYY h:mm A')}</span>`;
        }
        return data;
    };

    this.init = function () {
        oTablePcrNew = $('#dtPcrNew').DataTable({
            bLengthChange: false,
            searching: true,
            aaSorting: [[7, 'desc']],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center align-items-center"p>',
            columnDefs: [
                { orderable: false, targets: [0] },
                { className: 'text-center align-middle', targets: [0, 2, 3, 7] },
                { className: 'text-right align-middle', targets: [5] },
                { className: 'align-middle', targets: [1, 4, 6] }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTablePcrNew.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels('#dtPcrNew', tableHeaders);
                refreshListSummary();
                bindRowInteractions();
                $('[data-toggle="tooltip"]').tooltip();
            },
            aoColumns: [
                { mData: null, orderable: false },
                { mData: 'prRequestNo' },
                { mData: null, mRender: function () { return 'PPNS'; } },
                { mData: 'prQuotationNo', defaultContent: '' },
                { mData: 'supplierId', defaultContent: '' },
                { mData: 'prTotalCost', mRender: formatCurrencyCell },
                { mData: 'prRequestedBy', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        if (!data || !refUser || !refUser[data]) {
                            return '<span class="text-muted">—</span>';
                        }
                        return `<span class="font-weight-500">${refUser[data]['userFullName']}</span>`;
                    } },
                { mData: 'prTimeRequested', mRender: formatDateCell }
            ]
        });

        $('#dtPcrNew_filter').hide();
        bindSearchField('#txtPcrSearch');

        let exportCounter = 1;
        const exportOptions = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7],
            format: {
                body: function (data, row, column) {
                    if (column === 0) {
                        if (row === 0) {
                            exportCounter = 1;
                        }
                        return exportCounter++;
                    }
                    return stripHtml(data);
                }
            }
        };

        new $.fn.dataTable.Buttons(oTablePcrNew, {
            buttons: [
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    columns: [1, 2, 3, 4, 5, 6, 7]
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Prepare PR List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Prepare PR List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Prepare PR List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ]
        }).container().appendTo($('#btnDtPcrNewExport'));

        $('#btnPcrRefresh').off('click').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePcrNew();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTablePcrNew();
    };

    this.showMain = function () {
        $('.sectionPcrMain').show();
    };

    this.hideMain = function () {
        $('.sectionPcrMain').hide();
    };

    this.genTablePcrNew = function () {
        const dataDb = mzAjaxRequest2('pr/new_request', 'GET');
        purchaseDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTablePcrNew.clear().rows.add(purchaseDataCache).draw();
        purchaseDataCache = oTablePcrNew.rows().data().toArray();
        lastUpdatedText = getNowStamp();
        updatePurchaseMetrics(purchaseDataCache);
        refreshListSummary();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setSectionPrClass = function (_sectionPrClass) {
        sectionPrClass = _sectionPrClass;
    };
}