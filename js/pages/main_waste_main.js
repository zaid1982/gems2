function MainWasteMain () {

    const className = 'MainWasteMain';
    let self = this;
    let dtWtm;
    let modalWasteAdd;

    this.init = function () {
        $('#btnWtmAddWaste').on('click', function () {
            modalWasteAdd.add();
        });

        dtWtm = $('#dtWtm').DataTable({
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
                { bSortable: false, targets: [0, 8] },
                { className: 'text-center', targets: [0, 1, 3, 5, 6, 7, 8] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFctObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - Waste Collection List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - Waste Collection List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFctObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Waste Collection List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFctObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Waste Collection List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns: [
                {mData: null},
                {mData: 'date'},
                {mData: 'type'},
                {mData: 'due'},
                {mData: 'pic'},
                {mData: 'weight'},
                {mData: 'collect'},
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
                status: '<a class="badge badge-danger z-depth-2">Incomplete</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            },
            {
                date: '2023-08-03',
                type: 'Type B',
                due: '2023-08-15',
                pic: 'Junaidah Harun',
                weight: '70 kg',
                collect: '2023-08-16',
                status: '<a class="badge badge-success z-depth-2">Completed</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            },
            {
                date: '2023-08-10',
                type: 'Type B',
                due: '2023-08-18',
                pic: 'Hassan Ishak',
                weight: '55 kg',
                collect: '2023-08-19',
                status: '<a class="badge badge-success z-depth-2">Completed</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            },
            {
                date: '2023-08-16',
                type: 'Type A',
                due: '2023-08-31',
                pic: 'Muhammad Zulkarnaen Ismadi',
                weight: '',
                collect: '',
                status: '<a class="badge badge-warning z-depth-2">In Progress</a>',
                action: '<a><i class="far fa-edit fa-lg mr-1" data-toggle="tooltip" data-placement="top" title="Edit"></i></a><a><i class="far fa-trash-alt fa-lg" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>'
            }
        ];
        dtWtm.clear().rows.add(data).draw();
    };

    this.setModalWasteAdd = function (_modalWasteAdd) {
        modalWasteAdd = _modalWasteAdd;
    };
}