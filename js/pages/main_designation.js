function MainDesignation() {

    const className = 'MainDesignation';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let oTableDesignation;
    let modalDesignationClass;
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
        if (!oTableDesignation) {
            return;
        }
        const data = oTableDesignation.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['designationStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
        }
        $('#metricDsgTotal').text(total.toLocaleString());
        $('#metricDsgActive').text(active.toLocaleString());
        $('#metricDsgInactive').text(inactive.toLocaleString());
    }

    function updateSummary() {
        if (!oTableDesignation) {
            return;
        }
        const info = (oTableDesignation && typeof oTableDesignation.page === 'function' && typeof oTableDesignation.page.info === 'function')
            ? oTableDesignation.page.info()
            : null;
        if (info) {
            $('#lblDsgDesignationCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        }
        $('#lblDsgDesignationUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const statusVal = $('#optDsgDesignationStatus').val();
        let label = 'Status: All';
        if (statusVal && statusVal !== 'all' && refStatus && refStatus[statusVal]) {
            label = 'Status: ' + refStatus[statusVal]['statusDesc'];
        }
        $('#lblDsgDesignationFilter').text(label);
    }

    function buildStatusOptions() {
        const $select = $('#optDsgDesignationStatus');
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
        const status = refStatus && refStatus[row['designationStatus']];
        return status ? status['statusDesc'] : '';
    }

    function statusBadge(row, type) {
        const label = statusLabel(row);
        if (type !== 'display') {
            return label;
        }
        const kind = row['designationStatus'] === '1' ? 'success' : 'secondary';
        return GemsUI.badge(kind, GemsUI.escape(label));
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function rowDataFromLink(el) {
        const rowId = rowIdFromLink(el);
        if (!rowId || !oTableDesignation) {
            return null;
        }
        return { rowId: rowId, data: oTableDesignation.row(parseInt(rowId, 10)).data() };
    }

    this.init = function () {
        let cntDesignation;
        const exportOpt = {
            columns: [0, 1, 2],
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        cntDesignation = 1;
                    }
                    if (column === 0) {
                        return cntDesignation++;
                    }
                    return data;
                }
            }
        };
        const dtButtons = GemsUI.dtButtons('GEMS 2.0 - Designation List').map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });

        oTableDesignation = $('#dtDsgDesignation').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [1, 'asc'],
            dom: GemsUI.dtDomButtons,
            buttons: dtButtons,
            language: GemsUI.dtEmpty('fa-id-card-clip', 'No designations recorded yet.', 'No designations match the current search or status filter.'),
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableDesignation && oTableDesignation.page && typeof oTableDesignation.page.info === 'function')
                    ? oTableDesignation.page.info()
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
                {mData: 'designationDesc'},
                {mData: null,
                    mRender: function (data, type, row) {
                        return statusBadge(row, type);
                    }
                },
                {mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkDsgDesignationEdit',
                            id: 'lnkDsgDesignationEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['designationStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkDsgDesignationDeactivate',
                                id: 'lnkDsgDesignationDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkDsgDesignationActivate',
                                id: 'lnkDsgDesignationActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkDsgDesignationDelete',
                            id: 'lnkDsgDesignationDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                },
                {mData: 'designationId', visible: false, sClass: 'noVis'}
            ]
        });

        oTableDesignation.buttons().container().appendTo($('#btnDtDsgDesignationExport'));
        GemsUI.bindDtTooltips('#dtDsgDesignation');

        const tbody = $('#dtDsgDesignation tbody');
        tbody.on('click', '.lnkDsgDesignationEdit', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalDesignationClass.edit(current.data['designationId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkDsgDesignationDeactivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalDesignationClass.deactivate(current.data['designationId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkDsgDesignationActivate', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalDesignationClass.activate(current.data['designationId'], current.rowId);
            }
        });
        tbody.on('click', '.lnkDsgDesignationDelete', function () {
            const current = rowDataFromLink(this);
            if (current && current.data) {
                modalConfirmDeleteClass.delete(current.data['designationId'], modalDesignationClass);
            }
        });

        statusFilterFn = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'dtDsgDesignation') {
                return true;
            }
            const statusVal = $('#optDsgDesignationStatus').val();
            if (!statusVal || statusVal === 'all') {
                return true;
            }
            const rowData = oTableDesignation.row(dataIndex).data();
            return rowData && rowData['designationStatus'] === statusVal;
        };
        $.fn.dataTable.ext.search.push(statusFilterFn);
        $('#txtDsgDesignationSearch').on('keyup change', function () {
            oTableDesignation.search($(this).val()).draw();
        });

        $('#btnDsgDesignationAdd').on('click', function () {
            modalDesignationClass.add();
        });

        buildStatusOptions();
        updateFilterSummary();

        $('#optDsgDesignationStatus').on('change', function () {
            oTableDesignation.draw();
            updateFilterSummary();
        });

        $('#btnDtDsgDesignationRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableDsg(1);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableDsg(0);
    };

    this.genTableDsg = function (_type) {
        if (_type === 1) {
            versionLocal = mzGetDataVersion();
        }
        const refDesignation = mzGetLocalRaw('gems_designation', versionLocal, [], 'designation');
        lastUpdated = new Date();
        oTableDesignation.clear().rows.add(refDesignation).draw();
    };

    this.addTableDsg = function (_dataAdd) {
        lastUpdated = new Date();
        oTableDesignation.row.add(_dataAdd).draw();
    };

    this.updateTableDsg = function (_dataEdit, _rowEdit) {
        const currentRow = oTableDesignation.row(_rowEdit).data();
        if (typeof _dataEdit['designationDesc'] !== 'undefined') {
            currentRow['designationDesc'] = _dataEdit['designationDesc'];
        }
        if (typeof _dataEdit['designationStatus'] !== 'undefined') {
            currentRow['designationStatus'] = _dataEdit['designationStatus'];
        }
        lastUpdated = new Date();
        oTableDesignation.row(_rowEdit).data(currentRow).draw();
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
        if (oTableDesignation) {
            oTableDesignation.rows().invalidate();
            oTableDesignation.draw(false);
        }
    };

    this.setModalDesignationClass = function (_modalDesignationClass) {
        modalDesignationClass = _modalDesignationClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
