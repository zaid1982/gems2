function ModalPpmSetSelectAsset () {

    const className = 'ModalPpmSetSelectAsset';
    let self = this;
    let classFrom; // Reference to the calling class (e.g., ModalPpmSet)
    let dtMpssa; // DataTable for assets
    let refStatus; // Reference status array

    let currentPpmSetId;
    let currentContractId;
    let currentAssetTypeId;

    this.init = function () {
        // Initialize DataTable for Available Assets
        dtMpssa = $('#dtMpssa').DataTable({ // HTML table ID from modal_ppm_set_select_asset.html
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[2, 'asc']], // Sort by Asset No
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-12 col-sm-7 px-0 pb-2'B><'col-sm-5 d-none d-sm-block pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] }, // Checkbox column
                { className: 'text-center', targets: [0, 1, 5] }, // Checkbox, #, Status
                { className: 'text-right', targets: [] },
                { visible: false, targets: [] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [], // No buttons needed here, manual add button below
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(1).html(info.start + (iDisplayIndex + 1)); // Set # column
            },
            drawCallback: function () {
                // Handle individual checkbox clicks
                $('#dtMpssa tbody input[type="checkbox"]').off('change').on('change', function () {
                    if (!this.checked) {
                        $('#chkMpssaSelectAll').prop('checked', false); // Uncheck select all if any individual is unchecked
                    }
                });
            },
            aoColumns: [
                { // Checkbox for selection
                    mData: null, bSortable: false, mRender: function (data, type, row) {
                        return '<input type="checkbox" class="chkMpssaAsset" value="' + row.assetId + '">';
                    }
                },
                { mData: null}, // #
                { mData: 'assetNo'}, // Asset No
                { mData: 'assetName'}, // Asset Name
                { mData: 'assetLocationDesc'}, // Location
                { mData: 'assetStatus', mRender: function (data) { // Status (assuming from ast_asset.asset_status)
                        return '<h6 class="mb-0"><span class="badge badge-pill '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }}
            ]
        });

        // Handle "Select All" checkbox
        $('#chkMpssaSelectAll').off('change').on('change', function () {
            $('.chkMpssaAsset').prop('checked', this.checked);
        });

        // Handle "Add Selected Assets" button click
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

                // Call backend API to add selected assets to the PPM Set
                ShowLoader(); setTimeout(function () {
                    // API call to add assets. This is the mzFetch for Step 22 (add_assets_to_ppm_set).
                    mzFetch('api/ppm.php?action=add_assets_to_ppm_set', 'POST', {
                        ppmSetId: currentPpmSetId,
                        assetIds: selectedAssetIds
                    }).then(res => {
                        toastr['success'](res.totalAdded + ' asset(s) successfully added to PPM Set!', _ALERT_TITLE_SUCCESS);
                        self.close(); // Close this modal
                        if (classFrom && classFrom.genTable) { // Refresh the main PPM Set list table
                            classFrom.genTable();
                        }
                        // Optionally: Refresh the asset list within the edit modal if it gets updated (future step)
                    }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                }, 200);

            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };

    // Method to show the modal and load asset data
    this.show = function (_ppmSetId, _contractId, _assetTypeId) {
        try {
            mzCheckFuncParam([_ppmSetId, _contractId]); // assetTypeId can be null
            currentPpmSetId = _ppmSetId;
            currentContractId = _contractId;
            currentAssetTypeId = _assetTypeId;

            dtMpssa.clear().draw(); // Clear previous data
            $('#chkMpssaSelectAll').prop('checked', false); // Uncheck select all checkbox

            ShowLoader(); setTimeout(function () {
                // API call to get available assets
                mzFetch('api/ppm.php?type=assets_for_ppm_set_selection&ppmSetId='+currentPpmSetId+'&contractId='+currentContractId+'&assetTypeId='+currentAssetTypeId, 'GET').then(res => {
                    dtMpssa.rows.add(res).draw(); // Add new data
                    $('#modal_ppm_set_select_asset').modal({backdrop: 'static', keyboard: false}).scrollTop(0); // Show modal
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
            }, 200);

        } catch (e) { toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }
    };

    this.close = function () {
        $('#modal_ppm_set_select_asset').modal('hide');
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
    // Add other ref setters if this modal needs more reference data (e.g., asset groups for filtering)
}