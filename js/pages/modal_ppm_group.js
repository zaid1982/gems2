function ModalPpmGroup() {

    const className = 'ModalPpmGroup';
    let self = this;
    let ppmGroupId = '';
    let rowRefresh = '';
    let classFrom;
    let siteId;
    let roleId;

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

        let formValidate = new MzValidate('formMpg');
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
                        const assetGroupId = $('#optMpgReportTo').val();
                        const txtName = $('#txtMpgName').val();
                        const data = {
                            assetGroupId: assetGroupId,
                            ppmGroupName: txtName
                        };

                        mzAjaxRequest('ppm_group.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainPpmGroup') {
                            classFrom.genTableTechnician();
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

                //mzOptionStop('optMpgReportTo', refUser, 'Choose Asset Group', 'userId', 'userFirstName', {assetGroupStatus: '1'}, 'required');
                mzSetFieldValue('MpgClient', 'sssss', 'text');
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
                if (classFrom.getClassName() === 'MainAssetCategory') {
                    classFrom.genTableAct(1);
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
}