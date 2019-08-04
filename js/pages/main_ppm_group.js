function MainPpmGroup() {

    const className = 'MainPpmGroup';
    let self = this;
    let refStatus;
    let refRole;
    let refUser;
    let refClient;
    let refSite;
    let clientId;
    let siteId;
    let oTableTechnician;

    this.init = function () {
        clientId = '1';
        siteId = '1';

        mzOption('optPgrClientId', refClient, 'Choose Client', 'clientId', 'clientName', {}, 'required');
        mzOption('optPgrSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: clientId}, 'required');

        $('#optPgrClientId').val(clientId);
        $('#optPgrSiteId').val(siteId);

        $('#optPgrClientId').on('change', function () {
            mzOptionStop('optPgrSiteId', refSite, 'Choose Site', 'siteId', 'siteName', {clientId: $(this).val(), siteStatus: '1'}, 'required');
        });

        const vData = [
            {
                field_id: 'optPgrClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optPgrSiteId',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formPgrSearch');
        formValidate.registerFields(vData);

        $('#formPgrSearch').on('keyup change', function () {
            $('#btnPgrSearch').attr('disabled', !formValidate.validateForm());
        });

        oTableTechnician = $('#dtPgrTechnician').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            //aaSorting: [1, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableTechnician.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPgrLocationCodeEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableTechnician.row(parseInt(rowId)).data();
                       // modalLocationCodeClass.edit(currentRow['locationCodeId'], contractId, rowId);
                    }
                });
                $('.lnkPgrLocationCodeDelete').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableTechnician.row(parseInt(rowId)).data();
                        //modalConfirmDeleteClass.delete(currentRow['locationCodeId'], modalLocationCodeClass);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmGroupName', bSortable: false},
                    {mData: 'ppmGroupReportTo', bSortable: false},
                    {mData: 'totalUser', bSortable: false},
                    /*{mData: 'ppmGroupReportTo', mRender: function (data) { return mzFormatNumber(data);}},*/
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['ppmGroupStatus']]['statusColor']+' z-depth-2">'+refStatus[row['ppmGroupStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<a><i class="fas fa-edit lnkPgrLocationCodeEdit" id="lnkPgrLocationCodeEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-trash-alt lnkPgrLocationCodeDelete" id="lnkPgrLocationCodeDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                            return label;
                        }
                    }
                ]
        });
        $("#dtPgrTechnician_filter").hide();
        $('#txtPgrLocationCodeSearch').on('keyup change', function () {
            oTableTechnician.search($(this).val()).draw();
        });

        self.genTableTechnician();
    };

    this.genTableTechnician = function () {
        const dataTechnician = [{ppmGroupName:'Halimi Staff\'s', ppmGroupReportTo: 'Azmi Staff\'s', totalUser:'3', ppmGroupStatus:'1'}, {ppmGroupName:'Salim Staff\'s', ppmGroupReportTo: 'Azmi Staff\'s', totalUser:'3', ppmGroupStatus:'1'}]
        //const dataTechnician = mzAjaxRequest('track_monitoring.php?type=track_monitoring_list&siteCode='+siteCode+'&flowId='+flowId+'&year='+yearId, 'GET');
        oTableTechnician.clear().rows.add(dataTechnician).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };
}