function ModalSiteVisitorPublic() {
    let qrObj;
    let baseAppPath;
    let currentSiteId;
    
    this.init = function() {
        // Determine base application path similar to main_zone.js logic
        const urlParams = window.location.href.split('/site');
        console.log(urlParams);
        console.log(window.location);
        baseAppPath = urlParams[0];
        // Attach handlers
        // Row-level action icons will call window.modalSiteVisitorPublicGlobal.openForSite(siteId)
        $('#btnSiteVisitorPublicCopy').on('click', function() {
            const txt = $('#txtSiteVisitorPublicLink').val();
            if (txt) {
                navigator.clipboard.writeText(txt).then(function(){
                    toastr['success']('Copied to clipboard');
                });
            }
        });
        $('#btnSiteVisitorPublicRegenerate').on('click', function(){
            if (currentSiteId) {
                generate(currentSiteId); // simply regenerates QR
                toastr['info']('QR regenerated');
            }
        });
    };
    function openForSite(siteId) {
        currentSiteId = siteId;
        generate(siteId);
        $('#modal_site_visitor_public').modal({backdrop: 'static', keyboard: false});
    }
    function generate(siteId) {
        console.log(baseAppPath)
        const link = baseAppPath + '/vm_visit.html?site=' + encodeURIComponent(siteId);
        $('#txtSiteVisitorPublicLink').val(link);
        if (!qrObj) {
            const el = document.getElementById('divSiteVisitorPublicQr');
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
            const rows = dt.rows({filter:'applied'}).data();
            if (rows && rows.length > 0) {
                return rows[0];
            }
        } catch(e) { /* ignore */ }
        return null;
    }    
    this.openForSelectedSite = function() {
        const site = getSelectedActiveSite();
        if (site) {
            openForSite(site.id);
        }
    };
    this.openForSite = openForSite;
}