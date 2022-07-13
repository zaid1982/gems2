function MainFcaTask () {

    const className = 'MainFcaTask';
    let self = this;
    let oTableFctObserve;
    let oTableFctRecommend;
    let oTableFctValidate;
    let oTableFctCorrection;
    let isAuditor = false;
    let refStatus;
    let refUser;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;
    let refFcaZone;
    let refConditionScale;
    let refEvaluationType;
    let modalFcaAddClass;
    let sectionFcaInfoClass;

    this.init = function () {
        isAuditor = mzIsRoleExist('22');

        oTableFctObserve = $('#dtFctObserve').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[17, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 17, 18] },
                { visible: false, targets: [4, 10, 11, 12, 13, 14, 15, 16, 17] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Observation List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-plus mr-2"></i>Add New FCA', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnFctAddNew' }, titleAttr: 'Add New FCA Observation'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnFctAddNew').off('click').on('click', function () {
                    if (!isAuditor) {
                        toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                    modalFcaAddClass.setClassFrom(self);
                    modalFcaAddClass.add();
                });
                if ($('#divFctPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
                    $(this).DataTable().column(18).visible(false);
                    $('.btnFctObserveHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'fcaTaskNo'},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteCode'];
                    }},
                {mData: 'assetGroupId', mRender: function(data) {
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'fcaTaskAssetNo'},
                {mData: 'fcaTaskAssetEvaluated'},  <!--5-->
                {mData: 'fcaTaskDefectItem'},
                {mData: 'fcaDefectCategoryId', mRender: function(data) {
                        return data !== null ? refDefectCategory[data]['fcaDefectCategoryName'] : '';
                    }},
                {mData: 'fcaZoneId', mRender: function(data) {
                        return data !== null ? refFcaZone[data]['fcaZoneName'] : '';
                    }},
                {mData: 'fcaTaskArea'},
                {mData: 'fcaTaskConditionScale', mRender: function(data) {   <!--10-->
                        return mzReplaceNull(refConditionScale[data]);
                    }},
                {mData: 'fcaTaskEvaluationType', mRender: function(data) {
                        return mzReplaceNull(refEvaluationType[data]);
                    }},
                {mData: 'fcaTaskObservation'},
                {mData: 'fcaTaskRecommendation'},
                {mData: 'fcaTaskValidation'},
                {mData: 'fcaTaskCreatedBy', mRender: function(data) {   <!--15-->
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'fcaTaskValidateBy', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'fcaTaskTimeCreated'},
                {mData: 'fcaTaskStatus', mRender: function(data) {   <!--18-->
                        return refStatus[data]['statusAction'];
                    }}
            ]
        });
        let oTableFctObserveTbody = $('#dtFctObserve tbody');
        oTableFctObserveTbody.delegate('tr', 'click', function () {
            const data = $('#dtFctObserve').DataTable().row(this).data();
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Observe');
        });
        oTableFctObserveTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctObserve').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        oTableFctRecommend = $('#dtFctRecommend').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[11, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 11] },
                { visible: false, targets: [4, 10] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctRecommendHide', text:'<i class="fas fa-print"></i>', title:'GEMS - New FCA Recommendation Task List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctRecommendHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - New FCA Recommendation Task List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - New FCA Recommendation Task List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - New FCA Recommendation Task List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if ($('#divFctPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
                    $(this).DataTable().column(11).visible(false);
                    $('.btnFctRecommendHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'fcaTaskNo'},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteCode'];
                    }},
                {mData: 'assetGroupId', mRender: function(data) {
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'fcaTaskAssetNo'},
                {mData: 'fcaTaskAssetEvaluated'},  <!--5-->
                {mData: 'fcaTaskDefectItem'},
                {mData: 'fcaDefectCategoryId', mRender: function(data) {
                        return data !== null ? refDefectCategory[data]['fcaDefectCategoryName'] : '';
                    }},
                {mData: 'fcaZoneId', mRender: function(data) {
                        return data !== null ? refFcaZone[data]['fcaZoneName'] : '';
                    }},
                {mData: 'fcaTaskArea'},
                {mData: 'fcaTaskObservation'},  <!--10-->
                {mData: 'taskTimeCreated'}
            ]
        });
        let oTableFctRecommendTbody = $('#dtFctRecommend tbody');
        oTableFctRecommendTbody.delegate('tr', 'click', function () {
            const data = $('#dtFctRecommend').DataTable().row(this).data();
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Recommend');
        });
        oTableFctRecommendTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctRecommend').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        oTableFctValidate = $('#dtFctValidate').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[11, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 15] },
                { visible: false, targets: [4, 8, 12, 13, 14] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctValidateHide', text:'<i class="fas fa-print"></i>', title:'GEMS - New FCA Validation Task List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctValidateHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - New FCA Validation Task List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - New FCA Validation Task List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - New FCA Validation Task List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if ($('#divFctPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(9).visible(false);
                    $(this).DataTable().column(10).visible(false);
                    $(this).DataTable().column(15).visible(false);
                    $('.btnFctValidateHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'fcaTaskNo'},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteCode'];
                    }},
                {mData: 'assetGroupId', mRender: function(data) {
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'fcaTaskAssetNo'},
                {mData: 'fcaTaskAssetEvaluated'},  <!--5-->
                {mData: 'fcaTaskDefectItem'},
                {mData: 'fcaDefectCategoryId', mRender: function(data) {
                        return data !== null ? refDefectCategory[data]['fcaDefectCategoryName'] : '';
                    }},
                {mData: 'fcaZoneId', mRender: function(data) {
                        return data !== null ? refFcaZone[data]['fcaZoneName'] : '';
                    }},
                {mData: 'fcaTaskArea'},
                {mData: 'fcaTaskConditionScale', mRender: function(data) {   <!--10-->
                        return mzReplaceNull(refConditionScale[data]);
                    }},
                {mData: 'fcaTaskEvaluationType', mRender: function(data) {
                        return mzReplaceNull(refEvaluationType[data]);
                    }},
                {mData: 'fcaTaskCreatedBy', mRender: function(data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'fcaTaskObservation'},
                {mData: 'fcaTaskRecommendation'},
                {mData: 'taskTimeCreated'}   <!--15-->
            ]
        });
        let oTableFctValidateTbody = $('#dtFctValidate tbody');
        oTableFctValidateTbody.delegate('tr', 'click', function () {
            const data = $('#dtFctValidate').DataTable().row(this).data();
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Validate');
        });
        oTableFctValidateTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctValidate').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        oTableFctCorrection = $('#dtFctCorrection').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[16, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 16] },
                { visible: false, targets: [4, 10, 11, 12, 13, 15] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctCorrectionHide', text:'<i class="fas fa-print"></i>', title:'GEMS - New FCA Correction Task List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctCorrectionHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - New FCA Correction Task List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - New FCA Correction Task List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - New FCA Correction Task List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if ($('#divFctPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
                    $(this).DataTable().column(14).visible(false);
                    $(this).DataTable().column(16).visible(false);
                    $('.btnFctCorrectionHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'fcaTaskNo'},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteCode'];
                    }},
                {mData: 'assetGroupId', mRender: function(data) {
                        return refAssetGroup[data]['assetGroupName'];
                    }},
                {mData: 'fcaTaskAssetNo'},
                {mData: 'fcaTaskAssetEvaluated'},  <!--5-->
                {mData: 'fcaTaskDefectItem'},
                {mData: 'fcaDefectCategoryId', mRender: function(data) {
                        return data !== null ? refDefectCategory[data]['fcaDefectCategoryName'] : '';
                    }},
                {mData: 'fcaZoneId', mRender: function(data) {
                        return data !== null ? refFcaZone[data]['fcaZoneName'] : '';
                    }},
                {mData: 'fcaTaskArea'},
                {mData: 'fcaTaskConditionScale', mRender: function(data) {   <!--10-->
                        return mzReplaceNull(refConditionScale[data]);
                    }},
                {mData: 'fcaTaskEvaluationType', mRender: function(data) {
                        return mzReplaceNull(refEvaluationType[data]);
                    }},
                {mData: 'fcaTaskObservation'},
                {mData: 'fcaTaskRecommendation'},
                {mData: 'fcaTaskValidation'},
                {mData: 'fcaTaskValidateBy', mRender: function(data) {   <!--15-->
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'taskTimeCreated'}
            ]
        });
        let oTableFctCorrectionTbody = $('#dtFctCorrection tbody');
        oTableFctCorrectionTbody.delegate('tr', 'click', function () {
            const data = $('#dtFctCorrection').DataTable().row(this).data();
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Correction');
        });
        oTableFctCorrectionTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctCorrection').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        $('#btnFctAudit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('.sectionFctTask').hide();
                    $('.sectionFctObserve').show();
                    $('.btnFctTab').removeClass('lighten-2').addClass('lighten-2');
                    $('#btnFctAudit').removeClass('lighten-2');
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnFctRecommend').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('.sectionFctTask').hide();
                    $('.sectionFctRecommend').show();
                    $('.btnFctTab').removeClass('lighten-2').addClass('lighten-2');
                    $('#btnFctRecommend').removeClass('lighten-2');
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnFctValidate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    $('.sectionFctTask').hide();
                    $('.sectionFctValidate').show();
                    $('.btnFctTab').removeClass('lighten-2').addClass('lighten-2');
                    $('#btnFctValidate').removeClass('lighten-2');
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('.sectionFctRecommend, .sectionFctValidate').hide();
        self.genTable();
    };

    this.genTable = function () {
        const dataObserve = mzAjaxRequest2('fca_task/audit', 'GET');
        oTableFctObserve.clear().rows.add(dataObserve).draw();
        const dataRecommend = mzAjaxRequest2('fca_task/recommend', 'GET');
        oTableFctRecommend.clear().rows.add(dataRecommend).draw();
        $('#badgeFctTotalRecommend').text(dataRecommend.length);
        const dataValidate = mzAjaxRequest2('fca_task/validate', 'GET');
        oTableFctValidate.clear().rows.add(dataValidate).draw();
        $('#badgeFctTotalValidate').text(dataValidate.length);
        const dataCorrection = mzAjaxRequest2('fca_task/correction', 'GET');
        oTableFctCorrection.clear().rows.add(dataCorrection).draw();
        $('#badgeFctTotalCorrection').text(dataCorrection.length);
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function (_section) {
        $('.sectionFctMain').show();
        $('.sectionFctTask').hide();
        $('.sectionFct'+_section).show();
    };

    this.hideMain = function () {
        $('.sectionFctMain').hide();
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefDefectCategory = function (_refDefectCategory) {
        refDefectCategory = _refDefectCategory;
    };

    this.setRefFcaZone = function (_refFcaZone) {
        refFcaZone = _refFcaZone;
    };

    this.setRefConditionScale = function (_refConditionScale) {
        refConditionScale = _refConditionScale;
    };

    this.setRefEvaluationType = function (_refEvaluationType) {
        refEvaluationType = _refEvaluationType;
    };

    this.setModalFcaAddClass = function (_modalFcaAddClass) {
        modalFcaAddClass = _modalFcaAddClass;
    };

    this.setSectionFcaInfoClass = function (_sectionFcaInfoClass) {
        sectionFcaInfoClass = _sectionFcaInfoClass;
    };
}