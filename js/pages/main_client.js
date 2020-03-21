function MainClient() {

    const className = 'MainClient';
    let self = this;
    let versionLocal;
    let modalConfirmDeleteClass;
    let refStatus;
    let refSeverity;
    let oTableClient;
    let modalClientClass;
    let modalSeverityHourClass;

    this.init = function () {
        oTableClient =  $('#dtClnClient').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableClient.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkClnClientEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalClientClass.edit(currentRow['clientId'], rowId);
                    }
                });
                $('.lnkClnClientDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalClientClass.deactivate(currentRow['clientId'], rowId);
                    }
                });
                $('.lnkClnClientActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalClientClass.activate(currentRow['clientId'], rowId);
                    }
                });
                $('.lnkClnClientDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableClient.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['clientId'], modalClientClass);
                    }
                });
                $('.lnkClnClientHourEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const linkIndex2 = rowId.indexOf('_');
                        const linkIndex3 = rowId.indexOf('__');
                        const linkIndex4 = rowId.indexOf('___');
                        if (linkIndex2 > 0) {
                            const severityId = rowId.substring(linkIndex2 + 1, linkIndex3);
                            const severityHour = rowId.substring(linkIndex3 + 2, linkIndex4);
                            const severityRespondTime = rowId.substr(linkIndex4 + 3);
                            const currentRow = oTableClient.row(parseInt(rowId)).data();
                            //alert(currentRow['clientId'] + ' ' + severityId + ' ' + severityHour + ' ' + severityRespondTime);
                            const passParam = {
                                clientName:currentRow['clientName'],
                                severityId:severityId,
                                severityRespondTime:severityRespondTime,
                                severityHour:severityHour
                            };
                            modalSeverityHourClass.edit(currentRow['clientId'], rowId, passParam);
                        }
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'clientName'},
                    {mData: 'clientDesc'},
                    {mData: null,
                        mRender: function (data, type, row, meta) {
                            let label = '';
                            let severity = row['severities'];
                            let severityRespondTime = row['severityRespondTime'];
                            let severityHour = row['severityHours'];
                            if (severity !== '' && severityHour !== '') {
                                label = '<ul style="padding-left: 0px; margin-bottom: 0px !important;">';
                                const severitySplit = severity.split(',');
                                const hourSplit = severityHour.split(',');
                                const respondSplit = severityRespondTime.split(',');
                                for (let j=0; j<severitySplit.length; j++) {
                                    label += '<li>' + refSeverity[severitySplit[j]]['severityName'] + ' - ' + respondSplit[j] + '-minute/' + hourSplit[j] + '-hour <a><i class="fas fa-pen-alt lnkClnClientHourEdit" id="lnkClnClientHourEdit_'+meta.row+'_'+severitySplit[j]+'__'+hourSplit[j]+'___'+respondSplit[j]+'" data-toggle="tooltip" data-placement="top" title="Edit KPI hours"></i></a></li>';
                                }
                                label += '</ul>';
                            }
                            return label;
                        }
                    },
                    {mData: null, sClass: 'text-center',
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['clientStatus']]['statusColor']+' z-depth-2">'+refStatus[row['clientStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkClnClientEdit" id="lnkClnClientEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['clientStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkClnClientDeactivate" id="lnkClnClientDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkClnClientActivate" id="lnkClnClientActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkClnClientDelete" id="lnkClnClientDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    },
                    {mData: 'clientId', visible: false}
                ]
        });
        $("#dtClnClient_filter").hide();
        $('#txtClnClientSearch').on('keyup change', function () {
            oTableClient.search($(this).val()).draw();
        });

        let cntClient;
        let btnClientOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntClient = 1;
                        }
                        if (column === 3) {
                            const m = data.replace('<ul style="padding-left: 0px; margin-bottom: 0px !important;"><li>','');
                            const p = m.replace('</li></ul>', '');
                            let r = p;
                            let linkIndex = r.indexOf('<a>');
                            let linkIndex2 = r.indexOf('</a>');
                            while (linkIndex > 0) {
                                r = r.substr(0,linkIndex - 1) + r.substr(linkIndex2 + 4);
                                linkIndex = r.indexOf('<a>');
                                linkIndex2 = r.indexOf('</a>');
                            }
                            const q = r.split('</li><li>').join(', ');
                            return q;
                        } else if (column === 4) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntClient++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableClient, {
            buttons: [
                $.extend( true, {}, btnClientOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Client List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnClientOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Client List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnClientOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Client List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtClnClientExport'));

        $('#btnClnClientAdd').on('click', function () {
            modalClientClass.add();
        });

        $('#btnDtClnClientRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableCln();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        self.genTableCln();
    };

    this.genTableCln = function () {
        const refClient = mzAjaxRequest('client.php?type=with_severity', 'GET');
        oTableClient.clear().rows.add(refClient).draw();
    };

    this.addTableCln = function (_dataAdd) {
        oTableClient.row.add(_dataAdd).draw();
    };

    this.updateTableCln = function (_dataEdit, _rowEdit) {
        const currentRow = oTableClient.row(_rowEdit).data();
        if (typeof _dataEdit['clientName'] !== 'undefined') {
            currentRow['clientName'] = _dataEdit['clientName'];
        }
        if (typeof _dataEdit['clientDesc'] !== 'undefined') {
            currentRow['clientDesc'] = _dataEdit['clientDesc'];
        }
        if (typeof _dataEdit['clientStatus'] !== 'undefined') {
            currentRow['clientStatus'] = _dataEdit['clientStatus'];
        }
        oTableClient.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };

    this.setModalClientClass = function (_modalClientClass) {
        modalClientClass = _modalClientClass;
    };

    this.setModalSeverityHourClass = function (_modalSeverityHourClass) {
        modalSeverityHourClass = _modalSeverityHourClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}