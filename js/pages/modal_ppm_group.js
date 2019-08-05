function ModalPpmGroup() {

    const className = 'ModalPpmGroup';
    let self = this;
    let ppmGroupId = '';
    let rowRefresh = '';
    let classFrom;
    let siteId;
    let roleId;
    let refRole;
    let refClient;
    let refSite;
    let formValidate;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMpgName',
                type: 'text',
                name: 'Group Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'optMpgReportTo',
                type: 'select',
                name: 'Report To',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formMpg');
        formValidate.registerFields(vData);

        $('#formMpg').on('keyup change', function () {
            $('#btnMpgSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_ppm_group').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMpgSubmit').attr('disabled', true);
        });

        $('#btnMpgSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMpgName').val();
                        const reportTo = roleId === '5' || roleId === '3' ? $('#optMpgReportTo').val() : '';
                        const data = {
                            siteId: siteId,
                            roleId: roleId,
                            ppmGroupName: txtName,
                            reportTo: reportTo
                        };

                        mzAjaxRequest('ppm_group.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainPpmGroup') {
                            if (roleId === '5') {
                                classFrom.genTableTechnician();
                            } else if (roleId === '3') {
                                classFrom.genTableSupervisor();
                            } else if (roleId === '4') {
                                classFrom.genTableEngineer();
                            }
                        }
                        $('#modal_ppm_group').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function (_siteId, _roleId) {
        ppmGroupId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _roleId]);
                siteId = _siteId;
                roleId = _roleId;

                if (roleId === '5' || roleId === '3') {
                    const versionLocal = mzGetDataVersion();
                    const roleCur = roleId === '5' ? '3' : '4';
                    const refPpmGroup = mzGetLocalArray('gems_ppmGroup', versionLocal, 'ppmGroupId', [], 'ppm_group');
                    const defaultText = roleId === '5' ? 'Choose Supervisor Group' : 'Choose Engineer Group';
                    mzOptionStop('optMpgReportTo', refPpmGroup, defaultText, 'ppmGroupId', 'ppmGroupName', {roleId:roleCur, siteId:siteId, ppmGroupStatus: '1'}, 'required');
                    formValidate.enableField('optMpgReportTo');
                    $('#divMpgReportTo').show();
                } else {
                    formValidate.disableField('optMpgReportTo');
                    $('#divMpgReportTo').hide();
                }

                const clientId = refSite[siteId]['clientId'];
                mzSetFieldValue('MpgClient', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('MpgSite', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('MpgRole', refRole[roleId]['roleDesc'], 'text');

                $('#modal_ppm_group').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_ppmGroupId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_ppmGroupId]);
                mzAjaxRequest('ppm_group.php?action=delete_ppm_group&ppmGroupId='+_ppmGroupId, 'DELETE');
                if (classFrom.getClassName() === 'MainPpmGroup') {
                    classFrom.genTableTechnician();
                    classFrom.genTableSupervisor();
                    classFrom.genTableEngineer();
                    if (_ppmGroupId == classFrom.getPpmGroupId()) {
                        $('#divPgrMain').removeClass('col-md-7').addClass('col-md-12');
                        $('#divPgrDetails').hide();
                    }
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };
}