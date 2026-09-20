function MainAssetBrand() {

    const className = 'MainAssetBrand';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableAssetBrand;
    let modalAssetBrandClass;
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
        if (!oTableAssetBrand) {
            return;
        }
        const data = oTableAssetBrand.rows().data();
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
            switch (String(row['assetBrandStatus'])) {
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
        $('#metricAbrTotal').text(total.toLocaleString());
        $('#metricAbrActive').text(active.toLocaleString());
        $('#metricAbrInactive').text(inactive.toLocaleString());
        $('#metricAbrArchived').text(archived.toLocaleString());
    }

    function updateSummary() {
        if (!oTableAssetBrand) {
            return;
        }
        const info = (oTableAssetBrand && typeof oTableAssetBrand.page === 'function' && typeof oTableAssetBrand.page.info === 'function')
            ? oTableAssetBrand.page.info()
            : null;
        const summaryText = info
            ? ('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal)
            : 'Showing 0 of 0';
        $('#lblAbrFilterCount').text(summaryText);
        $('#lblAbrListCount').text(summaryText + ' records');
        const updatedText = formatTimestamp(lastUpdated);
        $('#lblAbrFilterUpdated').text(updatedText);
        $('#lblAbrListUpdated').html('<i class="far fa-clock me-1"></i>' + updatedText);
    }

    function updateFilterSummary() {
        const statusVal = $('#optAbrStatus').val();
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
        $('#lblAbrAssetBrandFilter').text(label);
    }

    function statusLabel(row) {
        const status = refStatus && refStatus[row['assetBrandStatus']];
        if (status) {
            return status['statusDesc'];
        }
        switch (String(row['assetBrandStatus'])) {
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
        return GemsUI.badge(statusBadgeKind(row['assetBrandStatus']), GemsUI.escape(label));
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableAssetBrand) {
            return null;
        }
        return { rowId: rowId, data: oTableAssetBrand.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let cntAssetBrand;
        const exportOpt = {
            columns: [0, 1, 2, 3],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntAssetBrand = 1;
                    }
                    if (column === 0) {
                        return cntAssetBrand++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Asset Brand List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableAssetBrand = $('#dtAbrAssetBrand').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            pageLength: 25,
            aaSorting: [[1, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-copyright', 'No asset brands recorded yet.', 'No asset brands match the current search or status filter.'),
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableAssetBrand && oTableAssetBrand.page && typeof oTableAssetBrand.page.info === 'function')
                    ? oTableAssetBrand.page.info()
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
                {mData: 'assetBrandName', sClass: 'text-nowrap'},
                {mData: 'assetBrandDesc'},
                {mData: null, bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row, type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkAbrAssetBrandEdit',
                            id: 'lnkAbrAssetBrandEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['assetBrandStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkAbrAssetBrandDeactivate',
                                id: 'lnkAbrAssetBrandDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkAbrAssetBrandActivate',
                                id: 'lnkAbrAssetBrandActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkAbrAssetBrandDelete',
                            id: 'lnkAbrAssetBrandDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'assetBrandId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableAssetBrand.buttons().container().appendTo($('#btnDtAbrAssetBrandExport'));
        GemsUI.bindDtTooltips('#dtAbrAssetBrand');

        const tbody = $('#dtAbrAssetBrand tbody');
        tbody.on('click', '.lnkAbrAssetBrandEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetBrandClass.edit(current.data['assetBrandId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkAbrAssetBrandDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetBrandClass.deactivate(current.data['assetBrandId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkAbrAssetBrandActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalAssetBrandClass.activate(current.data['assetBrandId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkAbrAssetBrandDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['assetBrandId'], modalAssetBrandClass);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtAbrAssetBrand') {
                return true;
            }
            const statusVal = $('#optAbrStatus').val();
            if (!statusVal) {
                return true;
            }
            const rowData = oTableAssetBrand.row(dataIndex).data();
            return rowData && String(rowData['assetBrandStatus']) === String(statusVal);
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);
        $('#txtAbrAssetBrandSearch').on('keyup change', function () {
            oTableAssetBrand.search($(this).val()).draw();
        });

        $('#btnAbrAssetBrandAdd').on('click', function () {
            modalAssetBrandClass.add();
        });

        updateFilterSummary();

        $('#optAbrStatus').on('change', function () {
            oTableAssetBrand.draw();
            updateFilterSummary();
        });

        $('#btnDtAbrAssetBrandRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableAbr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableAbr(0);
    };

    this.genTableAbr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refAssetBrand = mzGetLocalRaw('gems_assetBrand', versionLocal, [], 'asset_brand');
        lastUpdated = new Date();
        oTableAssetBrand.clear().rows.add(Array.isArray(refAssetBrand) ? refAssetBrand : []).draw();
    };

    this.addTableAbr = function (_dataAdd) {
        lastUpdated = new Date();
        oTableAssetBrand.row.add(_dataAdd).draw();
    };

    this.updateTableAbr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableAssetBrand.row(_rowEdit).data();
        if (typeof _dataEdit['assetBrandName'] !== 'undefined') {
            currentRow['assetBrandName'] = _dataEdit['assetBrandName'];
        }
        if (typeof _dataEdit['assetBrandDesc'] !== 'undefined') {
            currentRow['assetBrandDesc'] = _dataEdit['assetBrandDesc'];
        }
        if (typeof _dataEdit['assetBrandStatus'] !== 'undefined') {
            currentRow['assetBrandStatus'] = _dataEdit['assetBrandStatus'];
        }
        lastUpdated = new Date();
        oTableAssetBrand.row(_rowEdit).data(currentRow).draw();
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
        if (oTableAssetBrand) {
            oTableAssetBrand.rows().invalidate();
            oTableAssetBrand.draw(false);
        }
    };

    this.setModalAssetBrandClass = function (_modalAssetBrandClass) {
        modalAssetBrandClass = _modalAssetBrandClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
