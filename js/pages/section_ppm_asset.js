function SectionPpmAsset () {

    const className = 'SectionPpmAsset';
    let self = this;
    let classFrom;
    let refStatus;
    let refPpmGroup;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let ppmId;

    this.init = function () {
        self.hideSection();

        $('#btnSpgBack').on('click', function () {
            self.hideSection();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

    };

    this.load = function (_ppmId) {
        ShowLoader(); setTimeout(function () {
            try {
                ppmId = _ppmId;
                Promise.all([
                    mzFetch('ppm_v3/'+ppmId)
                ]).then(responses => {
                    console.log(responses);
                    const ppm = responses[0];
                    $('#pSpgName, #lblSpgName').text(mzNullToValue(ppm['ppmName'], '-'));
                    const assetTypeId = ppm['assetTypeId'];
                    $('#pSpgAssetType').text(mzNullToValue(assetTypeId, '-', 'assetTypeName', refAssetType));
                    const assetCategoryId = refAssetType[assetTypeId]['assetCategoryId'];
                    $('#pSpgAssetCategory').text(mzNullToValue(assetCategoryId, '-', 'assetCategoryName', refAssetCategory));
                    const assetGroupId = refAssetCategory[assetCategoryId]['assetGroupId'];
                    $('#pSpgAssetGroup').text(mzNullToValue(assetGroupId, '-', 'assetGroupName', refAssetGroup));
                    $('#pSpgTaskNo').text(mzNullToValue(ppm['ppmTaskNo'], '-'));
                    $('#pSpgIssueNo').text(mzNullToValue(ppm['ppmIssueNo'], '-'));
                    $('#pSpgFrequency').text(mzNullToValue(ppm['ppmFrequency'], '-'));
                    $('#pSpgDateStart').text(mzNullToValue(ppm['ppmDateStart'], '-', moment(ppm['ppmDateStart']).format('MMMM Do, YYYY')));
                    $('#pSpgTimeCreated').text(mzNullToValue(ppm['ppmTimeCreated'], '-', moment(ppm['ppmTimeCreated']).format('MMMM Do, YYYY, hh:mm:ss')));
                    $('#pSpgRemark').text(mzNullToValue(ppm['ppmRemark'], '-'));
                    $('#pSpgStatus').text(mzNullToValue(ppm['ppmStatus'], '-', 'statusDesc', refStatus));
                    classFrom.hideMain();
                    self.showSection();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        }, 200);
    };

    this.showSection = function () {
        $('.sectionPpmAsset').show();
    };

    this.hideSection = function () {
        $('.sectionPpmAsset').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
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

    this.setContractName = function (_contractName) {
        $('#pSpgContract').text(_contractName);
    };

    this.setSiteName = function (_siteName) {
        $('#pSpgSite').text(_siteName);
    };
}