function MainAttendanceConfig () {

    const className = 'MainAttendanceConfig';
    let self = this;
    let refStatus;
    let refClient;
    let oTableAtc;
    let sectionAttendanceConfigSiteClass;

    this.init = function () {
        let exportOptAtc = Object.assign({}, mzExportOpt);
        exportOptAtc['columns'] = [0, 1, 2, 3, 4, 5, 6];
        oTableAtc = $('#dtAtcData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [2, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 50,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 7] },
                { className: 'text-center', targets: [3, 5, 6, 7] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'Print', exportOptions: exportOptAtc},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'Copy', exportOptions: exportOptAtc},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'Excel', exportOptions: exportOptAtc},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - KPI / APD - PPNS', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptAtc}//,
                //{ text: 'Add Attendance', className: 'btn btn-outline-elegant btn-sm px-2'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkAtcActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAtc.row(parseInt(rowId)).data();
                        self.siteActivate(currentRow['siteId']);
                    }
                });
                $('.lnkAtcDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAtc.row(parseInt(rowId)).data();
                        self.siteDeactivate(currentRow['siteId']);
                    }
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'clientId', mRender: function (data) {
                        return data !== '' ? refClient[data]['clientName'] : '';
                    }},
                {mData: 'siteName'},
                {mData: 'siteCode'},
                {mData: 'siteDesc'},
                {mData: 'siteStatus', width: '10%', mRender: function(data) {
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }},
                {mData: 'siteIsAttendance', width: '10%', mRender: function(data) {
                        return data === '1' ? 'Yes' : 'No';
                    }},
                {mData: null, width: '5%',
                    mRender: function (data, type, row, meta) {
                        if (row['siteIsAttendance'] === '1') {
                            return '<a><i class="fas fa-toggle-off lnkAtcDeactivate" id="lnkAtcDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>';
                        } else {
                            return '<a><i class="fas fa-toggle-on lnkAtcActivate" id="lnkAtcActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>';
                        }
                    }
                }
            ]
        });
        let oTableAtcTbody = $('#dtAtcData tbody');
        oTableAtcTbody.delegate('tr', 'click', function (evt) {
            const data = $('#dtAtcData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            if (cell.index() < 7) {
                sectionAttendanceConfigSiteClass.load(data['siteId']);
            }
        });
        oTableAtcTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtAtcData').DataTable().row(this).data();
            const cell = $(evt.target).closest('td');
            if (cell.index() < 7) {
                cell.css('cursor', 'pointer');
                cell.attr('data-toggle', 'tooltip');
                cell.attr('title', 'Click to configure '+data['siteName']);
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        self.genTable();
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function () {
        $('.sectionAtcMain').show();
    };

    this.hideMain = function () {
        $('.sectionAtcMain').hide();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest('site.php', 'GET');
        oTableAtc.clear().rows.add(dataDb).draw();
    };

    this.siteActivate = function (_siteId) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_siteId]);
                    mzAjaxRequest2('att_group/activate_site/'+_siteId, 'PUT');
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.siteDeactivate = function (_siteId) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_siteId]);
                    mzAjaxRequest2('att_group/deactivate_site/'+_siteId, 'PUT');
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setSectionAttendanceConfigSiteClass = function (_sectionAttendanceConfigSiteClass) {
        sectionAttendanceConfigSiteClass = _sectionAttendanceConfigSiteClass;
    };
}