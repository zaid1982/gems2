function SectionPart () {

    const className = 'SectionPart';
    let self = this;
    let classFrom;
    let isEdit;
    let vData;
    let formValidate;
    let partId;
    let refStatus;
    let refSite;
    let refStore;
    let refAssetGroup;
    let refItemType;
    let refItem;
    let refUser;
    let oTableSptPartList;
    let oTableSptPending;
    let oTableSptCheckIn;
    let oTableSptCheckOut;

    this.init = function () {
        $('.sectionPart').hide();

        $('#btnSptBack').on('click', function () {
            $('.sectionPart').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });
        
        vData = [
            {
                field_id: 'txtSptThreshold',
                type: 'text',
                name: 'Default Threshold',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1
                }
            },
            {
                field_id: 'txtSptMinOrder',
                type: 'text',
                name: 'Minimum Order',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1,
                    higher: {
                        id: 'txtSptThreshold',
                        label: 'Default Threshold'
                    }
                }
            },
            {
                field_id: 'txtSptMaxOrder',
                type: 'text',
                name: 'Maximum Order',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1,
                    higher: {
                        id: 'txtSptMinOrder',
                        label: 'Minimum Order'
                    }
                }
            },
            {
                field_id: 'txaSptRemark',
                type: 'text',
                name: 'Remark',
                validator: {
                    maxLength: 1000,
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formSpt');
        formValidate.registerFields(vData);

        $('#btnSptSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            partThreshold: $('#txtSptThreshold').val(),
                            partMinOrder: $('#txtSptMinOrder').val(),
                            partMaxOrder: $('#txtSptMaxOrder').val(),
                            partRemark: $('#txaSptRemark').val()
                        };
                        mzAjaxRequest2('part/'+partId, 'PUT', data);
                        classFrom.genTable();
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnSptDisable').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('part/disable/'+partId, 'PUT');
                    mzSetFieldValue('SptStatus', refStatus[2]['statusDesc'], 'text');
                    classFrom.genTable();
                    $('#btnSptDisable').hide();
                    $('#btnSptEnable').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSptEnable').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('part/enable/'+partId, 'PUT');
                    mzSetFieldValue('SptStatus', refStatus[1]['statusDesc'], 'text');
                    classFrom.genTable();
                    $('#btnSptEnable').hide();
                    $('#btnSptDisable').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableSptPartList = $('#dtSptPartList').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 1, 2, 3, 4, 5, 7, 9, 11] },
                { className: 'text-right', targets: [6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Store Part List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Store Part List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Store Part List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Store Part List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'partSubNo'},
                {mData: 'partSubLocation'},
                {mData: 'partSubWarranty', mRender: function (data){
                        return data !== '' ? data + ' year' + (data !== '1' ? 's' : '') : '';
                    }},
                {mData: 'partSubValidity'},
                {mData: 'partSubCost'},
                {mData: 'doNo', visible: false},
                {mData: 'partSubRegisteredBy', visible: false, mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'partSubTimeRegistered'},
                {mData: 'woTaskNo', visible: false},
                {mData: 'partSubCollectedBy', visible: false, mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'partSubStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }}
            ]
        });

        oTableSptPending = $('#dtSptPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 6] },
                { className: 'text-right', targets: [4] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskRequestTimeOrdered'},
                {mData: 'woTaskNo'},
                {mData: 'woTaskRequestOrderBy', mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskPartsQuantity'},
                {mData: 'woTaskPartsRemark', visible: false},
                {mData: 'woTaskPartsStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }}
            ]
        });

        oTableSptCheckIn = $('#dtSptCheckIn').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 3, 4] },
                { className: 'text-right', targets: [5, 6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Part Check In History', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Part Check In History', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Part Check In History', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Part Check In History', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'doItemTimestamp'},
                {mData: 'doNo'},
                {mData: 'doDate'},
                {mData: 'doItemWarranty', mRender: function (data){
                        return data !== '' ? data + ' year' + (data !== '1' ? 's' : '') : '';
                    }},
                {mData: 'doItemValidity'},
                {mData: 'doItemCost'},
                {mData: 'doItemTotal'}
            ]
        });

        oTableSptCheckOut = $('#dtSptCheckOut').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2] },
                { className: 'text-right', targets: [5] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Part Check Out History', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Part Check Out History', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Part Check Out History', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Part Check Out History', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskRequestTimeCollected'},
                {mData: 'woTaskNo'},
                {mData: 'woTaskRequestOrderBy', mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskPartsRemark'},
                {mData: 'woTaskPartsQuantity'}
            ]
        });
    };

    this.load = function (_isEdit, _partId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_isEdit, _partId]);
                isEdit = _isEdit;
                partId = _partId;

                const part = mzAjaxRequest2('part/'+partId, 'GET');
                $('#lblTopTotalInStore').text(part['partCount']);
                $('#lblTopTotalILocked').text(part['partLocked']);
                $('#lblTopTotalAvailable').text(parseInt(part['partCount'])-parseInt(part['partLocked']));
                $('#txtSptDescription').text(refItem[part['itemId']]['itemDescription']);
                $('#txtSptItemType').text(refItemType[part['itemTypeId']]['itemTypeDesc']);
                mzSetFieldValue('SptAssetGroup', refAssetGroup[part['assetGroupId']]['assetGroupName'], 'text');
                mzSetFieldValue('SptSite', refSite[part['siteId']]['siteName'], 'text');
                mzSetFieldValue('SptStore', refStore[part['storeId']]['storeName'], 'text');
                mzSetFieldValue('SptThreshold', part['partThreshold'], 'text');
                mzSetFieldValue('SptMinOrder', part['partMinOrder'], 'text');
                mzSetFieldValue('SptMaxOrder', part['partMaxOrder'], 'text');
                mzSetFieldValue('SptRemark', part['partRemark'], 'textarea');
                mzSetFieldValue('SptStatus', refStatus[part['partStatus']]['statusDesc'], 'text');
                self.setEditable(part['partStatus']);

                const picture = mzAjaxRequest2('item_image/'+part['itemId'], 'GET');
                $('#divSptImages').html('');
                for (let i=0; i<picture.length; i++) {
                    let dataSize = '1600x1067';
                    if (picture[i]['uploadFileWidth'] !== '' && picture[i]['uploadFileHeight'] !== '') {
                        dataSize = picture[i]['uploadFileWidth'] + 'x' + picture[i]['uploadFileHeight'];
                    }
                    const classHide = i > 0 ? 'divSptImageHide' : '';
                    $('#divSptImages').append('<figure class="col-md-12 text-center '+classHide+'">\n' +
                        '<a href="'+mzUrlDownload+picture[i]['uploadFolder']+'/'+picture[i]['uploadFilename']+'.'+picture[i]['uploadExtension']+'" data-size="'+dataSize+'">\n' +
                        '<img src="'+mzUrlDownload+picture[i]['uploadFolder']+'/'+picture[i]['uploadFilename']+'.'+picture[i]['uploadExtension']+'" class="img-fluid" width="100%">\n' +
                        '</a>\n' +
                        '<p class="mb-0 font-small divSptImageHide">'+picture[i]['uploadName']+'</p>\n' +
                        '</figure>');
                }
                $('.divSptImageHide').hide();

                self.genTablePending();
                self.genTablePartList();
                self.genTableCheckIn();
                self.genTableCheckOut();

                $('.sectionPart').show();
                classFrom.hideMain();
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.setEditable = function (_partStatus) {
        $('#btnSptDisable, #btnSptEnable, #btnSptDelete, #btnSptSave, #btnSptTransfer').hide();
        if (isEdit) {
            if (_partStatus === '1') {
                $('#btnSptDisable').show();
            } else {
                $('#btnSptEnable').show();
            }
            $('#btnSptSave, #btnSptTransfer').show();
            $('#txtSptThresholdPre').addClass('text-dark');
            $('#txtSptMinOrderPre').addClass('text-dark');
            $('#txtSptMaxOrderPre').addClass('text-dark');
            $('#txaSptRemarkPre').addClass('text-dark');
        } else {
            $('#txtSptThresholdPre').removeClass('text-dark');
            $('#txtSptMinOrderPre').removeClass('text-dark');
            $('#txtSptMaxOrderPre').removeClass('text-dark');
            $('#txaSptRemarkPre').removeClass('text-dark');
        }
    };

    this.genTablePartList = function () {
        const dataDb = mzAjaxRequest2('part_sub/list_available/'+partId, 'GET');
        oTableSptPartList.clear().rows.add(dataDb).draw();
    };

    this.genTablePending = function () {
        const dataDb = mzAjaxRequest2('wo_parts/list_by_pending/'+partId, 'GET');
        oTableSptPending.clear().rows.add(dataDb).draw();
    };

    this.genTableCheckIn = function () {
        const dataDb = mzAjaxRequest2('do_item/'+partId, 'GET');
        oTableSptCheckIn.clear().rows.add(dataDb).draw();
    };

    this.genTableCheckOut = function () {
        const dataDb = mzAjaxRequest2('wo_parts/list_check_out/'+partId, 'GET');
        oTableSptCheckOut.clear().rows.add(dataDb).draw();
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

    this.setRefItemType = function (_refItemType) {
        refItemType = _refItemType;
    };

    this.setRefItem = function (_refItem) {
        refItem = _refItem;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStore = function (_refStore) {
        refStore = _refStore;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}