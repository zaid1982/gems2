function SectionPr() {

    const className = 'SectionPr';
    let self = this;
    let classFrom;
    let isEdit;
    let prId;
    let oTableItem;

    this.init = function () {
        $('.sectionPr').hide();

        $('#btnSprBack').on('click', function () {
            $('.sectionPr').hide();
            classFrom.showMain();
            $(window).scrollTop(0);
        });

        $('#txtSprSupplier').on('click', function () {
        });

        let exportOptSprItem = Object.assign({}, mzExportOpt);
        exportOptSprItem['columns'] = [1, 2, 4, 5, 6];
        let oTableItem = $('#dtSprItem').DataTable({
            bLengthChange: false,
            bFilter: false,
            language: _DATATABLE_LANGUAGE,
            bInfo: false,
            bPaginate: false,
            ordering: false,
            autoWidth: false,
            //aaSorting: [[9, 'asc']],
            dom: "<'row'<'col-sm-12'B>>" +
                "<'row'<'col-sm-12'tr>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 5, 8] },
                { className: 'text-right', targets: [2, 3, 4, 6, 7] }
            ],
            buttons: [
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-print"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - ', titleAttr: 'Cetak', exportOptions: exportOptSprItem},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-copy"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - ', titleAttr: 'Copy', exportOptions: exportOptSprItem},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - ', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 mx-1 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'SPDP 2.0 - Senarai Semak Pematuhan - ', titleAttr: 'PDF', exportOptions: exportOptSprItem}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                initPhotoSwipeFromDOM('.mdb-lightbox');
                $('.xx').hide();
            },
            aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'a1'},
                {mData: 'b1', width: '8%'},
                {mData: 'c1', width: '8%'},
                {mData: 'd1', width: '8%'},
                {mData: 'd2', width: '8%',
                    mRender: function (data, type, row) {
                        return '<input class="form-control form-control-sm" type="number" style="text-align: right;" value="25">';
                    }
                },
                {mData: 'e1', width: '8%',
                    mRender: function (data, type, row) {
                        return '<input class="form-control form-control-sm" type="number" style="text-align: right;" value="15.00">';
                    }
                },
                {mData: 'f1', width: '8%'},
                {mData: 'h1', width: '15%',
                    mRender: function (data, type, row) {
                        let label = '';
                            label += '<div class="mdb-lightbox no-margin">\n';
                            label += '<figure class="col-md-12">\n' +
                                '<a href="//localhost:8081/gems2/img/background/pen_book.jpg" data-size="1600x1067">\n' +
                                '<img src="//localhost:8081/gems2/img/background/pen_book.jpg" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                '</a>\n' +
                                '</figure>\n';
                            label += '<figure class="col-md-12 xx">\n' +
                                '<a href="//localhost:8081/gems2/img/background/lucky-draw.jpeg" data-size="1600x1067">\n' +
                                '<img src="//localhost:8081/gems2/img/background/lucky-draw.jpeg" class="img-fluid img-thumbnail" alt="thumbnail" width="100%">\n' +
                                '</a>\n' +
                                '</figure>\n';
                            return label;
                    }
                }
            ]
        });

        const dataItems = [
            {a1: 'LSP 4"x12" SS Push Plate with "PUSH" Sign<br/>(Civil - Door Accessories)', b1: '4', c1: '10', d1: '25', d2: '1', e1: '15.00',f1: '1,250.00', g1: 'box', h1: '2'},
            {a1: 'Indicator Lamp - Yellow/Amber<br/>(Electrical - Indicator Lamp)', b1: '4', c1: '10', d1: '25', d2: '1', e1: '15.00',f1: '1,250.00', g1: 'box', h1: '2'},
            {a1: 'Insulation Foam Sheet – 1 1/2 X 4 X 3<br/>(Mechanical - Aircond Insulation / Gas / Ventilation Fan)', b1: '4', c1: '10', d1: '25', d2: '1', e1: '15.00',f1: '1,250.00', g1: 'box', h1: '2'}
        ];
        oTableItem.clear().rows.add(dataItems).draw();

        $('#btnSprSaveItems').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const result = mzAjaxRequest2('pr/refresh_pdf/1', 'PUT');
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    this.load = function (_isEdit, _prId) {
        try {
            mzCheckFuncParam([_isEdit, _prId]);
            isEdit = _isEdit;
            prId = _prId;

            $('.sectionPr').show();
            classFrom.hideMain();
            window.scrollTo({top: 0, behavior: 'smooth'});
        } catch (e) {
            throw new Error(e.message);
        }
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

}