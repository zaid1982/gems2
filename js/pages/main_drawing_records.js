function MainDrawingRecords () {
	
	const className = 'MainDrawingRecords';
    let self = this;
	let oTableDwr;
	let modalDrawingClass;
	
	this.init = function () {
		oTableDwr = $('#dtDwrData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [5, 'desc'],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            },				
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0, 10] },
                { visible: false, targets: [7, 8, 9] },
                { className: 'text-center', targets: [0, 1, 3, 6, 9, 10] },
                { className: 'noVis', targets: [0, 10] }
            ],
			buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Drawing Records List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Drawing Records Lis', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Drawing Records Lis', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Drawing Records Lis', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
			aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'drawingId'},
                {mData: 'drawingTitle'},
                {mData: 'drawingVersion'},
                {mData: 'publishedBy'},
                {mData: 'publishedDate', width: '10%'},
                {mData: 'assetGroupId'},
                {mData: 'drawingRemark'},
                {mData: 'uploadBy'},
                {mData: 'uploadTime'},
                {mData: 'drawingStatus', width: '96px'}
			]				
        });			
		
		if ($('#dtDwrData').width() < 952) {
			oTableDwr.column(4).visible(false);
		} 
		if ($('#dtDwrData').width() < 720) {
			oTableDwr.column(6).visible(false);
		} 
		if ($('#dtDwrData').width() < 520) {
			oTableDwr.column(3).visible(false);
			oTableDwr.column(5).visible(false);
		}		

        $('#btnDtDwrDataRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
		
		$('#btnDwrDataAdd').on('click', function () {
            modalDrawingClass.add();
        });

        self.genTable();
	};
	
    this.genTable = function () {
        const dataDb = mzAjaxRequest2('drawing', 'GET');
        oTableDwr.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };	
	
    this.setModalDrawingClass = function (_modalDrawingClass) {
        modalDrawingClass = _modalDrawingClass;
    };
	
    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}