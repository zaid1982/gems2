function MainKpiIn () {

    const className = 'MainKpiIn';
    let self = this;
    let oTableKpi;
    let kpiData = [];
    let lastUpdatedText = '—';
    let selectedYear = new Date().getFullYear();
    let selectedMonth = new Date().getMonth() + 1;
    let categoryFilterValue = '';

    const tableHeaders = ['KPI No.', 'KPI', 'PI No.', 'Performance Indicator', 'Target (%)', 'Actual (%)', 'Demerit Point', 'Points Imposed', 'Weightage (W)', 'APD Value (RM)', 'APD Deducted (RM)'];

    const categoryLabelMap = {
        '': 'All',
        service: 'Service Delivery',
        asset: 'Asset Performance',
        energy: 'Energy Efficiency',
        safety: 'Safety & Compliance'
    };

    const categoryChipMap = {
        '': '#linkKpiAll',
        service: '#linkKpiService',
        asset: '#linkKpiAsset',
        energy: '#linkKpiEnergy',
        safety: '#linkKpiSafety'
    };

    const baseData = function () {
        return [
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1A', indicator: 'Customer Satisfaction Survey rating > 80%', target: 80, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 5, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1B', indicator: 'Customer Rating in Work Order sheet > 70%', target: 70, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 5, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1C', indicator: 'Response Time 100% meet target', target: 100, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 5, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1D', indicator: 'Execution Time > 95% meet target', target: 95, actual: null, demeritPoint: 2, demeritImposed: 0, weight: 5, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1E', indicator: 'Pending/Backlog Work Order Completion 100% (Schedule B)', target: 100, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 5, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1F', indicator: 'Self Finding Work Order Quantity > 80% from total Work Order', target: 80, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 10, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1G', indicator: 'Cleaning Performance > 85%', target: 85, actual: null, demeritPoint: 2, demeritImposed: 0, weight: 6, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1H', indicator: 'Pest Control Performance > 95%', target: 95, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 5, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1I', indicator: 'Critical Services > 95% available', target: 95, actual: null, demeritPoint: 3, demeritImposed: 0, weight: 8, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'service', kpiNo: '1', kpiName: 'Service Delivery related to Core Business', piNo: '1J', indicator: 'Normal Services > 85% available', target: 85, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 7, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'asset', kpiNo: '2', kpiName: 'Asset Performance', piNo: '2A', indicator: 'PPM for Architecture and C&S assets 100% implemented', target: 80, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 4, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'asset', kpiNo: '2', kpiName: 'Asset Performance', piNo: '2B', indicator: 'PPM for Mechanical assets 100% implemented', target: 70, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 4, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'asset', kpiNo: '2', kpiName: 'Asset Performance', piNo: '2C', indicator: 'PPM for Electrical assets 100% implemented', target: 100, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 4, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'asset', kpiNo: '2', kpiName: 'Asset Performance', piNo: '2D', indicator: 'Engineering Reports & Recommendation action 100% taken', target: 95, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 4, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'asset', kpiNo: '2', kpiName: 'Asset Performance', piNo: '2E', indicator: 'Work done as specification / asset quality meet standards', target: 100, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 4, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'energy', kpiNo: '3', kpiName: 'Building Energy Efficiency', piNo: '3A', indicator: 'Energy Conservation programs 100% implemented', target: 80, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 4, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'energy', kpiNo: '3', kpiName: 'Building Energy Efficiency', piNo: '3B', indicator: 'Building Energy Index (BEI) target 100% met (target to be set after energy audit)', target: 70, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 3, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'energy', kpiNo: '3', kpiName: 'Building Energy Efficiency', piNo: '3C', indicator: 'Utility Consumption 100% No Wastage', target: 100, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 3, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'safety', kpiNo: '4', kpiName: 'Safety & Statutory Compliance', piNo: '4A', indicator: 'Relevant Acts & Regulations 100% comply', target: 80, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 3, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'safety', kpiNo: '4', kpiName: 'Safety & Statutory Compliance', piNo: '4B', indicator: 'HSE programs 100% implemented', target: 70, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 3, apdValue: 0, apdDeducted: 0 },
            { categoryKey: 'safety', kpiNo: '4', kpiName: 'Safety & Statutory Compliance', piNo: '4C', indicator: 'Reports submitted 100% on time with sufficient content', target: 100, actual: null, demeritPoint: 1, demeritImposed: 0, weight: 3, apdValue: 0, apdDeducted: 0 }
        ];
    };

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy warning
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
        $('#dtKpi tbody tr').each(function () {
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

    const formatCurrency = function (value) {
        const numericValue = parseFloat(value);
        if (isNaN(numericValue) || numericValue === 0) {
            return 'RM 0.00';
        }
        return 'RM ' + mzFormatNumber(numericValue, 2);
    };

    const getNowStamp = function () {
        if (typeof moment !== 'undefined' && moment) {
            return moment().format('MMM D, YYYY h:mm A');
        }
        return new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableKpi) {
            return;
        }
        const info = oTableKpi.page.info();
        const showing = info ? info.end - info.start : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblKpiFilterCount').text(summaryText);
        $('#lblKpiFilterUpdated').text(lastUpdatedText);
        $('#lblKpiListCount').text(summaryText);
        $('#lblKpiListUpdated').text(lastUpdatedText);
    };

    const updateMetrics = function (dataSet) {
        let totalIndicators = 0;
        let totalWeight = 0;
        let totalDemerit = 0;
        let totalApdDeducted = 0;
        const categoryCounts = { '': 0, service: 0, asset: 0, energy: 0, safety: 0 };

        dataSet.forEach(function (item) {
            totalIndicators += 1;
            totalWeight += parseFloat(item.weight) || 0;
            totalDemerit += parseFloat(item.demeritPoint) || 0;
            totalApdDeducted += parseFloat(item.apdDeducted) || 0;
            categoryCounts[''] += 1;
            if (categoryCounts[item.categoryKey] !== undefined) {
                categoryCounts[item.categoryKey] += 1;
            }
        });

        $('#metricKpiTotal').text(mzFormatNumber(totalIndicators, 0));
        $('#metricKpiWeight').text(mzFormatNumber(totalWeight, 0));
        $('#metricKpiDemerit').text(mzFormatNumber(totalDemerit, 0));
        $('#metricKpiApdDeducted').text(formatCurrency(totalApdDeducted));

        $.each(categoryChipMap, function (key, selector) {
            const label = categoryLabelMap[key];
            const count = categoryCounts[key] || 0;
            $(selector).html(`${label} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
    };

    const updateSelectedLabel = function () {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const monthIndex = Math.max(1, Math.min(12, parseInt(selectedMonth, 10) || 1)) - 1;
        const label = `${monthNames[monthIndex]} ${selectedYear}`;
        $('#lblKpiSelected').text(`Viewing KPI / APD performance for ${label}`);
    };

    const setActiveCategoryChip = function (value) {
        $.each(categoryChipMap, function (key, selector) {
            if (key === value) {
                $(selector).addClass('active');
            } else {
                $(selector).removeClass('active');
            }
        });
    };

    const handlePeriodChange = function () {
        selectedYear = parseInt($('#optKpiYear').val(), 10) || selectedYear;
        selectedMonth = parseInt($('#optKpiMonth').val(), 10) || selectedMonth;
        updateSelectedLabel();
        self.refreshData();
    };

    const bindSearchField = function () {
        $('#txtKpiSearch').off('input change keyup').on('input change keyup', function () {
            if (!oTableKpi) {
                return;
            }
            oTableKpi.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const setCategoryFilter = function (value) {
        categoryFilterValue = value || '';
        setActiveCategoryChip(categoryFilterValue);
        if (oTableKpi) {
            oTableKpi.draw();
            refreshListSummary();
        }
    };

    const kpiFilterFn = function (settings, data, dataIndex) {
        if (!oTableKpi || settings.nTable.id !== 'dtKpi') {
            return true;
        }
        const rowData = oTableKpi.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        if (categoryFilterValue) {
            return String(rowData.categoryKey) === String(categoryFilterValue);
        }
        return true;
    };

    $.fn.dataTable.ext.search.push(kpiFilterFn);

    const buildTable = function () {
        oTableKpi = $('#dtKpi').DataTable({
            bLengthChange: false,
            searching: true,
            aaSorting: [[0, 'asc'], [2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 25,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            columnDefs: [
                { orderable: false, targets: [1, 3, 5, 9, 10] },
                { className: 'text-center align-middle', targets: [0, 2, 4, 5, 6, 7, 8, 9, 10] },
                { className: 'align-middle', targets: [1, 3] }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableKpi.page.info();
                $('td', nRow).eq(0).html(aData.kpiNo);
            },
            drawCallback: function () {
                applyTableDataLabels();
                refreshListSummary();
            },
            aoColumns: [
                { mData: 'kpiNo', defaultContent: '' },
                { mData: 'kpiName', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? `<strong>${data}</strong>` : '<span class="text-muted">—</span>';
                    } },
                { mData: 'piNo', defaultContent: '' },
                { mData: 'indicator', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">No description</span>';
                    } },
                { mData: 'target', defaultContent: '', mRender: function (data) {
                        return mzFormatNumber(data, 0);
                    } },
                { mData: 'actual', defaultContent: '', mRender: function (data) {
                        return data === null || data === undefined || data === '' ? '<span class="text-muted">—</span>' : mzFormatNumber(data, 2);
                    } },
                { mData: 'demeritPoint', defaultContent: '', mRender: function (data) {
                        return mzFormatNumber(data, 0);
                    } },
                { mData: 'demeritImposed', defaultContent: '', mRender: function (data) {
                        return mzFormatNumber(data, 0);
                    } },
                { mData: 'weight', defaultContent: '', mRender: function (data) {
                        return mzFormatNumber(data, 0);
                    } },
                { mData: 'apdValue', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || 0;
                        }
                        return formatCurrency(data);
                    } },
                { mData: 'apdDeducted', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || 0;
                        }
                        return formatCurrency(data);
                    } }
            ]
        });

        $('#dtKpi_filter').hide();

        const exportOptions = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            format: {
                body: function (data, row, column) {
                    if (column === 0) {
                        const rowData = oTableKpi.row(row).data();
                        return rowData ? rowData.kpiNo : stripHtml(data);
                    }
                    if (column === 1 || column === 3) {
                        return stripHtml(data);
                    }
                    if (column >= 4 && column <= 8) {
                        const clean = stripHtml(data);
                        return clean === '' ? '0' : clean;
                    }
                    if (column === 9 || column === 10) {
                        const clean = stripHtml(data).replace('RM', '').trim();
                        return clean === '' ? '0' : clean;
                    }
                    return stripHtml(data);
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableKpi, {
            buttons: [
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - KPI / APD Dashboard',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - KPI / APD Dashboard',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - KPI / APD Dashboard',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ]
        }).container().appendTo($('#btnDtKpiExport'));
    };

    const bindCategoryChips = function () {
        $.each(categoryChipMap, function (key, selector) {
            $(selector).off('click').on('click', function () {
                setCategoryFilter(key);
            });
        });
    };

    this.refreshData = function () {
        kpiData = baseData();
        if (!oTableKpi) {
            buildTable();
        }
        oTableKpi.clear().rows.add(kpiData).draw();
        lastUpdatedText = getNowStamp();
        updateMetrics(kpiData);
        refreshListSummary();
    };

    this.init = function () {
        initMaterialSelect('#optKpiYear');
        initMaterialSelect('#optKpiMonth');

        selectedYear = parseInt($('#optKpiYear').val(), 10) || selectedYear;
        selectedMonth = parseInt($('#optKpiMonth').val(), 10) || selectedMonth;
        updateSelectedLabel();

        bindSearchField();
        bindCategoryChips();

        $('#optKpiYear').off('change').on('change', handlePeriodChange);
        $('#optKpiMonth').off('change').on('change', handlePeriodChange);

        $('#btnKpiRefresh').off('click').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.refreshData();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnKpiDownload').off('click').on('click', function () {
            toastr['info']('Download link will be available once the source file is published.', 'Coming Soon');
        });

        this.refreshData();
        setCategoryFilter('');
    };

    this.getClassName = function () {
        return className;
    };

}