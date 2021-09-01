function MainKpiPpns () {

    const className = 'MainStoreManagement';
    let self = this;
    let oTableKpp;
    let sectionKpiPpnsClass;

    this.init = function () {
        const monthFulls = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        oTableKpp = $('#dtKppData').DataTable({
            bLengthChange: false,
            bFilter: false,
            ordering: false,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-12 px-0'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0] },
                { className: 'text-right', targets: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14] },
                { className: 'text-right font-weight-bolder', targets: [15] },
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkKppDetails').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    const linkIndex2 = linkId.indexOf('_', linkIndex+1);
                    if (linkIndex > 0 && linkIndex2 > 0) {
                        const rowId = linkId.substr(linkIndex2+1);
                        const currentRow = oTableKpp.row(parseInt(rowId)).data();
                        const category = linkId.substring(linkIndex+1, linkIndex2);
                        sectionKpiPpnsClass.load(true, currentRow['kpiId'], category);
                    }
                });
            },
            aoColumns: [
                {mData: 'kpiYear'},
                {mData: 'kpiMonth', mRender: function (data){
                        return monthFulls[data];
                    }},
                {mData: 'kpiCate2', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_2_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate3', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_3_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate4', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_4_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate5', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_5_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate6', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_6_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate7', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_7_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate8', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_8_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate9', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_9_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate10', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_10_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate11', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_11_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate12', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_12_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate13', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_13_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCate14', mRender: function (data, type, row, meta){
                        return '<a class="text-primary font-weight-bolder lnkKppDetails" id="lnkKppDetails_14_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deduction Details">'+mzFormatNumber(data,2)+'</a>';
                    }},
                {mData: 'kpiCateAll'}
            ]
        });

        self.genTable();
    };

    this.showMain = function () {
        $('.sectionKppMain').show();
    };

    this.hideMain = function () {
        $('.sectionKppMain').hide();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('kpi/ppns_list', 'GET');
        oTableKpp.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setSectionKpiPpnsClass = function (_sectionKpiPpnsClass) {
        sectionKpiPpnsClass = _sectionKpiPpnsClass;
    };
}