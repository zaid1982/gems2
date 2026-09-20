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

    function rowsFromRef(ref, idKey, labelKey, predicate) {
        const rows = [];
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            if (predicate && !predicate(row)) {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''), 'en', {numeric: true});
        });
        return rows;
    }

    function userHasRole(row, roleCur) {
        const roles = row['roles'];
        if (roles === null || roles === undefined || roles === '') {
            return false;
        }
        const parts = String(roles).split(',');
        for (let i = 0; i < parts.length; i++) {
            if (parts[i] === String(roleCur)) {
                return true;
            }
        }
        return false;
    }

    function fillUserSelect(roleCur, siteKey, placeholder) {
        GemsUI.fillSelect(
            'optMpuUserId',
            rowsFromRef(refUser, 'userId', 'userFullName', function (row) {
                if (String(row['siteId']) !== String(siteKey)) {
                    return false;
                }
                return userHasRole(row, roleCur);
            }),
            'userId',
            function (row) { return row['userFullName'] || ''; },
            placeholder
        );
    }

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

        $('#modal_ppm_user').on('hidden.bs.modal', function () {
            formValidate.clearValidation();
            $('#btnMpuSubmit').attr('disabled', true);
        });

        $('#btnMpuSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
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
                    fillUserSelect(roleId, siteId, 'Choose Reviewer');
                } else if (roleId === '4') {
                    fillUserSelect(roleId, siteId, 'Choose Verifier');
                } else if (roleId === '5') {
                    fillUserSelect(roleId, siteId, 'Choose Executor');
                } else if (roleId === '8') {
                    fillUserSelect(roleId, siteId, 'Choose WO Executor');
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
