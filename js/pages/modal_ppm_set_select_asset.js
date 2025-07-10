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
            
        console.log(refStatus);
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
                // { visible: false, targets: [] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [], // No buttons needed here, manual add button below
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(1).html(info.start + (iDisplayIndex + 1)); // Set # column
            },
            
            // REMOVE drawCallback
            /*
            drawCallback: function () {
                // Handle individual checkbox clicks
                $('#dtMpssa tbody input[type="checkbox"]').off('change').on('change', function () {
                    if (!this.checked) {
                        $('#chkMpssaSelectAll').prop('checked', false); // Uncheck select all if any individual is unchecked
                    }
                });
            },
            */
            aoColumns: [
                { // Checkbox for selection - MODIFIED MARKUP
                    mData: null, bSortable: false, mRender: function (data, type, row) {
                        // Ensure unique ID for the label
                        const checkboxId = 'chkMpssaAsset_' + row.assetId;
                        return `
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input chkMpssaAsset" id="${checkboxId}" value="${row.assetId}">
                                <label class="form-check-label" for="${checkboxId}"></label>
                            </div>
                        `;
                        // Alternative simplified MDB checkbox markup often used:
                        /*
                        return `
                            <input type="checkbox" class="filled-in chkMpssaAsset" id="${checkboxId}" value="${row.assetId}">
                            <label for="${checkboxId}"></label>
                        `;
                        */
                    }
                },
                { mData: null}, // #
                { mData: 'assetNo'}, // Asset No
                { mData: 'assetName'}, // Asset Name
                { mData: 'assetLocationDesc'}, // Location
                { mData: 'assetStatus', mRender: function (data) { // Status (assuming from ast_asset.asset_status)
                        // console.log({data});
                        // return '<h6 class="mb-0"><span class="badge badge-pill '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                        return '';
                    }}
            ]
        });

        // Handle "Select All" checkbox - This is fine as init() runs once.
        $('#chkMpssaSelectAll').off('change').on('change', function () {
            $('.chkMpssaAsset').prop('checked', this.checked);
        });

        // NEW: Event delegation for individual checkbox clicks
        // Bind to a static parent element (e.g., the DataTable container or even the modal itself)
        // This handler will only be bound once during init().
        $('#dtMpssa').on('change', 'tbody input[type="checkbox"].chkMpssaAsset', function () {
            if (!this.checked) {
                $('#chkMpssaSelectAll').prop('checked', false); // Uncheck select all if any individual is unchecked
            }
        });


        // Handle "Add Selected Assets" button click - This is fine as init() runs once.
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
                    try {
                        // Use mzAjaxRequest synchronously (async: false)
                        // Or, use its third parameter (functionStr) for callback
                        const res = mzAjaxRequest('ppm.php', 'POST', {
                            action: 'add_assets_to_ppm_set',
                            ppmSetId: parseInt(currentPpmSetId),
                            assetIds: JSON.stringify(selectedAssetIds) // Send as a plain JS array
                        }, /* functionStr = */ '', /* apiBeautify = */ false); // Call synchronously, no callback needed here

                        // If mzAjaxRequest doesn't throw an error, it means it was successful.
                        // 'res' here will be the 'result' property from the backend response.
                        toastr['success'](res.errmsg, _ALERT_TITLE_SUCCESS); // The backend now sets errmsg on success

                        self.close(); // Close this modal

                        // Callback to parent page to refresh assets list
                        if (callbackOnAddFunction) {
                            callbackOnAddFunction(res); // Pass res (the 'result' data) to callback if needed
                        }
                        
                        HideLoader(); // Hide loader after all successful operations

                    } catch (e) {
                        // mzAjaxRequest throws an Error object on failure.
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        HideLoader(); // Hide loader on error
                    }
                }, 200);

            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };

    // Method to show the modal and load asset data
    this.show = function (_ppmSetId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        try {
            mzCheckFuncParam([_ppmSetId, _assetGroupId, _assetCategoryId, _assetTypeId]); // assetTypeId can be null
            currentPpmSetId = _ppmSetId;
            currentAssetGroupId = _assetGroupId;
            currentAssetCategoryId = _assetCategoryId;
            currentAssetTypeId = _assetTypeId;

            dtMpssa.clear().draw(); // Clear previous data
            $('#chkMpssaSelectAll').prop('checked', false); // Uncheck select all checkbox

            ShowLoader(); setTimeout(function () {
                // API call to get available assets
                mzFetch('api/ppm.php?type=assets_for_ppm_set_selection&ppmSetId='+currentPpmSetId+'&contractId='+currentContractId+'&assetTypeId='+currentAssetTypeId+'&assetGroupId='+currentAssetGroupId+'&assetCategoryId='+currentAssetCategoryId, 'GET').then(res => {
                    dtMpssa.rows.add(res).draw(); // Add new data
                    $('#modal_ppm_set_select_asset').modal({backdrop: 'static', keyboard: false}).scrollTop(0); // Show modal
                }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
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
    // Add other ref setters if this modal needs more reference data (e.g., asset groups for filtering)
}