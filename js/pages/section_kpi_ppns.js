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
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let oTableSkpCategory4;
    let oTableSkpCategory6;
    let oTableSkpCategory9;
    let oTableSkpCategory10;
    let oTableSkpCategory11;

    this.init = function () {
        $('.sectionKpiPpns').hide();

        $('#btnSkpBack').on('click', function () {
            $('.sectionKpiPpns').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        let exportOpt4 = Object.assign({}, mzExportOpt);
        exportOpt4['columns'] = [0, 1, 2, 5, 8, 9, 13, 15, 16, 17, 19];
        let exportOptAll4 = Object.assign({}, mzExportOpt);
        exportOptAll4['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19];
        oTableSkpCategory4 = $('#dtSkpCategory4').DataTable({
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
                { visible: false, targets: [3, 4, 7, 8, 9, 11, 12, 13, 14, 15, 19] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS Turnaround Time - Work Order List', titleAttr: 'Print', exportOptions: exportOpt4},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS Turnaround Time - Work Order List', titleAttr: 'Copy', exportOptions: exportOptAll4},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS Turnaround Time - Work Order List', titleAttr: 'Excel', exportOptions: exportOptAll4},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS Turnaround Time - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt4}
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
                {mData: null, mRender: function (data, type, row) {
                        return moment(row['woTaskTimeCreated']).add(1, 'M').format('YYYY/MM/DD');
                    }},
                {mData: 'woTaskTimeExecuted', mRender: function (data){
                        return data.substr(0, 10);
                    }},
                {mData: null, mRender: function (data, type, row){
                        const start = moment(row['woTaskTimeCreated']);
                        const target = start.add(1, 'M');
                        const now = moment();
                        if (row['woTaskTimeExecuted'] === '') {
                            if (now.isAfter(target)) {
                                return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                            } else {
                                return '';
                            }
                        }
                        const complete = moment(row['woTaskTimeExecuted']);
                        if (complete.isAfter(target)) {
                            return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                        } else {
                            return '<h6><span class="badge badge-pill green z-depth-2">Success</span></h6>';
                        }
                    }},
                {mData: null, mRender: function (data, type, row){
                        const start = moment(row['woTaskTimeCreated']);
                        const target = start.add(1, 'M');
                        const now = moment();
                        if (row['woTaskTimeExecuted'] === '') {
                            if (now.isAfter(target)) {
                                return 'Fail';
                            } else {
                                return '';
                            }
                        }
                        const complete = moment(row['woTaskTimeExecuted']);
                        if (complete.isAfter(target)) {
                            return 'Fail';
                        } else {
                            return 'Success';
                        }
                    }}
            ]
        });

        let exportOpt6 = Object.assign({}, mzExportOpt);
        exportOpt6['columns'] = [0, 1, 2, 5, 8, 9, 13, 15, 16, 17, 19];
        let exportOptAll6 = Object.assign({}, mzExportOpt);
        exportOptAll6['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19];
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
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Copy', exportOptions: exportOptAll6},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS Respond Time - Work Order List', titleAttr: 'Excel', exportOptions: exportOptAll6},
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

        let exportOpt9 = Object.assign({}, mzExportOpt);
        exportOpt9['columns'] = [0, 1, 2, 3, 4, 6, 8, 9, 10, 11, 15, 16, 17, 18, 19, 20, 22];
        let exportOptAll9 = Object.assign({}, mzExportOpt);
        exportOptAll9['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22];
        oTableSkpCategory9 = $('#dtSkpCategory9').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
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
                { className: 'text-center', targets: [0, 1, 2, 3, 4, 6, 7, 12, 13, 14, 17, 18, 19, 20, 21, 22] },
                { className: 'noVis', targets: [0, 22] },
                { visible: false, targets: [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 22] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'Print', exportOptions: exportOpt9},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'Copy', exportOptions: exportOptAll9},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'Excel', exportOptions: exportOptAll9},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt9}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'ppmTaskStartDate'},
                {mData: 'ppmTaskScheduleDate'},
                {mData: 'ppmTaskNo'},
                {mData: 'frequency'},
                {mData: 'siteId', mRender: function (data){
                        return refSite[data]['siteDesc'];
                    }},
                {mData: 'documentNo'},
                {mData: 'assetNo'},
                {mData: 'assetName'},
                {mData: null, mRender: function (data, type, row){
                        return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                    }},
                {mData: null, mRender: function (data, type, row){
                        return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                    }},
                {mData: null, mRender: function (data, type, row){
                        return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                    }},
                {mData: 'assetLocationCode'},
                {mData: 'assetBlock'},
                {mData: 'assetLevel'},
                {mData: null, mRender: function (data, type, row){
                        return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : '';
                    }},
                {mData: null, mRender: function (data, type, row){
                        return row['executor'] !== '' ? refUser[row['executor']]['userFirstName'] : '';
                    }},
                {mData: 'ppmTaskStatus', mRender: function (data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: 'ppmTaskTimeStart'},
                {mData: 'ppmTaskTimeServiced'},
                {mData: 'lateness2'},
                {mData: null, mRender: function (data, type, row){
                        if (row['lateness2'] === 'In Progress') {
                            return '-';
                        } else if (row['lateness2'] === 'On-time') {
                                return '<h6><span class="badge badge-pill green z-depth-2">Success</span></h6>';
                        } else {
                            return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                        }
                    }},
                {mData: null, mRender: function (data, type, row){
                        if (row['lateness2'] === 'In Progress') {
                            return '-';
                        } else if (row['lateness2'] === 'On-time') {
                            return 'Success';
                        } else {
                            return 'Fail';
                        }
                    }}
            ]
        });

        let exportOpt10 = Object.assign({}, mzExportOpt);
        exportOpt10['columns'] = [0, 1, 2, 5, 8, 9, 13, 15, 16, 17, 19];
        let exportOptAll10 = Object.assign({}, mzExportOpt);
        exportOptAll10['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19];
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'Print', exportOptions: exportOpt10},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'Copy', exportOptions: exportOptAll10},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'Excel', exportOptions: exportOptAll10},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS CM Execution - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt10}
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

        let exportOpt11 = Object.assign({}, mzExportOpt);
        exportOpt11['columns'] = [0, 1, 2, 5, 8, 9, 12, 14, 15, 16, 18];
        let exportOptAll11 = Object.assign({}, mzExportOpt);
        exportOptAll11['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 18];
        oTableSkpCategory11 = $('#dtSkpCategory11').DataTable({
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
                { className: 'text-center', targets: [0, 1, 2, 5, 8, 9, 12, 14, 15, 16, 17, 18] },
                { className: 'noVis', targets: [0, 18] },
                { visible: false, targets: [1, 3, 4, 6, 7, 9, 10, 11, 13, 15, 18] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI PPNS Service Quality - Work Order List', titleAttr: 'Print', exportOptions: exportOpt10},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI PPNS Service Quality - Work Order List', titleAttr: 'Copy', exportOptions: exportOptAll11},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI PPNS Service Quality - Work Order List', titleAttr: 'Excel', exportOptions: exportOptAll11},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI PPNS Service Quality - Work Order List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt10}
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
                {mData: 'woTaskStatus', mRender: function (data) {
                        return refStatus[data]['statusDesc'];
                    }},
                {mData: 'woTaskAssignedBy', mRender: function (data) {
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskTimeCreated'},
                {mData: 'woTaskTimeExecuted'},
                {mData: 'woTaskRate'},
                {mData: null, mRender: function (data, type, row){
                        const result = parseInt(row['woTaskRateOri']);
                        if (result > 3) {
                            return '<h6><span class="badge badge-pill green z-depth-2">Success</span></h6>';
                        } else {
                            return '<h6><span class="badge badge-pill red z-depth-2">Fail</span></h6>';
                        }
                    }},
                {mData: null, mRender: function (data, type, row){
                        const result = parseInt(row['woTaskRateOri']);
                        if (result > 3) {
                            return 'Success';
                        } else {
                            return 'Fail';
                        }
                    }}
            ]
        });

        $('#btnSkpSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (kpiPpnsCategory === '4' || kpiPpnsCategory === '6' || kpiPpnsCategory === '9' || kpiPpnsCategory === '10' || kpiPpnsCategory === '11') {
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
        if (kpiPpnsCategory === '4') {
            const param4_1 = parseInt(kpiPpns['kpiPpnsParam1']);
            const param4_2 = parseInt(kpiPpns['kpiPpnsParam2']);
            const param4_3 = parseInt(kpiPpns['kpiPpnsParam3']);
            const param4_4 = parseInt(kpiPpns['kpiPpnsParam4']);
            const param4_5 = parseInt(kpiPpns['kpiPpnsParam5']);
            const param4_6 = parseInt(kpiPpns['kpiPpnsParam6']);
            mzSetFieldValue('Skp4TotalWoOpen', !isNaN(param4_1)?param4_1:'-', 'text');
            mzSetFieldValue('Skp4TotalWoLate', !isNaN(param4_2)?param4_2:'-', 'text');
            mzSetFieldValue('Skp4TargetWoInTime', !isNaN(param4_3)?param4_3:'-', 'text');
            mzSetFieldValue('Skp4ActualWoInTime', !isNaN(param4_4)?param4_4:'-', 'text');
            mzSetFieldValue('Skp4TargetWoInTimePerc', !isNaN(param4_5)?(param4_5*100)+'%':'-', 'text');
            mzSetFieldValue('Skp4ActualWoInTimePerc', !isNaN(param4_6)?(param4_6*100)+'%':'-', 'text');
            const dataCategory4 = mzAjaxRequest('wo.php?type=dashboard_list&kpiType=turnaroundTime&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory4.clear().rows.add(dataCategory4).draw();
            $('#divSkpCategory4, #divSkpData4').show();
        }
        else if (kpiPpnsCategory === '6') {
            const param6_1 = parseInt(kpiPpns['kpiPpnsParam1']);
            const param6_2 = parseInt(kpiPpns['kpiPpnsParam2']);
            const param6_3 = parseInt(kpiPpns['kpiPpnsParam3']);
            const param6_4 = parseInt(kpiPpns['kpiPpnsParam4']);
            const param6_5 = parseInt(kpiPpns['kpiPpnsParam5']);
            const param6_6 = parseInt(kpiPpns['kpiPpnsParam6']);
            const param6_7 = parseInt(kpiPpns['kpiPpnsParam7']);
            const param6_8 = parseInt(kpiPpns['kpiPpnsParam8']);
            mzSetFieldValue('Skp6EmergencyTotal', !isNaN(param6_1)?param6_1:'-', 'text');
            mzSetFieldValue('Skp6EmergencyNonComply', !isNaN(param6_2)?param6_2:'-', 'text');
            const emergencyPerc = !isNaN(param6_1) && param6_1 !== 0 ? mzFormatNumber((param6_1-param6_2)/param6_1*100,2) : 0;
            mzSetFieldValue('Skp6EmergencyNonComplyPerc', emergencyPerc+'%', 'text');
            mzSetFieldValue('Skp6UrgentTotal', !isNaN(param6_3)?param6_3:'-', 'text');
            mzSetFieldValue('Skp6UrgentNonComply', !isNaN(param6_4)?param6_4:'-', 'text');
            const urgentPerc = !isNaN(param6_3) && param6_3 !== 0 ? mzFormatNumber((param6_3-param6_4)/param6_3*100,2) : 0;
            mzSetFieldValue('Skp6UrgentNonComplyPerc', urgentPerc+'%', 'text');
            mzSetFieldValue('Skp6NormalTotal', !isNaN(param6_5)?param6_5:'-', 'text');
            mzSetFieldValue('Skp6NormalNonComply', !isNaN(param6_6)?param6_6:'-', 'text');
            const normalPerc = !isNaN(param6_5) && param6_5 !== 0 ? mzFormatNumber((param6_5-param6_6)/param6_5*100,2) : 0;
            mzSetFieldValue('Skp6NormalNonComplyPerc', normalPerc+'%', 'text');
            mzSetFieldValue('Skp6AllTotal', !isNaN(param6_7)?param6_7:'-', 'text');
            mzSetFieldValue('Skp6AllNonComply', !isNaN(param6_8)?param6_8:'-', 'text');
            const dataCategory6 = mzAjaxRequest('wo.php?type=dashboard_list&kpiType=responseTime&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory6.clear().rows.add(dataCategory6).draw();
            $('#divSkpCategory6, #divSkpData6').show();
        }
        else if (kpiPpnsCategory === '8') {
            const param8_1 = parseInt(kpiPpns['kpiPpnsParam1']);
            const param8_2 = parseInt(kpiPpns['kpiPpnsParam2']);
            const param8_3 = parseInt(kpiPpns['kpiPpnsParam3']);
            const param8_4 = parseInt(kpiPpns['kpiPpnsParam4']);
            const param8_5 = parseInt(kpiPpns['kpiPpnsParam5']);
            const param8_6 = parseInt(kpiPpns['kpiPpnsParam6']);
            const param8_7 = parseInt(kpiPpns['kpiPpnsParam7']);
            const param8_8 = parseInt(kpiPpns['kpiPpnsParam8']);
            mzSetFieldValue('Skp8TotalPlanned', !isNaN(param8_1)?param8_1:'-', 'text');
            mzSetFieldValue('Skp8TotalDone', !isNaN(param8_2)?param8_2:'-', 'text');
            mzSetFieldValue('Skp8TotalComply', !isNaN(param8_3)?param8_3:'-', 'text');
            mzSetFieldValue('Skp8TotalNonComply', !isNaN(param8_4)?param8_4:'-', 'text');
            mzSetFieldValue('Skp8TotalNonComplyTemp', !isNaN(param8_5)?param8_5:'-', 'text');
            mzSetFieldValue('Skp8TotalNonComplyHumid', !isNaN(param8_6)?param8_6:'-', 'text');
            mzSetFieldValue('Skp8TargetComplyPerc', !isNaN(param8_7)?(param8_7*100)+'%':'-', 'text');
            mzSetFieldValue('Skp8ActualComplyPerc', !isNaN(param8_8)?(param8_8*100)+'%':'-', 'text');
            const dataCategory8 = mzAjaxRequest2('kpi/ppnsComfortAvailability/'+kpi['kpiYear']+'/'+(kpi['kpiMonth']-1), 'GET');
            //oTableSkpCategory8.clear().rows.add(dataCategory8).draw();
            $('#divSkpCategory8, #divSkpData8').show();
        }
        else if (kpiPpnsCategory === '9') {
            const param9_1 = parseInt(kpiPpns['kpiPpnsParam1']);
            const param9_2 = parseInt(kpiPpns['kpiPpnsParam2']);
            const param9_3 = parseFloat(kpiPpns['kpiPpnsParam3']);
            const param9_4 = parseInt(kpiPpns['kpiPpnsParam4']);
            const param9_5 = parseInt(kpiPpns['kpiPpnsParam5']);
            const param9_6 = parseInt(kpiPpns['kpiPpnsParam6']);
            mzSetFieldValue('Skp9Total', !isNaN(param9_1)?param9_1:'-', 'text');
            mzSetFieldValue('Skp9TotalDue', !isNaN(param9_2)?param9_2:'-', 'text');
            mzSetFieldValue('Skp9TargetPerc', !isNaN(param9_3)?param9_3:'-', 'text');
            mzSetFieldValue('Skp9Target', !isNaN(param9_4)?param9_4:'-', 'text');
            mzSetFieldValue('Skp9TotalLate', !isNaN(param9_5)?param9_5:'-', 'text');
            mzSetFieldValue('Skp9TotalOnTime', !isNaN(param9_6)?param9_6:'-', 'text');
            const dataCategory9 = mzAjaxRequest('ppm.php?type=dashboard_list&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory9.clear().rows.add(dataCategory9).draw();
            $('#divSkpCategory9, #divSkpData9').show();
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
            mzSetFieldValue('Skp10EmergencyTotal', !isNaN(param10_1)?param10_1:'-', 'text');
            mzSetFieldValue('Skp10EmergencyNonComply', !isNaN(param10_2)?param10_2:'-', 'text');
            mzSetFieldValue('Skp10UrgentTotal', !isNaN(param10_3)?param10_3:'-', 'text');
            mzSetFieldValue('Skp10UrgentNonComply', !isNaN(param10_4)?param10_4:'-', 'text');
            mzSetFieldValue('Skp10NormalTotal', !isNaN(param10_5)?param10_5:'-', 'text');
            mzSetFieldValue('Skp10NormalNonComply', !isNaN(param10_6)?param10_6:'-', 'text');
            mzSetFieldValue('Skp10AllTotal', !isNaN(param10_7)?param10_7:'-', 'text');
            mzSetFieldValue('Skp10AllNonComply', !isNaN(param10_8)?param10_8:'-', 'text');
            const allPerc = !isNaN(param10_7) && param10_7 !== 0 ? mzFormatNumber((param10_7-param10_8)/param10_7*100,2) : 0;
            mzSetFieldValue('Skp10AllNonComplyPerc', allPerc+'%', 'text');
            const dataCategory10 = mzAjaxRequest('wo.php?type=dashboard_list&kpiType=mitigateTime&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory10.clear().rows.add(dataCategory10).draw();
            $('#divSkpCategory10, #divSkpData10').show();
        }
        else if (kpiPpnsCategory === '11') {
            const param11_1 = parseInt(kpiPpns['kpiPpnsParam1']);
            const param11_2 = parseInt(kpiPpns['kpiPpnsParam2']);
            const param11_3 = parseFloat(kpiPpns['kpiPpnsParam3']);
            const param11_4 = parseFloat(kpiPpns['kpiPpnsParam4']);
            mzSetFieldValue('Skp11AllTotal', !isNaN(param11_1)?param11_1:'-', 'text');
            mzSetFieldValue('Skp11AllNonComply', !isNaN(param11_2)?param11_2:'-', 'text');
            mzSetFieldValue('Skp11AllNonComplyPerc', !isNaN(param11_1)?(param11_2/param11_1*100)+'%':'-', 'text');
            mzSetFieldValue('Skp11AllTargetPerc', !isNaN(param11_3)?(param11_3*100)+'%':'-', 'text');
            mzSetFieldValue('Skp11Target', param11_4, 'text');
            const dataCategory11 = mzAjaxRequest('wo.php?type=dashboard_list&kpiType=serviceQuality&clientId='+refSite[siteId]['clientId']+'&siteId='+siteId+'&year='+kpi['kpiYear']+'&month='+(kpi['kpiMonth']-1), 'GET');
            oTableSkpCategory11.clear().rows.add(dataCategory11).draw();
            $('#divSkpCategory11, #divSkpData11').show();
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

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };
}