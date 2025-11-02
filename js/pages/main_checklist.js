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
    let modalChecklistDuplicateClass;
    let labelTitle = '';
    const checklistGroupHeaders = ['#', 'Checklist Type', 'Asset Group', 'Asset Category', 'Asset Type', 'Total Checklist', 'Actions'];
    const checklistHeaders = ['#', 'Checklist Name', 'Document No', 'Issue No', 'Minimum Execution Time', 'Maximum Execution Time', 'Maximum Total Assistant', 'Registered Time', 'Status', 'Actions'];
    let dataChecklistGroupCache = [];
    let lastChecklistGroupUpdatedText = '—';
    let lastChecklistUpdatedText = '—';

    const applyTableDataLabels = function (tableSelector, headers) {
        $(`${tableSelector} tbody tr`).each(function () {
            $('td', this).each(function (index) {
                if (headers[index]) {
                    $(this).attr('data-label', headers[index]);
                }
            });
        });
    };

    const refreshChecklistGroupSummary = function () {
        if (!oTableChecklistGroup) {
            return;
        }
        const info = oTableChecklistGroup.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        $('#lblPcmChecklistGroupCount').text(`Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`);
        $('#lblPcmChecklistGroupUpdated').text(lastChecklistGroupUpdatedText);
    };

    const refreshChecklistSummary = function () {
        if (!oTableChecklist) {
            return;
        }
        const info = oTableChecklist.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        $('#lblPcmChecklistCount').text(`Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`);
        $('#lblPcmChecklistUpdated').text(lastChecklistUpdatedText);
    };

    const updateChecklistMetrics = function (dataSet) {
        const totalTypes = dataSet.length;
        let totalChecklist = 0;
        let zeroChecklistTypes = 0;
        dataSet.forEach(function (item) {
            const total = parseInt(item['totalChecklist'] || 0, 10);
            totalChecklist += total;
            if (total === 0) {
                zeroChecklistTypes += 1;
            }
        });
        const average = totalTypes ? totalChecklist / totalTypes : 0;
        const averageFix = average % 1 === 0 ? 0 : 1;
        $('#metricChecklistTypes').text(mzFormatNumber(totalTypes, 0));
        $('#metricChecklistTotal').text(mzFormatNumber(totalChecklist, 0));
        $('#metricChecklistGaps').text(mzFormatNumber(zeroChecklistTypes, 0)).toggleClass('text-danger', zeroChecklistTypes > 0);
        $('#metricChecklistAverage').text(totalTypes ? mzFormatNumber(average, averageFix) : '0');
    };

    const getNowStamp = function () {
        return (typeof moment !== 'undefined' && moment) ? moment().format('MMM D, YYYY h:mm A') : new Date().toLocaleString();
    };

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
                applyTableDataLabels('#dtPcmChecklistGroup', checklistGroupHeaders);
                refreshChecklistGroupSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'checklistType'},
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
                            let label = '<div class="action-btn-group">';
                            label += '<button type="button" class="btn-action btn-view lnkPcmChecklistGroupExpand" id="lnkPcmChecklistGroupExpand_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Checklist list"><i class="fas fa-list-ul"></i></button>';
                            label += '</div>';
                            return label;
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
            oTableChecklistGroup.column(7).search($(this).val(), false, true, false).draw();
            oTableChecklistGroup.column(8).search('', false, true, false).draw();
            oTableChecklistGroup.column(9).search('', false, true, false).draw();
        });

        $('#optPcmCategoryId').on('change', function () {
            mzOptionStop('optPcmTypeId', refAssetType, 'All Asset Type', 'assetTypeId', 'assetTypeName', {assetCategoryId: $(this).val()});
            oTableChecklistGroup.column(8).search($(this).val(), false, true, false).draw();
            oTableChecklistGroup.column(9).search('', false, true, false).draw();
        });

        $('#optPcmTypeId').on('change', function () {
            oTableChecklistGroup.column(9).search($(this).val(), false, true, false).draw();
        });

        let cntChecklistGroup;
        let btnChecklistGroupOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntChecklistGroup = 1;
                        }
                        if (column === 5) {
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
                $('.lnkPcmChecklistDuplicate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklist.row(parseInt(rowId)).data();
                        modalChecklistDuplicateClass.setDocumentNo(currentRow['checklistDocumentNo']);
                        modalChecklistDuplicateClass.setChecklistName(currentRow['checklistName']);
                        modalChecklistDuplicateClass.setAssetType(labelTitle);
                        modalChecklistDuplicateClass.add(parseInt(currentRow['checklistId']));
                    }
                });
                applyTableDataLabels('#dtPcmChecklist', checklistHeaders);
                refreshChecklistSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'checklistName'},
                    {mData: 'checklistDocumentNo', sClass: 'text-center', width: '10%'},
                    {mData: 'checklistIssueNo', sClass: 'text-right', width: '8%'},
                    {mData: 'checklistMinExecTime', sClass: 'text-center', width: '10%'},
                    {mData: 'checklistMaxExecTime', sClass: 'text-center', width: '10%'},
                    {mData: 'checklistMaxAssistant', sClass: 'text-right', width: '10%'},
                    {mData: 'checklistTimeRegistered', sClass: 'text-center', width: '10%'},
                    {mData: null, sClass: 'text-center',
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['checklistStatus']]['statusColor']+' z-depth-2">'+refStatus[row['checklistStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<div class="action-btn-group">';
                            label += '<button type="button" class="btn-action btn-edit lnkPcmChecklistEdit" id="lnkPcmChecklistEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"><i class="fas fa-edit"></i></button>';
                            if (row['checklistStatus'] === '1') {
                                label += '<button type="button" class="btn-action btn-deactivate lnkPcmChecklistDeactivate" id="lnkPcmChecklistDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"><i class="fas fa-toggle-off"></i></button>';
                            } else if (row['checklistStatus'] === '2') {
                                label += '<button type="button" class="btn-action btn-activate lnkPcmChecklistActivate" id="lnkPcmChecklistActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"><i class="fas fa-toggle-on"></i></button>';
                            } else if (row['checklistStatus'] === '5') {
                                label += '<button type="button" class="btn-action btn-delete lnkPcmChecklistDelete" id="lnkPcmChecklistDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                            }
                            if (row['pdfId'] != '') {
                                label += '<button type="button" class="btn-action btn-pdf lnkPcmChecklistPdf" id="lnkPcmChecklistPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Checklist PDF"><i class="far fa-file-pdf"></i></button>';
                            }
                            label += '<button type="button" class="btn-action btn-view lnkPcmChecklistDuplicate" id="lnkPcmChecklistDuplicate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Duplicate Checklist"><i class="far fa-copy"></i></button>';
                            label += '</div>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPcmChecklist_filter").hide();

        let cntChecklist;
        let btnChecklistOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntChecklist = 1;
                        }
                        if (column === 8) {
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
        dataChecklistGroupCache = mzAjaxRequest('checklist.php?type=checklist_by_type', 'GET');
        oTableChecklistGroup.clear().rows.add(dataChecklistGroupCache).draw();
        updateChecklistMetrics(dataChecklistGroupCache);
        lastChecklistGroupUpdatedText = getNowStamp();
        refreshChecklistGroupSummary();
    };

    this.genTablePcmChecklist = function (_assetTypeId, _rowId) {
        assetTypeIdSelected = _assetTypeId;
        rowIdChecklistGroup = _rowId;

        const dataChecklist = mzAjaxRequest('checklist.php?assetTypeId='+_assetTypeId, 'GET');
        lastChecklistUpdatedText = getNowStamp();
        oTableChecklist.clear().rows.add(dataChecklist).draw();
        refreshChecklistSummary();

        const assetCategoryId = refAssetType[_assetTypeId]['assetCategoryId'];
        const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
        labelTitle = refAssetGroup[assetGroupId]['assetGroupName'] + ' -> ' +
            refAssetCategory[assetCategoryId]['assetCategoryName'] + ' -> ' +
            refAssetType[_assetTypeId]['assetTypeName'];
        $('#lblPcmChecklistTitle').html(labelTitle);
        $('#divPcmChecklistSelected').show();
    };

    this.genTablePcmChecklistRefresh = function (_dataAdd) {
        const dataChecklist = mzAjaxRequest('checklist.php?assetTypeId='+assetTypeIdSelected, 'GET');
        lastChecklistUpdatedText = getNowStamp();
        oTableChecklist.clear().rows.add(dataChecklist).draw();
        refreshChecklistSummary();
    };

    this.addTablePcmChecklist = function (_dataAdd) {
        lastChecklistUpdatedText = getNowStamp();
        oTableChecklist.row.add(_dataAdd).draw();
        refreshChecklistSummary();
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
        lastChecklistUpdatedText = getNowStamp();
        oTableChecklist.row(_rowEdit).data(currentRow).draw();
        refreshChecklistSummary();
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

    this.setModalChecklistDuplicateClass = function (_modalChecklistDuplicateClass) {
        modalChecklistDuplicateClass = _modalChecklistDuplicateClass;
    };
}