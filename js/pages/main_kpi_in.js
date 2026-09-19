/**
 * KPI / APD Summary.
 *
 * Reads the monthly evaluation through kpa/report/summary. When a month has not
 * been created yet the API returns the configured indicators with empty results
 * so the grid still shows the structure.
 */
function MainKpiIn () {

    const className = 'MainKpiIn';
    let self = this;
    const kc = new KpaCommon();
    let oTableKpi;
    let summary = null;
    let kpiData = [];
    let lastUpdatedText = '—';
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

    const updateMetrics = function () {
        $('#metricKpiTotal').text(mzFormatNumber(summary ? summary.piTotal : 0, 0));
        $('#metricKpiWeight').text(mzFormatNumber(summary ? summary.weightageTotal : 0, 0));
        $('#metricKpiDemerit').text(mzFormatNumber(summary ? summary.totalDemerit : 0, 0));
        $('#metricKpiApdDeducted').text(formatCurrency(summary ? summary.totalApdDeducted : 0));

        const categoryCounts = { '': 0, service: 0, asset: 0, energy: 0, safety: 0 };
        kpiData.forEach(function (item) {
            categoryCounts[''] += 1;
            if (categoryCounts[item.categoryKey] !== undefined) {
                categoryCounts[item.categoryKey] += 1;
            }
        });
        $.each(categoryChipMap, function (key, selector) {
            const label = categoryLabelMap[key];
            const count = categoryCounts[key] || 0;
            $(selector).html(`${label} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
        });
    };

    const updateSelectedLabel = function () {
        if (!summary) {
            $('#lblKpiSelected').text('Select a site and period.');
            return;
        }
        const site = summary.siteName ? ' · ' + summary.siteName : '';
        $('#lblKpiSelected').text(`Viewing KPI / APD performance for ${summary.periodLabel}${site}`);
        if (summary.exists) {
            $('#lblKpiEvalStatus').html(kc.evalStatusBadge(summary.status) +
                ' <span class="ml-2">' + summary.piSubmitted + ' of ' + summary.piTotal + ' submitted · MPV ' +
                kc.fmtMoney(summary.mpv) + ' · APD max ' + kc.fmtMoney(summary.apdMaxAmount) + '</span>');
        } else {
            $('#lblKpiEvalStatus').html(kc.evalStatusBadge('NOT_STARTED') +
                ' <span class="ml-2">No evaluation exists for this month yet. Showing the configured structure.</span>');
        }
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
            aaSorting: [],
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            pageLength: 25,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            columnDefs: [
                { className: 'text-center align-middle', targets: [0, 2, 4, 5, 6, 7, 8, 9, 10] },
                { className: 'align-middle', targets: [1, 3] }
            ],
            drawCallback: function () {
                applyTableDataLabels();
                refreshListSummary();
            },
            aoColumns: [
                { mData: 'groupNo', defaultContent: '' },
                { mData: 'groupName', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? `<strong>${data}</strong>` : '<span class="text-muted">—</span>';
                    } },
                { mData: 'piNo', defaultContent: '' },
                { mData: 'piName', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">No description</span>';
                    } },
                { mData: 'targetValue', defaultContent: '', mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return data || 0;
                        }
                        return mzFormatNumber(data, 2) + (row.targetUnit === 'BEI' ? ' BEI' : '');
                    } },
                { mData: 'actualValue', defaultContent: '', mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return data === null || data === undefined ? '' : data;
                        }
                        if (data === null || data === undefined || data === '') {
                            return '<span class="text-muted">—</span>';
                        }
                        const value = mzFormatNumber(data, 2);
                        if (row.isPass === null || row.isPass === undefined) {
                            return value;
                        }
                        return row.isPass
                            ? '<span class="text-success font-weight-bold">' + value + '</span>'
                            : '<span class="text-danger font-weight-bold">' + value + '</span>';
                    } },
                { mData: 'demeritPoint', defaultContent: '', mRender: function (data) {
                        return mzFormatNumber(data, 0);
                    } },
                { mData: 'demeritImposed', defaultContent: '', mRender: function (data) {
                        return mzFormatNumber(data, 0);
                    } },
                { mData: 'weightagePct', defaultContent: '', mRender: function (data) {
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
                    if (column === 1 || column === 3) {
                        return stripHtml(data);
                    }
                    if (column >= 4 && column <= 8) {
                        const clean = stripHtml(data);
                        return clean === '' || clean === '—' ? '0' : clean;
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

    const queryString = function () {
        const p = [];
        if ($('#optKpiSite').val()) { p.push('siteId=' + $('#optKpiSite').val()); }
        p.push('year=' + ($('#optKpiYear').val() || new Date().getFullYear()));
        p.push('month=' + ($('#optKpiMonth').val() || (new Date().getMonth() + 1)));
        return p.join('&');
    };

    this.refreshData = function () {
        summary = kc.apiGet('report/summary?' + queryString());
        kpiData = (summary && summary.indicators) ? summary.indicators : [];
        if (!oTableKpi) {
            buildTable();
        }
        oTableKpi.clear().rows.add(kpiData).draw();
        lastUpdatedText = getNowStamp();
        updateMetrics();
        updateSelectedLabel();
        refreshListSummary();
    };

    this.init = function () {
        kc.loadCaps();
        kc.loadSites();
        kc.fillSelect('optKpiSite', kc.sites, 'siteId', function (r) { return r.siteName; }, null, kc.caps.siteId || '');
        kc.fillYears('optKpiYear', new Date().getFullYear(), 5, 1);
        kc.fillMonths('optKpiMonth', new Date().getMonth() + 1);

        bindSearchField();
        bindCategoryChips();

        $('#optKpiSite, #optKpiYear, #optKpiMonth').off('change').on('change', function () {
            self.refreshData();
        });

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
            if (!oTableKpi) { return; }
            oTableKpi.button('.buttons-excel').trigger();
        });

        this.refreshData();
        setCategoryFilter('');
    };

    this.getClassName = function () {
        return className;
    };

}
