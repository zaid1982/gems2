function MainHome() {

    const className = 'MainHome';
    let self = this;
    let clientId;
    let refClient;
    let refSite;
    let refContract;
    let currentMonth;
    let currentYear;

    this.init = function () {
        $.each(refClient, function (clientId, client) {
            if (typeof client !== 'undefined') {
                $('#divHmeClient').append('<a class="dropdown-item lnkHmeClient" href="#" id="lnkHmeClient_'+clientId+'">'+client['clientName']+'</a>');
            }
        });

        $('.lnkHmeClient').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const clientId = linkId.substr(linkIndex + 1);
                alert(clientId);
            }
        });

        const monthsFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        let dateEarliest = new Date();
        dateEarliest.setFullYear(2019, 3, 1);
        let dateCtr = new Date();
        currentMonth = dateCtr.getMonth();
        currentYear = dateCtr.getFullYear();
        dateCtr.setDate(1);
        for (let i = 0; i < 100; i++) {
            if (dateCtr < dateEarliest) {
                break;
            }
            const isActive = (dateCtr.getMonth() === currentMonth && dateCtr.getFullYear() === currentYear) ? 'active text-white' : '';
            $('#divHmeMonth').append('<a class="dropdown-item lnkHmeMonth '+isActive+'" href="#" id="lnkHmeMonth_'+dateCtr.getFullYear()+dateCtr.getMonth()+'">'+monthsFull[dateCtr.getMonth()] + ' ' + dateCtr.getFullYear() + '</a>');
            dateCtr.setMonth(dateCtr.getMonth() - 1);
        }

        $('.lnkHmeMonth').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const year = linkId.substr(linkIndex + 1, 4);
                const month = linkId.substr(linkIndex + 1 + 4);
                $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('active');
                $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('text-white');
                $('#lnkHmeMonth_'+year+month).addClass('active');
                $('#lnkHmeMonth_'+year+month).addClass('text-white');
                currentMonth = month;
                currentYear = year;
                alert(year+month);
            }
        });

        const totalAsset = mzAjaxRequest('asset.php?type=total_asset&clientId='+clientId, 'GET');
        $('#lblHmeTotalAsset').html(mzFormatNumber(totalAsset));

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

}