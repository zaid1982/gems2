function ModalStore () {

    const className = 'ModalStore';
    let self = this;
    let formValidate;
    let storeId;
    let vData;
    let classFrom;
    let refClient;
    let refSite;

    this.init = function () {
        mzOption('optMsrClient', refClient, 'Choose Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');

        $('#optMsrClient').on('change', function () {
            mzOptionStop('optMsrSite', refSite, 'Choose Site', 'siteId', 'siteName', {siteStatus: '1', clientId: $(this).val()}, 'required');
        });

        vData = [
            {
                field_id: 'optMsrClient',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMsrSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMsrName',
                type: 'text',
                name: 'Store Name',
                validator: {
                    maxLength: 100,
                    notEmpty: true
                }
            },
            {
                field_id: 'txaMsrDesc',
                type: 'text',
                name: 'Store Description',
                validator: {
                    maxLength: 200
                }
            },
            {
                field_id: 'chkMsrStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        formValidate = new MzValidate('formMsr');
        formValidate.registerFields(vData);

        $('#btnMsrSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            storeName: $('#txtMsrName').val(),
                            storeDesc: $('#txaMsrDesc').val(),
                            siteId: $('#optMsrSite').val(),
                            storeStatus: $("input[name='chkMsrStatus']").is(":checked") ? '1' : '2'
                        };
                        mzAjaxRequest2('store/'+storeId, 'PUT', data);
                        classFrom.genTable();
                        $('#modal_store').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMsrSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            storeName: $('#txtMsrName').val(),
                            storeDesc: $('#txaMsrDesc').val(),
                            siteId: $('#optMsrSite').val(),
                            storeStatus: $("input[name='chkMsrStatus']").is(":checked") ? '1' : '2'
                        };
                        mzAjaxRequest2('store', 'POST', data);
                        classFrom.genTable();
                        $('#modal_store').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                storeId = '';
                mzOptionStopClear('optMsrSite', 'Choose Site', 'required');
                formValidate.clearValidation();

                mzSetFieldValue('MsrStatus', '1', 'checkSingle', '1');
                $('#lblMsrModalTitle').html('<i class="fas fa-plus text-white"></i> Add Inventory Store');
                $('#btnMsrSave').hide();
                $('#btnMsrSubmit').show();

                $('#modal_store').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_storeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_storeId]);
                storeId = _storeId;
                formValidate.clearValidation();

                const store = mzAjaxRequest2('store/'+storeId, 'GET');
                const clientId = refSite[store['siteId']]['clientId'];
                mzOptionStop('optMsrSite', refSite, 'Choose Site', 'siteId', 'siteName', {siteStatus: '1', clientId: clientId}, 'required');
                mzSetFieldValue('MsrName', store['storeName'], 'text');
                mzSetFieldValue('MsrDesc', store['storeDesc'], 'textarea');
                mzSetFieldValue('MsrClient', clientId, 'select');
                mzSetFieldValue('MsrSite', store['siteId'], 'select');
                mzSetFieldValue('MsrStatus', store['storeStatus'], 'checkSingle', '1');

                $('#lblMsrModalTitle').html('<i class="fas fa-edit text-white"></i> Edit Inventory Store');
                $('#btnMsrSubmit').hide();
                $('#btnMsrSave').show();

                $('#modal_store').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_storeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_storeId]);
                mzAjaxRequest2('store/'+_storeId, 'DELETE');
                classFrom.genTable();
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