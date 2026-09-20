function MainAssetGroup() {

    const className = 'MainAssetGroup';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAssetGroup;
    let modalAssetGroupClass;
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

    function updateMetrics() {
        if (!oTableAssetGroup) {
            return;
        }
        const data = oTableAssetGroup.rows().data();
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
            switch (String(row['assetGroupStatus'])) {
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
        $('#metricAgrTotal').text(total.toLocaleString());
        $('#metricAgrActive').text(active.toLocaleString());
        $('#metricAgrInactive').text(inactive.toLocaleString());
        $('#metricAgrArchived').text(archived.toLocaleString());
    }

    function updateSummary() {
        if (!oTableAssetGroup) {
            return;
        }
        const info = (oTableAssetGroup && typeof oTableAssetGroup.page === 'function' && typeof oTableAssetGroup.page.info === 'function')
            ? oTableAssetGroup.page.info()
            : null;
        const summaryText = info
            ? ('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal)
            : 'Showing 0 of 0';
        $('#lblAgrFilterCount').text(summaryText);
        $('#lblAgrListCount').text(summaryText + ' records');
        const updatedText = formatTimestamp(lastUpdated);
        $('#lblAgrFilterUpdated').text(updatedText);
        $('#lblAgrListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
    }

    function updateFilterSummary() {
        const statusVal = $('#optAgrStatus').val();
        let label = 'Status: All';
        if (statusVal && refStatus && refStatus[statusVal]) {
            label = 'Status: ' + refStatus[statusVal]['statusDesc'];
        } else if (statusVal === '1') {
            label = 'Status: Active';
        } else if (statusVal === '2') {
            label = 'Status: Inactive';
        } else if (statusVal === '5') {
            label = 'Status: Archived';
        }
        $('#lblAgrAssetGroupFilter').text(label);
    }

    function statusLabel(row) {
        const status = refStatus && refStatus[row['assetGroupStatus']];
        if (status) {
            return status['statusDesc'];
        }
        switch (String(row['assetGroupStatus'])) {
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
        return GemsUI.badge(statusBadgeKind(row['assetGroupStatus']), GemsUI.escape(label));
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableAssetGroup) {
            return null;
        }
        return { rowId: rowId, data: oTableAssetGroup.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let cntAssetGroup;
        const exportOpt = {
            columns: [0, 1, 2, 3],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntAssetGroup = 1;
                    }
                    if (column === 0) {
                        return cntAssetGroup++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Asset Group List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableAssetGroup = $('#dtAgrAssetGroup').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            pageLength: 25,
            aaSorting: [[1, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-object-group', 'No asset groups recorded yet.', 'No asset groups match the current search or status filter.'),
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableAssetGroup && oTableAssetGroup.page && typeof oTableAssetGroup.page.info === 'function')
                    ? oTableAssetGroup.page.info()
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
                {mData: 'assetGroupName', sClass: 'text-nowrap'},
                {mData: 'assetGroupDesc'},
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row, type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkAgrAssetGroupEdit',
                            id: 'lnkAgrAssetGroupEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['assetGroupStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkAgrAssetGroupDeactivate',
                                id: 'lnkAgrAssetGroupDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkAgrAssetGroupActivate',
                                id: 'lnkAgrAssetGroupActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkAgrAssetGroupDelete',
                            id: 'lnkAgrAssetGroupDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'assetGroupId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableAssetGroup.buttons().container().appendTo($('#btnDtAgrAssetGroupExport'));
        GemsUI.bindDtTooltips('#dtAgrAssetGroup');

        const tbody = $('#dtAgrAssetGroup tbody');
        tbody.on('click', '.lnkAgrAssetGroupEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetGroupClass.edit(current.data['assetGroupId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkAgrAssetGroupDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetGroupClass.deactivate(current.data['assetGroupId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkAgrAssetGroupActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetGroupClass.activate(current.data['assetGroupId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkAgrAssetGroupDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['assetGroupId'], modalAssetGroupClass);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtAgrAssetGroup') {
                return true;
            }
            const statusVal = $('#optAgrStatus').val();
            if (!statusVal) {
                return true;
            }
            const rowData = oTableAssetGroup.row(dataIndex).data();
            return rowData && String(rowData['assetGroupStatus']) === String(statusVal);
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);
        $('#txtAgrAssetGroupSearch').on('keyup change', function () {
            oTableAssetGroup.search($(this).val()).draw();
        });

        $('#btnAgrAssetGroupAdd').on('click', function () {
            modalAssetGroupClass.add();
        });

        updateFilterSummary();

        $('#optAgrStatus').on('change', function () {
            oTableAssetGroup.draw();
            updateFilterSummary();
        });

        $('#btnDtAgrAssetGroupRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAgr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAgr(0);
    };

    this.genTableAgr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetGroup = mzGetLocalRaw('gems_assetGroup', versionLocal, [], 'asset_group');
        lastUpdated = new Date();
        oTableAssetGroup.clear().rows.add(Array.isArray(refAssetGroup) ? refAssetGroup : []).draw();
    };

    this.addTableAgr = function (_dataAdd) {
        lastUpdated = new Date();
        oTableAssetGroup.row.add(_dataAdd).draw();
    };

    this.updateTableAgr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetGroup.row(_rowEdit).data();
        if (typeof _dataEdit['assetGroupName'] !== 'undefined') {
            currentRow['assetGroupName'] = _dataEdit['assetGroupName'];
        }
        if (typeof _dataEdit['assetGroupDesc'] !== 'undefined') {
            currentRow['assetGroupDesc'] = _dataEdit['assetGroupDesc'];
        }
        if (typeof _dataEdit['assetGroupStatus'] !== 'undefined') {
            currentRow['assetGroupStatus'] = _dataEdit['assetGroupStatus'];
        }
        lastUpdated = new Date();
        oTableAssetGroup.row(_rowEdit).data(currentRow).draw();
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
        if (oTableAssetGroup) {
            oTableAssetGroup.rows().invalidate();
            oTableAssetGroup.draw(false);
        }
    };

    this.setModalAssetGroupClass = function (_modalAssetGroupClass) {
        modalAssetGroupClass = _modalAssetGroupClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
