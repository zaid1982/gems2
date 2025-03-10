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

        $('#optMpasAsset').on('change', function () {
            const ids = $(this).val();
            console.log(ids);
        });

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

    this.add = function (_ppmId, _assetTypeId) {
        try {
            mzCheckFuncParam([_assetTypeId]);
            ppmId = _ppmId;
            ShowLoader(); setTimeout(function () {
                formValidate.clearValidation();
                if (assetTypeId !== _assetTypeId) {
                    assetTypeId = _assetTypeId;
                    mzFetch('ppm_asset/listSelection/'+assetTypeId, 'GET').then(res => {
                        for (const i in res) {
                            res[i]['display'] = res[i]['assetNo'] + ' - ' + res[i]['assetName'];
                        }
                        mzOptionStopV2('optMpasAsset', res, 'Choose Asset', 'display', {assetStatus: 1}, 'required');
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
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
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