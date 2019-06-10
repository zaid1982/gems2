function SectionChecklist() {

    const className = 'SectionAssetDetails';
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

    this.init = function () {
        $('.sectionChecklist').hide();

        $('#btnSckBack').on('click', function () {
            $('.sectionChecklist').hide();
            $('.sectionPcmMain').show();
            $(window).scrollTop(0);
        });

        const vData = [
            {
                field_id: 'txtSckChecklistName',
                type: 'text',
                name: 'Checklist Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtSckChecklistVersion',
                type: 'text',
                name: 'Checklist Version',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txaSckChecklistDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'txaSckChecklistGuideline',
                type: 'summernote',
                name: 'General Guidelines',
                validator: {
                    notEmptySummernote: true
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
            }
        ];

        formValidate = new MzValidate('formSck');
        formValidate.registerFields(vData);

        $('#formSck').on('keyup change', function () {
            $('#btnSckSubmit, #btnSckUpdate').attr('disabled', !formValidate.validateForm());
        });

        $('#txaSckChecklistGuideline').summernote({
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']]
            ],
            height: 240,
            disableDragAndDrop: true,
            callbacks: {
                onKeyup: function(e) {
                    formValidate.validateSummernote();
                }
            }
        });

        oTableChecklistQual =  $('#dtSckChecklistQual').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo : false,
            aaSorting: [0, 'asc'],
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSckChecklistQualEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalChecklistQualClass.edit(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkSckChecklistQualDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalChecklistQualClass.deactivate(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkSckChecklistQualActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQual.row(parseInt(rowId)).data();
                        modalChecklistQualClass.activate(currentRow['checklistId'], rowId);
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
            aaSorting: [0, 'asc'],
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkSckChecklistQuanEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalChecklistQuanClass.edit(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkSckChecklistQuanDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalChecklistQuanClass.deactivate(currentRow['checklistId'], rowId);
                    }
                });
                $('.lnkSckChecklistQuanActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableChecklistQuan.row(parseInt(rowId)).data();
                        modalChecklistQuanClass.activate(currentRow['checklistId'], rowId);
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
                        checklistVersion: $('#txtSckChecklistVersion').val(),
                        checklistDesc: $('#txaSckChecklistDesc').val(),
                        checklistGuideline: $('#txaSckChecklistGuideline').summernote('code'),
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
                    }
                    else {
                        console.log($('#txaSckChecklistGuideline').summernote('code'));
                        console.log($('#txaSckChecklistGuideline').summernote('isEmpty'));
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
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
        mzSetFieldValue('SckChecklistVersion', dataSck['checklistVersion'], 'text');
        mzSetFieldValue('SckChecklistDesc', dataSck['checklistDesc'], 'textarea');
        mzSetFieldValue('SckChecklistGuideline', dataSck['checklistGuideline'], 'summernote');
        mzSetFieldValue('SckAssetTypeName', refAssetType[assetTypeId]['assetTypeName'], 'text');
        mzSetFieldValue('SckAssetCategoryName', refAssetCategory[assetCategoryId]['assetCategoryName'], 'text');
        mzSetFieldValue('SckAssetGroupName', refAssetGroup[assetGroupId]['assetGroupName'], 'text');
        mzSetFieldValue('SckChecklistRegisteredBy', registeredBy, 'text');
        mzSetFieldValue('SckChecklistTimeRegistered', mzConvertDateDisplay(dataSck['checklistTimeRegistered']), 'text');
        mzSetFieldValue('SckChecklistStatus', refStatus[checklistStatus]['statusDesc'], 'text');

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
                    checklistVersion: '',
                    checklistTimeRegistered: '',
                    assetTypeId: _assetTypeId,
                    checklistStatus: '5'
                };
                rowRefresh = classFrom.addTablePcmChecklist(tempRow);

                formValidate.clearValidation();
                self.getDetails();
                $('#txtSckChecklistName, #txtSckChecklistVersion, #txaSckChecklistDesc').prop('disabled', false);
                $('#txaSckChecklistGuideline').summernote('enable');
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
                const checklistStatus = self.getDetails();
                if (checklistStatus === '5') {
                    $('#btnSckUpdate, #divSckChecklistRegisteredBy, #divSckChecklistTimeRegistered').hide();
                    $('#btnSckSubmit, #btnSckSave').show();
                    $('#btnSckSubmit').prop('disabled', true);
                } else {
                    $('#btnSckSubmit, #btnSckSave').hide();
                    $('#btnSckUpdate, #divSckChecklistRegisteredBy, #divSckChecklistTimeRegistered').show();
                    $('#btnSckSubmit').prop('disabled', true);
                }

                $('#txtSckChecklistName, #txtSckChecklistVersion, #txaSckChecklistDesc').prop('disabled', false);
                $('#txaSckChecklistGuideline').summernote('enable');
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