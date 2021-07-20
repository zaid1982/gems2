function SectionKpiPpns () {

    const className = 'SectionKpiPpns';
    let self = this;
    let classFrom;
    let kpi;
    let kpiId;
    let kpiPpnsCategory;
    let kpiPpnsId;
    let siteId;
    let isEdit;
    let refSite;
    let refStatus;
    let refUser;
    let refPpmGroup;
    let oTableSkpCategory6;

    this.init = function () {
        $('.sectionKpiPpns').hide();

        $('#btnSkpBack').on('click', function () {
            $('.sectionKpiPpns').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        let exportOpt = Object.assign({}, mzExportOpt);
        exportOpt['columns'] = [0, 1, 2, 5, 8, 9, 13, 15, 16, 17, 19];
        oTableSkpCategory6 = $('#dtSkpCategory6').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[15, 'asc']],
            language: _DATATABLE_LANGUAGE,
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
                { className: 'text-center', targets: [0, 1, 2, 5, 8, 9, 12, 13, 15, 16, 17, 18, 19] },
                { className: 'noVis', targets: [0, 19] },
                { visible: false, targets: [1, 3, 4, 6, 7, 9, 10, 11, 12, 14, 19] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Print', exportOptions: exportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskTimeCreated', mRender: function (data){
                        return data.substr(0, 10);
                    }},
                {mData: 'woTaskNoOri'},
                {mData: 'siteId', mRender: function (data){
                        return refSite[data]['siteDesc'];
                    }},
                {mData: 'woTaskLocation'},
                {mData: 'woTaskTypeDesc'},
                {mData: 'woTaskCreatedBy', mRender: function (data){
                        return refUser[data]['userFirstName'];
                    }},
                {mData: 'woTaskComplaint'},
                {mData: 'woTaskSeverity'},
                {mData: 'ppmGroupId', mRender: function (data){
                        return data !== '' ? refPpmGroup[data]['ppmGroupName'] : '';
                    }},
                {mData: 'woTaskAssignedTo', mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskRepairDesc'},
                {mData: 'woTaskRate'},
                {mData: 'woTaskStatus', mRender: function (data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: 'woTaskAssignedBy', mRender: function (data) {
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskTimeCreated'},
                {mData: 'woTaskTimeAssigned'},
                {mData: 'durationResponded'},
                {mData: null, mRender: function (data, type, row){
                        const result = row['durationKpi'];
                        if (result === 'Success') {
                            return '<h6><span class="badge badge-pill green z-depth-2">Success</span></h6>';
                        } else if (result === 'Fail') {
                            return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                        }
                        return result;
                    }},
                {mData: 'durationKpi'}
            ]
        });

        $('#btnSkpSubmit').on('click', function () {
            const assetGroupId = $(this).val();
            ShowLoader();
            setTimeout(function () {
                try {
                    if (kpiPpnsCategory === '6') {
                        mzAjaxRequest2('kpi/calculate_ppns/'+kpiPpnsId, 'PUT');
                        self.displayDeductedData();
                        classFrom.genTable();
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.displayDeductedData = function () {
        if (kpiPpnsCategory === '6') {
            const kpiPpns = mzAjaxRequest2('kpi/ppns_2/'+kpiId+'/'+kpiPpnsCategory, 'GET');
            kpiPpnsId = kpiPpns['kpiPpnsId'];
            mzSetFieldValue('SkpEmergencyTotal', mzFormatNumber(kpiPpns['kpiPpnsParam1']), 'text');
            mzSetFieldValue('SkpEmergencyNonComply', mzFormatNumber(kpiPpns['kpiPpnsParam2']), 'text');
            const emergencyPerc = mzFormatNumber(kpiPpns['kpiPpnsParam1']) !== '0' ? mzFormatNumber(parseInt(kpiPpns['kpiPpnsParam2'])/parseInt(kpiPpns['kpiPpnsParam1'])*100,2) : '0';
            mzSetFieldValue('SkpEmergencyNonComplyPerc', emergencyPerc+'%', 'text');
            mzSetFieldValue('SkpUrgentTotal', mzFormatNumber(kpiPpns['kpiPpnsParam3']), 'text');
            mzSetFieldValue('SkpUrgentNonComply', mzFormatNumber(kpiPpns['kpiPpnsParam4']), 'text');
            const urgentPerc = mzFormatNumber(kpiPpns['kpiPpnsParam3']) !== '0' ? mzFormatNumber(parseInt(kpiPpns['kpiPpnsParam4'])/parseInt(kpiPpns['kpiPpnsParam3'])*100,2) : '0';
            mzSetFieldValue('SkpUrgentNonComplyPerc', urgentPerc+'%', 'text');
            mzSetFieldValue('SkpNormalTotal', mzFormatNumber(kpiPpns['kpiPpnsParam5']), 'text');
            mzSetFieldValue('SkpNormalNonComply', mzFormatNumber(kpiPpns['kpiPpnsParam6']), 'text');
            const normalPerc = mzFormatNumber(kpiPpns['kpiPpnsParam5']) !== '0' ? mzFormatNumber(parseInt(kpiPpns['kpiPpnsParam6'])/parseInt(kpiPpns['kpiPpnsParam5'])*100,2) : '0';
            mzSetFieldValue('SkpNormalNonComplyPerc', normalPerc+'%', 'text');
            mzSetFieldValue('SkpAllTotal', kpiPpns['kpiPpnsParam7'], 'text');
            mzSetFieldValue('SkpAllNonComply', kpiPpns['kpiPpnsParam8'], 'text');
            mzSetFieldValue('SkpNfp', kpiPpns['kpiPpnsNcp'], 'text');
            mzSetFieldValue('SkpWeightage', (parseFloat(kpiPpns['kpiPpnsWeightage'])*100)+'%', 'text');
            mzSetFieldValue('SkpTmd', 'RM '+mzFormatNumber(parseFloat(kpiPpns['kpiPpnsNcp'])*parseFloat(kpiPpns['kpiPpnsWeightage'])*parseFloat(kpi['kpiPortionTotalFee'])*parseFloat(kpi['kpiPortionPerc'])/100,2), 'text');
            const dataCategory6 = mzAjaxRequest('wo.php?type=dashboard_list&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory6.clear().rows.add(dataCategory6).draw();
            $('#divSkpCategory6, #divSkpData6').show();
        }
    };

    this.load = function (_isEdit, _kpiId, _kpiPpnsCategory) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_isEdit, _kpiId, _kpiPpnsCategory]);
                    isEdit = _isEdit;
                    kpiId = _kpiId;
                    kpiPpnsCategory = _kpiPpnsCategory;

                    kpi = mzAjaxRequest2('kpi/'+kpiId, 'GET');
                    siteId = kpi['siteId'];
                    const kpiInfo = mzAjaxRequest2('kpi/info_list/'+siteId+'/'+kpiPpnsCategory+'/'+kpi['kpiInfoVersion'], 'GET');
                    $('#pSkpSiteName').text(refSite[siteId]['siteName']);
                    $('#pSkpServiceDescription').text(kpiInfo['kpiInfoServiceDescription']);
                    $('#pSkpPerformanceMeasure').text(kpiInfo['kpiInfoPerformanceMeasure']);
                    $('#pSkpTargetValue').text(kpiInfo['kpiInfoTargetValue']);
                    $('#pSkpSourceOfData').text(kpiInfo['kpiInfoSourceOfData']);
                    $('#pSkpWeightage').text((parseFloat(kpiInfo['kpiInfoWeightage'])*100)+'%');
                    $('#pSkpNcp').text(kpiInfo['kpiInfoNcp']);
                    $('#pSkpRemark').text(kpiInfo['kpiInfoRemark']);
                    mzSetFieldValue('SkpWeightage', (parseFloat(kpiInfo['kpiInfoWeightage'])*100)+'%', 'text');
                    mzSetFieldValue('SkpMonthlyFee', 'RM '+mzFormatNumber(kpi['kpiPortionTotalFee'],2), 'text');
                    mzSetFieldValue('SkpPtf', 'RM '+mzFormatNumber(parseFloat(kpi['kpiPortionTotalFee'])*parseFloat(kpi['kpiPortionPerc'])/100,2), 'text');

                    $('.divSkpCategory').hide();
                    self.displayDeductedData();

                    $('.sectionKpiPpns').show();
                    classFrom.hideMain();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
}