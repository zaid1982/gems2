function MainWaterTariff() {
    const wc = new WaterCommon();
    let data = null;

    const siteId = function () { return $('#optWtfSite').val() || ''; };

    const fmtRate = function (value) {
        const n = Number(value);
        if (!Number.isFinite(n)) { return '—'; }
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    };

    const fmtDate = function (value) {
        if (!value) { return ''; }
        if (window.moment) { return moment(value).format('DD/MM/YYYY'); }
        return value;
    };

    const tierHtml = function (tier) {
        const from = tier && tier.fromVolume !== undefined && tier.fromVolume !== null ? tier.fromVolume : '';
        const to = tier && tier.toVolume !== null && tier.toVolume !== undefined ? tier.toVolume : '';
        const rate = tier && tier.rateRm !== undefined && tier.rateRm !== null && tier.rateRm !== '' ? tier.rateRm : '';
        return '<div class="row g-2 align-items-end mb-2 wtfTier">' +
            '<div class="col-sm-6 col-md-3"><label class="form-label">From m&sup3;</label>' +
            '<input type="number" step="0.01" min="0" class="form-control wtfFrom" value="' + from + '"></div>' +
            '<div class="col-sm-6 col-md-3"><label class="form-label">To m&sup3;</label>' +
            '<input type="number" step="0.01" min="0" class="form-control wtfTo" value="' + to + '" placeholder="Open"></div>' +
            '<div class="col-sm-6 col-md-3"><label class="form-label">Rate RM</label>' +
            '<input type="number" step="0.0001" min="0" class="form-control wtfRate" value="' + rate + '"></div>' +
            '<div class="col-sm-6 col-md-3"><button type="button" class="btn btn-outline-secondary wtfRemove">Remove</button></div>' +
            '</div>';
    };

    const defaultTiers = function () {
        return [
            { fromVolume: 0, toVolume: 35, rateRm: '' },
            { fromVolume: 35, toVolume: null, rateRm: '' }
        ];
    };

    const paintTiers = function (tiers) {
        $('#wtfTiers').html((tiers || defaultTiers()).map(tierHtml).join(''));
    };

    const clearEditor = function () {
        $('#hidWtfPrevious').val('');
        $('#txtWtfDate').val('');
        $('#lblWtfEditor').text('New tariff');
        paintTiers(defaultTiers());
    };

    const render = function () {
        data = wc.apiGet('tariff?siteId=' + siteId());
        if (!data) { return; }
        const canEdit = !!(data.canSetup && wc.caps.canSetup);
        $('#cardWtfEditor').toggle(canEdit);
        $('#lblWtfSite').text(data.siteName || '');
        const sets = data.sets || [];
        if (!sets.length) {
            $('#tbodyWtf').html('<tr><td colspan="6" class="gems-empty-cell">' + wc.emptyState('fa-file-invoice', 'No tariff for this site yet.') + '</td></tr>');
            return;
        }
        const rows = [];
        sets.forEach(function (set) {
            (set.tiers || []).forEach(function (tier, index) {
                const actions = (canEdit && index === 0)
                    ? wc.actionBtn({ tint: 'gems-btn-action-edit', cls: 'lnkWtfEdit', title: 'Edit', icon: 'fas fa-pen-to-square', extra: 'data-date="' + set.effectiveFrom + '"' }) +
                      wc.actionBtn({ tint: 'gems-btn-action-delete', cls: 'lnkWtfDel', title: 'Remove', icon: 'fas fa-trash', extra: 'data-date="' + set.effectiveFrom + '"' })
                    : '';
                rows.push('<tr>' +
                    '<td>' + (index === 0 ? fmtDate(set.effectiveFrom) : '') + '</td>' +
                    '<td>' + tier.tierNo + '</td>' +
                    '<td class="text-end">' + wc.fmtNumber(tier.fromVolume, 2) + '</td>' +
                    '<td class="text-end">' + (tier.toVolume === null ? 'and above' : wc.fmtNumber(tier.toVolume, 2)) + '</td>' +
                    '<td class="text-end">' + fmtRate(tier.rateRm) + '</td>' +
                    '<td class="text-nowrap">' + actions + '</td>' +
                    '</tr>');
            });
        });
        $('#tbodyWtf').html(rows.join(''));
        wc.initTooltips(document.getElementById('tblWtf'));
    };

    const collectTiers = function () {
        const tiers = [];
        $('#wtfTiers .wtfTier').each(function () {
            tiers.push({
                fromVolume: $(this).find('.wtfFrom').val(),
                toVolume: $(this).find('.wtfTo').val(),
                rateRm: $(this).find('.wtfRate').val()
            });
        });
        return tiers;
    };

    this.init = function () {
        wc.loadCaps();
        wc.loadSites();
        wc.fillSelect('optWtfSite', wc.sites, 'siteId', function (r) { return r.siteName; }, null, wc.caps.siteId || '');
        clearEditor();
        render();

        $('#optWtfSite').off('change').on('change', function () {
            clearEditor();
            render();
        });
        $('#btnWtfRefresh').off('click').on('click', render);
        $('#btnWtfAdd').off('click').on('click', function () {
            const lastTo = $('#wtfTiers .wtfTier').last().find('.wtfTo').val();
            $('#wtfTiers').append(tierHtml({ fromVolume: lastTo, toVolume: null, rateRm: '' }));
        });
        $('#btnWtfClear').off('click').on('click', clearEditor);
        $(document).off('click', '.wtfRemove').on('click', '.wtfRemove', function () {
            if ($('#wtfTiers .wtfTier').length <= 1) {
                toastr['warning']('A tariff needs at least one tier.', _ALERT_TITLE_WARNING);
                return;
            }
            $(this).closest('.wtfTier').remove();
        });
        $('#btnWtfSave').off('click').on('click', function () {
            const body = {
                siteId: siteId(),
                effectiveFrom: $('#txtWtfDate').val(),
                previousEffectiveFrom: $('#hidWtfPrevious').val(),
                tiers: collectTiers()
            };
            if (!body.effectiveFrom) {
                toastr['warning']('Enter the date this tariff takes effect.', _ALERT_TITLE_WARNING);
                return;
            }
            ShowLoader();
            try {
                data = wc.api('tariff', 'POST', body);
                clearEditor();
                render();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
        $(document).off('click', '.lnkWtfEdit').on('click', '.lnkWtfEdit', function (e) {
            e.preventDefault();
            const date = $(this).attr('data-date');
            const set = (data.sets || []).find(function (s) { return s.effectiveFrom === date; });
            if (!set) { return; }
            $('#hidWtfPrevious').val(set.effectiveFrom);
            $('#txtWtfDate').val(set.effectiveFrom);
            $('#lblWtfEditor').text('Edit tariff effective ' + fmtDate(set.effectiveFrom));
            paintTiers(set.tiers);
            document.getElementById('cardWtfEditor').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        $(document).off('click', '.lnkWtfDel').on('click', '.lnkWtfDel', function (e) {
            e.preventDefault();
            const date = $(this).attr('data-date');
            if (!window.confirm('Remove the tariff effective ' + fmtDate(date) + '? Months that used it will need another set.')) { return; }
            ShowLoader();
            try {
                data = wc.api('tariff?siteId=' + encodeURIComponent(siteId()) + '&effectiveFrom=' + encodeURIComponent(date), 'DELETE', {
                    siteId: siteId(),
                    effectiveFrom: date
                });
                if ($('#hidWtfPrevious').val() === date) { clearEditor(); }
                render();
            } catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
            HideLoader();
        });
    };
}
