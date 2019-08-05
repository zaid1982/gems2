function MainPpmGroup() {

    const className = 'MainPpmGroup';
    let self = this;
    let refStatus;
    let refRole;
    let refUser;
    let refClient;
    let refSite;
    let clientId;
    let siteId;
    let oTableTechnician;
    let oTableSupervisor;
    let oTableEngineer;
    let modalConfirmDeleteClass;
    let modalPpmGroupClass;

    this.init = function () {
        clientId = '1';
        siteId = '1';
        $('#divPgrMain').removeClass('col-md-7').addClass('col-md-12');
        $('#divPgrDetails').hide();

        mzOption('optPgrClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        mzOption('optPgrSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: clientId}, 'required');

        $('#optPgrClientId').val(clientId);
        $('#optPgrSiteId').val(siteId);

        $('#optPgrClientId').on('change', function () {
            mzOptionStop('optPgrSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: $(this).val(), siteStatus: '1'}, 'required');
        });

        const vData = [
            {
                field_id: 'optPgrClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optPgrSiteId',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formPgrSearch');
        formValidate.registerFields(vData);

        $('#formPgrSearch').on('keyup change', function () {
            $('#btnPgrSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableTechnician = $('#dtPgrTechnician').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableTechnician.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrTechnicianEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableTechnician.row(parseInt(rowId)).data();
                        self.viewDetails(currentRow['ppmGroupId'], rowId);
                        $('#divPgrMain').removeClass('col-md-12').addClass('col-md-7');
                        $('#divPgrDetails').show();
                    }
                });
                $('.lnkPgrTechnicianDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableTechnician.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmGroupName', bSortable: false},
                    {mData: 'reportTo', bSortable: false},
                    {mData: 'totalUser', bSortable: false},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmGroupStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmGroupStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkPgrTechnicianEdit" id="lnkPgrTechnicianEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-trash-alt lnkPgrTechnicianDelete" id="lnkPgrTechnicianDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPgrTechnician_filter").hide();

        $('#btnPgrTechnicianAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '5');
        });

        $('#btnDtPgrTechnicianRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableTechnician();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableSupervisor = $('#dtPgrSupervisor').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableSupervisor.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrSupervisorEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSupervisor.row(parseInt(rowId)).data();
                        self.viewDetails(currentRow['ppmGroupId'], rowId);
                        $('#divPgrMain').removeClass('col-md-12').addClass('col-md-7');
                        $('#divPgrDetails').show();
                    }
                });
                $('.lnkPgrSupervisorDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableSupervisor.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmGroupName', bSortable: false},
                    {mData: 'reportTo', bSortable: false},
                    {mData: 'totalUser', bSortable: false},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmGroupStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmGroupStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkPgrSupervisorEdit" id="lnkPgrSupervisorEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-trash-alt lnkPgrSupervisorDelete" id="lnkPgrSupervisorDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPgrSupervisor_filter").hide();

        $('#btnPgrSupervisorAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '3');
        });

        $('#btnDtPgrSupervisorRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSupervisor();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableEngineer = $('#dtPgrEngineer').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableEngineer.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrEngineerEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableEngineer.row(parseInt(rowId)).data();
                        self.viewDetails(currentRow['ppmGroupId'], rowId);
                        $('#divPgrMain').removeClass('col-md-12').addClass('col-md-7');
                        $('#divPgrDetails').show();
                    }
                });
                $('.lnkPgrEngineerDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableEngineer.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmGroupName', bSortable: false},
                    {mData: 'totalUser', bSortable: false},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmGroupStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmGroupStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkPgrEngineerEdit" id="lnkPgrEngineerEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-trash-alt lnkPgrEngineerDelete" id="lnkPgrEngineerDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPgrEngineer_filter").hide();

        $('#btnPgrEngineerAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '4');
        });

        $('#btnDtPgrEngineerRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableEngineer();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTableTechnician();
        self.genTableSupervisor();
        self.genTableEngineer();
    };

    this.genTableTechnician = function () {
        const dataTechnician = mzAjaxRequest('ppm_group.php?roleId=5&siteId='+siteId, 'GET');
        oTableTechnician.clear().rows.add(dataTechnician).draw();
    };

    this.genTableSupervisor = function () {
        const dataSupervisor = mzAjaxRequest('ppm_group.php?roleId=3&siteId='+siteId, 'GET');
        oTableSupervisor.clear().rows.add(dataSupervisor).draw();
    };

    this.genTableEngineer = function () {
        const dataEngineer = mzAjaxRequest('ppm_group.php?roleId=4&siteId='+siteId, 'GET');
        oTableEngineer.clear().rows.add(dataEngineer).draw();
    };

    this.viewDetails = function (_ppmGroupId, _rowId) {
        ShowLoader();
        setTimeout(function () {
            try {
                alert(_ppmGroupId);
                alert(_rowId);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalPpmGroupClass = function (_modalPpmGroupClass) {
        modalPpmGroupClass = _modalPpmGroupClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}