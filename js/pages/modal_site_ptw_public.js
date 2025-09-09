function ModalSitePtwPublic() {
    let qrObj;
    let baseAppPath;
    let currentSiteId;

    this.init = function() {
        // Determine base application path similar to main_zone.js logic
        const urlParams = window.location.href.split('/p_');
        baseAppPath = urlParams[0];
        // Attach handlers
    // Row-level action icons will call window.modalSitePtwPublicGlobal.openForSite(siteId)

        $('#btnSitePtwPublicCopy').on('click', function() {
            const txt = $('#txtSitePtwPublicLink').val();
            if (txt) {
                navigator.clipboard.writeText(txt).then(function(){
                    toastr['success']('Copied to clipboard');
                });
            }
        });

        $('#btnSitePtwPublicRegenerate').on('click', function(){
            if (currentSiteId) {
                generate(currentSiteId); // simply regenerates QR
                toastr['info']('QR regenerated');
            }
        });
    };

    function openForSite(siteId) {
        currentSiteId = siteId;
        generate(siteId);
        $('#modal_site_ptw_public').modal({backdrop: 'static', keyboard: false});
    }

    function generate(siteId) {
        const link = baseAppPath + '/ptw_form.html?site_id=' + encodeURIComponent(siteId);
        $('#txtSitePtwPublicLink').val(link);
        if (!qrObj) {
            const el = document.getElementById('divSitePtwPublicQr');
            if (el) {
                qrObj = new QRCode(el, {});
            }
        } else if (typeof qrObj.clear === 'function') {
            qrObj.clear();
        }
        if (qrObj) {
            qrObj.makeCode(link);
        }
    }

    // Utility: pick the first highlighted or filtered site row data from DataTable
    function getSelectedActiveSite() {
        try {
            const dt = $('#dtSteSite').DataTable();
            // If a row has been clicked previously we can store selection later (future enhancement)
            // For now just pick first row in current page
            const data = dt.rows({page:'current'}).data();
            if (data.length > 0) {
                return data[0];
            }
        } catch (e) {}
        return null;
    }

    // Expose a manual open by siteId for integration if needed
    this.openForSite = openForSite;
}
