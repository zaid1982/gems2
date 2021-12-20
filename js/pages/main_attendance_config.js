function MainAttendanceConfig () {

    const className = 'MainAttendanceConfig';
    let self = this;
    let refStatus;
    let refClient;
    let oTableAtc;
    let modalConfirmSubmitClass;
    let sectionAttendanceConfigSiteClass;

    this.init = function () {
        let exportOptAtc = Object.assign({}, mzExportOpt);
        exportOptAtc['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8];
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
                { bSortable: false, targets: [0, 9] },
                { className: 'text-center', targets: [0, 3, 5, 6, 7, 8, 9] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ', text:'<i class="fas fa-print"></i>', title:'GEMS - Attendance Site List', titleAttr: 'Print', exportOptions: exportOptAtc},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 ', text:'<i class="fas fa-copy"></i>', title:'GEMS - Attendance Site List', titleAttr: 'Copy', exportOptions: exportOptAtc},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Attendance Site List', titleAttr: 'Excel', exportOptions: exportOptAtc},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 ', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Attendance Site List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOptAtc}//,
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
                        modalConfirmSubmitClass.setClassFrom(self);
                        modalConfirmSubmitClass.load(currentRow['siteId'], 'Activate', 'Are you sure to <b>ACTIVATE</b> attendance for <b>'+currentRow['siteName']+'</b> site?');
                    }
                });
                $('.lnkAtcDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableAtc.row(parseInt(rowId)).data();
                        modalConfirmSubmitClass.setClassFrom(self);
                        modalConfirmSubmitClass.load(currentRow['siteId'], 'Deactivate', 'Are you sure to <b>DEACTIVATE</b> attendance for <b>'+currentRow['siteName']+'</b> site?');
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
                {mData: 'totalGroup'},
                {mData: 'totalParticipant'},
                {mData: 'siteIsAttendance', width: '10%', mRender: function(data) {
                        return data === '1' ? 'Yes' : 'No';
                    }},
                {mData: null, width: '5%',
                    mRender: function (data, type, row, meta) {
                        if (row['siteIsAttendance'] === '1') {
                            return '<a><i class="fas fa-toggle-off lnkAtcDeactivate" id="lnkAtcDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to Deactivate"></i></a>';
                        } else {
                            return '<a><i class="fas fa-toggle-on lnkAtcActivate" id="lnkAtcActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Click to Activate"></i></a>';
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
        const dataDb = mzAjaxRequest2('att_group/site', 'GET');
        oTableAtc.clear().rows.add(dataDb).draw();
    };

    this.confirmSubmit = function (_siteId, _flag) {
        try {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzCheckFuncParam([_siteId, _flag]);
                    if (_flag === 'Activate') {
                        mzAjaxRequest2('att_group/activate_site/'+_siteId, 'PUT');
                        self.genTable();
                    } else if (_flag === 'Deactivate') {
                        mzAjaxRequest2('att_group/deactivate_site/'+_siteId, 'PUT');
                        self.genTable();
                    } else {
                        toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                    }
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

    this.setModalConfirmSubmitClass = function (_modalConfirmSubmitClass) {
        modalConfirmSubmitClass = _modalConfirmSubmitClass;
    };

    this.setSectionAttendanceConfigSiteClass = function (_sectionAttendanceConfigSiteClass) {
        sectionAttendanceConfigSiteClass = _sectionAttendanceConfigSiteClass;
    };
}