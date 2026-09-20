function MainFailureCode() {

    const className = 'MainFailureCode';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableFailureCode;
    let modalFailureCodeClass;
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
        if (!oTableFailureCode) {
            return;
        }
        const data = oTableFailureCode.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['failureCodeStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
        }
        $('#metricFlcTotal').text(total.toLocaleString());
        $('#metricFlcActive').text(active.toLocaleString());
        $('#metricFlcInactive').text(inactive.toLocaleString());
    }

    function updateSummary() {
        if (!oTableFailureCode) {
            return;
        }
        const info = (oTableFailureCode && typeof oTableFailureCode.page === 'function' && typeof oTableFailureCode.page.info === 'function')
            ? oTableFailureCode.page.info()
            : null;
        if (info) {
            $('#lblFlcFailureCodeCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        }
        $('#lblFlcFailureCodeUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const statusVal = $('#optFlcFailureCodeStatus').val();
        let label = 'Status: All';
        if (statusVal && statusVal !== 'all' && refStatus && refStatus[statusVal]) {
            label = 'Status: ' + refStatus[statusVal]['statusDesc'];
        }
        $('#lblFlcFailureCodeFilter').text(label);
    }

    function buildStatusOptions() {
        const $select = $('#optFlcFailureCodeStatus');
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
        const status = refStatus && refStatus[row['failureCodeStatus']];
        return status ? status['statusDesc'] : '';
    }

    function statusBadge(row, type) {
        const label = statusLabel(row);
        if (type !== 'display') {
            return label;
        }
        const kind = row['failureCodeStatus'] === '1' ? 'success' : 'secondary';
        return GemsUI.badge(kind, GemsUI.escape(label));
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableFailureCode) {
            return null;
        }
        return { rowId: rowId, data: oTableFailureCode.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let cntFailureCode;
        const exportOpt = {
            columns: [0, 1, 2],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntFailureCode = 1;
                    }
                    if (column === 0) {
                        return cntFailureCode++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Failure Code List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableFailureCode = $('#dtFlcFailureCode').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [1, 'asc'],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-circle-exclamation', 'No failure codes recorded yet.', 'No failure codes match the current search or status filter.'),
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableFailureCode && oTableFailureCode.page && typeof oTableFailureCode.page.info === 'function')
                    ? oTableFailureCode.page.info()
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
                {mData: 'failureCodeName'},
                {mData: null,
                    mRender: function (data, type, row) {
                        return statusBadge(row, type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkFlcFailureCodeEdit',
                            id: 'lnkFlcFailureCodeEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['failureCodeStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkFlcFailureCodeDeactivate',
                                id: 'lnkFlcFailureCodeDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkFlcFailureCodeActivate',
                                id: 'lnkFlcFailureCodeActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkFlcFailureCodeDelete',
                            id: 'lnkFlcFailureCodeDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'failureCodeId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableFailureCode.buttons().container().appendTo($('#btnDtFlcFailureCodeExport'));
        GemsUI.bindDtTooltips('#dtFlcFailureCode');

        const tbody = $('#dtFlcFailureCode tbody');
        tbody.on('click', '.lnkFlcFailureCodeEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalFailureCodeClass.edit(current.data['failureCodeId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkFlcFailureCodeDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalFailureCodeClass.deactivate(current.data['failureCodeId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkFlcFailureCodeActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalFailureCodeClass.activate(current.data['failureCodeId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkFlcFailureCodeDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['failureCodeId'], modalFailureCodeClass);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtFlcFailureCode') {
                return true;
            }
            const statusVal = $('#optFlcFailureCodeStatus').val();
            if (!statusVal || statusVal === 'all') {
                return true;
            }
            const rowData = oTableFailureCode.row(dataIndex).data();
            return rowData && rowData['failureCodeStatus'] === statusVal;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);
        $('#txtFlcFailureCodeSearch').on('keyup change', function () {
            oTableFailureCode.search($(this).val()).draw();
        });

        $('#btnFlcFailureCodeAdd').on('click', function () {
            modalFailureCodeClass.add();
        });

        buildStatusOptions();
        updateFilterSummary();

        $('#optFlcFailureCodeStatus').on('change', function () {
            oTableFailureCode.draw();
            updateFilterSummary();
        });

        $('#btnDtFlcFailureCodeRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableFlc(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableFlc(0);
    };

    this.genTableFlc = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refFailureCode = mzGetLocalRaw('gems_failureCode', versionLocal, [], 'failure_code');
        lastUpdated = new Date();
        oTableFailureCode.clear().rows.add(refFailureCode).draw();
    };

    this.addTableFlc = function (_dataAdd) {
        lastUpdated = new Date();
        oTableFailureCode.row.add(_dataAdd).draw();
    };

    this.updateTableFlc = function (_dataEdit, _rowEdit) {
        const currentRow = oTableFailureCode.row(_rowEdit).data();
        if (typeof _dataEdit['failureCodeName'] !== 'undefined') {
            currentRow['failureCodeName'] = _dataEdit['failureCodeName'];
        }
        if (typeof _dataEdit['failureCodeDesc'] !== 'undefined') {
            currentRow['failureCodeDesc'] = _dataEdit['failureCodeDesc'];
        }
        if (typeof _dataEdit['failureCodeStatus'] !== 'undefined') {
            currentRow['failureCodeStatus'] = _dataEdit['failureCodeStatus'];
        }
        lastUpdated = new Date();
        oTableFailureCode.row(_rowEdit).data(currentRow).draw();
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
        if (oTableFailureCode) {
            oTableFailureCode.rows().invalidate();
            oTableFailureCode.draw(false);
        }
    };

    this.setModalFailureCodeClass = function (_modalFailureCodeClass) {
        modalFailureCodeClass = _modalFailureCodeClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
