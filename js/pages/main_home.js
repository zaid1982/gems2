function MainHome() {

    const className = 'MainHome';
    let self = this;
    let refClient;
    let refSite;
    let refContract;
    let clientId = '1';
    let currentMonth;
    let currentYear;

    this.init = function () {
        $.each(refClient, function (_clientId, _client) {
            if (typeof _client !== 'undefined') {
                $('#divHmeClient').append('<a class="dropdown-item lnkHmeClient" href="#" id="lnkHmeClient_'+_clientId+'">'+_client['clientName']+'</a>');
            }
        });

        $('.lnkHmeClient').off('click').on('click', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            ShowLoader();
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        clientId = linkId.substr(linkIndex + 1);
                        self.generateTotalAsset();
                        self.generateTotalPpmTask();
                        $('#lblHmeSelected').html('<i>'+refClient[clientId]['clientName']+', '+monthFull[currentMonth]+' '+currentYear+'</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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
            $('#divHmeMonth').append('<a class="dropdown-item lnkHmeMonth '+isActive+'" href="#" id="lnkHmeMonth_'+dateCtr.getFullYear()+dateCtr.getMonth()+'">'+monthFull[dateCtr.getMonth()] + ' ' + dateCtr.getFullYear() + '</a>');
            dateCtr.setMonth(dateCtr.getMonth() - 1);
        }

        $('.lnkHmeMonth').off('click').on('click', function () {ShowLoader();
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            setTimeout(function () {
                try {
                    if (linkIndex > 0) {
                        const year = linkId.substr(linkIndex + 1, 4);
                        const month = linkId.substr(linkIndex + 1 + 4);
                        $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('active');
                        $('#lnkHmeMonth_'+currentYear+currentMonth).removeClass('text-white');
                        $('#lnkHmeMonth_'+year+month).addClass('active');
                        $('#lnkHmeMonth_'+year+month).addClass('text-white');
                        currentMonth = month;
                        currentYear = year;
                        self.generateTotalPpmTask();
                        $('#lblHmeSelected').html('<i>'+refClient[clientId]['clientName']+', '+monthFull[currentMonth]+' '+currentYear+'</i>');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#lblHmeSelected').html('<i>'+refClient[clientId]['clientName']+', '+monthFull[currentMonth]+' '+currentYear+'</i>');
        self.generateTotalAsset();
        self.generateTotalPpmTask();
    };

    this.generateTotalAsset = function () {
        const totalAsset = mzAjaxRequest('asset.php?type=total_asset&clientId='+clientId, 'GET');
        $('#lblHmeTotalAsset').html(mzFormatNumber(totalAsset));
    };

    this.generateTotalPpmTask = function () {
        const totalppmTask = mzAjaxRequest('ppm.php?type=total_ppm_task&clientId='+clientId+'&year='+currentYear+'&month='+currentMonth, 'GET');
        $('#lblHmeTotalPpm').html(mzFormatNumber(totalppmTask));
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