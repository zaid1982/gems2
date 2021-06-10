function SectionPart () {

    const className = 'SectionPart';
    let self = this;
    let classFrom;
    let isEdit;
    let partId;
    let refStatus;
    let refSite;
    let refStore;
    let refAssetGroup;
    let refItemType;
    let refItem;
    let refUser;
    let oTableSptPending;

    this.init = function () {
        $('.sectionPart').hide();

        $('#btnSptBack').on('click', function () {
            $('.sectionPart').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        oTableSptPending = $('#dtSptPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[0, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 5] },
                { className: 'text-right', targets: [4] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskPartsTimeOrder'},
                {mData: 'woTaskNo'},
                {mData: 'woTaskPartsOrderBy', mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskPartsQuantity'},
                {mData: 'woTaskPartsStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }}
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

    this.genTablePending = function () {
        const dataDb = mzAjaxRequest2('wo_parts/list_by_pending/'+partId, 'GET');
        oTableSptPending.clear().rows.add(dataDb).draw();
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