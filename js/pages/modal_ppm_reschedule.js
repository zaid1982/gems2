function ModalPpmReschedule() {

    const className = 'ModalPpmReschedule';
    let self = this;
    let classFrom;
    let rowRefresh;
    let ppmTaskId;

    this.init = function () {

    };

    this.edit = function (_ppmTaskId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                //mzOptionStop('optMzcAssetGroupId', refAssetGroup, 'Choose Asset Group', 'assetGroupId', 'assetGroupName');
                mzCheckFuncParam([_ppmTaskId, _rowRefresh]);
                ppmTaskId = _ppmTaskId;
                rowRefresh = _rowRefresh;

                //const dataMzc = mzAjaxRequest('asset_category.php?assetCategoryId='+assetCategoryId, 'GET');
                //mzSetFieldValue('MzcAssetGroupId', dataMzc['assetGroupId'], 'select', 'Asset Group *');
                //mzSetFieldValue('MzcName', dataMzc['assetCategoryName'], 'text');
                //mzSetFieldValue('MzcDesc', dataMzc['assetCategoryDesc'], 'textarea');
                //mzSetFieldValue('MzcStatus', dataMzc['assetCategoryStatus'], 'checkSingle', '1');

                //mzDisableSelect('optMzcAssetGroupId', true);

                $('#modal_ppm_reschedule').modal({backdrop: 'static', keyboard: false});
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