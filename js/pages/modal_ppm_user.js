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
    let refUser;
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

        $('#modal_ppm_user').on('hidden.bs.modal', function(){
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
                        const userId = $('#optMpuUserId').val();
                        const data = {
                            action: 'add_ppm_group_user',
                            ppmGroupId: ppmGroupId,
                            userId: userId
                        };

                        mzAjaxRequest('ppm_group.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainPpmGroup') {
                            classFrom.genTableUser();
                            if (roleId === '5') {
                                classFrom.genTableTechnician();
                            } else if (roleId === '3') {
                                classFrom.genTableSupervisor();
                            } else if (roleId === '4') {
                                classFrom.genTableEngineer();
                            } else if (roleId === '8') {
                                classFrom.genTableWoTechnician();
                            }
                        }
                        $('#modal_ppm_user').modal('hide');
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

                if (roleId === '3') {
                    mzOptionStop('optMpuUserId', refUser, 'Choose Verifier', 'userId', 'userFullName', {roles: '#'+roleId, siteId: siteId}, 'required');
                } else if (roleId === '4') {
                    mzOptionStop('optMpuUserId', refUser, 'Choose Reviewer', 'userId', 'userFullName', {roles: '#'+roleId, siteId: siteId}, 'required');
                } else if (roleId === '5') {
                    mzOptionStop('optMpuUserId', refUser, 'Choose Executor', 'userId', 'userFullName', {roles: '#'+roleId, siteId: siteId}, 'required');
                } else if (roleId === '8') {
                    mzOptionStop('optMpuUserId', refUser, 'Choose WO Executor', 'userId', 'userFullName', {roles: '#'+roleId, siteId: siteId}, 'required');
                }

                const clientId = refSite[siteId]['clientId'];
                mzSetFieldValue('MpuClient', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('MpuSite', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('MpuRole', refRole[roleId]['roleDesc'], 'text');
                mzSetFieldValue('MpuGroupName', refPpmGroup[ppmGroupId]['ppmGroupName'], 'text');

                $('#modal_ppm_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_ppmGroupUserId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_ppmGroupUserId]);
                mzAjaxRequest('ppm_group.php?action=delete_ppm_group_user&ppmGroupUserId='+_ppmGroupUserId, 'DELETE');
                if (classFrom.getClassName() === 'MainPpmGroup') {
                    classFrom.genTableUser();
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

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}