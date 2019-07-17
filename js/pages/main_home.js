function MainHome() {

    const className = 'MainHome';
    let self = this;
    let clientId;

    this.init = function () {

        const totalAsset = mzAjaxRequest('asset.php?type=total_asset&clientId='+clientId, 'GET');
        $('#lblHmeTotalAsset').html(mzFormatNumber(totalAsset));

    };

}