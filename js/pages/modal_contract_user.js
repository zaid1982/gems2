function ModalContractUser() {

    const className = 'ModalContractUser';
    let self = this;
    let contractUserId = '';
    let contractId = '';
    let locationCodeId = '';
    let rowRefresh = '';
    let classFrom;
    let refClient;
    let refSite;
    let refContract;
    let refAssetGroup;
    let refUser;

    this.init = function () {
        mzOption('optMcuAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName', [], 'required');
        mzOption('optMcuUserId', refUser, 'Choose Technician', 'userId', 'userFullName', {roles: '#5'}, 'required');

        const vData = [
            {
                field_id: 'txtMcuClientName',
                type: 'text',
                name: 'Client Name',
                validator: {}
            },
            {
                field_id: 'txtMcuSiteName',
                type: 'text',
                name: 'Site Name',
                validator: {}
            },
            {
                field_id: 'txtMcuContractName',
                type: 'text',
                name: 'Contract Name',
                validator: {}
            },
            {
                field_id: 'optMcuLocationCodeId',
                type: 'select',
                name: 'Location Code',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMcuUserId',
                type: 'select',
                name: 'Technician Assigned',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMcuAssetGroupId',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formMcu');
        formValidate.registerFields(vData);

        $('#formMcu').on('keyup change', function () {
            $('#btnMcuSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_contract_user').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMcuSubmit').attr('disabled',true);
        });

        $('#btnMcuSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = {
                            contractId: contractId,
                            locationCodeId: $('#optMcuLocationCodeId').val(),
                            userId: $('#optMcuUserId').val(),
                            assetGroupId: $('#optMcuAssetGroupId').val()
                        };

                        mzAjaxRequest('contract_user.php', 'POST', data);
                        if (classFrom.getClassName() === 'SectionContract') {
                            classFrom.genTableLocationUser();
                        }
                        $('#modal_contract_user').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function (_contractId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId]);
                contractUserId = '';
                rowRefresh = '';
                contractId = _contractId;
                locationCodeId = '';

                const versionLocal = mzGetDataVersion();
                const refLocationCode = mzGetLocalArray('gems_locationCode', versionLocal, 'locationCodeId', {contractId: contractId}, 'location_code');
                mzOptionStop('optMcuLocationCodeId', refLocationCode, 'Choose Location Code', 'locationCodeId', 'locationCodeName', [], 'required');

                const siteId = refContract[contractId]['siteId'];
                const clientId = refSite[siteId]['clientId'];

                mzSetFieldValue('McuClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('McuSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('McuContractName', refContract[contractId]['contractName'], 'text');

                $('#lblMcuTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Technician Assigned');
                $('#modal_contract_user').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_contractUserId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractUserId]);
                mzAjaxRequest('contract_user.php?contractUserId='+_contractUserId, 'DELETE');
                if (classFrom.getClassName() === 'SectionContract') {
                    classFrom.genTableLocationUser();
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

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}