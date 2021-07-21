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
    let oTableSkpCategory10;

    this.init = function () {
        $('.sectionKpiPpns').hide();

        $('#btnSkpBack').on('click', function () {
            $('.sectionKpiPpns').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        let exportOpt6 = Object.assign({}, mzExportOpt);
        exportOpt6['columns'] = [0, 1, 2, 5, 8, 9, 13, 15, 16, 17, 19];
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Print', exportOptions: exportOpt6},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt6}
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
                        const result = row['kpiResponseResult'];
                        if (result === 'Success') {
                            return '<h6><span class="badge badge-pill green z-depth-2">Success</span></h6>';
                        } else if (result === 'Fail') {
                            return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                        }
                        return result;
                    }},
                {mData: 'kpiResponseResult'}
            ]
        });

        let exportOpt10 = Object.assign({}, mzExportOpt);
        exportOpt10['columns'] = [0, 1, 2, 5, 8, 9, 13, 15, 16, 17, 19];
        oTableSkpCategory10 = $('#dtSkpCategory10').DataTable({
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Print', exportOptions: exportOpt10},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt10}
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
                {mData: 'woTaskTimeExecuted'},
                {mData: 'durationMitigated'},
                {mData: null, mRender: function (data, type, row){
                        const result = row['kpiMitigateResult'];
                        if (result === 'Success') {
                            return '<h6><span class="badge badge-pill green z-depth-2">Success</span></h6>';
                        } else if (result === 'Fail') {
                            return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                        }
                        return result;
                    }},
                {mData: 'kpiMitigateResult'}
            ]
        });

        $('#btnSkpSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (kpiPpnsCategory === '6' || kpiPpnsCategory === '10') {
                        mzAjaxRequest2('kpi/calculate_ppns/'+kpiPpnsCategory+'/'+kpiPpnsId, 'PUT');
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
        const kpiPpns = mzAjaxRequest2('kpi/ppns_2/'+kpiId+'/'+kpiPpnsCategory, 'GET');
        kpiPpnsId = kpiPpns['kpiPpnsId'];
        mzSetFieldValue('SkpNcp', kpiPpns['kpiPpnsNcp'], 'text');
        mzSetFieldValue('SkpWeightage', (parseFloat(kpiPpns['kpiPpnsWeightage'])*100)+'%', 'text');
        mzSetFieldValue('SkpTmd', 'RM '+mzFormatNumber(parseFloat(kpiPpns['kpiPpnsNcp'])*parseFloat(kpiPpns['kpiPpnsWeightage'])*parseFloat(kpi['kpiPortionTotalFee'])*parseFloat(kpi['kpiPortionPerc'])/100,2), 'text');
        if (kpiPpnsCategory === '6') {
			const param6_1 = parseInt(kpiPpns['kpiPpnsParam1']);
			const param6_2 = parseInt(kpiPpns['kpiPpnsParam2']);
			const param6_3 = parseInt(kpiPpns['kpiPpnsParam3']);
			const param6_4 = parseInt(kpiPpns['kpiPpnsParam4']);
			const param6_5 = parseInt(kpiPpns['kpiPpnsParam5']);
			const param6_6 = parseInt(kpiPpns['kpiPpnsParam6']);
			const param6_7 = parseInt(kpiPpns['kpiPpnsParam7']);
			const param6_8 = parseInt(kpiPpns['kpiPpnsParam8']);
            mzSetFieldValue('Skp6EmergencyTotal', param6_1, 'text');
            mzSetFieldValue('Skp6EmergencyNonComply', param6_2, 'text');
            const emergencyPerc = param6_1 !== 0 ? mzFormatNumber((param6_1-param6_2)/param6_1*100,2) : 0;
            mzSetFieldValue('Skp6EmergencyNonComplyPerc', emergencyPerc+'%', 'text');
            mzSetFieldValue('Skp6UrgentTotal', param6_3, 'text');
            mzSetFieldValue('Skp6UrgentNonComply', param6_4, 'text');
            const urgentPerc = param6_3 !== 0 ? mzFormatNumber((param6_3-param6_4)/param6_3*100,2) : 0;
            mzSetFieldValue('Skp6UrgentNonComplyPerc', urgentPerc+'%', 'text');
            mzSetFieldValue('Skp6NormalTotal', param6_5, 'text');
            mzSetFieldValue('Skp6NormalNonComply', param6_6, 'text');
            const normalPerc = param6_5 !== 0 ? mzFormatNumber((param6_5-param6_6)/param6_5*100,2) : 0;
            mzSetFieldValue('Skp6NormalNonComplyPerc', normalPerc+'%', 'text');
            mzSetFieldValue('Skp6AllTotal', param6_7, 'text');
            mzSetFieldValue('Skp6AllNonComply', param6_8, 'text');
            const dataCategory6 = mzAjaxRequest('wo.php?type=dashboard_list&kpiType=responseTime&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory6.clear().rows.add(dataCategory6).draw();
            $('#divSkpCategory6, #divSkpData6').show();
        }
        else if (kpiPpnsCategory === '10') {
			const param10_1 = parseInt(kpiPpns['kpiPpnsParam1']);
			const param10_2 = parseInt(kpiPpns['kpiPpnsParam2']);
			const param10_3 = parseInt(kpiPpns['kpiPpnsParam3']);
			const param10_4 = parseInt(kpiPpns['kpiPpnsParam4']);
			const param10_5 = parseInt(kpiPpns['kpiPpnsParam5']);
			const param10_6 = parseInt(kpiPpns['kpiPpnsParam6']);
			const param10_7 = parseInt(kpiPpns['kpiPpnsParam7']);
			const param10_8 = parseInt(kpiPpns['kpiPpnsParam8']);
            mzSetFieldValue('Skp10EmergencyTotal', param10_1, 'text');
            mzSetFieldValue('Skp10EmergencyNonComply', param10_2, 'text');
            mzSetFieldValue('Skp10UrgentTotal', param10_3, 'text');
            mzSetFieldValue('Skp10UrgentNonComply', param10_4, 'text');
            mzSetFieldValue('Skp10NormalTotal', param10_5, 'text');
            mzSetFieldValue('Skp10NormalNonComply', param10_6, 'text');
            mzSetFieldValue('Skp10AllTotal', param10_7, 'text');
            mzSetFieldValue('Skp10AllNonComply', param10_8, 'text');
            const allPerc = param10_7 !== 0 ? mzFormatNumber((param10_7-param10_8)/param10_7*100,2) : 0;
            mzSetFieldValue('Skp10AllNonComplyPerc', allPerc+'%', 'text');
            const dataCategory10 = mzAjaxRequest('wo.php?type=dashboard_list&kpiType=mitigateTime&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory10.clear().rows.add(dataCategory10).draw();
            $('#divSkpCategory10, #divSkpData10').show();
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