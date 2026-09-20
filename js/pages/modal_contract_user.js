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

    function userHasTechnicianRole(row) {
        const roles = row['roles'];
        if (roles === null || roles === undefined || roles === '') {
            return false;
        }
        const parts = String(roles).split(',');
        for (let i = 0; i < parts.length; i++) {
            if (parts[i] === '5') {
                return true;
            }
        }
        return false;
    }

    function fillUserSelect(selected) {
        GemsUI.fillSelect(
            'optMcuUserId',
            rowsFromRef(refUser, 'userId', 'userFullName', userHasTechnicianRole, selected),
            'userId',
            function (row) {
                return row['userFullName'] || '';
            },
            'Choose Technician',
            selected
        );
    }

    function fillAssetGroupSelect(selected) {
        GemsUI.fillSelect(
            'optMcuAssetGroupId',
            rowsFromRef(refAssetGroup, 'assetGroupId', 'assetGroupName', null, selected),
            'assetGroupId',
            function (row) {
                return row['assetGroupName'] || '';
            },
            'Choose Asset Group',
            selected
        );
    }

    function fillLocationSelect(locationRef, selected) {
        GemsUI.fillSelect(
            'optMcuLocationCodeId',
            rowsFromRef(locationRef, 'locationCodeId', 'locationCodeName', null, selected),
            'locationCodeId',
            function (row) {
                return row['locationCodeName'] || '';
            },
            'Choose Location Code',
            selected
        );
    }

    this.init = function () {
        fillAssetGroupSelect('');
        fillUserSelect('');

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

        $('#modal_contract_user').on('hidden.bs.modal', function () {
            formValidate.clearValidation();
            $('#btnMcuSubmit').attr('disabled', true);
        });

        $('#btnMcuSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
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

                const siteId = refContract[contractId]['siteId'];
                const clientId = refSite[siteId]['clientId'];

                const versionLocal = mzGetDataVersion();
                const refLocationCode = mzGetLocalArray('gems_locationCode', versionLocal, 'locationCodeId', {siteId: siteId}, 'location_code');
                fillLocationSelect(refLocationCode, '');
                fillUserSelect('');
                fillAssetGroupSelect('');

                mzSetFieldValue('McuClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('McuSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('McuContractName', refContract[contractId]['contractName'], 'text');

                $('#lblMcuTitle').html('<i class="fas fa-plus me-2"></i>Add Technician Assigned');
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
                mzAjaxRequest('contract_user.php?contractUserId=' + _contractUserId, 'DELETE');
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
