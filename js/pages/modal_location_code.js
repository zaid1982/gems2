function ModalLocationCode() {

    const className = 'ModalLocationCode';
    let self = this;
    let locationCodeId = '';
    let contractId = '';
    let rowRefresh = '';
    let classFrom;
    let refClient;
    let refSite;
    let refContract;

    this.init = function () {
        const vData = [
            {
                field_id: 'txtMlcClientName',
                type: 'text',
                name: 'Client Name',
                validator: {}
            },
            {
                field_id: 'txtMlcSiteName',
                type: 'text',
                name: 'Site Name',
                validator: {}
            },
            {
                field_id: 'txtMlcContractName',
                type: 'text',
                name: 'Contract Name',
                validator: {}
            },
            {
                field_id: 'txtMlcLocationCodeName',
                type: 'text',
                name: 'Location Code',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'chkMlcStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMlc');
        formValidate.registerFields(vData);

        $('#formMlc').on('keyup change', function () {
            $('#btnMlcSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_location_code').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#btnMlcSubmit').attr('disabled',true);
        });

        $('#btnMlcSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMlcLocationCodeName').val();
                        const statusVal = $("input[name='chkMlcStatus']").is(":checked") ? '1' : '2';
                        const data = {
                            contractId: contractId,
                            locationCodeName: txtName,
                            locationCodeStatus: statusVal
                        };

                        if (locationCodeId === '') {
                            data['locationCodeId'] = mzAjaxRequest('location_code.php', 'POST', data);
                            if (classFrom.getClassName() === 'SectionContract') {
                                classFrom.addTableLocationCode(data);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('location_code.php?locationCodeId='+locationCodeId, 'PUT', data);
                            if (classFrom.getClassName() === 'SectionContract') {
                                classFrom.updateTableLocationCode(data, rowRefresh);
                            }
                        }
                        $('#modal_location_code').modal('hide');
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
                locationCodeId = '';
                rowRefresh = '';
                contractId = _contractId;

                const siteId = refContract[contractId]['siteId'];
                const clientId = refSite[siteId]['clientId'];

                mzSetFieldValue('MlcClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('MlcSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('MlcContractName', refContract[contractId]['contractName'], 'text');
                mzSetFieldValue('MlcStatus', '1', 'checkSingle', '1');

                $('#lblMlcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Location Code');
                $('#modal_location_code').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_locationCodeId, _contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_locationCodeId, _contractId, _rowRefresh]);
                locationCodeId = _locationCodeId;
                rowRefresh = _rowRefresh;
                contractId = _contractId;

                const siteId = refContract[contractId]['siteId'];
                const clientId = refSite[siteId]['clientId'];

                const dataMlc = mzAjaxRequest('location_code.php?locationCodeId='+locationCodeId, 'GET');
                mzSetFieldValue('MlcClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('MlcSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('MlcContractName', refContract[contractId]['contractName'], 'text');
                mzSetFieldValue('MlcLocationCodeName', dataMlc['locationCodeName'], 'text');
                mzSetFieldValue('MlcStatus', dataMlc['locationCodeStatus'], 'checkSingle', '1');

                $('#lblMlcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Location Code');
                $('#modal_location_code').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_locationCodeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_locationCodeId, _rowRefresh]);
                mzAjaxRequest('location_code.php?locationCodeId='+_locationCodeId, 'PUT', {action: 'deactivate'});
                const tempRow = {locationCodeStatus:'2'};
                if (classFrom.getClassName() === 'SectionContract') {
                    classFrom.updateTableLocationCode(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_locationCodeId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_locationCodeId, _rowRefresh]);
                mzAjaxRequest('location_code.php?locationCodeId='+_locationCodeId, 'PUT', {action: 'activate'});
                const tempRow = {locationCodeStatus:'1'};
                if (classFrom.getClassName() === 'SectionContract') {
                    classFrom.updateTableLocationCode(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_locationCodeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_locationCodeId]);
                mzAjaxRequest('location_code.php?locationCodeId='+_locationCodeId, 'DELETE');
                if (classFrom.getClassName() === 'SectionContract') {
                    classFrom.genTableLocationCode();
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
}