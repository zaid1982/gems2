function MainFcaTask () {

    const className = 'MainFcaTask';
    let self = this;
    let oTableFctObserve;
    let oTableFctRecommend;
    let isAuditor = false;
    let refStatus;
    let refUser;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;
    let refFcaZone;
    let modalFcaAddClass;
    let sectionFcaInfoClass;

    this.init = function () {
        isAuditor = mzIsRoleExist('22');
        
        let exportOptFctObserve = Object.assign({}, mzExportOpt);
        exportOptFctObserve['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        oTableFctObserve = $('#dtFctObserve').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[17, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Print', exportOptions: exportOptFctObserve},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Copy', exportOptions: exportOptFctObserve},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Observation List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptFctObserve},
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
                    modalFcaAddClass.add();
                });
                if ($('#divFctPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
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
                {mData: 'fcaTaskConditionScale'},  <!--10-->
                {mData: 'fcaTaskEvaluationType'},
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
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Observe', data['taskId']);
        });
        oTableFctObserveTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctObserve').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        let exportOptFctRecommend = Object.assign({}, mzExportOpt);
        exportOptFctRecommend['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        oTableFctRecommend = $('#dtFctRecommend').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[11, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctRecommendHide', text:'<i class="fas fa-print"></i>', title:'GEMS - New FCA Recommendation List', titleAttr: 'Print', exportOptions: exportOptFctRecommend},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctRecommendHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - New FCA Recommendation List', titleAttr: 'Copy', exportOptions: exportOptFctRecommend},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - New FCA Recommendation List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - New FCA Recommendation List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptFctRecommend}
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
                    modalFcaAddClass.add();
                });
                if ($('#divFctPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(5).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
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
                {mData: 'fcaTaskTimeCreated'}
            ]
        });
        let oTableFctRecommendTbody = $('#dtFctRecommend tbody');
        oTableFctRecommendTbody.delegate('tr', 'click', function () {
            const data = $('#dtFctRecommend').DataTable().row(this).data();
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Recommend', data['taskId']);
        });
        oTableFctRecommendTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFctRecommend').DataTable().row(this).data();
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

        $('.sectionFctRecommend, .sectionFctValidate').hide();
        self.genTable();
    };

    this.genTable = function () {
        const dataObserve = mzAjaxRequest2('fca_task/audit', 'GET');
        oTableFctObserve.clear().rows.add(dataObserve).draw();
        const dataRecommend = mzAjaxRequest2('fca_task/recommend', 'GET');
        oTableFctRecommend.clear().rows.add(dataRecommend).draw();
        $('#badgeFctTotalRecommend').text(dataRecommend.length);
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

    this.setModalFcaAddClass = function (_modalFcaAddClass) {
        modalFcaAddClass = _modalFcaAddClass;
    };

    this.setSectionFcaInfoClass = function (_sectionFcaInfoClass) {
        sectionFcaInfoClass = _sectionFcaInfoClass;
    };
}