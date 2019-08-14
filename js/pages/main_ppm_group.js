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
    let roleId;
    let oTableTechnician;
    let oTableSupervisor;
    let oTableEngineer;
    let oTableWoTechnician;
    let oTableWoVerifier;
    let oTableUser;
    let modalConfirmDeleteClass;
    let modalPpmGroupClass;
    let modalPpmUserClass;
    let ppmGroupId = '';
    let rowRefresh = '';
    let formValidateInfo;

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

        oTableWoTechnician = $('#dtPgrWoTechnician').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableWoTechnician.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrWoTechnicianEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWoTechnician.row(parseInt(rowId)).data();
                        self.viewDetails(currentRow['ppmGroupId'], rowId);
                        $('#divPgrMain').removeClass('col-md-12').addClass('col-md-7');
                        $('#divPgrDetails').show();
                    }
                });
                $('.lnkPgrWoTechnicianDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWoTechnician.row(parseInt(rowId)).data();
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
                            let label = '<a><i class="fas fa-edit lnkPgrWoTechnicianEdit" id="lnkPgrWoTechnicianEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-trash-alt lnkPgrWoTechnicianDelete" id="lnkPgrWoTechnicianDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPgrWoTechnician_filter").hide();

        $('#btnPgrWoTechnicianAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '8');
        });

        $('#btnDtPgrWoTechnicianRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoTechnician();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableWoVerifier = $('#dtPgrWoVerifier').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableWoVerifier.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrWoVerifierEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWoVerifier.row(parseInt(rowId)).data();
                        self.viewDetails(currentRow['ppmGroupId'], rowId);
                    }
                });
                $('.lnkPgrWoVerifierDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableWoVerifier.row(parseInt(rowId)).data();
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
                            let label = '<a><i class="fas fa-edit lnkPgrWoVerifierEdit" id="lnkPgrWoVerifierEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-trash-alt lnkPgrWoVerifierDelete" id="lnkPgrWoVerifierDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPgrWoVerifier_filter").hide();

        $('#btnPgrWoVerifierAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '9');
        });

        $('#btnDtPgrWoVerifierRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableWoVerifier();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        const vDataInfo = [
            {
                field_id: 'txtPgrInfoGroupName',
                type: 'text',
                name: 'Group Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'optPgrInfoReportTo',
                type: 'select',
                name: 'Report To',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'chkPgrInfoStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        formValidateInfo = new MzValidate('formPgrInfo');
        formValidateInfo.registerFields(vDataInfo);

        $('#formPgrInfo').on('keyup change', function () {
            $('#btnPgrInfoUpdate').attr('disabled', !formValidateInfo.validateForm());
        });

        $('#btnPgrInfoUpdate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidateInfo.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtPgrInfoGroupName').val();
                        const reportTo = roleId === '5' || roleId === '3' ? $('#optPgrInfoReportTo').val() : '';
                        const statusVal = $("input[name='chkPgrInfoStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            ppmGroupId: ppmGroupId,
                            ppmGroupName: txtName,
                            siteId: siteId,
                            roleId: roleId,
                            reportTo: reportTo,
                            ppmGroupStatus: statusVal
                        };

                        data['action'] = 'update';
                        mzAjaxRequest('ppm_group.php?ppmGroupId='+ppmGroupId, 'PUT', data);
                        if (roleId === '5') {
                            self.genTableTechnician();
                        } else if (roleId === '3') {
                            self.genTableSupervisor();
                        } else if (roleId === '4') {
                            self.genTableEngineer();
                        } else if (roleId === '8') {
                            self.genTableWoTechnician();
                        } else if (roleId === '9') {
                            self.genTableWoVerifier();
                        }
                        $('#btnPgrInfoUpdate').attr('disabled', true);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableUser = $('#dtPgrUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableUser.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrUserDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableUser.row(parseInt(rowId)).data();
                        modalConfirmDeleteClass.delete(currentRow['ppmGroupUserId'], modalPpmUserClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'userId', bSortable: false,
                        mRender: function (data) {
                            return refUser[data]['userFirstName'];
                        }},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            const userId = row['userId'];
                            const userStatus = refUser[userId]['userStatus'];
                            return '<h6><span class="badge badge-pill '+refStatus[userStatus]['statusColor']+' z-depth-2">'+refStatus[userStatus]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            return '<a><i class="fas fa-trash-alt lnkPgrUserDelete" id="lnkPgrUserDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                        }
                    }
                ]
        });
        $("#dtPgrUser_filter").hide();

        $('#btnPgrUserAdd').on('click', function () {
            modalPpmUserClass.add(ppmGroupId, siteId, roleId);
        });

        $('#btnDtPgrUserRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableUser();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnPgrSearch').on('click', function () {
            siteId = $('#optPgrSiteId').val();
            self.genTableTechnician();
            self.genTableSupervisor();
            self.genTableEngineer();
            $('#divPgrMain').removeClass('col-md-7').addClass('col-md-12');
            $('#divPgrDetails').hide();
        });

        self.genTableTechnician();
        self.genTableSupervisor();
        self.genTableEngineer();
    };

    this.viewDetails = function (_ppmGroupId, _rowId) {
        ShowLoader();
        setTimeout(function () {
            try {
                formValidateInfo.clearValidation();
                $('#btnPgrInfoUpdate').attr('disabled', true);

                mzCheckFuncParam([_ppmGroupId, _rowId]);
                ppmGroupId = _ppmGroupId;
                rowRefresh = _rowId;

                const dataPpmGroup = mzAjaxRequest('ppm_group.php?ppmGroupId='+ppmGroupId, 'GET');
                roleId = dataPpmGroup['roleId'];
                siteId = dataPpmGroup['siteId'];
                clientId = refSite[siteId]['clientId'];

                if (roleId == '5' || roleId == '3') {
                    const versionLocal = mzGetDataVersion();
                    const roleCur = roleId === '5' ? '3' : '4';
                    const refPpmGroup = mzGetLocalArray('gems_ppmGroup', versionLocal, 'ppmGroupId', [], 'ppm_group');
                    const defaultText = roleId === '5' ? 'Choose Supervisor Group' : 'Choose Engineer Group';
                    mzOptionStop('optPgrInfoReportTo', refPpmGroup, defaultText, 'ppmGroupId', 'ppmGroupName', {roleId:roleCur, siteId:siteId, ppmGroupStatus: '1'}, 'required');
                    formValidateInfo.enableField('optPgrInfoReportTo');
                    $('#divPgrInfoReportTo').show();
                } else {
                    formValidateInfo.disableField('optPgrInfoReportTo');
                    $('#divPgrInfoReportTo').hide();
                }

                mzSetFieldValue('PgrInfoReportTo', dataPpmGroup['ppmGroupReportTo'], 'select', 'Report to *');
                mzSetFieldValue('PgrInfoGroupName', dataPpmGroup['ppmGroupName'], 'text');
                mzSetFieldValue('PgrInfoRoleDesc', refRole[roleId]['roleDesc'], 'text');
                mzSetFieldValue('PgrInfoClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('PgrInfoSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('PgrInfoStatus', dataPpmGroup['ppmGroupStatus'], 'checkSingle', '1');

                self.genTableUser();

                $('#divPgrMain').removeClass('col-md-12').addClass('col-md-7');
                $('#divPgrDetails').show();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
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

    this.genTableWoTechnician = function () {
        const dataWoTechnician = mzAjaxRequest('ppm_group.php?roleId=8&siteId='+siteId, 'GET');
        oTableWoTechnician.clear().rows.add(dataWoTechnician).draw();
    };

    this.genTableWoVerifier = function () {
        const dataWoVerifier = mzAjaxRequest('ppm_group.php?roleId=9&siteId='+siteId, 'GET');
        oTableWoVerifier.clear().rows.add(dataWoVerifier).draw();
    };

    this.genTableUser = function () {
        const dataUser = mzAjaxRequest('ppm_group.php?type=ppm_group_user&ppmGroupId='+ppmGroupId, 'GET');
        oTableUser.clear().rows.add(dataUser).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.getPpmGroupId = function () {
        return ppmGroupId;
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

    this.setModalPpmUserClass = function (_modalPpmUserClass) {
        modalPpmUserClass = _modalPpmUserClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}