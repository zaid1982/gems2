function MainChecklist() {

    const className = 'MainChecklist';
    let self = this;
    let modalConfirmDeleteClass;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let oTableChecklistGroup;
    let oTableChecklist;
    let assetTypeIdSelected;
    let rowIdChecklistGroup;
    let sectionChecklistClass;

    this.init = function () {
        $('#divPcmChecklistSelected').hide();
        mzOption('optPcmGroupId', refAssetGroup, 'All Asset Group', 'assetGroupId', 'assetGroupName', {});

        oTableChecklistGroup =  $('#dtPcmChecklistGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableChecklistGroup.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPcmChecklistGroupExpand').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistGroup.row(parseInt(rowId)).data();
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                self.genTablePcmChecklist(currentRow['assetTypeId'], rowId);
                                const elmnt = document.getElementById("divPcmChecklistSelected");
                                elmnt.scrollIntoView();
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 300);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                        }},
                    {mData: 'totalChecklist', sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            const color = data === 0 ? 'red lighten-1' : 'cyan accent-4';
                            return '<a class="trigger '+color+' text-white lnkPcmChecklistGroupExpand" id="lnkPcmChecklistGroupTotal_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Checklist list">'+mzFormatNumber(data)+'</a>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            return '<a><i class="fas fa-list-ul lnkPcmChecklistGroupExpand" id="lnkPcmChecklistGroupExpand_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Checklist list"></i></a>&nbsp;&nbsp;';
                        }
                    },
                    {mData: 'assetGroupId', visible: false},
                    {mData: 'assetCategoryId', visible: false},
                    {mData: 'assetTypeId', visible: false}
                ]
        });
        $("#dtPcmChecklistGroup_filter").hide();
        $('#txtPcmChecklistGroupSearch').on('keyup change', function () {
            oTableChecklistGroup.search($(this).val()).draw();
        });

        $('#optPcmGroupId').on('change', function () {
            mzOptionStop('optPcmCategoryId', refAssetCategory, 'All Asset Category', 'assetCategoryId', 'assetCategoryName', {assetGroupId: $(this).val()});
            mzOptionStopClear('optPcmTypeId', 'All Asset Type');
            oTableChecklistGroup.column(6).search($(this).val(), false, true, false).draw();
            oTableChecklistGroup.column(7).search('', false, true, false).draw();
            oTableChecklistGroup.column(8).search('', false, true, false).draw();
        });

        $('#optPcmCategoryId').on('change', function () {
            mzOptionStop('optPcmTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val()});
            oTableChecklistGroup.column(7).search($(this).val(), false, true, false).draw();
            oTableChecklistGroup.column(8).search('', false, true, false).draw();
        });

        $('#optPcmTypeId').on('change', function () {
            oTableChecklistGroup.column(8).search($(this).val(), false, true, false).draw();
        });

        let cntChecklistGroup;
        let btnChecklistGroupOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntChecklistGroup = 1;
                        }
                        if (column === 4) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</a>','');
                        }
                        return column === 0 ? cntChecklistGroup++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableChecklistGroup, {
            buttons: [
                $.extend( true, {}, btnChecklistGroupOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistGroupOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistGroupOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtPcmChecklistGroupExport'));

        $('#btnDtPcmChecklistGroupRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePcmChecklistGroup();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableChecklist =  $('#dtPcmChecklist').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            "aaSorting": [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableChecklist.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPcmChecklistEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklist.row(parseInt(rowId)).data();
                        sectionChecklistClass.edit(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkPcmChecklistDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklist.row(parseInt(rowId)).data();
                        sectionChecklistClass.deactivate(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkPcmChecklistActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklist.row(parseInt(rowId)).data();
                        sectionChecklistClass.activate(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkPcmChecklistDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklist.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['checklistId'], sectionChecklistClass);
                    }
                });
                $('.lnkPcmChecklistPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableChecklist.row(parseInt(rowId)).data();
                                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+currentRow['pdfId'], 'GET');
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Checklist: '+currentRow['checklistName']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'checklistName'},
                    {mData: 'checklistDocumentNo'},
                    {mData: 'checklistIssueNo'},
                    {mData: 'checklistTimeRegistered'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['checklistStatus']]['statusColor']+' z-depth-2">'+refStatus[row['checklistStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkPcmChecklistEdit" id="lnkPcmChecklistEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>';
                            if (row['checklistStatus'] === '1') {
                                label += '&nbsp;&nbsp;<a><i class="fas fa-toggle-off lnkPcmChecklistDeactivate" id="lnkPcmChecklistDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>';
                            } else if (row['checklistStatus'] === '2') {
                                label += '&nbsp;&nbsp;<a><i class="fas fa-toggle-on lnkPcmChecklistActivate" id="lnkPcmChecklistActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>';
                            } else if (row['checklistStatus'] === '5') {
                                label += '&nbsp;&nbsp;<a><i class="fas fa-trash-alt lnkPcmChecklistDelete" id="lnkPcmChecklistDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            }
                            if (row['pdfId'] != '') {
                                label += '&nbsp;&nbsp;<a><i class="far fa-file-pdf lnkPcmChecklistPdf" id="lnkPcmChecklistPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Checklist PDF"></i></a>';
                            }
                            return label;
                        }
                    }
                ]
        });
        $("#dtPcmChecklist_filter").hide();

        let cntChecklist;
        let btnChecklistOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntChecklist = 1;
                        }
                        if (column === 5) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntChecklist++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableChecklist, {
            buttons: [
                $.extend( true, {}, btnChecklistOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtPcmChecklistExport'));

        $('#btnPcmChecklistAdd').on('click', function () {
            sectionChecklistClass.add(assetTypeIdSelected);
        });

        $('#btnDtPcmChecklistRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePcmChecklist(assetTypeIdSelected, rowIdChecklistGroup);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        self.genTablePcmChecklistGroup();
    };

    this.genTablePcmChecklistGroup = function () {
        const dataChecklistGroup = mzAjaxRequest('checklist.php?type=checklist_by_type', 'GET');
        oTableChecklistGroup.clear().rows.add(dataChecklistGroup).draw();
    };

    this.genTablePcmChecklist = function (_assetTypeId, _rowId) {
        assetTypeIdSelected = _assetTypeId;
        rowIdChecklistGroup = _rowId;

        const dataChecklist = mzAjaxRequest('checklist.php?assetTypeId='+_assetTypeId, 'GET');
        oTableChecklist.clear().rows.add(dataChecklist).draw();

        const assetCategoryId = refAssetType[_assetTypeId]['assetCategoryId'];
        const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
        const labelTitle = refAssetGroup[assetGroupId]['assetGroupName'] + ' -> ' +
            refAssetCategory[assetCategoryId]['assetCategoryName'] + ' -> ' +
            refAssetType[_assetTypeId]['assetTypeName'];
        $('#lblPcmChecklistTitle').html(labelTitle);
        $('#divPcmChecklistSelected').show();
    };

    this.addTablePcmChecklist = function (_dataAdd) {
        oTableChecklist.row.add(_dataAdd).draw();
        self.genTablePcmChecklistGroup();
    };

    this.updateTablePcmChecklist = function (_dataEdit, _rowEdit) {
        const currentRow = oTableChecklist.row(_rowEdit).data();
        if (typeof _dataEdit['action'] !== 'undefined') {
            currentRow['checklistName'] = _dataEdit['checklistName'];
            currentRow['checklistDocumentNo'] = _dataEdit['checklistDocumentNo'];
            currentRow['checklistIssueNo'] = _dataEdit['checklistIssueNo'];
            currentRow['checklistDesc'] = _dataEdit['checklistDesc'];
            if (_dataEdit['action'] === 'submit') {
                currentRow['checklistStatus'] = '1';
            }
        }
        if (typeof _dataEdit['checklistStatus'] !== 'undefined') {
            currentRow['checklistStatus'] = _dataEdit['checklistStatus'];
        }
        oTableChecklist.row(_rowEdit).data(currentRow).draw();
    };

    this.deleteTablePcmChecklist = function () {
        self.genTablePcmChecklist(assetTypeIdSelected, rowIdChecklistGroup);
        self.genTablePcmChecklistGroup();
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

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };

    this.setSectionChecklistClass = function (_sectionChecklistClass) {
        sectionChecklistClass = _sectionChecklistClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}