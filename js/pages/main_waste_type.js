function MainWasteType () {

    const className = 'MainWasteType';
    let self = this;
    let dtWtt;
    let modalWasteType;

    this.init = function () {
        $('#btnWttAddWaste').on('click', function () {
            modalWasteType.add();
        });

        dtWtt = $('#dtWtt').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            dom: "<'row'<'col-5 px-0 pb-2'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, ] },
                { className: 'text-center', targets: [0, 1, 2, 3, 4] },
                { className: 'noVis', targets: [0] }
            ],
            drawCallback: function () {
                $('.lnkWttEdit').off('click').on('click', function () {
                    modalWasteType.add();
                });
            },
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Waste Type List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Waste Type List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Waste Type List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Waste Type List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns: [
                {mData: null},
                {mData: 'type'},
                {mData: 'date'},
                {mData: 'status'},
                {mData: 'action'}
            ]
        });

        const data = [
            {
                date: '2023-08-01',
                type: 'Type C',
                due: '2023-08-12',
                pic: 'Ismail Hambal',
                weight: '-',
                collect: '-',
                status: '<a class="badge badge-success z-depth-2">Active</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1 lnkWttEdit" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            },
            {
                date: '2023-08-10',
                type: 'Type B',
                due: '2023-08-18',
                pic: 'Hassan Ishak',
                weight: '55 kg',
                collect: '2023-08-19',
                status: '<a class="badge badge-success z-depth-2">Active</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1 lnkWttEdit" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            },
            {
                date: '2023-08-16',
                type: 'Type A',
                due: '2023-08-31',
                pic: 'Muhammad Zulkarnaen Ismadi',
                weight: '',
                collect: '',
                status: '<a class="badge badge-danger z-depth-2">Disable</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1 lnkWttEdit" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            }
        ];
        dtWtt.clear().rows.add(data).draw();
    };

    this.setModalWasteType = function (_modalWasteType) {
        modalWasteType = _modalWasteType;
    };
}