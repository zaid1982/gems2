function ModalContract() {

    const className = 'ModalContract';
    let self = this;
    let contractId = '';
    let rowRefresh = '';
    let classFrom;
    let refClient;
    let refSite;
    let dataMcr;

    function rowsFromRef(ref, idKey, labelKey, predicate, selectedId) {
        const rows = [];
        let hasSelected = false;
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            if (!row[labelKey] && (row[idKey] === undefined || row[idKey] === '')) {
                return true;
            }
            const isSelected = selectedId !== undefined && selectedId !== null && selectedId !== ''
                && String(row[idKey]) === String(selectedId);
            if (predicate && !predicate(row) && !isSelected) {
                return true;
            }
            if (isSelected) {
                hasSelected = true;
            }
            rows.push(row);
            return true;
        });
        if (selectedId !== undefined && selectedId !== null && selectedId !== '' && !hasSelected && ref && ref[selectedId]) {
            const extra = $.extend({}, ref[selectedId]);
            if (extra[idKey] === undefined || extra[idKey] === null || extra[idKey] === '') {
                extra[idKey] = selectedId;
            }
            rows.push(extra);
        }
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''));
        });
        return rows;
    }

    function fillClientSelect(activeOnly, selected) {
        GemsUI.fillSelect(
            'optMcrClientId',
            rowsFromRef(refClient, 'clientId', 'clientName', function (row) {
                if (activeOnly && String(row['clientStatus']) !== '1') {
                    return false;
                }
                return true;
            }, selected),
            'clientId',
            function (row) {
                return row['clientName'] || '';
            },
            'Choose Client',
            selected
        );
    }

    function fillSiteSelect(clientId, activeOnly, selected) {
        GemsUI.fillSelect(
            'optMcrSiteId',
            rowsFromRef(refSite, 'siteId', 'siteName', function (row) {
                if (clientId !== undefined && clientId !== null && clientId !== ''
                    && String(row['clientId']) !== String(clientId)) {
                    return false;
                }
                if (activeOnly && String(row['siteStatus']) !== '1') {
                    return false;
                }
                return true;
            }, selected),
            'siteId',
            function (row) {
                return row['siteName'] || '';
            },
            'Choose Site',
            selected
        );
    }

    function setParentSelectsDisabled(disabled) {
        $('#optMcrClientId, #optMcrSiteId').prop('disabled', !!disabled);
    }

    this.init = function () {
        mzDateFromTo('txtMcrContractDateStart', 'txtMcrContractDateEnd');

        $('#optMcrClientId').on('change', function () {
            fillSiteSelect($(this).val(), true, '');
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
                field_id: 'txtMcrContractDateStart',
                type: 'text',
                name: 'Date Start',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMcrContractDateEnd',
                type: 'text',
                name: 'Date Start',
                validator: {
                    notEmpty: true
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

        $('#modal_contract').on('hidden.bs.modal', function () {
            $('#btnMcrSubmit').attr('disabled', true);
            formValidate.clearValidation();
            mzDateFromToReset('txtMcrContractDateStart', 'txtMcrContractDateEnd');
            setParentSelectsDisabled(false);
            $('#txtMcrContractDateStart').prop('disabled', false);
        });

        $("input[name='radMcrContractType']:radio").on('change', function () {
            const value = $(this).val();
            if (value === '1') {
                mzSetDate('txtMcrContractDateEnd', dataMcr['contractDateEnd']);
                mzSetDate('txtMcrContractDateStart', dataMcr['contractDateStart']);
                $('#txtMcrContractDateStart').prop('disabled', false);
            } else if (value === '2') {
                mzSetDate('txtMcrContractDateEnd', dataMcr['contractDateEndExtend']);
                mzSetDate('txtMcrContractDateStart', dataMcr['contractDateStartExtend']);
                $('#txtMcrContractDateStart').prop('disabled', true);
            }
        });

        $('#btnMcrSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const clientId = $('#optMcrClientId').val();
                        const siteId = $('#optMcrSiteId').val();
                        const txtName = $('#txtMcrName').val();
                        const txtDesc = $('#txaMcrDesc').val();
                        const contractDateStart = mzConvertDate($('#txtMcrContractDateStart').val());
                        const contractDateEnd = mzConvertDate($('#txtMcrContractDateEnd').val());
                        const statusVal = $("input[name='chkMcrStatus']").is(':checked') ? '1' : '2';
                        let data = {
                            siteId: siteId,
                            contractName: txtName,
                            contractDesc: txtDesc,
                            contractDateStart: contractDateStart,
                            contractDateEnd: contractDateEnd,
                            contractStatus: statusVal
                        };

                        let tempRow = {};
                        if (contractId === '') {
                            data['contractDateStart'] = contractDateStart;
                            contractId = mzAjaxRequest('contract.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainContract') {
                                tempRow['contractId'] = contractId;
                                tempRow['clientId'] = clientId;
                                tempRow['siteId'] = siteId;
                                tempRow['contractName'] = txtName;
                                tempRow['contractDesc'] = txtDesc;
                                tempRow['contractDateStart'] = contractDateStart.replace('-', '/');
                                tempRow['contractDateEnd'] = contractDateEnd.replace('-', '/');
                                tempRow['contractStatus'] = statusVal;
                                classFrom.addTableCcr(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            const contractType = $("input[name='radMcrContractType']:checked").val();
                            if (contractType === '1') {
                                data['contractDateStart'] = contractDateStart;
                                tempRow['contractDateStart'] = contractDateStart.replaceAll('-', '/');
                            }
                            mzAjaxRequest('contract.php?contractId=' + contractId, 'PUT', data);
                            if (contractType === '2') {
                                data['contractId'] = contractId;
                                mzFetch('ppm_v3/contractExtension', 'PUT', data);
                                toastr['info']('PPM Task is currently being processed to the date the contract extended. Please check the PPM task status in the PPM Management menu.', 'INFORMATION');
                            }
                            tempRow['contractId'] = contractId;
                            tempRow['contractName'] = txtName;
                            tempRow['contractDesc'] = txtDesc;
                            tempRow['contractDateEnd'] = contractDateEnd.replaceAll('-', '/');
                            tempRow['contractStatus'] = statusVal;
                            classFrom.updateTableCcr(tempRow, rowRefresh);
                        }
                        $('#modal_contract').modal('hide');
                    }
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
                fillClientSelect(true, '');
                fillSiteSelect('', true, '');
                mzSetFieldValue('McrStatus', '1', 'checkSingle', '1');
                mzSetFieldValue('McrContractType', '1', 'radio');
                $('input[name="radMcrContractType"]').prop('disabled', true);
                $('#lblMcrTitle').html('<i class="fas fa-plus me-2"></i>Add Contract');
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
                mzSetFieldValue('McrContractType', '1', 'radio');
                $('input[name="radMcrContractType"]').prop('disabled', false);
                mzCheckFuncParam([_contractId, _rowRefresh]);
                contractId = _contractId;
                rowRefresh = _rowRefresh;

                dataMcr = mzAjaxRequest('contract.php?contractId=' + contractId, 'GET');
                const siteId = dataMcr['siteId'];
                const clientId = refSite[siteId]['clientId'];
                fillClientSelect(false, clientId);
                fillSiteSelect('', false, siteId);
                mzSetFieldValue('McrName', dataMcr['contractName'], 'text');
                mzSetFieldValue('McrDesc', dataMcr['contractDesc'], 'textarea');
                mzSetDate('txtMcrContractDateEnd', dataMcr['contractDateEnd']);
                mzSetDate('txtMcrContractDateStart', dataMcr['contractDateStart']);
                mzSetFieldValue('McrStatus', dataMcr['contractStatus'], 'checkSingle', '1');

                setParentSelectsDisabled(true);

                $('#lblMcrTitle').html('<i class="far fa-edit me-2"></i>Edit Contract');
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
                mzAjaxRequest('contract.php?contractId=' + _contractId, 'PUT', {action: 'deactivate'});
                const tempRow = {contractStatus: '2'};
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
                mzAjaxRequest('contract.php?contractId=' + _contractId, 'PUT', {action: 'activate'});
                const tempRow = {contractStatus: '1'};
                if (classFrom.getClassName() === 'MainContract') {
                    classFrom.updateTableCcr(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_contractId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_contractId]);
                mzAjaxRequest('contract.php?contractId=' + _contractId, 'DELETE');
                if (classFrom.getClassName() === 'MainContract') {
                    classFrom.genTableCcr(1);
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
