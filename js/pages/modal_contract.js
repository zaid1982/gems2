function ModalContract() {

    const className = 'ModalContract';
    let self = this;
    let contractId = '';
    let rowRefresh = '';
    let classFrom;
    let refClient;
    let refSite;

    this.init = function () {
        $('#optMcrClientId').on('change', function () {
            mzOption('optMcrSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: $(this).val(), siteStatus: '1'}, 'required');
        });

        const vData = [
            {
                field_id: 'optMcrClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMcrSiteId',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMcrName',
                type: 'text',
                name: 'Contract Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txaMcrDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMcrStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMcr');
        formValidate.registerFields(vData);

        $('#formMcr').on('keyup change', function () {
            $('#btnMcrSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_contract').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('#optMcrClientId').material_select('destroy');
            $('#optMcrClientId').prop('disabled', false);
            $('#optMcrClientId').material_select();

            $('#optMcrSiteId').material_select('destroy');
            $('#optMcrSiteId').prop('disabled', false);
            $('#optMcrSiteId').material_select();
        });

        $('#btnMcrSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        throw new Error(_ALERT_MSG_VALIDATION);
                    }
                    const statusVal = $("input[name='chkMcrStatus']").is(":checked") ? '1' : '2';
                    const data = {
                        siteId :$('#optMcrSiteId').val(),
                        contractName: $('#txtMcrName').val(),
                        contractDesc: $('#txaMcrDesc').val(),
                        contractStatus: statusVal
                    };

                    let tempRow = {};
                    if (contractId === '') {
                        contractId = mzAjaxRequest('contract.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainContract') {
                            tempRow['contractId'] = contractId;
                            tempRow['clientId'] = $('#optMcrClientId').val();
                            tempRow['siteId'] = $('#optMcrSiteId').val();
                            tempRow['contractName'] = $('#txtMcrName').val();
                            tempRow['contractDesc'] = $('#txaMcrDesc').val();
                            tempRow['contractStatus'] = statusVal;
                            classFrom.addTableCcr(tempRow);
                        }
                    } else {
                        data['action'] = 'update';
                        mzAjaxRequest('contract.php?contractId='+contractId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainContract') {
                            tempRow['contractId'] = contractId;
                            tempRow['contractName'] = $('#txtMcrName').val();
                            tempRow['contractDesc'] = $('#txaMcrDesc').val();
                            tempRow['contractStatus'] = statusVal;
                            classFrom.updateTableCcr(tempRow, rowRefresh);
                        }
                    }
                    $('#modal_contract').modal('hide');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        contractId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMcrClientId', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');

                $('#lblMcrTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add Contract');
                $('#btnMcrSubmit').html('<i class="far fa-paper-plane ml-1"></i> Submit');
                $('#btnMcrSubmit').attr('disabled', true);
                $('#modal_contract').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.edit = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzOption('optMcrClientId', refClient, 'Choose Client', 'clientId', 'clientName');
                mzOption('optMcrSiteId', refSite, 'Choose Site', 'siteId', 'siteName');
                mzCheckFuncParam([_contractId, _rowRefresh]);
                contractId = _contractId;
                rowRefresh = _rowRefresh;

                const dataMcr = mzAjaxRequest('contract.php?contractId='+contractId, 'GET');
                const siteId = dataMcr['siteId'];
                mzSetFieldValue('McrSiteId', siteId, 'select', 'Client *');
                mzSetFieldValue('McrClientId', refSite[siteId]['clientId'], 'select', 'Client *');
                mzSetFieldValue('McrName', dataMcr['contractName'], 'text');
                mzSetFieldValue('McrDesc', dataMcr['contractDesc'], 'textarea');
                mzSetFieldValue('McrStatus', dataMcr['contractStatus'], 'checkSingle', '1');

                $('#optMcrClientId').material_select('destroy');
                $('#optMcrClientId').prop('disabled', true);
                $('#optMcrClientId').material_select();

                $('#optMcrSiteId').material_select('destroy');
                $('#optMcrSiteId').prop('disabled', true);
                $('#optMcrSiteId').material_select();

                $('#lblMcrTitle').html('<i class="far fa-edit text-white"></i> &nbsp;Edit Contract');
                $('#btnMcrSubmit').html('<i class="far fa-paper-plane ml-1"></i> Submit');
                $('#btnMcrSubmit').attr('disabled', true);
                $('#modal_contract').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.deactivate = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId, _rowRefresh]);
                mzAjaxRequest('contract.php?contractId='+_contractId, 'PUT', {action: 'deactivate'});
                const tempRow = {contractStatus:'2'};
                if (classFrom.getClassName() === 'MainContract') {
                    classFrom.updateTableCcr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId, _rowRefresh]);
                mzAjaxRequest('contract.php?contractId='+_contractId, 'PUT', {action: 'activate'});
                const tempRow = {contractStatus:'1'};
                if (classFrom.getClassName() === 'MainContract') {
                    classFrom.updateTableCcr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId, _rowRefresh]);
                mzAjaxRequest('contract.php?contractId='+_contractId, 'DELETE');
                if (classFrom.getClassName() === 'MainContract') {
                    classFrom.deleteTableCcr(_rowRefresh);
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
}