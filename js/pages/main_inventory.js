function MainInventory () {

    const className = 'MainInventory';
    let self = this;
    let oTableInv;
    let refStatus;
    let refClient;
    let refSite;
    let refStore;
    let refAssetGroup;
    let refItemType;
    let refItem;
    let storeId;
    let sectionPartClass;
    let modalPartAddClass;
    let userSite;
    let userClient;
    let userIsEdit = false;
    let formValidate;
    let inventoryDataCache = [];
    let lastUpdatedText = '—';
    let statusFilterValue = '';

    const statusChipMap = {
        '': '#linkInvAll',
        '1': '#linkInvActive',
        '2': '#linkInvInactive',
        '5': '#linkInvArchived'
    };

    const tableHeaders = ['#', 'Asset Group', 'Item Type', 'Item Description', 'Total in Store', 'Total Locked', 'Total Available', 'Threshold', 'Minimum Order', 'Maximum Order', 'Remark', 'Status', '', '', 'Image'];

    const initMaterialSelect = function (selector) {
        // Disabled to prevent double rendering - using plain select instead
        // const $element = $(selector);
        // if (!$element.length || typeof $element.materialSelect !== 'function') {
        //     return;
        // }
        // try {
        //     $element.materialSelect('destroy');
        // } catch (e) {
        //     // ignore destroy errors
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
        if (!oTableInv) {
            return;
        }
        const info = oTableInv.page.info();
        const showing = info ? (info.end - info.start) : 0;
        const total = info ? info.recordsDisplay : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblInvFilterCount').text(summaryText);
        $('#lblInvListCount').text(summaryText);
        $('#lblInvFilterUpdated').text(lastUpdatedText);
        $('#lblInvListUpdated').text(lastUpdatedText);
    };

    const getStatusDesc = function (statusId) {
        if (statusId === null || statusId === undefined || statusId === '') {
            return 'Unknown';
        }
        const key = String(statusId);
        if (refStatus && refStatus[key] && refStatus[key]['statusDesc']) {
            return refStatus[key]['statusDesc'];
        }
        return 'Unknown';
    };

    const getStatusBadge = function (statusId) {
        if (statusId === null || statusId === undefined || statusId === '') {
            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
        }
        const key = String(statusId);
        if (refStatus && refStatus[key]) {
            const statusObj = refStatus[key];
            const badgeClass = statusObj['statusColor'] ? statusObj['statusColor'] : 'badge-secondary';
            const desc = statusObj['statusDesc'] || 'Unknown';
            return `<span class="badge badge-pill ${badgeClass} z-depth-2">${desc}</span>`;
        }
        return '<span class="badge badge-pill badge-secondary">Unknown</span>';
    };

    const getAssetGroupName = function (assetGroupId) {
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

    const getItemDesc = function (itemId) {
        if (!itemId || !refItem || !refItem[itemId]) {
            return '—';
        }
        return refItem[itemId]['itemDescription'] || '—';
    };

    const formatNumber = function (value) {
        const num = parseFloat(value);
        if (isNaN(num)) {
            return '0';
        }
        return mzFormatNumber(num);
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

    const handleStatusSelectChange = function () {
        setStatusFilter($(this).val() || '', true);
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        if (oTableInv) {
            oTableInv.draw();
            refreshListSummary();
        }
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optInvStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                } catch (e) {
                    // ignore
                }
                $statusSelect.val(statusFilterValue);
                $statusSelect.materialSelect();
                $statusSelect.off('change').on('change', handleStatusSelectChange);
            }
        }
    };

    const populateStatusFilter = function () {
        const $select = $('#optInvStatus');
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
        initMaterialSelect('#optInvStatus');
        $select.off('change').on('change', handleStatusSelectChange);
    };

    const inventoryFilterFn = function (settings, data, dataIndex) {
        if (!oTableInv || settings.nTable.id !== 'dtInvData') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = oTableInv.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        return String(rowData['partStatus']) === String(statusFilterValue);
    };

    const updateInventoryMetrics = function (dataSet) {
        let total = 0;
        let availableTotal = 0;
        let lockedTotal = 0;
        let thresholdCount = 0;
        const statusCounts = { '': 0 };

        dataSet.forEach(function (item) {
            total += 1;
            statusCounts[''] += 1;
            const statusKey = item['partStatus'] !== undefined && item['partStatus'] !== null ? String(item['partStatus']) : '';
            statusCounts[statusKey] = (statusCounts[statusKey] || 0) + 1;

            const partCount = parseFloat(item['partCount']) || 0;
            const partLocked = parseFloat(item['partLocked']) || 0;
            const available = partCount - partLocked;
            const threshold = parseFloat(item['partThreshold']) || 0;

            availableTotal += available;
            lockedTotal += partLocked;
            if (available <= threshold) {
                thresholdCount += 1;
            }
        });

        $('#metricInvTotal').text(mzFormatNumber(total, 0));
        $('#metricInvAvailable').text(mzFormatNumber(Math.max(availableTotal, 0), 0));
        $('#metricInvLocked').text(mzFormatNumber(Math.max(lockedTotal, 0), 0));
        $('#metricInvThreshold').text(mzFormatNumber(thresholdCount, 0)).toggleClass('text-warning', thresholdCount > 0);

        updateStatusChips(statusCounts);
    };

    const bindSearchField = function (selector) {
        if (!selector || !$(selector).length) {
            return;
        }
        $(selector).off('input change keyup').on('input change keyup', function () {
            if (!oTableInv) {
                return;
            }
            oTableInv.search($(this).val() || '').draw();
            refreshListSummary();
        });
    };

    const bindRowInteractions = function () {
        const screenWidth = $('.sectionInvMain').width();
        $('#dtInvData tbody tr').off('click').on('click', function (evt) {
            const rowData = oTableInv.row(this).data();
            if (!rowData) {
                return;
            }
            const cellIndex = $(evt.target).closest('td').index();
            const allowClick = (screenWidth >= 920 && cellIndex < 9) || (screenWidth >= 620 && screenWidth < 920 && cellIndex < 6) || (screenWidth < 620 && cellIndex < 3);
            if (!allowClick) {
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    self.loadSectionPart(userIsEdit, rowData['partId']);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        }).off('mouseenter').on('mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            const cellIndex = cell.index();
            const allowHover = (screenWidth >= 920 && cellIndex < 9) || (screenWidth >= 620 && screenWidth < 920 && cellIndex < 7) || (screenWidth < 620 && cellIndex < 3);
            if (allowHover) {
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to see details');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    };

    const adjustColumnsForViewport = function () {
        if (!oTableInv) {
            return;
        }
        const width = $('.sectionInvMain').width();
        if (width < 620) {
            oTableInv.column(1).visible(false);
            oTableInv.column(2).visible(false);
            oTableInv.column(4).visible(false);
            oTableInv.column(11).visible(false);
            oTableInv.column(6).visible(true);
            oTableInv.column(7).visible(false);
        } else if (width < 920) {
            oTableInv.column(1).visible(true);
            oTableInv.column(2).visible(true);
            oTableInv.column(4).visible(true);
            oTableInv.column(11).visible(true);
            oTableInv.column(6).visible(false);
            oTableInv.column(7).visible(false);
        } else {
            oTableInv.columns().visible(true);
            oTableInv.column(12).visible(false);
            oTableInv.column(13).visible(false);
            oTableInv.column(14).visible(true);
        }
    };

    const triggerSearch = function () {
        if (!formValidate || !formValidate.validateNow()) {
            toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            return;
        }
        ShowLoader();
        setTimeout(function () {
            try {
                storeId = $('#optInvStore').val();
                self.genTable();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    $.fn.dataTable.ext.search.push(inventoryFilterFn);

    this.init = function () {
        $('#sectionInvResult').hide();
        userIsEdit = mzIsRoleExist('14');
        userSite = mzGetUserInfoByParam('siteId');
        userClient = mzGetUserInfoByParam('clientId');

        if (!userIsEdit) {
            $('#btnInvAdd').hide();
        }

        if (mzIsRoleExist('1,10')) {
            mzOption('optInvClient', refClient, 'Select Client', 'clientId', 'clientName', { clientStatus: '1' }, 'required');
            initMaterialSelect('#optInvClient');
            $('#optInvClient').off('change').on('change', function () {
                mzOptionStop('optInvSite', refSite, 'Select Site', 'siteId', 'siteName', { siteStatus: '1', clientId: $(this).val() }, 'required');
                initMaterialSelect('#optInvSite');
            });
            $('#optInvSite').off('change').on('change', function () {
                mzOptionStop('optInvStore', refStore, 'Select Store', 'storeId', 'storeName', { storeStatus: '1', siteId: $(this).val() }, 'required');
                initMaterialSelect('#optInvStore');
            });
        } else {
            mzOption('optInvClient', refClient, 'Select Client', 'clientId', 'clientName', { clientId: userClient, clientStatus: '1' }, 'required');
            mzOption('optInvSite', refSite, 'Select Site', 'siteId', 'siteName', { siteId: userSite, siteStatus: '1' }, 'required');
            mzOption('optInvStore', refStore, 'Select Store', 'storeId', 'storeName', { siteId: userSite, storeStatus: '1' }, 'required');
            mzSetFieldValue('InvClient', userClient, 'select', '', true);
            mzSetFieldValue('InvSite', userSite, 'select', '', true);
            initMaterialSelect('#optInvClient');
            initMaterialSelect('#optInvSite');
            initMaterialSelect('#optInvStore');
        }

        populateStatusFilter();

        const vData = [
            { field_id: 'optInvClient', type: 'select', name: 'Client', validator: { notEmpty: true } },
            { field_id: 'optInvSite', type: 'select', name: 'Site', validator: { notEmpty: true } },
            { field_id: 'optInvStore', type: 'select', name: 'Store', validator: { notEmpty: true } }
        ];

        formValidate = new MzValidate('formInvSearch');
        formValidate.registerFields(vData);

        $('#btnInvSearch').off('click').on('click', function (evt) {
            evt.preventDefault();
            triggerSearch();
        });

        $('#btnInvRefresh').off('click').on('click', function () {
            if (storeId) {
                triggerSearch();
            } else {
                triggerSearch();
            }
        });

        $('#btnInvAdd').off('click').on('click', function () {
            try {
                mzCheckFuncParam([storeId]);
                const siteId = refStore[storeId] ? refStore[storeId]['siteId'] : null;
                if (!siteId) {
                    throw new Error('Please select a store before adding items.');
                }
                modalPartAddClass.setStoreName(refStore[storeId]['storeName']);
                modalPartAddClass.setSiteName(refSite[siteId]['siteName']);
                modalPartAddClass.add(storeId);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });

        oTableInv = $('#dtInvData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [12, 'asc'], [13, 'asc']],
            language: _DATATABLE_LANGUAGE,
            dom: 't<"dt-pagination-wrapper d-flex justify-content-center"p>',
            pagingType: 'simple_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: [0, 14], orderable: false, className: 'text-center align-middle' },
                { targets: [4, 5, 6, 7, 8, 9], className: 'text-right align-middle' },
                { targets: [1, 2, 3, 10, 11], className: 'align-middle' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableInv.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels('#dtInvData', tableHeaders);
                refreshListSummary();
                bindRowInteractions();
                initPhotoSwipeFromDOM('.mdb-lightbox');
                $('.divInvImageHide').hide();
                adjustColumnsForViewport();
            },
            aoColumns: [
                { mData: null },
                { mData: 'assetGroupId', mRender: function (data, type) {
                        const value = getAssetGroupName(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'itemTypeId', mRender: function (data, type) {
                        const value = getItemTypeDesc(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'itemId', mRender: function (data, type) {
                        const value = getItemDesc(data);
                        return type !== 'display' ? value : `<span class="font-weight-500">${value}</span>`;
                    } },
                { mData: 'partCount', mRender: function (data, type) {
                        const value = formatNumber(data);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'partLocked', mRender: function (data, type) {
                        const value = formatNumber(data);
                        return type !== 'display' ? value : `<span class="text-monospace text-warning">${value}</span>`;
                    } },
                { mData: null, mRender: function (data, type, row) {
                        const count = parseFloat(row['partCount']) || 0;
                        const locked = parseFloat(row['partLocked']) || 0;
                        const available = count - locked;
                        const value = formatNumber(available);
                        if (type !== 'display') {
                            return value;
                        }
                        const threshold = parseFloat(row['partThreshold']) || 0;
                        const flag = available <= threshold;
                        return `<span class="text-monospace ${flag ? 'text-danger font-weight-bold' : ''}">${value}</span>`;
                    } },
                { mData: 'partThreshold', mRender: function (data, type) {
                        const value = formatNumber(data);
                        return type !== 'display' ? value : `<span class="text-monospace">${value}</span>`;
                    } },
                { mData: 'partMinOrder', visible: false, mRender: function (data, type) {
                        const value = formatNumber(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'partMaxOrder', visible: false, mRender: function (data, type) {
                        const value = formatNumber(data);
                        return type !== 'display' ? value : value;
                    } },
                { mData: 'partRemark', visible: false, mRender: function (data, type) {
                        if (type !== 'display') {
                            return data || '';
                        }
                        return data ? data : '<span class="text-muted">—</span>';
                    } },
                { mData: 'partStatus', mRender: function (data, type) {
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
                { mData: null, visible: false, mRender: function (data, type, row) {
                        const itemId = row['itemId'];
                        if (!itemId || !refItem || !refItem[itemId]) {
                            return '';
                        }
                        return refItem[itemId]['itemTurn'] || '';
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
                            label += `<figure class="col-md-12 ${index > 0 ? 'divInvImageHide' : ''}">`;
                            label += `<a href="${mzUrlDownload + url}" data-size="${dataSize}">`;
                            label += `<img src="${mzUrlDownload + url}" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">`;
                            label += '</a>';
                            label += `<p class="mb-0 font-small ${index > 0 ? 'divInvImageHide' : ''}">${title}</p>`;
                            label += '</figure>';
                        });
                        label += '</div>';
                        return label;
                    } }
            ]
        });

        $('#dtInvData_filter').hide();
        bindSearchField('#txtInvSearch');

        let exportCounter = 1;
        const btnExportOptions = {
            exportOptions: {
                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                format: {
                    body: function (data, row, column) {
                        if (column === 0) {
                            if (row === 0) {
                                exportCounter = 1;
                            }
                            return exportCounter++;
                        }
                        if (column === 11) {
                            const rowData = oTableInv.row(row).data();
                            return rowData ? getStatusDesc(rowData['partStatus']) : stripHtml(data);
                        }
                        return stripHtml(data);
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableInv, {
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
                    title: 'GEMS 2.0 - Inventory List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnExportOptions, {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i>',
                    title: 'GEMS 2.0 - Inventory List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend(true, {}, btnExportOptions, {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i>',
                    title: 'GEMS 2.0 - Inventory List',
                    titleAttr: 'PDF',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtInvExport'));

        $.each(statusChipMap, function (status, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(status, false);
            });
        });

        setStatusFilter('', true);
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2(`part/list_with_image/${storeId}`, 'GET');
        inventoryDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTableInv.clear().rows.add(inventoryDataCache).draw();
        inventoryDataCache = oTableInv.rows().data().toArray();
        lastUpdatedText = getNowStamp();
        updateInventoryMetrics(inventoryDataCache);
        refreshListSummary();
        setStatusFilter(statusFilterValue || '', true);
        $('#sectionInvResult').show();
    };

    this.loadSectionPart = function (_isEdit, _partId) {
        sectionPartClass.load(_isEdit, _partId);
    };

    this.showMain = function () {
        $('.sectionInvMain').show();
    };

    this.hideMain = function () {
        $('.sectionInvMain').hide();
    };

    this.getClassName = function () {
        return className;
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

    this.setRefItem = function (_refItem) {
        refItem = _refItem;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStore = function (_refStore) {
        refStore = _refStore;
    };

    this.setSectionPartClass = function (_sectionPartClass) {
        sectionPartClass = _sectionPartClass;
    };

    this.setModalPartAddClass = function (_modalPartAddClass) {
        modalPartAddClass = _modalPartAddClass;
    };
}