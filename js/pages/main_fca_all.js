function MainFcaAll () {

    const className = 'MainFcaAll';
    let self = this;
    let isAuditor = false;
    let oTable;
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
        oTable = $('#dtFcaData').DataTable({
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
                { visible: false, targets: [4, 12, 13, 14, 15, 16, 18, 19] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFcaObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFcaObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFcaObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFcaObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Observation List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if ($('#divFcaPageWidth').width() < 546) {
                    $(this).DataTable().column(1).visible(false);
                    $(this).DataTable().column(2).visible(false);
                    $(this).DataTable().column(7).visible(false);
                    $(this).DataTable().column(8).visible(false);
                    $(this).DataTable().column(18).visible(false);
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
                {mData: 'fcaTaskTimeRecommended'},
                {mData: 'fcaTaskTimeValidated'},
                {mData: 'fcaTaskStatus', mRender: function(data) {   <!--20-->
                        return refStatus[data]['statusAction'];
                    }}
            ]
        });
        let oTableTbody = $('#dtFcaData tbody');
        oTableTbody.delegate('tr', 'click', function () {
            const data = $('#dtFcaData').DataTable().row(this).data();
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Main');
        });
        oTableTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFcaData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see '+data['fcaTaskNo']+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('fca_task', 'GET');
        oTable.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function (_section) {
        $('.sectionFcaMain').show();
    };

    this.hideMain = function () {
        $('.sectionFcaMain').hide();
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