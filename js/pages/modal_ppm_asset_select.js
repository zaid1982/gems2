function ModalPpmAssetSelect () {

    const className = 'ModalPpmAssetSelect';
    let self = this;
    let formValidate;
    let classFrom;
    let ppmId;
    let assetTypeId;

    const vData = [
        {
            field_id: 'optMpasAsset',
            type: 'select',
            name: 'Asset',
            validator: {
                notEmpty: true
            }
        }
    ];

    this.init = function () {
        formValidate = new MzValidate('formMpas');
        formValidate.registerFields(vData);

        $('#btnMpasSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    ShowLoader(); setTimeout(function () {
                        mzFetch('ppm_asset', 'POST', {ppmId: ppmId, listAsset: $('#optMpasAsset').val()}).then(res => {
                            classFrom.setIsUpdate(true);
                            classFrom.genTableAsset();
                            $('#modal_ppm_asset_select').modal('hide');
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };

    this.add = function (_ppmId, _contractId, _assetTypeId) {
        try {
            mzCheckFuncParam([_assetTypeId]);
            ppmId = _ppmId;
            ShowLoader(); setTimeout(function () {
                formValidate.clearValidation();
                if (assetTypeId !== _assetTypeId) {
                    assetTypeId = _assetTypeId;
                    mzFetch('ppm_asset/listSelection/'+_contractId+'/'+assetTypeId, 'GET').then(res => {
                        const rows = [];
                        $.each(res, function (n, u) {
                            if (!u || typeof u !== 'object') {
                                return;
                            }
                            if (u['assetStatus'] !== 1) {
                                return;
                            }
                            const row = $.extend({}, u);
                            if (row['id'] === undefined) {
                                row['id'] = String(n);
                            }
                            row['display'] = (row['assetNo'] || '') + ' - ' + (row['assetName'] || '');
                            rows.push(row);
                        });
                        rows.sort(function (a, b) {
                            return String(a['display']).localeCompare(String(b['display']));
                        });
                        GemsUI.fillSelect('optMpasAsset', rows, 'id', function (row) {
                            return row['display'];
                        }, null);
                        $('#modal_ppm_asset_select').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
                    }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                } else {
                    $('#modal_ppm_asset_select').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
                    HideLoader();
                }
            }, 200);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.delete = function (_ppmAssetId) {
        try {
            mzCheckFuncParam([_ppmAssetId]);
            ShowLoader(); setTimeout(function () {
                mzFetch('ppm_asset/'+_ppmAssetId, 'DELETE').then(res => {
                    classFrom.setIsUpdate(true);
                    classFrom.genTableAsset();
                    $('#modal_ppm_asset_select').modal('hide');
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);
        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setLabelName = function (_value) {
        $('#pMpasName').text(_value);
    };

    this.setLabelDocNo = function (_value) {
        $('#pMpasDocNo').text(_value);
    };
}
