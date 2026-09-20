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

    function rowsFromRef(ref, idKey, labelKey, predicate) {
        const rows = [];
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            if (predicate && !predicate(row)) {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''), 'en', {numeric: true});
        });
        return rows;
    }

    function fillGroupSelect(selected) {
        GemsUI.fillSelect(
            'optPcmGroupId',
            rowsFromRef(refAssetGroup, 'assetGroupId', 'assetGroupName'),
            'assetGroupId',
            function (row) { return row['assetGroupName'] || ''; },
            'All Asset Group',
            selected
        );
    }

    function fillCategorySelect(groupId, selected) {
        const rows = groupId
            ? rowsFromRef(refAssetCategory, 'assetCategoryId', 'assetCategoryName', function (row) {
                return String(row['assetGroupId']) === String(groupId);
            })
            : [];
        GemsUI.fillSelect(
            'optPcmCategoryId',
            rows,
            'assetCategoryId',
            function (row) { return row['assetCategoryName'] || ''; },
            'All Asset Category',
            selected
        );
    }

    function fillTypeSelect(categoryId, selected) {
        const rows = categoryId
            ? rowsFromRef(refAssetType, 'assetTypeId', 'assetTypeName', function (row) {
                return String(row['assetCategoryId']) === String(categoryId);
            })
            : [];
        GemsUI.fillSelect(
            'optPcmTypeId',
            rows,
            'assetTypeId',
            function (row) { return row['assetTypeName'] || ''; },
            'All Asset Type',
            selected
        );
    }

    function statusLabel(statusId, fallback) {
        if (refStatus && refStatus[statusId] && refStatus[statusId]['statusDesc']) {
            return refStatus[statusId]['statusDesc'];
        }
        switch (String(statusId)) {
            case '1':
                return 'Active';
            case '2':
                return 'Inactive';
            case '5':
                return 'Draft';
            default:
                return fallback || 'Unknown';
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

    function statusBadge(statusId, type) {
        const label = statusLabel(statusId, 'Unknown');
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
    }

    function displayText(value, type) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        return GemsUI.escape(text);
    }

    function exportPlainText(data) {
        return $('<div>').html(data).text();
    }

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
        fillGroupSelect('');
        fillCategorySelect('', '');
        fillTypeSelect('', '');

        oTableChecklistGroup =  $('#dtPcmChecklistGroup').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            "aaSorting": [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableChecklistGroup.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
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
            language: GemsUI.dtEmpty('fa-clipboard-list', 'No checklist types found.'),
            dom: GemsUI.dtDom,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'checklistType', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: null, mRender: function (data, type, row){
                            const name = row['assetGroupId'] !== '' && refAssetGroup[row['assetGroupId']]
                                ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                            return displayText(name, type);
                        }},
                    {mData: null, mRender: function (data, type, row){
                            const name = row['assetCategoryId'] !== '' && refAssetCategory[row['assetCategoryId']]
                                ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                            return displayText(name, type);
                        }},
                    {mData: null, mRender: function (data, type, row){
                            const name = row['assetTypeId'] !== '' && refAssetType[row['assetTypeId']]
                                ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                            return displayText(name, type);
                        }},
                    {mData: 'totalChecklist', sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            if (type !== 'display') {
                                return data;
                            }
                            const kind = parseInt(data, 10) === 0 ? 'danger' : 'info';
                            return '<a href="javascript:void(0)" class="lnkPcmChecklistGroupExpand" id="lnkPcmChecklistGroupTotal_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Checklist list">' + GemsUI.badge(kind, mzFormatNumber(data)) + '</a>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            return GemsUI.actionBtn({id:'lnkPcmChecklistGroupExpand_' + meta.row, cls:'lnkPcmChecklistGroupExpand', icon:'fas fa-list-ul', title:'Checklist list'});
                        }
                    },
                    {mData: 'assetGroupId', visible: false},
                    {mData: 'assetCategoryId', visible: false},
                    {mData: 'assetTypeId', visible: false}
                ]
        });
        $("#dtPcmChecklistGroup_filter").hide();
        GemsUI.bindDtTooltips('#dtPcmChecklistGroup');
        $('#txtPcmChecklistGroupSearch').on('keyup change', function () {
            oTableChecklistGroup.search($(this).val()).draw();
        });

        $('#optPcmGroupId').on('change', function () {
            fillCategorySelect($(this).val(), '');
            fillTypeSelect('', '');
            oTableChecklistGroup.column(7).search($(this).val(), false, true, false).draw();
            oTableChecklistGroup.column(8).search('', false, true, false).draw();
            oTableChecklistGroup.column(9).search('', false, true, false).draw();
        });

        $('#optPcmCategoryId').on('change', function () {
            fillTypeSelect($(this).val(), '');
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
                            return exportPlainText(data);
                        }
                        return column === 0 ? cntChecklistGroup++ : exportPlainText(data);
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
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnChecklistGroupOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnChecklistGroupOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-secondary btn-sm'
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
            language: GemsUI.dtEmpty('fa-clipboard-check', 'No checklists found for this asset type.'),
            dom: GemsUI.dtDom,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'checklistName', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: 'checklistDocumentNo', sClass: 'text-center', width: '10%', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: 'checklistIssueNo', sClass: 'text-right', width: '8%', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: 'checklistMinExecTime', sClass: 'text-center', width: '10%', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: 'checklistMaxExecTime', sClass: 'text-center', width: '10%', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: 'checklistMaxAssistant', sClass: 'text-right', width: '10%', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: 'checklistTimeRegistered', sClass: 'text-center', width: '10%', mRender: function (data, type) { return displayText(data, type); }},
                    {mData: null, sClass: 'text-center',
                        mRender: function (data, type, row) {
                            return statusBadge(row['checklistStatus'], type);
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '';
                            label += GemsUI.actionBtn({id:'lnkPcmChecklistEdit_' + meta.row, cls:'lnkPcmChecklistEdit', icon:'fas fa-edit', title:'Edit'});
                            if (row['checklistStatus'] === '1') {
                                label += GemsUI.actionBtn({id:'lnkPcmChecklistDeactivate_' + meta.row, cls:'lnkPcmChecklistDeactivate', icon:'fas fa-toggle-off', title:'Deactivate'});
                            } else if (row['checklistStatus'] === '2') {
                                label += GemsUI.actionBtn({id:'lnkPcmChecklistActivate_' + meta.row, cls:'lnkPcmChecklistActivate', icon:'fas fa-toggle-on', title:'Activate'});
                            } else if (row['checklistStatus'] === '5') {
                                label += GemsUI.actionBtn({id:'lnkPcmChecklistDelete_' + meta.row, cls:'lnkPcmChecklistDelete', icon:'fas fa-trash-alt', title:'Delete'});
                            }
                            if (row['pdfId'] != '') {
                                label += GemsUI.actionBtn({id:'lnkPcmChecklistPdf_' + meta.row, cls:'lnkPcmChecklistPdf', icon:'far fa-file-pdf', title:'Checklist PDF'});
                            }
                            label += GemsUI.actionBtn({id:'lnkPcmChecklistDuplicate_' + meta.row, cls:'lnkPcmChecklistDuplicate', icon:'far fa-copy', title:'Duplicate Checklist'});
                            return label;
                        }
                    }
                ]
        });
        $("#dtPcmChecklist_filter").hide();
        GemsUI.bindDtTooltips('#dtPcmChecklist');

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
                            return exportPlainText(data);
                        }
                        return column === 0 ? cntChecklist++ : exportPlainText(data);
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
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnChecklistOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnChecklistOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Total Checklist by Type',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-secondary btn-sm'
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
        $('#lblPcmChecklistTitle').html(GemsUI.escape(labelTitle));
        $('#divPcmChecklistSelected').show();
        oTableChecklist.columns.adjust();
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
