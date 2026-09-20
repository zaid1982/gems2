function MainPpmSetForm () {

    const className = 'MainPpmSetForm';
    let self = this;
    let formValidate;
    let submitType = '';
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let ppmSetId;
    let currentContractId;
    let currentAssetGroupId;
    let currentAssetCategoryId;
    let currentAssetTypeId;

    let dtPpsa;

    let modalPpmSetSelectAssetClass;
    let modalConfirmDeleteClass;

    const vData = [
        {
            field_id: 'txtMpsName',
            type: 'text',
            name: 'PPM Set Name',
            validator: {
                notEmpty: true,
                maxLength: 200
            }
        },
        {
            field_id: 'txaMpsDesc',
            type: 'textarea',
            name: 'Description',
            validator: {
                notEmpty: false,
                maxLength: 1000
            }
        },
        {
            field_id: 'optMpsAssetGroup',
            type: 'select',
            name: 'Asset Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsAssetCategory',
            type: 'select',
            name: 'Asset Category',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsAssetType',
            type: 'select',
            name: 'Asset Type',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMpsPpmGroupId',
            type: 'select',
            name: 'PPM Executor Group',
            validator: {
                notEmpty: true
            }
        }
    ];

    const userSiteId = mzGetUserInfoByParam('siteId');

    function rowsFromRef(ref, idKey) {
        const rows = [];
        if (!ref) {
            return rows;
        }
        $.each(ref, function (id, rec) {
            if (!rec || typeof rec !== 'object') {
                return;
            }
            const row = $.extend({}, rec);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = String(id);
            }
            rows.push(row);
        });
        return rows;
    }

    function filterRows(rows, filters) {
        return (rows || []).filter(function (row) {
            const keys = Object.keys(filters || {});
            for (let i = 0; i < keys.length; i++) {
                if (String(row[keys[i]]) !== String(filters[keys[i]])) {
                    return false;
                }
            }
            return true;
        });
    }

    function fillNamed(id, rows, valueKey, labelKey, placeholder, selected) {
        const sorted = (rows || []).slice().sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''));
        });
        GemsUI.fillSelect(id, sorted, valueKey, function (row) {
            return row[labelKey] || '';
        }, placeholder, selected);
        $('#' + id + 'Err').html('');
    }

    this.init = function () {
        $('#optMpsAssetGroup').on('change', function () {
            const id = $(this).val();
            try {
                fillNamed(
                    'optMpsAssetCategory',
                    filterRows(rowsFromRef(refAssetCategory, 'assetCategoryId'), {assetGroupId: id, assetCategoryStatus: '1'}),
                    'assetCategoryId',
                    'assetCategoryName',
                    'Select Asset Category'
                );
                mzSetFieldValue('optMpsAssetType', '', 'select');
                mzSetFieldValue('optMpsPpmGroupId', '', 'select');
                mzDisableSelect('optMpsAssetCategory', false);
                mzDisableSelect('optMpsAssetType', true);
                mzDisableSelect('optMpsPpmGroupId', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#optMpsAssetCategory').on('change', function () {
            const id = $(this).val();
            try {
                fillNamed(
                    'optMpsAssetType',
                    filterRows(rowsFromRef(refAssetType, 'assetTypeId'), {assetCategoryId: id, assetTypeStatus: '1'}),
                    'assetTypeId',
                    'assetTypeName',
                    'Select Asset Type'
                );
                mzDisableSelect('optMpsAssetType', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#btnMpsSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                let data = {
                    ppmSetName: mzNullString('txtMpsName'),
                    ppmSetDesc: mzNullString('txaMpsDesc'),
                    assetGroupId: mzNullInt('optMpsAssetGroup'),
                    assetCategoryId: mzNullInt('optMpsAssetCategory'),
                    assetTypeId: mzNullInt('optMpsAssetType'),
                    ppmGroupId: mzNullInt('optMpsPpmGroupId')
                };
                ShowLoader(); setTimeout(function () {
                    if (submitType === 'add') {
                        mzFetch('api/ppm.php?action=create_ppm_set', 'POST', data).then(res => {
                            toastr['success']('PPM Set "' + data.ppmSetName + '" successfully created!', _ALERT_TITLE_SUCCESS);
                            window.location.href = 'ppm_set_form.html?ppmSetId=' + res.ppmSetId;
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    } else if (submitType === 'put') {
                        data['ppmSetId'] = parseInt(ppmSetId);
                        data['action'] = 'update_ppm_set';

                        try
                        {
                            toastr['success']('PPM Set "' + data.ppmSetName + '" successfully updated!', _ALERT_TITLE_SUCCESS);
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        } finally {
                            HideLoader();
                        }
                    }
                }, 200);
            }
        });

        formValidate = new MzValidate('formMps');
        formValidate.registerFields(vData);

        const urlParams = new URLSearchParams(window.location.search);
        const urlPpmSetId = urlParams.get('ppmSetId');

        if (urlPpmSetId) {
            ppmSetId = parseInt(urlPpmSetId);
            self.loadPpmSetForEdit(ppmSetId);
        } else {
            self.initForAdd();
        }

        dtPpsa = $('#dtPpsa').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-cube', 'No assets in this PPM set.'),
            pageLength: 5,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { className: 'text-center', targets: [0, 4] },
                { className: 'noVis', targets: [0] }
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns: [
                { mData: null },
                { mData: 'assetNo' },
                { mData: 'assetName' },
                { mData: 'assetLocationDesc' },
                {
                    mData: null, bSortable: false, mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return '';
                        }
                        return GemsUI.actionBtn({
                            cls: 'btn-remove-asset',
                            icon: 'fas fa-minus-circle',
                            title: 'Remove',
                            extra: 'data-ppmset-asset-id="' + row.ppmSetAssetId + '" data-asset-id="' + row.assetId + '" data-asset-no="' + GemsUI.escape(row.assetNo || '') + '"'
                        });
                    }
                }
            ]
        });
        GemsUI.bindDtTooltips('#dtPpsa');

        $('#btnAddAssetsToSet').on('click', function () {
            if (submitType === 'put' && ppmSetId) {
                if (!currentAssetGroupId || !currentAssetCategoryId || !currentAssetTypeId) {
                    toastr['warning']('PPM Set Asset Group, Category, or Type is not defined. Cannot add assets.', _ALERT_TITLE_WARNING);
                    return;
                }
                modalPpmSetSelectAssetClass.show(ppmSetId, currentAssetGroupId, currentAssetCategoryId, currentAssetTypeId);
            } else {
                toastr['warning']('Please save the PPM Set first before adding assets.', _ALERT_TITLE_WARNING);
            }
        });

        $('#dtPpsa').on('click', '.btn-remove-asset', function () {
            const btn = $(this);
            const assetIdToRemove = btn.data('asset-id');
            const assetNoToRemove = btn.data('asset-no');

            const confirmationMessage = 'Are you sure you want to remove Asset No: ' + assetNoToRemove + ' from this PPM Set?';

            if (window.confirm(confirmationMessage)) {
                self.removeAssetFromPpmSetConfirmed(ppmSetId, [assetIdToRemove]);
            } else {
                toastr['info']('Asset removal cancelled.', _ALERT_TITLE_INFO);
            }
        });
    };

    this.initForAdd = function () {
        submitType = 'add';
        formValidate.clearValidation();
        self.resetFormFields();

        fillNamed(
            'optMpsAssetGroup',
            filterRows(rowsFromRef(refAssetGroup, 'assetGroupId'), {assetGroupStatus: '1'}),
            'assetGroupId',
            'assetGroupName',
            'Select Asset Group'
        );
        mzDisableSelect('optMpsAssetGroup', false);

        fillNamed(
            'optMpsPpmGroupId',
            filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: userSiteId, ppmGroupStatus: '1'}),
            'ppmGroupId',
            'ppmGroupName',
            'Select PPM Executor Group'
        );
        mzDisableSelect('optMpsPpmGroupId', false);

        $('#h5PpmSetFormTitle').html('<i class="fas fa-plus me-2"></i> Add New PPM Set');
        $('#sectionAssetsInSet').hide();
    };

    this.loadPpmSetForEdit = function (_ppmSetId) {
        submitType = 'put';
        ShowLoader();

        setTimeout(function () {
            mzFetch('api/ppm.php?type=ppm_set_details&ppmSetId='+_ppmSetId, 'GET').then(res => {
                formValidate.clearValidation();
                self.resetFormFields();

                $('#lblMpsId').val(res['ppmSetId']);
                mzSetFieldValue('txtMpsName', res['ppmSetName']);
                $('#txtMpsName').change();

                mzSetFieldValue('txaMpsDesc', res['ppmSetDesc']);
                $('#txaMpsDesc').change();

                currentContractId = res['contractId'];
                currentAssetGroupId = res['assetGroupId'];
                currentAssetCategoryId = res['assetCategoryId'];
                currentAssetTypeId = res['assetTypeId'];

                fillNamed(
                    'optMpsAssetGroup',
                    filterRows(rowsFromRef(refAssetGroup, 'assetGroupId'), {assetGroupStatus: '1'}),
                    'assetGroupId',
                    'assetGroupName',
                    'Select Asset Group'
                );
                mzDisableSelect('optMpsAssetGroup', false);

                fillNamed(
                    'optMpsPpmGroupId',
                    filterRows(rowsFromRef(refPpmGroup, 'ppmGroupId'), {roleId: '5', siteId: userSiteId, ppmGroupStatus: '1'}),
                    'ppmGroupId',
                    'ppmGroupName',
                    'Select PPM Executor Group'
                );
                mzDisableSelect('optMpsPpmGroupId', false);

                mzSetFieldValue('optMpsAssetGroup', res['assetGroupId']);
                $('#optMpsAssetGroup').trigger('change');

                setTimeout(() => {
                    mzSetFieldValue('optMpsAssetCategory', res['assetCategoryId']);
                    $('#optMpsAssetCategory').trigger('change');

                    setTimeout(() => {
                        mzSetFieldValue('optMpsAssetType', res['assetTypeId']);
                        $('#optMpsAssetType').trigger('change');
                    }, 100);
                }, 100);

                const isDisable = res['ppmSetStatus'] != 1;
                mzDisableSelect('optMpsAssetGroup', isDisable);
                mzDisableSelect('optMpsAssetCategory', isDisable);
                mzDisableSelect('optMpsAssetType', isDisable);
                mzDisableSelect('optMpsPpmGroupId', isDisable);
                $('#txtMpsName').prop('disabled', isDisable);
                $('#txaMpsDesc').prop('disabled', isDisable);
                $('#btnMpsSubmit').prop('disabled', isDisable);

                formValidate.disableField('optMpsAssetGroup', isDisable);
                formValidate.disableField('optMpsAssetCategory', isDisable);
                formValidate.disableField('optMpsAssetType', isDisable);
                formValidate.disableField('optMpsPpmGroupId', isDisable);
                formValidate.disableField('txtMpsName', isDisable);
                formValidate.disableField('txaMpsDesc', isDisable);

                $('#h5PpmSetFormTitle').html('<i class="fas fa-edit me-2"></i> Edit PPM Set: ' + GemsUI.escape(res['ppmSetName'] || ''));
                $('#sectionAssetsInSet').show();

                self.loadAssetsInPpmSet(ppmSetId);
                HideLoader();

            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); HideLoader(); });
        }, 200);
    };

    this.loadAssetsInPpmSet = function (_ppmSetId) {
        dtPpsa.clear().draw();

        ShowLoader(); setTimeout(function () {
            mzFetch('api/ppm.php?type=assets_in_ppm_set&ppmSetId=' + _ppmSetId, 'GET').then(res => {
                dtPpsa.rows.add(res).draw();
                HideLoader();
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); HideLoader(); });
        }, 100);
    };

    this.removeAssetFromPpmSetConfirmed = function (_ppmSetId, _assetIds) {
        ShowLoader(); setTimeout(function () {
            try
            {
                mzAjaxRequest('ppm.php', 'DELETE', {
                    action: 'remove_assets_from_ppm_set',
                    ppmSetId: _ppmSetId,
                    assetIds: JSON.stringify(_assetIds)
                });

                toastr['success']("Successfully remove asset from this PPM Set.", _ALERT_TITLE_SUCCESS);
                self.loadAssetsInPpmSet(_ppmSetId);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            } finally {
                HideLoader();
            }
        }, 200);
    };

    this.assetsAddedToPpmSetCallback = function () {
        if (ppmSetId) {
            self.loadAssetsInPpmSet(ppmSetId);
        }
    };

    this.resetFormFields = function () {
        formValidate.clearValidation();

        fillNamed('optMpsAssetGroup', [], 'assetGroupId', 'assetGroupName', 'Select Asset Group');
        mzDisableSelect('optMpsAssetGroup', false);
        fillNamed('optMpsAssetCategory', [], 'assetCategoryId', 'assetCategoryName', 'Select Asset Category');
        mzDisableSelect('optMpsAssetCategory', true);
        fillNamed('optMpsAssetType', [], 'assetTypeId', 'assetTypeName', 'Select Asset Type');
        mzDisableSelect('optMpsAssetType', true);
        fillNamed('optMpsPpmGroupId', [], 'ppmGroupId', 'ppmGroupName', 'Select PPM Executor Group');
        mzDisableSelect('optMpsPpmGroupId', true);

        mzSetFieldValue('txtMpsName', '', 'text');
        mzSetFieldValue('txaMpsDesc', '', 'text');
        $('#lblMpsId').val('');
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };
    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };
    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };
    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
    this.setModalPpmSetSelectAssetClass = function (_modalPpmSetSelectAssetClass) {
        modalPpmSetSelectAssetClass = _modalPpmSetSelectAssetClass;
        modalPpmSetSelectAssetClass.setCallbackOnAdd(self.assetsAddedToPpmSetCallback);
    };
    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    this.getClassName = function () {
        return className;
    };
}
