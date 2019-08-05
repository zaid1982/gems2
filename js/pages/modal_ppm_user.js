function ModalPpmUser() {

    const className = 'ModalPpmUser';
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
                field_id: 'optMpuUserId',
                type: 'select',
                name: 'Report To',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formMpu');
        formValidate.registerFields(vData);

        $('#formMpu').on('keyup change', function () {
            $('#btnMpuSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_ppm_group').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMpuSubmit').attr('disabled', true);
        });

        $('#btnMpuSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMpuName').val();
                        const reportTo = roleId === '5' || roleId === '3' ? $('#optMpuReportTo').val() : '';
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

    this.add = function (_ppmGroupId, _siteId, _roleId) {
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_ppmGroupId, _siteId, _roleId]);
                ppmGroupId = _ppmGroupId;
                siteId = _siteId;
                roleId = _roleId;

                const versionLocal = mzGetDataVersion();
                const refPpmGroup = mzGetLocalArray('gems_ppmGroup', versionLocal, 'ppmGroupId', [], 'ppm_group');
                mzOptionStop('optMpuReportTo', refPpmGroup, 'Choose Assigned User', 'ppmGroupId', 'ppmGroupName', {roleId:roleId, siteId:siteId, ppmGroupStatus: '1'}, 'required');


                const clientId = refSite[siteId]['clientId'];
                mzSetFieldValue('MpuClient', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('MpuSite', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('MpuRole', refRole[roleId]['roleDesc'], 'text');

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
                mzAjaxRequest('ppm_group.php?ppmGroupId='+_ppmGroupId, 'DELETE');
                if (classFrom.getClassName() === 'MainPpmGroup') {
                    classFrom.genTableTechnician();
                    classFrom.genTableSupervisor();
                    classFrom.genTableEngineer();
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