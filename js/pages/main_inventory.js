function MainInventory () {

    const className = 'MainInventory';
    let self = this;
    let oTableInv;
    let refStatus;
    let refClient;
    let refSite;
    let refStore;
    let refAssetGroup;
    let refItemType;
    let refItem;
    let storeId;
    let sectionPartClass;

    this.init = function () {
        $('#sectionInvResult').hide();

        if (mzIsRoleExist('1')) {
            mzOption('optInvClient', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');
            $('#optInvClient').on('change', function () {
                mzOptionStop('optInvSite', refSite, 'Choose Site', 'siteId', 'siteName', {siteStatus: '1', clientId: $(this).val()}, 'required');
            });
            $('#optInvSite').on('change', function () {
                mzOptionStop('optInvStore', refStore, 'Choose Store', 'storeId', 'storeName', {storeStatus: '1', siteId: $(this).val()}, 'required');
            });
        }
        else {
            const siteIdUser = mzGetUserInfoByParam('siteId');
            const clientIdUser = refSite[siteIdUser]['clientId'];
            mzOption('optInvClient', refClient, 'Choose Client', 'clientId', 'clientName', {clientId: clientIdUser, clientStatus: '1'}, 'required');
            mzOption('optInvSite', refSite, 'Choose Site', 'siteId', 'siteName', {siteId: siteIdUser, siteStatus: '1'}, 'required');
            mzOption('optInvStore', refStore, 'Choose Store', 'storeId', 'storeName', {siteId: siteIdUser, storeStatus: '1'}, 'required');
            mzSetFieldValue('InvClient', clientIdUser, 'select', '', true);
            mzSetFieldValue('InvSite', siteIdUser, 'select', '', true);
        }

        const vData = [
            {
                field_id: 'optInvClient',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optInvSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optInvStore',
                type: 'select',
                name: 'Store',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formInvSearch');
        formValidate.registerFields(vData);

        $('#btnInvSearch').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        storeId = $('#optInvStore').val();
                        self.genTable();
                        $('#sectionInvResult').show();
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        let exportOpt = Object.assign({}, mzExportOpt);
        exportOpt['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

        oTableInv = $('#dtInvData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [12, 'asc'], [13, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                initPhotoSwipeFromDOM('.mdb-lightbox');
                $('.divInvImageHide').hide();
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 14] },
                { className: 'text-center', targets: [0, 11, 14] },
                { className: 'text-right', targets: [4, 5, 6, 7, 8, 9] },
                { className: 'noVis', targets: [0, 12, 13, 14] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Inventory Part List', titleAttr: 'Print', exportOptions: exportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Inventory Part List', titleAttr: 'Copy', exportOptions: exportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Inventory Part List', titleAttr: 'Excel', exportOptions: exportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Inventory Part List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'assetGroupId', mRender: function (data){
                        return data !== '' ? refAssetGroup[data]['assetGroupName'] : '';
                    }},
                {mData: 'itemTypeId', mRender: function (data){
                        return data !== '' ? refItemType[data]['itemTypeDesc'] : '';
                    }},
                {mData: 'itemId', mRender: function (data){
                        return data !== '' ? refItem[data]['itemDescription'] : '';
                    }},
                {mData: 'partCount', mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'partLocked', mRender: function (data){ return mzFormatNumber(data)}},
                {mData: null, mRender: function (data, type, row){ return mzFormatNumber(parseInt(row['partCount']) - parseInt(row['partLocked']))}},
                {mData: 'partThreshold', mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'partMinOrder', visible: false, mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'partMaxOrder', visible: false, mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'partRemark', visible: false},
                {mData: 'partStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }},
                {mData: null, visible: false, mRender: function (data, type, row){
                        const itemTypeId = row['itemTypeId'];
                        return itemTypeId !== '' ? refItemType[itemTypeId]['itemTypeTurn'] : '';
                    }},
                {mData: null, visible: false, mRender: function (data, type, row){
                        const itemId = row['itemId'];
                        return itemId !== '' ? refItem[itemId]['itemTurn'] : '';
                    }},
                {mData: 'uploadList', width: '125px',
                    mRender: function (data, type, row) {
                        let label = '';
                        if (data !== '') {
                            const pictureUrls = data.split('||');
                            const pictureTitles = (row['titleList']).split('||');
                            const pictureWidth = (row['widthList']).split('||');
                            const pictureHeight = (row['heightList']).split('||');
                            label += '<div class="mdb-lightbox no-margin">\n';
                            for (let i=0; i<pictureUrls.length; i++) {
                                let dataSize = '1600x1067';
                                if (pictureWidth[i] !== '' && pictureHeight[i] !== '') {
                                    dataSize = pictureWidth[i] + 'x' + pictureHeight[i];
                                }
                                if (i > 0) {
                                    label += '<figure class="col-md-12 divInvImageHide">\n';
                                } else {
                                    label += '<figure class="col-md-12">\n';
                                }
                                label += '<a href="'+mzUrlDownload+pictureUrls[i]+'" data-size="'+dataSize+'">\n' +
                                    '<img src="'+mzUrlDownload+pictureUrls[i]+'" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                    '</a>\n' +
                                    '<p class="mb-0 font-small divInvImageHide">'+pictureTitles[i]+'</p>\n' +
                                    '</figure>\n';
                            }
                            label += '</div>\n';
                        }
                        return label;
                    }}
            ]
        });
        let oTableInvTbody = $('#dtInvData tbody');
        oTableInvTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtInvData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            const screenWidth = $('.sectionInvMain').width();
            if ((screenWidth >= 920 && cell.index() < 9) || (screenWidth >= 620 && screenWidth < 920 && cell.index() < 6) || (screenWidth < 620 && cell.index() < 3)) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        sectionPartClass.load(true, data['partId']);
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
        oTableInvTbody.delegate('tr', 'mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            const screenWidth = $('.sectionInvMain').width();
            if ((screenWidth >= 920 && cell.index() < 9) || (screenWidth >= 620 && screenWidth < 920 && cell.index() < 7) || (screenWidth < 620 && cell.index() < 3)) {
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to see details');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        if ($('.sectionInvMain').width() < 920) {
            oTableInv.column(6).visible(false);
            oTableInv.column(7).visible(false);
        }
        if ($('.sectionInvMain').width() < 620) {
            oTableInv.column(1).visible(false);
            oTableInv.column(2).visible(false);
            oTableInv.column(4).visible(false);
            oTableInv.column(11).visible(false);
        }

        $('#btnDtInvDataRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnInvDataAdd').on('click', function () {
            sectionItemClass.add();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('part/list_with_image/'+storeId, 'GET');
        oTableInv.clear().rows.add(dataDb).draw();
    };

    this.showMain = function () {
        $('.sectionInvMain').show();
    };

    this.hideMain = function () {
        $('.sectionInvMain').hide();
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

    this.setRefItemType = function (_refItemType) {
        refItemType = _refItemType;
    };

    this.setRefItem = function (_refItem) {
        refItem = _refItem;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStore = function (_refStore) {
        refStore = _refStore;
    };

    this.setSectionPartClass = function (_sectionPartClass) {
        sectionPartClass = _sectionPartClass;
    };
}