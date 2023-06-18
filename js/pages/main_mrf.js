function MainMrf() {

    const className = 'MainMrf';
    let self = this;
    let oTableMrfList;
    let userSite;
    let userClient;
    let refStatus;
    let refClient;
    let refSite;
    let refUser;
    let arrYear = [];
    let dt = new Date();
    const currentYear = dt.getFullYear();

    this.init = function () {
        $('#divMrfTable').hide();
        userSite = mzGetUserInfoByParam('siteId');
        userClient = mzGetUserInfoByParam('clientId');
        for (let year = 2021; year <= currentYear; year++) {
            arrYear.push({yearId:year.toString(), yearDesc:year.toString()});
        }

        if (mzIsRoleExist('1,10')) {
            mzOption('optMrfClient', refClient, 'Select Client', 'clientId', 'clientName', {clientStatus: '1'}, 'required');
            $('#optMrfClient').on('change', function () {
                mzOptionStop('optMrfSite', refSite, 'Select Site', 'siteId', 'siteName', {siteStatus: '1', clientId: $(this).val()}, 'required');
            });
        }
        else {
            mzOption('optMrfClient', refClient, 'Select Client', 'clientId', 'clientName', {clientId: userClient, clientStatus: '1'}, 'required');
            mzOption('optMrfSite', refSite, 'Select Site', 'siteId', 'siteName', {siteId: userSite, siteStatus: '1'}, 'required');
            mzSetFieldValue('MrfClient', userClient, 'select', '', true);
            mzSetFieldValue('MrfSite', userSite, 'select', '', true);
        }
        mzOption('optMrfYear', arrYear, 'Select Year', 'yearId', 'yearDesc', {}, 'required');
        mzOption('optMrfMonth', mzGetMonthArray(), 'Select Month', 'monthId', 'monthName', {}, 'required', false);

        const vData = [
            {
                field_id: 'optMrfClient',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMrfSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMrfYear',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMrfMonth',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formMrf');
        formValidate.registerFields(vData);

        $('#btnMrfSearch').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        self.genMrfTable();
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        oTableMrfList = $('#dtMrfList').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0 pb-2'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 2, 3, 4, 6] },
                { className: 'noVis', targets: [0, 7] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0 z-depth-2', titleAttr: 'Pilihan Kolum'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 z-depth-2', text:'<i class="fas fa-print"></i>', title:'GEMS - MRF List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 z-depth-2', text:'<i class="fas fa-copy"></i>', title:'GEMS - MRF List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 z-depth-2', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - MRF List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 z-depth-2', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - MRF List', titleAttr: 'PDF', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                // 707 546
                if ($('#divPageWidth').width() < 707) {
                    $(this).DataTable().column(4).visible(false);
                    $(this).DataTable().column(5).visible(false);
                }
                if ($('#divPageWidth').width() < 546) {
                    $(this).DataTable().column(3).visible(false);
                }
                $('.lnkMrfListPdf').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        ShowLoader();
                        setTimeout(function () {
                            try {
                                const rowId = linkId.substr(linkIndex+1);
                                const currentRow = oTableMrfList.row(parseInt(rowId)).data();
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
            aoColumns: [
                {mData: null},
                {mData: 'woTaskRequestTimeOrdered'},
                {mData: 'woTaskRequestNo'},
                {mData: 'woTaskNo'},
                {mData: 'woTaskRequestOrderBy', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                {mData: 'woTaskRequestRemark'},
                {mData: 'woTaskRequestStatus', mRender: function(data) {
                        return data !== null ? refStatus[data]['statusDesc'] : '';
                    }},
                {mData: null, bSortable: false, sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return '<a><i class="far fa-file-pdf lnkMrfListPdf" id="lnkMrfListPdf_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="MRF PDF"></i></a>';
                    }
                }
            ]
        });
    };

    this.genMrfTable = function () {
        const dataDb = mzAjaxRequest2('wo_task_request/list_mrf/'+$('#optMrfSite').val()+'/'+$('#optMrfYear').val()+'/'+$('#optMrfMonth').val(), 'GET');
        oTableMrfList.clear().rows.add(dataDb).draw();
        $('#divMrfTable').show();
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
}