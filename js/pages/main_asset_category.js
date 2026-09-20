function MainAssetCategory() {

    const className = 'MainAssetCategory';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let oTableAssetCategory;
    let modalAssetCategoryClass;
    let lastUpdated = null;
    let statusFilterFn;

    function formatTimestamp(value) {
        if (!value) {
            return '—';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function assetGroupRows() {
        const rows = [];
        $.each(refAssetGroup, function (key, group) {
            if (!group || typeof group !== 'object') {
                return true;
            }
            rows.push(group);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['assetGroupName'] || '').localeCompare(b['assetGroupName'] || '');
        });
        return rows;
    }

    function populateGroupFilter() {
        const currentValue = $('#optActGroupId').val() || '';
        GemsUI.fillSelect(
            'optActGroupId',
            assetGroupRows(),
            'assetGroupId',
            function (row) {
                return row['assetGroupName'] || '';
            },
            'All Groups',
            currentValue
        );
    }

    function groupName(groupId) {
        if (refAssetGroup && refAssetGroup[groupId] && refAssetGroup[groupId]['assetGroupName']) {
            return refAssetGroup[groupId]['assetGroupName'];
        }
        return 'Unknown Group';
    }

    function updateMetrics() {
        if (!oTableAssetCategory) {
            return;
        }
        const data = oTableAssetCategory.rows().data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        let archived = 0;
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            switch (String(row['assetCategoryStatus'])) {
                case '1':
                    active++;
                    break;
                case '2':
                    inactive++;
                    break;
                case '5':
                    archived++;
                    break;
                default:
                    break;
            }
        }
        $('#metricActTotal').text(total.toLocaleString());
        $('#metricActActive').text(active.toLocaleString());
        $('#metricActInactive').text(inactive.toLocaleString());
        $('#metricActArchived').text(archived.toLocaleString());
    }

    function updateSummary() {
        if (!oTableAssetCategory) {
            return;
        }
        const info = (oTableAssetCategory && typeof oTableAssetCategory.page === 'function' && typeof oTableAssetCategory.page.info === 'function')
            ? oTableAssetCategory.page.info()
            : null;
        const summaryText = info
            ? ('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal)
            : 'Showing 0 of 0';
        $('#lblActFilterCount').text(summaryText);
        $('#lblActListCount').text(summaryText + ' records');
        const updatedText = formatTimestamp(lastUpdated);
        $('#lblActFilterUpdated').text(updatedText);
        $('#lblActListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
    }

    function updateFilterSummary() {
        const groupVal = $('#optActGroupId').val();
        const statusVal = $('#optActStatus').val();
        let groupLabel = 'All';
        if (groupVal) {
            groupLabel = groupName(groupVal);
        }
        let statusLabel = 'All';
        if (statusVal && refStatus && refStatus[statusVal]) {
            statusLabel = refStatus[statusVal]['statusDesc'];
        } else if (statusVal === '1') {
            statusLabel = 'Active';
        } else if (statusVal === '2') {
            statusLabel = 'Inactive';
        } else if (statusVal === '5') {
            statusLabel = 'Archived';
        }
        $('#lblActAssetCategoryFilter').text('Group: ' + groupLabel + ' · Status: ' + statusLabel);
    }

    function applyGroupFilter() {
        if (!oTableAssetCategory) {
            return;
        }
        const groupVal = $('#optActGroupId').val() || '';
        if (groupVal) {
            oTableAssetCategory.column(6).search('^' + groupVal + '$', true, false, true);
        } else {
            oTableAssetCategory.column(6).search('', true, false, true);
        }
    }

    function statusLabel(row) {
        const status = refStatus && refStatus[row['assetCategoryStatus']];
        if (status) {
            return status['statusDesc'];
        }
        switch (String(row['assetCategoryStatus'])) {
            case '1':
                return 'Active';
            case '2':
                return 'Inactive';
            case '5':
                return 'Archived';
            default:
                return '';
        }
    }

    function statusBadgeKind(status) {
        switch (String(status)) {
            case '1':
                return 'success';
            case '5':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function statusBadge(row, type) {
        const label = statusLabel(row);
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(row['assetCategoryStatus']), GemsUI.escape(label));
    }

    function displayText(value, type) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        return GemsUI.escape(text);
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableAssetCategory) {
            return null;
        }
        return { rowId: rowId, data: oTableAssetCategory.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let cntAssetCategory;
        const exportOpt = {
            columns: [0, 1, 2, 3, 4],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntAssetCategory = 1;
                    }
                    if (column === 0) {
                        return cntAssetCategory++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Asset Category List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        populateGroupFilter();

        oTableAssetCategory = $('#dtActAssetCategory').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            pageLength: 25,
            aaSorting: [[1, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-layer-group', 'No asset categories recorded yet.', 'No asset categories match the current search or filters.'),
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableAssetCategory && oTableAssetCategory.page && typeof oTableAssetCategory.page.info === 'function')
                    ? oTableAssetCategory.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            drawCallback: function () {
                updateMetrics();
                updateSummary();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'assetGroupId', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        const name = groupName(data);
                        if (type !== 'display') {
                            return name;
                        }
                        return GemsUI.escape(name);
                    }
                },
                {mData: 'assetCategoryName', sClass: 'text-nowrap',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: 'assetCategoryDesc',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row, type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkActAssetCategoryEdit',
                            id: 'lnkActAssetCategoryEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['assetCategoryStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkActAssetCategoryDeactivate',
                                id: 'lnkActAssetCategoryDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkActAssetCategoryActivate',
                                id: 'lnkActAssetCategoryActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkActAssetCategoryDelete',
                            id: 'lnkActAssetCategoryDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'assetGroupId', visible: false, sClass: 'noVis'},
                {mData: 'assetCategoryId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableAssetCategory.buttons().container().appendTo($('#btnDtActAssetCategoryExport'));
        GemsUI.bindDtTooltips('#dtActAssetCategory');

        const tbody = $('#dtActAssetCategory tbody');
        tbody.on('click', '.lnkActAssetCategoryEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetCategoryClass.edit(current.data['assetCategoryId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkActAssetCategoryDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetCategoryClass.deactivate(current.data['assetCategoryId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkActAssetCategoryActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetCategoryClass.activate(current.data['assetCategoryId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkActAssetCategoryDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['assetCategoryId'], modalAssetCategoryClass);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtActAssetCategory') {
                return true;
            }
            const statusVal = $('#optActStatus').val();
            if (!statusVal) {
                return true;
            }
            const rowData = oTableAssetCategory.row(dataIndex).data();
            return rowData && String(rowData['assetCategoryStatus']) === String(statusVal);
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);

        $('#txtActAssetCategorySearch').on('keyup change', function () {
            oTableAssetCategory.search($(this).val()).draw();
        });

        $('#optActGroupId').on('change', function () {
            applyGroupFilter();
            oTableAssetCategory.draw();
            updateFilterSummary();
        });

        $('#optActStatus').on('change', function () {
            oTableAssetCategory.draw();
            updateFilterSummary();
        });

        $('#btnActAssetCategoryAdd').on('click', function () {
            modalAssetCategoryClass.add();
        });

        $('#btnDtActAssetCategoryRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAct(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        updateFilterSummary();
        self.genTableAct(0);
    };

    this.genTableAct = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetCategory = mzGetLocalRaw('gems_assetCategory', versionLocal, [], 'asset_category');
        lastUpdated = new Date();
        oTableAssetCategory.clear().rows.add(Array.isArray(refAssetCategory) ? refAssetCategory : []);
        applyGroupFilter();
        oTableAssetCategory.draw();
    };

    this.addTableAct = function (_dataAdd) {
        lastUpdated = new Date();
        oTableAssetCategory.row.add(_dataAdd).draw();
    };

    this.updateTableAct = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetCategory.row(_rowEdit).data();
        if (typeof _dataEdit['assetCategoryName'] !== 'undefined') {
            currentRow['assetCategoryName'] = _dataEdit['assetCategoryName'];
        }
        if (typeof _dataEdit['assetCategoryDesc'] !== 'undefined') {
            currentRow['assetCategoryDesc'] = _dataEdit['assetCategoryDesc'];
        }
        if (typeof _dataEdit['assetCategoryStatus'] !== 'undefined') {
            currentRow['assetCategoryStatus'] = _dataEdit['assetCategoryStatus'];
        }
        if (typeof _dataEdit['assetGroupId'] !== 'undefined') {
            currentRow['assetGroupId'] = _dataEdit['assetGroupId'];
        }
        lastUpdated = new Date();
        oTableAssetCategory.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
        updateFilterSummary();
        if (oTableAssetCategory) {
            oTableAssetCategory.rows().invalidate();
            oTableAssetCategory.draw(false);
        }
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
        if ($('#optActGroupId').length) {
            populateGroupFilter();
        }
        updateFilterSummary();
        if (oTableAssetCategory) {
            oTableAssetCategory.rows().invalidate();
            oTableAssetCategory.draw(false);
        }
    };

    this.setModalAssetCategoryClass = function (_modalAssetCategoryClass) {
        modalAssetCategoryClass = _modalAssetCategoryClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
