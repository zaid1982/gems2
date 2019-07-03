function ModalTaskHistory() {

    const className = 'ModalTaskHistory';
    let self = this;
    let assetBrandId = '';
    let rowRefresh = '';
    let classFrom;

    this.init = function () {

    };

    this.view = function (_assetBrandId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetBrandId, _rowRefresh]);
                assetBrandId = _assetBrandId;
                rowRefresh = _rowRefresh;

                //const dataMth = mzAjaxRequest('asset_brand.php?assetBrandId='+assetBrandId, 'GET');
                //mzSetFieldValue('MthName', dataMth['assetBrandName'], 'text');
                //mzSetFieldValue('MthDesc', dataMth['assetBrandDesc'], 'textarea');
                //mzSetFieldValue('MthStatus', dataMth['assetBrandStatus'], 'checkSingle', '1');

                $('#lblMthTitle').html('<i class="far fa-tasks text-white"></i> &nbsp;Task Information');
                $('#modal_asset_brand').modal({backdrop: 'static', keyboard: false});
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