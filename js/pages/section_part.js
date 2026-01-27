function SectionPart () {

    const className = 'SectionPart';
    let self = this;
    let classFrom;
    let isEdit;
    let vData;
    let formValidate;
    let partId;
    let refStatus;
    let refSite;
    let refStore;
    let refAssetGroup;
    let refItemType;
    let refItem;
    let refUser;
    let oTableSptPartList;
    let oTableSptPending;
    let oTableSptCheckIn;
    let oTableSptCheckOut;
    let oTableSptReturnHistory;
    let checkOutTableWidth;
    const updatePartListSummary = function () {
        if (!oTableSptPartList) {
            $('#lblSptCount').text('Showing 0 of 0');
            $('#lblSptUpdated').text('—');
            return;
        }
        const info = oTableSptPartList.page.info();
        if (!info) {
            return;
        }
        $('#lblSptCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal);
        $('#lblSptUpdated').text(new Date().toLocaleString());
    };
    const updateStatusPill = function (statusDesc) {
        const text = statusDesc && statusDesc !== '' ? statusDesc : '—';
        $('#lblSptStatusName').text(text);
        const $pill = $('#sptStatusPill');
        if (!$pill.length) {
            return;
        }
        const normalized = text.toLowerCase();
        if (normalized.indexOf('disable') >= 0) {
            $pill.attr('data-state', 'disabled');
        } else {
            $pill.attr('data-state', 'active');
        }
    };

    this.init = function () {
        checkOutTableWidth = $('#divSptCheckOutTable').width();
        $('.sectionPart').hide();

        $.fn.dataTable.ext.search.push(function (settings, data) {
            if (!settings.nTable || settings.nTable.id !== 'dtSptPartList') {
                return true;
            }
            const selectedStatus = $('#optSptStatus').val();
            if (!selectedStatus) {
                return true;
            }
            const statusCell = (data[11] || '').toString().toLowerCase();
            return statusCell.indexOf(selectedStatus.toLowerCase()) !== -1;
        });

        $('#btnSptRefreshPartList').on('click', function () {
            self.genTablePartList();
        });
        $('#btnSptRefreshPending').on('click', function () {
            self.genTablePending();
        });
        $('#btnSptRefreshCheckIn').on('click', function () {
            self.genTableCheckIn();
        });
        $('#btnSptRefreshCheckOut').on('click', function () {
            self.genTableCheckOut();
        });
        $('#btnSptRefreshReturn').on('click', function () {
            self.genTableReturnHistory();
        });

        $('#txtSptSearch').on('keyup change', function () {
            if (oTableSptPartList) {
                oTableSptPartList.search(this.value).draw();
            }
        });
        $('#optSptStatus').on('change', function () {
            if (oTableSptPartList) {
                oTableSptPartList.draw();
            }
        });

        $('#btnSptBack').on('click', function () {
            $('.sectionPart').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        // Update Image button - triggers file input
        $('#btnSptUpdateImage').on('click', function () {
            if (!partId) {
                toastr['error']('Please select a part first', _ALERT_TITLE_ERROR);
                return;
            }
            $('#fileSptImage').click();
        });

        // Handle file selection for image upload
        $('#fileSptImage').on('change', function () {
            const file = this.files[0];
            const inputElement = this;
            if (!file) {
                return;
            }
            
            // Validate file type
            if (!file.type.match('image.*')) {
                toastr['error']('Please select an image file', _ALERT_TITLE_ERROR);
                $(this).val('');
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                toastr['error']('Image size must be less than 5MB', _ALERT_TITLE_ERROR);
                $(this).val('');
                return;
            }

            ShowLoader();
            
            // Read file as base64
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64Data = e.target.result.split(',')[1]; // Remove data:image/xxx;base64, prefix
                
                // Prepare data in format expected by uploadDocument
                const uploadData = {
                    name: 'Part Image',
                    filename: file.name,
                    type: file.type,
                    size: file.size,
                    data: base64Data,
                    partId: partId
                };
                
                try {
                    // Use existing mzAjaxRequest2 for consistent API calls
                    const result = mzAjaxRequest2('part_image/' + partId, 'POST', uploadData);
                    HideLoader();
                    toastr['success']('Image uploaded successfully', _ALERT_TITLE_SUCCESS);
                    // Refresh the part details to show new image
                    self.genDetails(partId);
                    classFrom.genTable();
                } catch (error) {
                    HideLoader();
                    toastr['error'](error.message || 'Failed to upload image', _ALERT_TITLE_ERROR);
                }
                
                // Reset file input
                $(inputElement).val('');
            };
            
            reader.onerror = function() {
                HideLoader();
                toastr['error']('Failed to read image file', _ALERT_TITLE_ERROR);
                $(inputElement).val('');
            };
            
            reader.readAsDataURL(file);
        });
        
        vData = [
            {
                field_id: 'txtSptThreshold',
                type: 'text',
                name: 'Default Threshold',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1
                }
            },
            {
                field_id: 'txtSptMinOrder',
                type: 'text',
                name: 'Minimum Order',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1,
                    higher: {
                        id: 'txtSptThreshold',
                        label: 'Default Threshold'
                    }
                }
            },
            {
                field_id: 'txtSptMaxOrder',
                type: 'text',
                name: 'Maximum Order',
                validator: {
                    maxLength: 4,
                    notEmpty: true,
                    digit: true,
                    numeric: true,
                    min: 1,
                    higher: {
                        id: 'txtSptMinOrder',
                        label: 'Minimum Order'
                    }
                }
            },
            {
                field_id: 'txaSptRemark',
                type: 'text',
                name: 'Remark',
                validator: {
                    maxLength: 1000,
                    notEmpty: true
                }
            }
        ];

        formValidate = new MzValidate('formSpt');
        formValidate.registerFields(vData);

        $('#btnSptSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            }
            else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        let data = {
                            partThreshold: $('#txtSptThreshold').val(),
                            partMinOrder: $('#txtSptMinOrder').val(),
                            partMaxOrder: $('#txtSptMaxOrder').val(),
                            partRemark: $('#txaSptRemark').val()
                        };
                        mzAjaxRequest2('part/'+partId, 'PUT', data);
                        classFrom.genTable();
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnSptDisable').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('part/disable/'+partId, 'PUT');
                    mzSetFieldValue('SptStatus', refStatus[2]['statusDesc'], 'text');
                    classFrom.genTable();
                    $('#btnSptDisable').hide();
                    $('#btnSptEnable').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSptEnable').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    mzAjaxRequest2('part/enable/'+partId, 'PUT');
                    mzSetFieldValue('SptStatus', refStatus[1]['statusDesc'], 'text');
                    classFrom.genTable();
                    $('#btnSptEnable').hide();
                    $('#btnSptDisable').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        oTableSptPartList = $('#dtSptPartList').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                const labels = ['#', 'Part No.', 'Location', 'Warranty', 'Expiration Date', 'Price per unit', 'DO No.', 'Registered By', 'Check In Time', 'Work Order No.', 'Technician', 'Status'];
                for (let i = 0; i < labels.length; i++) {
                    $('td', nRow).eq(i).attr('data-label', labels[i]);
                }
            },
            dom: "Brt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 1, 2, 3, 4, 5, 7, 9, 11] },
                { className: 'text-right', targets: [6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Store Part List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Store Part List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Store Part List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Store Part List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'partSubNo'},
                {mData: 'partSubLocation'},
                {mData: 'partSubWarranty', mRender: function (data){
                        return data !== '' ? data + ' year' + (data !== '1' ? 's' : '') : '';
                    }},
                {mData: 'partSubValidity'},
                {mData: 'partSubCost'},
                {mData: 'doNo', visible: false},
                {mData: 'partSubRegisteredBy', visible: false, mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'partSubTimeRegistered'},
                {mData: 'woTaskNo', visible: false},
                {mData: 'partSubCollectedBy', visible: false, mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'partSubStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }}
            ]
        });
        oTableSptPartList.buttons().container().appendTo('#sptPartListActions');
        oTableSptPartList.on('draw', function () {
            updatePartListSummary();
        });

        oTableSptPending = $('#dtSptPending').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if (checkOutTableWidth < 774) {
                    $(this).DataTable().column(6).visible(false);
                }
                if (checkOutTableWidth < 713) {
                    $(this).DataTable().column(3).visible(false);
                }
                if (checkOutTableWidth < 554) {
                    $(this).DataTable().column(4).visible(false);
                }
                $('.lnkSptPendingPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableSptPending.row(parseInt(rowId)).data();
                                let pdfId = currentRow['woTaskRequestMrfPdf'];
                                let pdfSrc;
                                if (pdfId !== null && currentRow['woTaskRequestMrfGenerate'] === 0) {
                                    pdfSrc = mzAjaxRequest2('wo_task_request/get_mrf_pdf_link/'+pdfId, 'GET');
                                } else {
                                    pdfSrc = mzAjaxRequest2('wo_task_request/preview_mrf_pdf/'+currentRow['woTaskRequestId'], 'GET');
                                }
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Material Request Form (MRF): '+currentRow['woTaskNo']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
            },
            dom: "Brt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 3, 7, 8] },
                { className: 'text-right', targets: [5] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Pending Request Part List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskRequestTimeOrdered'},
                {mData: 'woTaskRequestNo'},
                {mData: 'woTaskNo'},
                {mData: 'woTaskRequestOrderBy', mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskPartsQuantity'},
                {mData: 'woTaskPartsRemark'},
                {mData: 'woTaskPartsStatus', mRender: function (data){
                        return data !== '' ? refStatus[data]['statusDesc'] : '';
                    }},
                {mData: null, sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-file-pdf lnkSptPendingPdf" id="lnkSptPendingPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="MRF PDF"></i></a>';
                    }
                }
            ]
        });
        oTableSptPending.buttons().container().appendTo('#sptPendingActions');

        oTableSptCheckIn = $('#dtSptCheckIn').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "Brt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 3, 4] },
                { className: 'text-right', targets: [5, 6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Part Check In History', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Part Check In History', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Part Check In History', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Part Check In History', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'doItemTimestamp'},
                {mData: 'doNo'},
                {mData: 'doDate'},
                {mData: 'doItemWarranty', mRender: function (data){
                        return data !== '' ? data + ' year' + (data !== '1' ? 's' : '') : '';
                    }},
                {mData: 'doItemValidity'},
                {mData: 'doItemCost'},
                {mData: 'doItemTotal'}
            ]
        });
        oTableSptCheckIn.buttons().container().appendTo('#sptCheckInActions');

        oTableSptCheckOut = $('#dtSptCheckOut').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                if (checkOutTableWidth < 729) {
                    $(this).DataTable().column(5).visible(false);
                }
                if (checkOutTableWidth < 641) {
                    $(this).DataTable().column(3).visible(false);
                }
                $('.lnkSptCheckOutPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableSptCheckOut.row(parseInt(rowId)).data();
                                let pdfId = currentRow['woTaskRequestMrfPdf'];
                                let pdfSrc;
                                if (pdfId !== null && currentRow['woTaskRequestMrfGenerate'] === 0) {
                                    pdfSrc = mzAjaxRequest2('wo_task_request/get_mrf_pdf_link/'+pdfId, 'GET');
                                } else {
                                    pdfSrc = mzAjaxRequest2('wo_task_request/preview_mrf_pdf/'+currentRow['woTaskRequestId'], 'GET');
                                }
                                $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Material Request Form (MRF): '+currentRow['woTaskNo']);
                                $('#mpdf_iframe').attr('src', pdfSrc);
                                $('#modal_pdf').modal('show');
                            } catch (e) {
                                toastr['error'](e.message, _ALERT_TITLE_ERROR);
                            }
                            HideLoader();
                        }, 200);
                    }
                });
            },
            dom: "Brt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 3, 7] },
                { className: 'text-right', targets: [6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Part Check Out History', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Part Check Out History', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Part Check Out History', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Part Check Out History', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'woTaskRequestTimeCollected'},
                {mData: 'woTaskRequestNo'},
                {mData: 'woTaskNo'},
                {mData: 'woTaskRequestOrderBy', mRender: function (data){
                        return data !== '' ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskPartsRemark'},
                {mData: 'woTaskPartsQuantity'},
                {mData: null, sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-file-pdf lnkSptCheckOutPdf" id="lnkSptCheckOutPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="MRF PDF"></i></a>';
                    }
                }
            ]
        });
        oTableSptCheckOut.buttons().container().appendTo('#sptCheckOutActions');

        oTableSptReturnHistory = $('#dtSptReturnHistory').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            language: _DATATABLE_LANGUAGE,
            ordering: false,
            pageLength: 10,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "Brt<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 2, 5, 7] },
                { className: 'text-right', targets: [3] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Part Return History', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Part Return History', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Part Return History', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Part Return History', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'returnRequestDate'},
                {mData: 'technicianUserId', mRender: function (data){
                        return data && refUser[data] ? refUser[data]['userFirstName'] : '-';
                    }},
                {mData: 'quantityReturned'},
                {mData: 'returnReason', mRender: function (data){
                        if (!data) {
                            return '';
                        }
                        const formatted = data.replace(/_/g, ' ');
                        return formatted.replace(/\b\w/g, function (str) { return str.toUpperCase(); });
                    }},
                {mData: 'returnStatus', mRender: function (data){
                        if (!data) {
                            return '';
                        }
                        let badge = 'badge-secondary';
                        if (data === 'pending') {
                            badge = 'badge-warning';
                        } else if (data === 'completed') {
                            badge = 'badge-success';
                        } else if (data === 'rejected') {
                            badge = 'badge-danger';
                        }
                        const label = data.charAt(0).toUpperCase() + data.slice(1);
                        return '<span class="badge ' + badge + '">' + label + '</span>';
                    }},
                {mData: 'returnConfirmedDate', mRender: function (data){
                        return data && data !== '0000-00-00 00:00:00' ? data : '—';
                    }},
                {mData: 'storekeeperUserId', mRender: function (data){
                        return data && refUser[data] ? refUser[data]['userFirstName'] : '-';
                    }},
                {mData: 'returnRemarks', mRender: function (data){
                        return data ? $('<div>').text(data).html() : '';
                    }}
            ]
        });
        oTableSptReturnHistory.buttons().container().appendTo('#sptReturnActions');
    };

    this.load = function (_isEdit, _partId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_isEdit, _partId]);
                isEdit = _isEdit;
                partId = _partId;

                const part = mzAjaxRequest2('part/'+partId, 'GET');
                $('#lblTopTotalInStore').text(part['partCount']);
                $('#lblTopTotalILocked').text(part['partLocked']);
                $('#lblTopTotalAvailable').text(parseInt(part['partCount'])-parseInt(part['partLocked']));
                $('#txtSptDescription').text(refItem[part['itemId']]['itemDescription']);
                $('#txtSptItemType').text(refItemType[part['itemTypeId']]['itemTypeDesc']);

                const site = refSite[part['siteId']] || {};
                const store = refStore[part['storeId']] || {};
                const assetGroup = refAssetGroup[part['assetGroupId']] || {};
                const statusDesc = refStatus[part['partStatus']] ? refStatus[part['partStatus']]['statusDesc'] : '';

                $('#lblSptSiteName').text(site['siteName'] || '—');
                $('#lblSptStoreName').text(store['storeName'] || '—');
                $('#lblSptAssetGroupName').text(assetGroup['assetGroupName'] || '—');
                updateStatusPill(statusDesc);

                mzSetFieldValue('SptAssetGroup', assetGroup['assetGroupName'], 'text');
                mzSetFieldValue('SptSite', site['siteName'], 'text');
                mzSetFieldValue('SptStore', store['storeName'], 'text');
                mzSetFieldValue('SptThreshold', part['partThreshold'], 'text');
                mzSetFieldValue('SptMinOrder', part['partMinOrder'], 'text');
                mzSetFieldValue('SptMaxOrder', part['partMaxOrder'], 'text');
                mzSetFieldValue('SptRemark', part['partRemark'], 'textarea');
                mzSetFieldValue('SptStatus', statusDesc, 'text');
                self.setEditable(part['partStatus']);

                const picture = mzAjaxRequest2('item_image/'+part['itemId'], 'GET');
                $('#divSptImages').html('');
                for (let i=0; i<picture.length; i++) {
                    let dataSize = '1600x1067';
                    if (picture[i]['uploadFileWidth'] !== '' && picture[i]['uploadFileHeight'] !== '') {
                        dataSize = picture[i]['uploadFileWidth'] + 'x' + picture[i]['uploadFileHeight'];
                    }
                    const classHide = i > 0 ? 'divSptImageHide' : '';
                    $('#divSptImages').append('<figure class="col-md-12 text-center '+classHide+'">\n' +
                        '<a href="'+mzUrlDownload+picture[i]['uploadFolder']+'/'+picture[i]['uploadFilename']+'.'+picture[i]['uploadExtension']+'" data-size="'+dataSize+'">\n' +
                        '<img src="'+mzUrlDownload+picture[i]['uploadFolder']+'/'+picture[i]['uploadFilename']+'.'+picture[i]['uploadExtension']+'" class="img-fluid" width="100%">\n' +
                        '</a>\n' +
                        '<p class="mb-0 font-small divSptImageHide">'+picture[i]['uploadName']+'</p>\n' +
                        '</figure>');
                }
                $('.divSptImageHide').hide();

                self.genTablePending();
                self.genTablePartList();
                self.genTableCheckIn();
                self.genTableCheckOut();
                self.genTableReturnHistory();

                $('.sectionPart').show();
                classFrom.hideMain();
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.setEditable = function (_partStatus) {
        $('#btnSptDisable, #btnSptEnable, #btnSptDelete, #btnSptSave, #btnSptTransfer').hide();
        if (isEdit) {
            if (_partStatus === '1') {
                $('#btnSptDisable').show();
            } else {
                $('#btnSptEnable').show();
            }
            $('#btnSptSave, #btnSptTransfer').show();
            $('#txtSptThresholdPre').addClass('text-dark');
            $('#txtSptMinOrderPre').addClass('text-dark');
            $('#txtSptMaxOrderPre').addClass('text-dark');
            $('#txaSptRemarkPre').addClass('text-dark');
        } else {
            $('#txtSptThresholdPre').removeClass('text-dark');
            $('#txtSptMinOrderPre').removeClass('text-dark');
            $('#txtSptMaxOrderPre').removeClass('text-dark');
            $('#txaSptRemarkPre').removeClass('text-dark');
        }
    };

    this.genTablePartList = function () {
        const dataDb = mzAjaxRequest2('part_sub/list_available/'+partId, 'GET');
        oTableSptPartList.clear().rows.add(dataDb).draw();
        updatePartListSummary();
    };

    this.genTablePending = function () {
        const dataDb = mzAjaxRequest2('wo_parts/list_by_pending/'+partId, 'GET');
        oTableSptPending.clear().rows.add(dataDb).draw();
    };

    this.genTableCheckIn = function () {
        const dataDb = mzAjaxRequest2('do_item/'+partId, 'GET');
        oTableSptCheckIn.clear().rows.add(dataDb).draw();
    };

    this.genTableCheckOut = function () {
        const dataDb = mzAjaxRequest2('wo_parts/list_check_out/'+partId, 'GET');
        oTableSptCheckOut.clear().rows.add(dataDb).draw();
    };

    this.genTableReturnHistory = function () {
        const dataDb = mzAjaxRequest2('m_inventory/return_history', 'GET');
        const filtered = dataDb.filter(function (row) {
            return row && String(row['partId']) === String(partId);
        });
        oTableSptReturnHistory.clear().rows.add(filtered).draw();
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

    this.setRefItemType = function (_refItemType) {
        refItemType = _refItemType;
    };

    this.setRefItem = function (_refItem) {
        refItem = _refItem;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefStore = function (_refStore) {
        refStore = _refStore;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}