function SectionPpmAsset () {

    const className = 'SectionPpmAsset';
    let self = this;
    let classFrom;
    let refStatus;
    let refPpmGroup;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let modalConfirmDeleteClass;
    let modalPpmAssetClass;
    let modalPpmAssetSelectClass;
    let dtSpgAsset;
    let dtSpgTask;
    let ppmId;
    let assetTypeId;
    let contractId;
    let isUpdate = false;

    this.init = function () {
        self.hideSection();

        $('#btnSpgBack').on('click', function () {
            self.hideSection();
            $(window).scrollTop(0);
        });

        $('#btnSpgEdit').on('click', function () {
            modalPpmAssetClass.setClassFrom(self);
            modalPpmAssetClass.edit(ppmId);
        });

        $('#btnSpgRemove').on('click', function () {
            modalPpmAssetClass.setClassFrom(self);
            modalConfirmDeleteClass.delete(ppmId, modalPpmAssetClass);
        });

        dtSpgAsset = $('#dtSpgAsset').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 6] },
                { className: 'text-center', targets: [0, 1] },
                { visible: false, targets: [3, 5] },
                { className: 'noVis', targets: [0, 6] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0', text:'<i class="fas fa-print"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - PPM Asset Group - Asset List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0 mr-2', attr: { id: 'btnSpgAssetRefresh' }, titleAttr: 'Refresh'},
                { text: '<i class="fas fa-plus mr-2"></i>Add Asset', className: 'btn btn-outline-blue btn-sm px-2 ml-0', attr: { id: 'btnSpgAssetAdd' }, titleAttr: 'Add Asset'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnSpgAssetRefresh').off('click').on('click', function () {
                    self.genTableAsset();
                });
                $('#btnSpgAssetAdd').off('click').on('click', function () {
                    modalPpmAssetSelectClass.setLabelName($('#pSpgName').text());
                    modalPpmAssetSelectClass.setLabelDocNo($('#pSpgTaskNo').text());
                    modalPpmAssetSelectClass.add(ppmId, contractId, assetTypeId);
                });
                $('.lnkSpgAssetRemove').off('click').on('click', function () {
                    const ppmAssetId = mzGetLinkId($(this), dtSpgAsset, 'ppmAssetId');
                    modalConfirmDeleteClass.delete(ppmAssetId, modalPpmAssetSelectClass);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'assetNo'},
                { mData: 'assetName'},
                { mData: 'assetSerialNo'},
                { mData: 'assetLocationCode'},
                { mData: 'assetDesc'},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-trash-alt lnkSpgAssetRemove" id="lnkSpgAssetRemove_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Remove"></i></a>';
                    }}
            ]
        });

        dtSpgTask = $('#dtSpgTask').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 6] },
                { className: 'text-center', targets: [0, 1, 2, 4, 5, 6] },
                { className: 'noVis', targets: [0, 6] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0', text:'<i class="fas fa-print"></i>', title:'GEMS - PPM Asset Group - Task List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0', text:'<i class="fas fa-copy"></i>', title:'GEMS - PPM Asset Group - Task List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - PPM Asset Group - Task List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - PPM Asset Group - Task List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-sync"></i>', className: 'btn btn-outline-purple btn-sm px-2 ml-0 mr-2', attr: { id: 'btnSpgTaskRefresh' }, titleAttr: 'Refresh'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnSpgTaskRefresh').off('click').on('click', function () {
                    self.genTableTask();
                });
                $('.lnkSpgAssetPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = dtSpgTask.row(parseInt(rowId)).data();
                                let pdfId = currentRow['pdfId'];
                                if (currentRow['pdfId'] === null) {
                                    pdfId = mzAjaxRequest('ppm.php', 'POST', {action: 'generate_pdf', ppmTaskId:currentRow['ppmTaskId']});
                                }
                                const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;PPM Report: '+currentRow['ppmTaskNo']);
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
            aoColumns: [
                { mData: null},
                { mData: 'ppmTaskNo'},
                { mData: 'ppmTaskStartDate'},
                { mData: 'ppmTaskAssignedTo'},
                { mData: 'ppmTaskTimeServiced'},
                { mData: 'ppmTaskStatus', mRender: function (data) {
                        return '<h6 class="mb-0"><span class="badge badge-pill '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-file-pdf lnkSpgAssetPdf" id="lnkSpgAssetPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="PPM PDF"></i></a>';
                    }}
            ]
        });

        $('#btnSpgSubmit').on('click', function () {
            ShowLoader(); setTimeout(function () {
                mzFetch('ppm_v3/ppmAssetGroup/submit/'+ppmId, 'PUT').then(res => {
                    isUpdate = true;
                    self.genTableTask();
                    $('#pSpgStatus').text(refStatus[1]['statusDesc']);
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);
        });
    };

    this.load = function (_ppmId, _isRefresh) {
        ShowLoader(); setTimeout(function () {
            try {
                ppmId = _ppmId;
                const isRefresh = typeof _isRefresh !== 'undefined' && _isRefresh === true;
                isUpdate = isRefresh ? true : isUpdate;
                mzFetch('ppm_v3/'+ppmId).then(res => {
                    self.genTableAsset();
                    if (!isRefresh) {
                        self.genTableTask();
                    }
                    const ppm = res;
                    $('#pSpgName, #lblSpgName').text(mzNullToValue(ppm['ppmName'], '-'));
                    assetTypeId = ppm['assetTypeId'];
                    contractId = ppm['contractId'];
                    $('#pSpgAssetType').text(refAssetType[assetTypeId]['assetTypeName']);
                    const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
                    $('#pSpgAssetCategory').text(refAssetCategory[assetCategoryId]['assetCategoryName']);
                    const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                    $('#pSpgAssetGroup').text(refAssetGroup[assetGroupId]['assetGroupName']);
                    $('#pSpgTaskNo').text(mzNullToValue(ppm['ppmTaskNo'], '-'));
                    $('#pSpgIssueNo').text(mzNullToValue(ppm['ppmIssueNo'], '-'));
                    $('#pSpgFrequency').text(mzNullToValue(ppm['ppmFrequency'], '-'));
                    $('#pSpgDateStart').text(mzNullToValue(ppm['ppmDateStart'], '-', moment(ppm['ppmDateStart']).format('MMMM Do, YYYY')));
                    $('#pSpgTimeCreated').text(moment(ppm['ppmTimeCreated']).format('MMMM Do, YYYY, hh:mm:ss'));
                    $('#pSpgRemark').text(mzNullToValue(ppm['ppmRemark'], '-'));
                    $('#pSpgStatus').text(refStatus[ppm['ppmStatus']]['statusDesc']);
                    classFrom.hideMain();
                    self.showSection();
                    ppm['ppmStatus'] === 11 ? $('#btnSpgSubmit').show() : $('#btnSpgSubmit').hide();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        }, 200);
    };

    this.genTableAsset = function () {
        ShowLoader(); setTimeout(function () { mzFetch('ppm_asset/list/'+ppmId).then(res => {
            dtSpgAsset.clear().rows.add(res).draw();
            $('#pSpgTotalAsset').text(res.length);
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.genTableTask = function () {
        ShowLoader(); setTimeout(function () { mzFetch('ppm_task/list/'+ppmId).then(res => {
            dtSpgTask.clear().rows.add(res).draw();
            $('#pSpgTotalTask').text(res.length);
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.showSection = function () {
        $('.sectionPpmAsset').show();
    };

    this.hideSection = function () {
        $('.sectionPpmAsset').hide();
        classFrom.showMain();
        if (isUpdate) {
            classFrom.genTable();
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

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalPpmAssetClass = function (_modalPpmAssetClass) {
        modalPpmAssetClass = _modalPpmAssetClass;
    };

    this.setModalPpmAssetSelectClass = function (_modalPpmAssetSelectClass) {
        modalPpmAssetSelectClass = _modalPpmAssetSelectClass;
    };

    this.setContractName = function (_contractName) {
        $('#pSpgContract').text(_contractName);
    };

    this.setSiteName = function (_siteName) {
        $('#pSpgSite').text(_siteName);
    };

    this.setIsUpdate = function (_isUpdate) {
        isUpdate = _isUpdate;
    };
}