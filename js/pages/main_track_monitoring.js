function MainTrackMonitoring() {

    const className = 'MainTrackMonitoring';
    let self = this;
    let refStatus;
    let refRole;
    let refUser;
    let refFlow;
    let refCheckpoint;
    let oTableTrack;
    let modalTaskHistoryClass;

    this.init = function () {
        mzOption('optTnmFlowId', refFlow, 'All Flow', 'flowId', 'flowDesc', {}, 'required');

        oTableTrack =  $('#dtTnmList').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [6, 'desc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableTrack.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkTnmListEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableTrack.row(parseInt(rowId)).data();
                        modalTaskHistoryClass.edit(currentRow['assetId'], rowId);
                    }
                });
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'transactionNo'},
                    {mData: null, mRender: function (data, type, row){
                            return row['flowId'] !== '' ? refFlow[row['flowId']]['flowDesc'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['checkpointId'] !== '' ? refCheckpoint[row['checkpointId']]['checkpointDesc'] : '';
                        }},
                    {mData: 'userId', mRender: function (data){
                            return data !== '' ? refUser[data]['userFullName'] : '';
                        }},
                    {mData: 'roleId', mRender: function (data){
                            return data !== '' ? refRole[data]['roleDesc'] : '';
                        }},
                    {mData: 'taskTimeCreated'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['transactionStatus']]['statusColor']+' z-depth-2">'+refStatus[row['transactionStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            return '<a><i class="fas fa-edit lnkTnmListEdit" id="lnkTnmListEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                        }
                    },
                    {mData: 'flowId', visible: false},
                    {mData: 'checkpointId', visible: false}
                ]
        });
        $("#dtTnmList_filter").hide();
        $('#txtTnmListSearch').on('keyup change', function () {
            oTableTrack.search($(this).val()).draw();
        });

        $('#optTnmFlowId').on('change', function () {
            mzOptionStop('optTnmCheckpointId', refCheckpoint, 'All Checkpoint', 'checkpointId', 'checkpointDesc', {flowId: $(this).val()});
            oTableTrack.column(9).search($(this).val(), false, true, false).draw();
            oTableTrack.column(10).search('', false, true, false).draw();
        });

        $('#optTnmCheckpointId').on('change', function () {
            oTableTrack.column(10).search($(this).val(), false, true, false).draw();
        });

        let cntTrack;
        let btnTrackOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntTrack = 1;
                        }
                        if (column === 7) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntTrack++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableTrack, {
            buttons: [
                $.extend( true, {}, btnTrackOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Transaction List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnTrackOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Transaction List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnTrackOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Transaction List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtTnmListExport'));

        $('#btnDtTnmListRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableTrack();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
        
        self.genTableTrack();
    };

    this.genTableTrack = function () {
        const dataTrack = mzAjaxRequest('track_monitoring.php?type=track_monitoring_list', 'GET');
        oTableTrack.clear().rows.add(dataTrack).draw();
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

    this.setRefFlow = function (_refFlow) {
        refFlow = _refFlow;
    };

    this.setRefCheckpoint = function (_refCheckpoint) {
        refCheckpoint = _refCheckpoint;
    };

    this.setModalTaskHistoryClass = function (_modalTaskHistoryClass) {
        modalTaskHistoryClass = _modalTaskHistoryClass;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

}