function ModalPpmSetSelectAsset () {

    const className = 'ModalPpmSetSelectAsset';
    let self = this;
    let classFrom;
    let dtMpssa;
    let refStatus;

    let currentPpmSetId;
    let currentContractId;
    let currentAssetGroupId;
    let currentAssetCategoryId;
    let currentAssetTypeId;
    let callbackOnAddFunction;

    const statusColorMap = {
        'badge-primary': 'info',
        'badge-info': 'info',
        'badge-success': 'success',
        'badge-danger': 'danger',
        'badge-warning': 'warning',
        'badge-secondary': 'secondary'
    };

    this.init = function () {

        dtMpssa = $('#dtMpssa').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[2, 'asc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-cubes', 'No available assets for this PPM set.'),
            pageLength: 10,
            autoWidth: false,
            dom: GemsUI.dtDom,
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 1, 5] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(1).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns: [
                {
                    mData: null, bSortable: false, mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return '';
                        }
                        const checkboxId = 'chkMpssaAsset_' + row.assetId;
                        return '<input type="checkbox" class="form-check-input chkMpssaAsset" id="' + checkboxId + '" value="' + row.assetId + '" aria-label="Select asset">';
                    }
                },
                { mData: null},
                { mData: 'assetNo'},
                { mData: 'assetName'},
                { mData: 'assetLocationDesc'},
                { mData: 'assetStatus', mRender: function (data, type) {
                        const rec = refStatus && refStatus[data];
                        const label = rec ? rec['statusDesc'] : 'N/A';
                        if (type !== 'display') {
                            return label;
                        }
                        return GemsUI.badge(statusColorMap[rec && rec['statusColor']] || 'secondary', GemsUI.escape(label));
                    }}
            ]
        });
        GemsUI.bindDtTooltips('#dtMpssa');

        $('#dtMpssa_filter').hide();
        $('#txtMpssaSearch').off('keyup change').on('keyup change', function () {
            if (dtMpssa) {
                dtMpssa.search($(this).val()).draw();
            }
        });
        $('#chkMpssaSelectAll').off('change').on('change', function () {
            $('.chkMpssaAsset').prop('checked', this.checked);
        });

        $('#dtMpssa').on('change', 'tbody input[type="checkbox"].chkMpssaAsset', function () {
            if (!this.checked) {
                $('#chkMpssaSelectAll').prop('checked', false);
            }
        });

        $('#btnMpssaAddAllAssetSelected').off('click').on('click', function () {
            if(!confirm('This action will add all asset listed under this groupe, category and type. Are you sure to proceed?')) {
                return;
            }

            ShowLoader(); setTimeout(function () {
                try {
                    const res = mzAjaxRequest('ppm.php', 'POST', {
                        action: 'add_assets_to_ppm_set',
                        ppmSetId: parseInt(currentPpmSetId),
                        assetIds: JSON.stringify([]),
                        allAssetSelected: true
                    }, '', false);

                    toastr['success'](res.errmsg, _ALERT_TITLE_SUCCESS);

                    self.close();

                    if (callbackOnAddFunction) {
                        callbackOnAddFunction(res);
                    }

                    HideLoader();
                } catch (e) {
                    HideLoader();
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
            }, 200);
        });

        $('#btnMpssaAddSelected').off('click').on('click', function () {
            try {
                const selectedAssetIds = [];
                $('#dtMpssa tbody input[type="checkbox"]:checked').each(function () {
                    selectedAssetIds.push(parseInt($(this).val()));
                });

                if (selectedAssetIds.length === 0) {
                    toastr['warning']('Please select at least one asset to add.', _ALERT_TITLE_WARNING);
                    return;
                }

                ShowLoader(); setTimeout(function () {
                    try {
                        const res = mzAjaxRequest('ppm.php', 'POST', {
                            action: 'add_assets_to_ppm_set',
                            ppmSetId: parseInt(currentPpmSetId),
                            assetIds: JSON.stringify(selectedAssetIds)
                        }, '', false);

                        toastr['success'](res.errmsg, _ALERT_TITLE_SUCCESS);

                        self.close();

                        if (callbackOnAddFunction) {
                            callbackOnAddFunction(res);
                        }

                        HideLoader();

                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        HideLoader();
                    }
                }, 200);

            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };

    this.show = function (_ppmSetId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        try {
            mzCheckFuncParam([_ppmSetId, _assetGroupId, _assetCategoryId, _assetTypeId]);
            currentPpmSetId = _ppmSetId;
            currentAssetGroupId = _assetGroupId;
            currentAssetCategoryId = _assetCategoryId;
            currentAssetTypeId = _assetTypeId;

            dtMpssa.clear().draw();
            $('#chkMpssaSelectAll').prop('checked', false);

            ShowLoader(); setTimeout(function () {
                mzFetch('api/ppm.php?type=assets_for_ppm_set_selection&ppmSetId='+currentPpmSetId+'&assetTypeId='+currentAssetTypeId+'&assetGroupId='+currentAssetGroupId+'&assetCategoryId='+currentAssetCategoryId, 'GET').then(res => {
                    dtMpssa.rows.add(res).draw();
                    $('#modal_ppm_set_select_asset').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }).finally(() => { HideLoader(); });
            }, 200);

        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.close = function () {
        $('#modal_ppm_set_select_asset').modal('hide');
    };

    this.setCallbackOnAdd = function (callback) {
        callbackOnAddFunction = callback;
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
}
