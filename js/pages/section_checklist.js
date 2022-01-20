function SectionChecklist() {

    const className = 'SectionChecklist';
    let self = this;
    let modalConfirmDeleteClass;
    let checklistId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refUser;
    let refFrequency;
    let formValidate;
    let oTableChecklistQual;
    let modalChecklistQualClass;
    let oTableChecklistQuan;
    let modalChecklistQuanClass;
    let checklistStatus;

    this.init = function () {
        $('.sectionChecklist').hide();

        $('#btnSckBack').on('click', function () {
            $('.sectionChecklist').hide();
            if (classFrom.getClassName() === 'MainChecklist') {
                $('.sectionPcmMain').show();
            }
            $(window).scrollTop(0);
        });

        const vData = [
            {
                field_id: 'txtSckChecklistName',
                type: 'text',
                name: 'Checklist Name',
                validator: {
                    notEmpty: true,
                    maxLength: 255
                }
            },
            {
                field_id: 'txtSckChecklistDocumentNo',
                type: 'text',
                name: 'Document No',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSckChecklistIssueNo',
                type: 'text',
                name: 'Issue No',
                validator: {
                    notEmpty: true,
                    maxLength: 10
                }
            },
            {
                field_id: 'txaSckChecklistDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 1000
                }
            },
            {
                field_id: 'txaSckChecklistGuideline',
                type: 'text',
                name: 'General Guidelines',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtSckAssetGroupName',
                type: 'text',
                name: 'Asset Group',
                validator: {}
            },
            {
                field_id: 'txtSckAssetCategoryName',
                type: 'text',
                name: 'Asset Category',
                validator: {}
            },
            {
                field_id: 'txtSckAssetTypeName',
                type: 'text',
                name: 'Asset Type',
                validator: {}
            },
            {
                field_id: 'txtSckChecklistMinExecTimeHour',
                type: 'text',
                name: 'Min Exec Time (Hour)',
                validator: {
                    notEmpty: true,
                    digit: true,
                    min: 0,
                    max: 838,
                    lower: {
                        id: 'txtSckChecklistMinExecTimeHour',
                        label: 'Max Exec Time (Hour)'
                    }
                }
            },
            {
                field_id: 'txtSckChecklistMinExecTimeMinute',
                type: 'text',
                name: 'Minimum Execution Time (Minute)',
                validator: {
                    notEmpty: true,
                    digit: true,
                    min: 0,
                    max: 60
                }
            },
            {
                field_id: 'txtSckChecklistMaxExecTimeHour',
                type: 'text',
                name: 'Maximum Execution Time (Hour)',
                validator: {
                    notEmpty: true,
                    digit: true,
                    min: 0,
                    max: 838,
                    higher: {
                        id: 'txtSckChecklistMinExecTimeHour',
                        label: 'Minimum Execution Time (Hour)'
                    }
                }
            },
            {
                field_id: 'txtSckChecklistMaxExecTimeMinute',
                type: 'text',
                name: 'Maximum Execution Time (Minute)',
                validator: {
                    notEmpty: true,
                    digit: true,
                    min: 0,
                    max: 60
                }
            },
            {
                field_id: 'txtSckChecklistMaxAssistant',
                type: 'text',
                name: 'Maximum Total Assistant',
                validator: {
                    notEmpty: true,
                    digit: true,
                    min: 0,
                    max: 5
                }
            }
        ];

        formValidate = new MzValidate('formSck');
        formValidate.registerFields(vData);

        $('#formSck').on('keyup change', function () {
            $('#btnSckSubmit, #btnSckUpdate').attr('disabled', !formValidate.validateForm());
        });

        oTableChecklistQual = $('#dtSckChecklistQual').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo : false,
            ordering: false,
            //aaSorting: [0, 'asc'],
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSckChecklistQualEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalChecklistQualClass.edit(currentRow['checklistQualId'], rowId);
                    }
                });
                $('.lnkSckChecklistQualDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalChecklistQualClass.deactivate(currentRow['checklistQualId'], rowId);
                    }
                });
                $('.lnkSckChecklistQualActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalChecklistQualClass.activate(currentRow['checklistQualId'], rowId);
                    }
                });
                $('.lnkSckChecklistQualDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['checklistQualId'], modalChecklistQualClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'checklistQualNumb', bSortable: false},
                    {mData: 'checklistQualDesc', bSortable: false},
                    {mData: 'frequencyId', bSortable: false, mRender: function (data){
                            return data !== '' ? refFrequency[data]['frequencyName'] : '';
                        }},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['checklistQualStatus']]['statusColor']+' z-depth-2">'+refStatus[row['checklistQualStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkSckChecklistQualEdit" id="lnkSckChecklistQualEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['checklistQualStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkSckChecklistQualDeactivate" id="lnkSckChecklistQualDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkSckChecklistQualActivate" id="lnkSckChecklistQualActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkSckChecklistQualDelete" id="lnkSckChecklistQualDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtSckChecklistQual_filter").hide();

        let btnChecklistQualOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (column === 3) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableChecklistQual, {
            buttons: [
                $.extend( true, {}, btnChecklistQualOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Qualitative Task List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistQualOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Qualitative Task List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistQualOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Qualitative Task List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSckChecklistQualExport'));

        $('#btnSckChecklistQualAdd').on('click', function () {
            modalChecklistQualClass.add(checklistId);
        });

        $('#btnDtSckChecklistQualRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSckChecklistQual();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableChecklistQuan =  $('#dtSckChecklistQuan').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo : false,
            //aaSorting: [0, 'asc'],
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSckChecklistQuanEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalChecklistQuanClass.edit(currentRow['checklistQuanId'], rowId);
                    }
                });
                $('.lnkSckChecklistQuanDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalChecklistQuanClass.deactivate(currentRow['checklistQuanId'], rowId);
                    }
                });
                $('.lnkSckChecklistQuanActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalChecklistQuanClass.activate(currentRow['checklistQuanId'], rowId);
                    }
                });
                $('.lnkSckChecklistQuanDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['checklistQuanId'], modalChecklistQuanClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: 'checklistQuanNumb', bSortable: false},
                    {mData: 'checklistQuanDesc', bSortable: false},
                    {mData: 'checklistQuanUnit', bSortable: false},
                    {mData: 'checklistQuanSetValues', bSortable: false},
                    {mData: 'frequencyId', bSortable: false, mRender: function (data){
                            return data !== '' ? refFrequency[data]['frequencyName'] : '';
                        }},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['checklistQuanStatus']]['statusColor']+' z-depth-2">'+refStatus[row['checklistQuanStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkSckChecklistQuanEdit" id="lnkSckChecklistQuanEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            if (row['checklistQuanStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkSckChecklistQuanDeactivate" id="lnkSckChecklistQuanDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkSckChecklistQuanActivate" id="lnkSckChecklistQuanActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
                            label += '<a><i class="fas fa-trash-alt lnkSckChecklistQuanDelete" id="lnkSckChecklistQuanDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtSckChecklistQuan_filter").hide();

        let btnChecklistQuanOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5],
                format: {
                    body: function ( data, row, column ) {
                        if (column === 5) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableChecklistQuan, {
            buttons: [
                $.extend( true, {}, btnChecklistQuanOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Quantitative Task List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistQuanOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Quantitative Task List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistQuanOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Quantitative Task List',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSckChecklistQuanExport'));

        $('#btnSckChecklistQuanAdd').on('click', function () {
            modalChecklistQuanClass.add(checklistId);
        });

        $('#btnDtSckChecklistQuanRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSckChecklistQuan();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSckSave').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const data = {
                        action: 'save',
                        checklistName: $('#txtSckChecklistName').val(),
                        checklistDocumentNo: $('#txtSckChecklistDocumentNo').val(),
                        checklistIssueNo: $('#txtSckChecklistIssueNo').val(),
                        checklistDesc: $('#txaSckChecklistDesc').val(),
                        checklistGuideline: $('#txaSckChecklistGuideline').val(),
                        checklistMinExecTimeHour: $('#txtSckChecklistMinExecTimeHour').val(),
                        checklistMinExecTimeMinute: $('#txtSckChecklistMinExecTimeMinute').val(),
                        checklistMaxExecTimeHour: $('#txtSckChecklistMaxExecTimeHour').val(),
                        checklistMaxExecTimeMinute: $('#txtSckChecklistMaxExecTimeMinute').val(),
                        checklistMaxAssistant: $('#txtSckChecklistMaxAssistant').val()
                    };

                    mzAjaxRequest('checklist.php?checklistId='+checklistId, 'PUT', data);
                    if (classFrom.getClassName() === 'MainChecklist') {
                        classFrom.updateTablePcmChecklist(data, rowRefresh);
                        $('.sectionPcmMain').show();
                    }
                    $('.sectionChecklist').hide();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSckSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    } else if (oTableChecklistQual.data().length === 0) {
                        toastr['error']('Please make sure Qualitative Task not empty', _ALERT_TITLE_ERROR);
                    } else if ($('#txtSckChecklistMinExecTimeHour').val() > $('#txtSckChecklistMaxExecTimeHour').val() ||
                        ($('#txtSckChecklistMinExecTimeHour').val() === $('#txtSckChecklistMaxExecTimeHour').val() && $('#txtSckChecklistMaxExecTimeMinute').val() < $('#txtSckChecklistMinExecTimeMinute').val())) {
                        toastr['error']('Please make sure Maximum Execution Time higher than Minimum Execution Time', _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            action: 'submit',
                            checklistName: $('#txtSckChecklistName').val(),
                            checklistDocumentNo: $('#txtSckChecklistDocumentNo').val(),
                            checklistIssueNo: $('#txtSckChecklistIssueNo').val(),
                            checklistDesc: $('#txaSckChecklistDesc').val(),
                            checklistGuideline: $('#txaSckChecklistGuideline').val(),
                            checklistMinExecTimeHour: $('#txtSckChecklistMinExecTimeHour').val(),
                            checklistMinExecTimeMinute: $('#txtSckChecklistMinExecTimeMinute').val(),
                            checklistMaxExecTimeHour: $('#txtSckChecklistMaxExecTimeHour').val(),
                            checklistMaxExecTimeMinute: $('#txtSckChecklistMaxExecTimeMinute').val(),
                            checklistMaxAssistant: $('#txtSckChecklistMaxAssistant').val()
                        };

                        mzAjaxRequest('checklist.php?checklistId='+checklistId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainChecklist') {
                            classFrom.updateTablePcmChecklist(data, rowRefresh);
                            $('.sectionPcmMain').show();
                        }
                        $('.sectionChecklist').hide();
                        $(window).scrollTop(0);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSckUpdate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    } else if (oTableChecklistQual.data().length === 0) {
                        toastr['error']('Please make sure Qualitative Task not empty', _ALERT_TITLE_ERROR);
                    } else if ($('#txtSckChecklistMinExecTimeHour').val() > $('#txtSckChecklistMaxExecTimeHour').val() ||
                        ($('#txtSckChecklistMinExecTimeHour').val() === $('#txtSckChecklistMaxExecTimeHour').val() && $('#txtSckChecklistMaxExecTimeMinute').val() < $('#txtSckChecklistMinExecTimeMinute').val())) {
                        toastr['error']('Please make sure Maximum Execution Time higher than Minimum Execution Time', _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            action: 'update',
                            checklistName: $('#txtSckChecklistName').val(),
                            checklistDocumentNo: $('#txtSckChecklistDocumentNo').val(),
                            checklistIssueNo: $('#txtSckChecklistIssueNo').val(),
                            checklistDesc: $('#txaSckChecklistDesc').val(),
                            checklistGuideline: $('#txaSckChecklistGuideline').val(),
                            checklistMinExecTimeHour: $('#txtSckChecklistMinExecTimeHour').val(),
                            checklistMinExecTimeMinute: $('#txtSckChecklistMinExecTimeMinute').val(),
                            checklistMaxExecTimeHour: $('#txtSckChecklistMaxExecTimeHour').val(),
                            checklistMaxExecTimeMinute: $('#txtSckChecklistMaxExecTimeMinute').val(),
                            checklistMaxAssistant: $('#txtSckChecklistMaxAssistant').val()
                        };

                        mzAjaxRequest('checklist.php?checklistId='+checklistId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainChecklist') {
                            classFrom.updateTablePcmChecklist(data, rowRefresh);
                            $('.sectionPcmMain').show();
                        }
                        $('.sectionChecklist').hide();
                        $(window).scrollTop(0);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#btnSckPdf').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const data = {
                        action: 'pdf',
                        checklistName: $('#txtSckChecklistName').val(),
                        checklistDocumentNo: $('#txtSckChecklistDocumentNo').val(),
                        checklistIssueNo: $('#txtSckChecklistIssueNo').val(),
                        checklistDesc: $('#txaSckChecklistDesc').val(),
                        checklistGuideline: $('#txaSckChecklistGuideline').val(),
                        checklistMinExecTimeHour: $('#txtSckChecklistMinExecTimeHour').val(),
                        checklistMinExecTimeMinute: $('#txtSckChecklistMinExecTimeMinute').val(),
                        checklistMaxExecTimeHour: $('#txtSckChecklistMaxExecTimeHour').val(),
                        checklistMaxExecTimeMinute: $('#txtSckChecklistMaxExecTimeMinute').val(),
                        checklistMaxAssistant: $('#txtSckChecklistMaxAssistant').val(),
                        checklistStatus: checklistStatus
                    };
                    const pdfId = mzAjaxRequest('checklist.php?checklistId='+checklistId, 'PUT', data);
                    const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                    $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Checklist: '+$('#txtSckChecklistName').val(),);
                    $('#mpdf_iframe').attr('src', pdfSrc);
                    $('#modal_pdf').modal('show');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.genTableSckChecklistQual = function () {
        const dataChecklistQual = mzAjaxRequest('checklist_qual.php?checklistId='+checklistId, 'GET');
        oTableChecklistQual.clear().rows.add(dataChecklistQual).draw();
    };

    this.genTableSckChecklistQuan = function () {
        const dataChecklistQuan = mzAjaxRequest('checklist_quan.php?checklistId='+checklistId, 'GET');
        oTableChecklistQuan.clear().rows.add(dataChecklistQuan).draw();
    };

    this.getDetails = function () {
        const dataSck = mzAjaxRequest('checklist.php?checklistId='+checklistId, 'GET');
        const registeredBy = dataSck['checklistRegisteredBy']!==''?refUser[dataSck['checklistRegisteredBy']]['userFullName']:'';
        const assetTypeId = dataSck['assetTypeId'];
        const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
        const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
        const checklistStatus = dataSck['checklistStatus'];

        mzSetFieldValue('SckChecklistName', dataSck['checklistName'], 'text');
        mzSetFieldValue('SckChecklistDocumentNo', dataSck['checklistDocumentNo'], 'text');
        mzSetFieldValue('SckChecklistIssueNo', dataSck['checklistIssueNo'], 'text');
        mzSetFieldValue('SckChecklistDesc', dataSck['checklistDesc'], 'textarea');
        mzSetFieldValue('SckChecklistGuideline', dataSck['checklistGuideline'], 'textarea');
        mzSetFieldValue('SckAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
        mzSetFieldValue('SckAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
        mzSetFieldValue('SckAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
        mzSetFieldValue('SckChecklistRegisteredBy', registeredBy, 'text');
        mzSetFieldValue('SckChecklistTimeRegistered', mzConvertDateDisplay(dataSck['checklistTimeRegistered']), 'text');
        mzSetFieldValue('SckChecklistStatus', refStatus[checklistStatus]['statusDesc'], 'text');
        const minExecTime = dataSck['checklistMinExecTime'];
        mzSetFieldValue('SckChecklistMinExecTimeHour', minExecTime !== '' ? parseInt(minExecTime.substr(0, 2)) : '0', 'text');
        mzSetFieldValue('SckChecklistMinExecTimeMinute', minExecTime !== '' ? parseInt(minExecTime.substr(3, 2)) : '0', 'text');
        const maxExecTime = dataSck['checklistMaxExecTime'];
        mzSetFieldValue('SckChecklistMaxExecTimeHour', maxExecTime !== '' ? parseInt(maxExecTime.substr(0, 2)) : '0', 'text');
        mzSetFieldValue('SckChecklistMaxExecTimeMinute', maxExecTime !== '' ? parseInt(maxExecTime.substr(3, 2)) : '0', 'text');
        mzSetFieldValue('SckChecklistMaxAssistant', dataSck['checklistMaxAssistant'], 'text');

        self.genTableSckChecklistQual();
        self.genTableSckChecklistQuan();

        return checklistStatus;
    };

    this.add = function (_assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetTypeId]);

                checklistId = mzAjaxRequest('checklist.php', 'POST', {assetTypeId: _assetTypeId});
                const tempRow = {
                    checklistId: checklistId,
                    checklistName: '',
                    checklistDocumentNo: '',
                    checklistIssueNo: '',
                    checklistMinExecTime: '',
                    checklistMaxExecTime: '',
                    checklistMaxAssistant: '',
                    checklistTimeRegistered: '',
                    assetTypeId: _assetTypeId,
                    checklistStatus: '5'
                };
                rowRefresh = classFrom.addTablePcmChecklist(tempRow);

                formValidate.clearValidation();
                checklistStatus = self.getDetails();
                $('#txtSckChecklistName, #txtSckChecklistDocumentNo, #txtSckChecklistIssueNo, #txaSckChecklistDesc').prop('disabled', false);
                $('#divSckChecklistRegisteredBy, #divSckChecklistTimeRegistered').hide();

                $('#btnSckUpdate').hide();
                $('.sectionChecklist, #btnSckSubmit, #btnSckSave').show();
                $('#btnSckSubmit').prop('disabled', true);

                if (classFrom.getClassName() === 'MainChecklist') {
                    $('.sectionPcmMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_checklistId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistId, _rowRefresh]);
                checklistId = _checklistId;
                rowRefresh = _rowRefresh;

                formValidate.clearValidation();
                checklistStatus = self.getDetails();
                if (checklistStatus === '5') {
                    $('#btnSckUpdate, #divSckChecklistRegisteredBy, #divSckChecklistTimeRegistered').hide();
                    $('#btnSckSubmit, #btnSckSave').show();
                    $('#btnSckSubmit').prop('disabled', !formValidate.validateForm());
                } else {
                    $('#btnSckSubmit, #btnSckSave').hide();
                    $('#btnSckUpdate, #divSckChecklistRegisteredBy, #divSckChecklistTimeRegistered').show();
                    $('#btnSckUpdate').prop('disabled', true);
                }

                $('#txtSckChecklistName, #txtSckChecklistDocumentNo, #txtSckChecklistIssueNo, #txaSckChecklistDesc').prop('disabled', false);
                $('#btnSckUpdate').prop('disabled', true);
                $('.sectionChecklist').show();

                if (classFrom.getClassName() === 'MainChecklist') {
                    $('.sectionPcmMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_checklistId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistId, _rowRefresh]);
                mzAjaxRequest('checklist.php?checklistId='+_checklistId, 'PUT', {action: 'deactivate'});
                const tempRow = {checklistStatus:'2'};
                if (classFrom.getClassName() === 'MainChecklist') {
                    classFrom.updateTablePcmChecklist(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_checklistId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistId, _rowRefresh]);
                mzAjaxRequest('checklist.php?checklistId='+_checklistId, 'PUT', {action: 'activate'});
                const tempRow = {checklistStatus:'1'};
                if (classFrom.getClassName() === 'MainChecklist') {
                    classFrom.updateTablePcmChecklist(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_checklistId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_checklistId]);
                mzAjaxRequest('checklist.php?checklistId='+_checklistId, 'DELETE');
                if (classFrom.getClassName() === 'MainChecklist') {
                    classFrom.deleteTablePcmChecklist();
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.addTableSckChecklistQual = function (_dataAdd) {
        oTableChecklistQual.row.add(_dataAdd).draw();
    };

    this.updateTableSckChecklistQual = function (_dataEdit, _rowEdit) {
        const currentRow = oTableChecklistQual.row(_rowEdit).data();
        if (typeof _dataEdit['action'] !== 'undefined') {
            currentRow['checklistQualNumb'] = _dataEdit['checklistQualNumb'];
            currentRow['checklistQualDesc'] = _dataEdit['checklistQualDesc'];
            currentRow['frequencyId'] = _dataEdit['frequencyId'];
        }
        if (typeof _dataEdit['checklistQualStatus'] !== 'undefined') {
            currentRow['checklistQualStatus'] = _dataEdit['checklistQualStatus'];
        }
        oTableChecklistQual.row(_rowEdit).data(currentRow).draw();
    };

    this.addTableSckChecklistQuan = function (_dataAdd) {
        oTableChecklistQuan.row.add(_dataAdd).draw();
    };

    this.updateTableSckChecklistQuan = function (_dataEdit, _rowEdit) {
        const currentRow = oTableChecklistQuan.row(_rowEdit).data();
        if (typeof _dataEdit['action'] !== 'undefined') {
            currentRow['checklistQuanNumb'] = _dataEdit['checklistQuanNumb'];
            currentRow['checklistQuanDesc'] = _dataEdit['checklistQuanDesc'];
            currentRow['checklistQuanUnit'] = _dataEdit['checklistQuanUnit'];
            currentRow['checklistQuanSetValues'] = _dataEdit['checklistQuanSetValues'];
            currentRow['frequencyId'] = _dataEdit['frequencyId'];
        }
        if (typeof _dataEdit['checklistQuanStatus'] !== 'undefined') {
            currentRow['checklistQuanStatus'] = _dataEdit['checklistQuanStatus'];
        }
        oTableChecklistQuan.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
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

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefFrequency = function (_refFrequency) {
        refFrequency = _refFrequency;
    };

    this.setModalChecklistQualClass = function (_modalChecklistQualClass) {
        modalChecklistQualClass = _modalChecklistQualClass;
    };

    this.setModalChecklistQuanClass = function (_modalChecklistQuanClass) {
        modalChecklistQuanClass = _modalChecklistQuanClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}