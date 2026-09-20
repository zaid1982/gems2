function MainSeverity() {

    const className = 'MainSeverity';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableSeverity;
    let modalSeverityClass;
    let lastUpdated = null;
    let statusFilterFn;

    function formatTimestamp(value) {
        if (!value) {
            return 'Updated —';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function updateMetrics() {
        if (!oTableSeverity) {
            return;
        }
        const data = oTableSeverity.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['severityStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
        }
        $('#metricSvrTotal').text(total.toLocaleString());
        $('#metricSvrActive').text(active.toLocaleString());
        $('#metricSvrInactive').text(inactive.toLocaleString());
    }

    function updateSummary() {
        if (!oTableSeverity) {
            return;
        }
        const info = (oTableSeverity && typeof oTableSeverity.page === 'function' && typeof oTableSeverity.page.info === 'function')
            ? oTableSeverity.page.info()
            : null;
        if (info) {
            $('#lblSvrSeverityCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        }
        $('#lblSvrSeverityUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const statusVal = $('#optSvrSeverityStatus').val();
        let label = 'Status: All';
        if (statusVal && statusVal !== 'all' && refStatus && refStatus[statusVal]) {
            label = 'Status: ' + refStatus[statusVal]['statusDesc'];
        }
        $('#lblSvrSeverityFilter').text(label);
    }

    function buildStatusOptions() {
        const $select = $('#optSvrSeverityStatus');
        if (!$select.length) {
            return;
        }
        const currentValue = $select.val() || 'all';
        $select.find('option:not([value="all"])').remove();
        if (refStatus) {
            Object.keys(refStatus).forEach(function (key) {
                const status = refStatus[key];
                if (!status) {
                    return;
                }
                $select.append('<option value="' + key + '">' + status['statusDesc'] + '</option>');
            });
        }
        $select.val(currentValue);
    }

    function statusLabel(row) {
        const status = refStatus && refStatus[row['severityStatus']];
        return status ? status['statusDesc'] : '';
    }

    function statusBadge(row, type) {
        const label = statusLabel(row);
        if (type !== 'display') {
            return label;
        }
        const kind = row['severityStatus'] === '1' ? 'success' : 'secondary';
        return GemsUI.badge(kind, GemsUI.escape(label));
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableSeverity) {
            return null;
        }
        return { rowId: rowId, data: oTableSeverity.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let cntSeverity;
        const exportOpt = {
            columns: [0, 1, 2],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntSeverity = 1;
                    }
                    if (column === 0) {
                        return cntSeverity++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Severity List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableSeverity = $('#dtSvrSeverity').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [1, 'asc'],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-triangle-exclamation', 'No severity levels recorded yet.', 'No severity levels match the current search or status filter.'),
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableSeverity && oTableSeverity.page && typeof oTableSeverity.page.info === 'function')
                    ? oTableSeverity.page.info()
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
                {mData: 'severityName'},
                {mData: null,
                    mRender: function (data, type, row) {
                        return statusBadge(row, type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkSvrSeverityEdit',
                            id: 'lnkSvrSeverityEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['severityStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkSvrSeverityDeactivate',
                                id: 'lnkSvrSeverityDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkSvrSeverityActivate',
                                id: 'lnkSvrSeverityActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkSvrSeverityDelete',
                            id: 'lnkSvrSeverityDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'severityId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableSeverity.buttons().container().appendTo($('#btnDtSvrSeverityExport'));
        GemsUI.bindDtTooltips('#dtSvrSeverity');

        const tbody = $('#dtSvrSeverity tbody');
        tbody.on('click', '.lnkSvrSeverityEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalSeverityClass.edit(current.data['severityId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkSvrSeverityDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalSeverityClass.deactivate(current.data['severityId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkSvrSeverityActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalSeverityClass.activate(current.data['severityId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkSvrSeverityDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['severityId'], modalSeverityClass);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtSvrSeverity') {
                return true;
            }
            const statusVal = $('#optSvrSeverityStatus').val();
            if (!statusVal || statusVal === 'all') {
                return true;
            }
            const rowData = oTableSeverity.row(dataIndex).data();
            return rowData && rowData['severityStatus'] === statusVal;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);
        $('#txtSvrSeveritySearch').on('keyup change', function () {
            oTableSeverity.search($(this).val()).draw();
        });

        $('#btnSvrSeverityAdd').on('click', function () {
            modalSeverityClass.add();
        });

        buildStatusOptions();
        updateFilterSummary();

        $('#optSvrSeverityStatus').on('change', function () {
            oTableSeverity.draw();
            updateFilterSummary();
        });

        $('#btnDtSvrSeverityRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSvr(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableSvr(0);
    };

    this.genTableSvr = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refSeverity = mzGetLocalRaw('gems_severity', versionLocal, [], 'severity');
        lastUpdated = new Date();
        oTableSeverity.clear().rows.add(refSeverity).draw();
    };

    this.addTableSvr = function (_dataAdd) {
        lastUpdated = new Date();
        oTableSeverity.row.add(_dataAdd).draw();
    };

    this.updateTableSvr = function (_dataEdit, _rowEdit) {
        const currentRow = oTableSeverity.row(_rowEdit).data();
        if (typeof _dataEdit['severityName'] !== 'undefined') {
            currentRow['severityName'] = _dataEdit['severityName'];
        }
        if (typeof _dataEdit['severityStatus'] !== 'undefined') {
            currentRow['severityStatus'] = _dataEdit['severityStatus'];
        }
        lastUpdated = new Date();
        oTableSeverity.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
        buildStatusOptions();
        updateFilterSummary();
        if (oTableSeverity) {
            oTableSeverity.rows().invalidate();
            oTableSeverity.draw(false);
        }
    };

    this.setModalSeverityClass = function (_modalSeverityClass) {
        modalSeverityClass = _modalSeverityClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
