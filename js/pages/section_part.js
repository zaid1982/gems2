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
    let storeId;

    this.init = function () {
        $('.sectionPart').hide();

        $('#btnSptBack').on('click', function () {
            $('.sectionPart').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        initPhotoSwipeFromDOM('.mdb-lightbox');
    };

    this.load = function (_isEdit, _partId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_isEdit, _partId]);
                isEdit = _isEdit;
                partId = _partId;

                const part = mzAjaxRequest2('part/'+partId, 'GET');
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
}