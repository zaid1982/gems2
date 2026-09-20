function MainFcaDefect () {

    const className = 'MainFcaDefect';
    let self = this;
    let oTableFcdDefect;
    let oTableFcdSite;
    let refStatus;
    let refSite;
    let refDefectCategory;
    let isAuditor = false;
    let modalFcaDefectClass;
    let modalFcaDefectSiteClass;
    let modalConfirmDeleteClass;

    function siteName(siteId) {
        const row = refSite && (refSite[siteId] || refSite[String(siteId)]);
        return row && row['siteName'] ? row['siteName'] : siteId;
    }

    function siteCode(siteId) {
        const row = refSite && (refSite[siteId] || refSite[String(siteId)]);
        return row && row['siteCode'] ? row['siteCode'] : siteId;
    }

    function statusDesc(statusId) {
        const row = refStatus && (refStatus[statusId] || refStatus[String(statusId)]);
        return row && row['statusDesc'] ? row['statusDesc'] : statusId;
    }

    function defectName(id) {
        const row = refDefectCategory && (refDefectCategory[id] || refDefectCategory[String(id)]);
        return row && row['fcaDefectCategoryName'] ? row['fcaDefectCategoryName'] : id;
    }

    this.init = function () {
        isAuditor = mzIsRoleExist('22');

        oTableFcdDefect = $('#dtFcdDefectData').DataTable({
            bLengthChange: false,
            bFilter: false,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-exclamation-triangle', 'No defect categories recorded yet.', 'No defect categories match the current search.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDomButtons,
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 2] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-secondary btn-sm btnFcdDefectHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Defect Category List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm btnFcdDefectHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Defect Category List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-secondary btn-sm', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Defect Category List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-secondary btn-sm', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Defect Category List', titleAttr: 'PDF', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-plus me-2"></i>Add Defect Category', className: 'btn btn-primary btn-sm', attr: { id: 'btnFcdDefectAdd' }, titleAttr: 'Add New FCA Defect Category'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('#btnFcdDefectAdd').off('click').on('click', function () {
                    if (!isAuditor) {
                        toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                    modalFcaDefectClass.add();
                });
                if ($('#divFcdDefectCard').width() < 388) {
                    $('.btnFcdDefectHide').hide();
                }
            },
            aoColumns: [
                {mData: null},
                {mData: 'fcaDefectCategoryName'},
                {mData: 'fcaDefectCategoryStatus', mRender: function(data) {
                        return statusDesc(data);
                    }}
            ]
        });
        oTableFcdDefect.buttons().container().appendTo($('#btnDtFcdDefectExport'));
        GemsUI.bindDtTooltips('#dtFcdDefectData');
        let oTableFcdDefectTbody = $('#dtFcdDefectData tbody');
        oTableFcdDefectTbody.delegate('tr', 'click', function () {
            if (!isAuditor) {
                toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                return false;
            }
            const data = $('#dtFcdDefectData').DataTable().row(this).data();
            modalFcaDefectClass.edit(data['fcaDefectCategoryId']);
        });
        oTableFcdDefectTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFcdDefectData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to edit '+data['fcaDefectCategoryName']+' details');
            GemsUI.initTooltips(cell[0]);
        });

        let exportOptFcdSite = Object.assign({}, mzExportOpt);
        exportOptFcdSite['columns'] = [0, 1, 3];
        oTableFcdSite = $('#dtFcdSiteData').DataTable({
            bLengthChange: false,
            bFilter: false,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-building', 'No site defect categories recorded yet.', 'No site assignments match the current search.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDomButtons,
            columnDefs: [
                { bSortable: false, targets: [0] },
                { visible: false, targets: [3] },
                { className: 'text-center', targets: [0] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-secondary btn-sm btnFcdSiteHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Defect Category by Site', titleAttr: 'Print', exportOptions: exportOptFcdSite},
                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm btnFcdSiteHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Defect Category by Site', titleAttr: 'Copy', exportOptions: exportOptFcdSite},
                { extend: 'excelHtml5', className: 'btn btn-outline-secondary btn-sm', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Defect Category by Site', titleAttr: 'Excel', exportOptions: exportOptFcdSite},
                { extend: 'pdfHtml5', className: 'btn btn-outline-secondary btn-sm', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Defect Category by Site', titleAttr: 'PDF', exportOptions: exportOptFcdSite},
                { text: '<i class="fas fa-plus me-2"></i>Add Site Defect Category', className: 'btn btn-primary btn-sm', attr: { id: 'btnFcdSiteAdd' }, titleAttr: 'Add New FCA Defect Category'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('#btnFcdSiteAdd').off('click').on('click', function () {
                    if (!isAuditor) {
                        toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                        return false;
                    }
                    modalFcaDefectSiteClass.add();
                });
                if ($('#divFcdSiteCard').width() < 388) {
                    $('.btnFcdSiteHide').hide();
                }
                $('.lnkFcdDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkArr = linkId.split('_');
                    if (linkArr[0] === 'lnkFcdDelete' && mzValidNumeric(linkArr[1]) && mzValidNumeric(linkArr[2])) {
                        modalConfirmDeleteClass.delete(linkArr[1]+'_'+linkArr[2], modalFcaDefectSiteClass);
                    }
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'siteId', mRender: function(data) {
                        return siteName(data);
                    }},
                {mData: 'defectCategoryList', mRender: function(data, type, row) {
                        let label = '<ul style="padding-left: 25px; margin-bottom: 0px !important;">';
                        const dataSplit = data.split(',');
                        for (let j=0; j<dataSplit.length; j++) {
                            label += '<li>' + defectName(dataSplit[j]) +
                                '<a><i class="fas fa-trash-alt lnkFcdDelete ms-2" id="lnkFcdDelete_'+row['siteId']+'_'+dataSplit[j]+'" data-toggle="tooltip" data-placement="top" title="Remove Defect Category"></i></a>' +
                                '</li>';
                        }
                        label += '</ul>';
                        return label;
                    }},
                {mData: 'defectCategoryList', mRender: function(data) {
                        let label = '';
                        const dataSplit = data.split(',');
                        for (let j =0; j<dataSplit.length; j++) {
                            label += defectName(dataSplit[j]);
                            if (j < dataSplit.length - 1) {
                                label += ', ';
                            }
                        }
                        return label;
                    }}
            ]
        });
        oTableFcdSite.buttons().container().appendTo($('#btnDtFcdSiteExport'));
        GemsUI.bindDtTooltips('#dtFcdSiteData');
        let oTableFcdSiteTbody = $('#dtFcdSiteData tbody');
        oTableFcdSiteTbody.delegate('tr', 'click', function (evt) {
            if (!isAuditor) {
                toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
                return false;
            }
            const data = $('#dtFcdSiteData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            if (cell.index() < 2) {
                modalFcaDefectSiteClass.add(data['siteId']);
            }
        });
        oTableFcdSiteTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtFcdSiteData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            if (cell.index() < 2) {
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to add Defect Category at ' + siteCode(data['siteId']) + ' site');
                GemsUI.initTooltips(cell[0]);
            }
        });

        self.genTableDefect();
        self.genTableSite();
    };

    this.genTableDefect = function () {
        const dataDb = mzAjaxRequest2('fca_defect_category', 'GET');
        oTableFcdDefect.clear().rows.add(dataDb).draw();
        refDefectCategory = mzAjaxRequest2('fca_defect_category/ref', 'GET');
        modalFcaDefectSiteClass.setRefDefectCategory(refDefectCategory);
    };

    this.genTableSite = function () {
        const dataDb = mzAjaxRequest2('fca_defect_category_site/grouped', 'GET');
        oTableFcdSite.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.setModalFcaDefectClass = function (_modalFcaDefectClass) {
        modalFcaDefectClass = _modalFcaDefectClass;
    };

    this.setModalFcaDefectSiteClass = function (_modalFcaDefectSiteClass) {
        modalFcaDefectSiteClass = _modalFcaDefectSiteClass;
    };
}
