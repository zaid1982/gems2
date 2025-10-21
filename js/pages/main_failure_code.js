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

    this.init = function () {
        oTableFailureCode =  $('#dtFlcFailureCode').DataTable({
            bLengthChange: false,
            bFilter: false,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = (oTableFailureCode && oTableFailureCode.page && typeof oTableFailureCode.page.info === 'function')
                    ? oTableFailureCode.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber).attr('data-label', '#');
                $('td', nRow).eq(1).attr('data-label', 'Failure Code');
                $('td', nRow).eq(2).attr('data-label', 'Status');
                $('td', nRow).eq(3).attr('data-label', 'Actions');
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkFlcFailureCodeEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableFailureCode.row(parseInt(rowId)).data();
                        modalFailureCodeClass.edit(currentRow['failureCodeId'], rowId);
                    }
                });
                $('.lnkFlcFailureCodeDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableFailureCode.row(parseInt(rowId)).data();
                        modalFailureCodeClass.deactivate(currentRow['failureCodeId'], rowId);
                    }
                });
                $('.lnkFlcFailureCodeActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableFailureCode.row(parseInt(rowId)).data();
                        modalFailureCodeClass.activate(currentRow['failureCodeId'], rowId);
                    }
                });
                $('.lnkFlcFailureCodeDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableFailureCode.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['failureCodeId'], modalFailureCodeClass);
                    }
                });
                updateMetrics();
                updateSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'failureCodeName'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill ' + refStatus[row['failureCodeStatus']]['statusColor'] + ' z-depth-2">' + refStatus[row['failureCodeStatus']]['statusDesc'] + '</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkFlcFailureCodeEdit" id="lnkFlcFailureCodeEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['failureCodeStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkFlcFailureCodeDeactivate" id="lnkFlcFailureCodeDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkFlcFailureCodeActivate" id="lnkFlcFailureCodeActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkFlcFailureCodeDelete" id="lnkFlcFailureCodeDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'failureCodeId', visible: false}
                ]
        });
        $('#dtFlcFailureCode_filter').hide();

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

        let cntFailureCode;
        let btnFailureCodeOpt = {
            exportOptions: {
                columns: [ 0, 1, 2],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntFailureCode = 1;
                        }
                        if (column === 2) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntFailureCode++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableFailureCode, {
            buttons: [
                $.extend( true, {}, btnFailureCodeOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Failure Code List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnFailureCodeOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Failure Code List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnFailureCodeOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Failure Code List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtFlcFailureCodeExport'));

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
            }, 200);
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