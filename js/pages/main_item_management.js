function MainItemManagement () {

    const className = 'MainItemManagement';
    let self = this;
    let oTableIty;
    let sectionItemClass;
    let refStatus;
    let refAssetGroup;
    let refItemType;

    this.init = function () {
        let exportOpt = Object.assign({}, mzExportOpt);
        exportOpt['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

        oTableIty = $('#dtItyData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [10, 'asc'], [8, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                initPhotoSwipeFromDOM('.mdb-lightbox');
                $('.divItyImageHide').hide();
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 11] },
                { className: 'text-center', targets: [0, 9, 11] },
                { className: 'text-right', targets: [4, 5, 6, 8] },
                { className: 'noVis', targets: [0, 10, 11] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Item Description List', titleAttr: 'Print', exportOptions: exportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Item Description List', titleAttr: 'Copy', exportOptions: exportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Item Description List', titleAttr: 'Excel', exportOptions: exportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Item Description List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: null, mRender: function (data, type, row){
                        let result = '';
                        const itemTypeId = row['itemTypeId'];
                        if (itemTypeId !== '') {
                            const itemType = refItemType[itemTypeId];
                            const assetGroupId = itemType['assetGroupId'];
                            if (assetGroupId !== '') {
                                result = refAssetGroup[assetGroupId]['assetGroupName'];
                            }
                        }
                        return result;
                    }},
                {mData: 'itemTypeId', mRender: function (data){
                        return data !== '' ? refItemType[data]['itemTypeDesc'] : '';
                    }},
                {mData: 'itemDescription'},
                {mData: 'itemThreshold', mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'itemMinOrder', mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'itemMaxOrder', mRender: function (data){ return mzFormatNumber(data)}},
                {mData: 'itemRemark'},
                {mData: 'itemTurn', visible: false},
                {mData: 'itemStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }},
                {mData: null, visible: false, mRender: function (data, type, row){
                        const itemTypeId = row['itemTypeId'];
                        return itemTypeId !== '' ? refItemType[itemTypeId]['itemTypeTurn'] : '';
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
                                    label += '<figure class="col-md-12 divItyImageHide">\n';
                                } else {
                                    label += '<figure class="col-md-12">\n';
                                }
                                label += '<a href="'+mzUrlDownload+pictureUrls[i]+'" data-size="'+dataSize+'">\n' +
                                    '<img src="'+mzUrlDownload+pictureUrls[i]+'" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                    '</a>\n' +
                                    '<p class="mb-0 font-small">'+pictureTitles[i]+'</p>\n' +
                                    '</figure>\n';
                            }
                            label += '</div>\n';
                        }
                        return label;
                    }}
            ]
        });
        let oTableItyTbody = $('#dtItyData tbody');
        oTableItyTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtItyData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            const screenWidth = $('.sectionItyMain').width();
            if ((screenWidth >= 920 && cell.index() < 9) || (screenWidth >= 620 && screenWidth < 920 && cell.index() < 6) || (screenWidth < 620 && cell.index() < 3)) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        sectionItemClass.load(false, data['itemId']);
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
        oTableItyTbody.delegate('tr', 'mouseenter', function (evt) {
            const cell = $(evt.target).closest('td');
            const screenWidth = $('.sectionItyMain').width();
            if ((screenWidth >= 920 && cell.index() < 9) || (screenWidth >= 620 && screenWidth < 920 && cell.index() < 6) || (screenWidth < 620 && cell.index() < 3)) {
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to see details');
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        if ($('.sectionItyMain').width() < 920) {
            oTableIty.column(5).visible(false);
            oTableIty.column(6).visible(false);
            oTableIty.column(7).visible(false);
        }
        if ($('.sectionItyMain').width() < 620) {
            oTableIty.column(1).visible(false);
            oTableIty.column(4).visible(false);
            oTableIty.column(9).visible(false);
        }

        $('#btnDtItyDataRefresh').on('click', function () {
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

        $('#btnItyDataAdd').on('click', function () {
            sectionItemClass.add();
        });

        self.genTable();
    };

    this.showMain = function () {
        $('.sectionItyMain').show();
    };

    this.hideMain = function () {
        $('.sectionItyMain').hide();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('item/list_with_image', 'GET');
        oTableIty.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setSectionItemClass = function (_sectionItemClass) {
        sectionItemClass = _sectionItemClass;
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
}