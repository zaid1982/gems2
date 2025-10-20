function MainItemManagement () {

    const className = 'MainItemManagement';
    let self = this;
    let oTableIty;
    let sectionItemClass;
    let refStatus;
    let refAssetGroup;
    let refItemType;
    let itemDataCache = [];
    let lastUpdatedText = '—';
    let assetGroupFilterValue = '';
    let itemTypeFilterValue = '';
    let statusFilterValue = '';

    const tableHeaders = ['#', 'Asset Group', 'Item Type', 'Item Description', 'Default Threshold', 'Minimum Order', 'Maximum Order', 'Remark', 'Option Turn', 'Status', '', 'Image'];
    const statusChipMap = {
        '': '#linkItyAll',
        '1': '#linkItyActive',
        '2': '#linkItyInactive',
        '5': '#linkItyArchived'
    };

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
        if (typeof moment !== 'undefined' && moment) {
            return moment().format('MMM D, YYYY h:mm A');
        }
        return new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTableIty) {
            return;
        }
        const info = oTableIty.page.info();
        const showing = info ? info.end - info.start : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblItyFilterCount').text(summaryText);
        $('#lblItyFilterUpdated').text(lastUpdatedText);
        $('#lblItyListCount').text(summaryText);
        $('#lblItyListUpdated').text(lastUpdatedText);
    };

    const getStatusDesc = function (statusId) {
        if (!statusId && statusId !== 0) {
            return 'Unknown';
        }
        const key = String(statusId);
        if (refStatus && refStatus[key] && refStatus[key]['statusDesc']) {
            return refStatus[key]['statusDesc'];
        }
        return 'Unknown';
    };

    const getStatusBadge = function (statusId) {
        if (!statusId && statusId !== 0) {
            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
        }
        const key = String(statusId);
        if (refStatus && refStatus[key]) {
            const desc = refStatus[key]['statusDesc'] || 'Unknown';
            const badgeClass = refStatus[key]['statusColor'] || 'badge-secondary';
            return `<span class="badge badge-pill ${badgeClass} z-depth-2">${desc}</span>`;
        }
        return '<span class="badge badge-pill badge-secondary">Unknown</span>';
    };

    const getAssetGroupName = function (row) {
        const itemTypeId = row['itemTypeId'];
        if (!itemTypeId || !refItemType || !refItemType[itemTypeId]) {
            return '—';
        }
        const assetGroupId = refItemType[itemTypeId]['assetGroupId'];
        if (!assetGroupId || !refAssetGroup || !refAssetGroup[assetGroupId]) {
            return '—';
        }
        return refAssetGroup[assetGroupId]['assetGroupName'] || '—';
    };

    const getItemTypeDesc = function (itemTypeId) {
        if (!itemTypeId || !refItemType || !refItemType[itemTypeId]) {
            return '—';
        }
        return refItemType[itemTypeId]['itemTypeDesc'] || '—';
    };

    const formatNumber = function (value, decimals) {
        const num = parseFloat(value);
        if (isNaN(num)) {
            return '0';
        }
        return mzFormatNumber(num, decimals);
    };

    const updateStatusChips = function (counts) {
        const labelMap = {
            '': 'All',
            '1': refStatus && refStatus['1'] ? refStatus['1']['statusDesc'] : 'Active',
            '2': refStatus && refStatus['2'] ? refStatus['2']['statusDesc'] : 'Inactive',
            '5': refStatus && refStatus['5'] ? refStatus['5']['statusDesc'] : 'Archived'
        };
        $.each(statusChipMap, function (status, selector) {
            const count = typeof counts[status] !== 'undefined' ? counts[status] : 0;
            $(selector).html(`${labelMap[status]} <span class="chip-count">${mzFormatNumber(count, 0)}</span>`);
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

    const updateMetrics = function (dataSet) {
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        let withImages = 0;
        const statusCounts = { '': 0 };

        dataSet.forEach(function (item) {
            total += 1;
            statusCounts[''] += 1;
            const statusKey = item['itemStatus'] !== undefined && item['itemStatus'] !== null ? String(item['itemStatus']) : '';
            statusCounts[statusKey] = (statusCounts[statusKey] || 0) + 1;

            if (statusKey === '1') {
                active += 1;
            } else if (statusKey === '2') {
                inactive += 1;
            } else if (statusKey === '5') {
                archived += 1;
            }

            if (item['uploadList']) {
                withImages += 1;
            }
        });

        $('#metricItyTotal').text(mzFormatNumber(total, 0));
        $('#metricItyActive').text(mzFormatNumber(active, 0));
        $('#metricItyInactive').text(mzFormatNumber(inactive, 0));
        $('#metricItyImages').text(mzFormatNumber(withImages, 0));

        updateStatusChips(statusCounts);
    };

    const allowRowClick = function (cellIndex, width) {
        if (width >= 920) {
            return cellIndex < 9;
        }
        if (width >= 620) {
            return cellIndex < 6;
        }
        return cellIndex < 3;
    };

    const bindRowInteractions = function () {
        if (!oTableIty) {
            return;
        }
        const width = $('.sectionItyMain').width();
        $('#dtItyData tbody tr').off('click').on('click', function (evt) {
            const rowData = oTableIty.row(this).data();
            if (!rowData) {
                return;
            }
            const cellIndex = $(evt.target).closest('td').index();
            if (!allowRowClick(cellIndex, width)) {
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    sectionItemClass.load(true, rowData['itemId']);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        }).off('mouseenter').on('mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            const cellIndex = cell.index();
            if (allowRowClick(cellIndex, width)) {
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to see details');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    };

    const populateAssetGroupFilter = function () {
        const $select = $('#optItyAssetGroup');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Asset Groups</option>'];
        if (refAssetGroup) {
            Object.keys(refAssetGroup).sort((a, b) => {
                const nameA = (refAssetGroup[a]['assetGroupName'] || '').toLowerCase();
                const nameB = (refAssetGroup[b]['assetGroupName'] || '').toLowerCase();
                return nameA.localeCompare(nameB);
            }).forEach(function (key) {
                options.push(`<option value="${key}">${refAssetGroup[key]['assetGroupName']}</option>`);
            });
        }
        $select.html(options.join(''));
        initMaterialSelect('#optItyAssetGroup');
    };

    const populateItemTypeOptions = function (assetGroupId) {
        const $select = $('#optItyItemType');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Item Types</option>'];
        if (refItemType) {
            Object.keys(refItemType).forEach(function (key) {
                const type = refItemType[key];
                if (!assetGroupId || assetGroupId === type['assetGroupId']) {
                    options.push(`<option value="${key}">${type['itemTypeDesc']}</option>`);
                }
            });
        }
        $select.html(options.join(''));
        initMaterialSelect('#optItyItemType');
        if (itemTypeFilterValue) {
            $select.val(itemTypeFilterValue);
            initMaterialSelect('#optItyItemType');
        }
    };

    const populateStatusFilter = function () {
        const $select = $('#optItyStatus');
        if (!$select.length) {
            return;
        }
        const options = ['<option value="" selected>All Status</option>'];
        if (refStatus) {
            ['1', '2', '5'].forEach(function (statusId) {
                if (refStatus[statusId]) {
                    options.push(`<option value="${statusId}">${refStatus[statusId]['statusDesc']}</option>`);
                }
            });
        }
        $select.html(options.join(''));
        initMaterialSelect('#optItyStatus');
    };

    const handleAssetGroupChange = function () {
        assetGroupFilterValue = $('#optItyAssetGroup').val() || '';
        itemTypeFilterValue = '';
        populateItemTypeOptions(assetGroupFilterValue);
        setTimeout(function () {
            $('#optItyItemType').val('').change();
        }, 0);
        if (oTableIty) {
            oTableIty.draw();
            refreshListSummary();
        }
    };

    const handleItemTypeChange = function () {
        itemTypeFilterValue = $('#optItyItemType').val() || '';
        if (oTableIty) {
            oTableIty.draw();
            refreshListSummary();
        }
    };

    const handleStatusSelectChange = function () {
        setStatusFilter($('#optItyStatus').val() || '', true);
    };

    const bindSearchField = function (selector) {
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).off('input change keyup').on('input change keyup', function () {
            if (!oTableIty) {
                return;
            }
            oTableIty.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const itemFilterFn = function (settings, data, dataIndex) {
        if (!oTableIty || settings.nTable.id !== 'dtItyData') {
            return true;
        }
        const rowData = oTableIty.row(dataIndex).data();
        if (!rowData) {
            return true;
        }

        if (assetGroupFilterValue) {
            const agName = getAssetGroupName(rowData);
            const actualAssetGroupId = (function () {
                const itemTypeId = rowData['itemTypeId'];
                if (!itemTypeId || !refItemType || !refItemType[itemTypeId]) {
                    return '';
                }
                return refItemType[itemTypeId]['assetGroupId'] || '';
            })();
            if (String(actualAssetGroupId) !== String(assetGroupFilterValue)) {
                return false;
            }
        }

        if (itemTypeFilterValue) {
            if (String(rowData['itemTypeId']) !== String(itemTypeFilterValue)) {
                return false;
            }
        }

        if (statusFilterValue) {
            if (String(rowData['itemStatus']) !== String(statusFilterValue)) {
                return false;
            }
        }

        return true;
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableIty) {
            oTableIty.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $status = $('#optItyStatus');
            if ($status.length) {
                try {
                    $status.materialSelect('destroy');
                } catch (e) {
                    // ignore
                }
                $status.val(statusFilterValue);
                $status.materialSelect();
                $status.off('change').on('change', handleStatusSelectChange);
            }
        }
    };

    $.fn.dataTable.ext.search.push(itemFilterFn);

    this.init = function () {
        populateAssetGroupFilter();
        populateItemTypeOptions('');
        populateStatusFilter();

        $('#optItyAssetGroup').off('change').on('change', handleAssetGroupChange);
        $('#optItyItemType').off('change').on('change', handleItemTypeChange);
        $('#optItyStatus').off('change').on('change', handleStatusSelectChange);

        oTableIty = $('#dtItyData').DataTable({
            bLengthChange: false,
            searching: true,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            columnDefs: [
                { orderable: false, targets: [0, 10, 11] },
                { className: 'text-center align-middle', targets: [0, 9, 10, 11] },
                { className: 'text-right align-middle', targets: [4, 5, 6, 8] },
                { className: 'align-middle', targets: [1, 2, 3, 7] }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableIty.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels('#dtItyData', tableHeaders);
                refreshListSummary();
                bindRowInteractions();
                initPhotoSwipeFromDOM('.mdb-lightbox');
                $('.divItyImageHide').hide();
            },
            aoColumns: [
                { mData: null },
                { mData: null, mRender: function (data, type, row) {
                        const value = getAssetGroupName(row);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'itemTypeId', mRender: function (data, type) {
                        const value = getItemTypeDesc(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'itemDescription', defaultContent: '' },
                { mData: 'itemThreshold', mRender: function (data, type) {
                        const value = formatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'itemMinOrder', mRender: function (data, type) {
                        const value = formatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'itemMaxOrder', mRender: function (data, type) {
                        const value = formatNumber(data, 0);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'itemRemark', defaultContent: '', mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">—</span>';
                    } },
                { mData: 'itemTurn', visible: false, mRender: function (data, type) {
                        const value = formatNumber(data, 0);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'itemStatus', mRender: function (data, type) {
                        if (type !== 'display') {
                            return getStatusDesc(data);
                        }
                        return `<h6 class="mb-0">${getStatusBadge(data)}</h6>`;
                    } },
                { mData: null, visible: false, mRender: function (data, type, row) {
                        const itemTypeId = row['itemTypeId'];
                        if (!itemTypeId || !refItemType || !refItemType[itemTypeId]) {
                            return '';
                        }
                        return refItemType[itemTypeId]['itemTypeTurn'] || '';
                    } },
                { mData: 'uploadList', width: '140px', mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        if (!data) {
                            return '<span class="text-muted">No image</span>';
                        }
                        const pictureUrls = data.split('||');
                        const pictureTitles = (row['titleList'] || '').split('||');
                        const pictureWidth = (row['widthList'] || '').split('||');
                        const pictureHeight = (row['heightList'] || '').split('||');
                        let label = '<div class="mdb-lightbox no-margin">';
                        pictureUrls.forEach(function (url, index) {
                            if (!url) {
                                return;
                            }
                            let dataSize = '1600x1067';
                            if (pictureWidth[index] && pictureHeight[index]) {
                                dataSize = `${pictureWidth[index]}x${pictureHeight[index]}`;
                            }
                            const title = pictureTitles[index] || '';
                            label += `<figure class="col-md-12 ${index > 0 ? 'divItyImageHide' : ''}">`;
                            label += `<a href="${mzUrlDownload + url}" data-size="${dataSize}">`;
                            label += `<img src="${mzUrlDownload + url}" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">`;
                            label += '</a>';
                            label += `<p class="mb-0 font-small ${index > 0 ? 'divItyImageHide' : ''}">${title}</p>`;
                            label += '</figure>';
                        });
                        label += '</div>';
                        return label;
                    } }
            ]
        });

        $('#dtItyData_filter').hide();
        bindSearchField('#txtItySearch');

        let exportCounter = 1;
        const exportOptions = {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            format: {
                body: function (data, row, column) {
                    if (column === 0) {
                        if (row === 0) {
                            exportCounter = 1;
                        }
                        return exportCounter++;
                    }
                    if (column === 9) {
                        const rowData = oTableIty.row(row).data();
                        return rowData ? getStatusDesc(rowData['itemStatus']) : stripHtml(data);
                    }
                    return stripHtml(data);
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableIty, {
            buttons: [
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i>',
                    titleAttr: 'Column Visibility',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9]
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i>',
                    title: 'GEMS 2.0 - Item Description List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Item Description List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Item Description List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2',
                    exportOptions: exportOptions
                }
            ]
        }).container().appendTo($('#btnDtItyExport'));

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        $('#btnItyRefresh').off('click').on('click', function () {
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

        $('#btnItyAdd').off('click').on('click', function () {
            sectionItemClass.add();
        });

        self.genTable();
        setStatusFilter('', true);
    };

    this.showMain = function () {
        $('.sectionItyMain').show();
    };

    this.hideMain = function () {
        $('.sectionItyMain').hide();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('item/list_with_image', 'GET');
        itemDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTableIty.clear().rows.add(itemDataCache).draw();
        itemDataCache = oTableIty.rows().data().toArray();
        lastUpdatedText = getNowStamp();
        updateMetrics(itemDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue, true);
    };

    this.getClassName = function () {
        return className;
    };

    this.setSectionItemClass = function (_sectionItemClass) {
        sectionItemClass = _sectionItemClass;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefItemType = function (_refItemType) {
        refItemType = _refItemType;
    };
}