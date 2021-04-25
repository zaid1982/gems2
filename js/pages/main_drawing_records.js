function MainDrawingRecords () {
	
	const className = 'MainDrawingRecords';
    let self = this;
	let oTableDwr;
	
	this.init = function () {
		oTableDwr = $('#dtDwrData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [1, 'asc'],
            language: _DATATABLE_LANGUAGE,
            autoWidth: false,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { className: 'text-center', targets: [0, 1, 3, 6, 7] },
                { bSortable: false, targets: [0, 7] },
                { className: 'noVis', targets: [0, 7] }
            ],
			buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'two-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-sm px-2 ml-0 mb-1', titleAttr: 'Pilihan Kolum'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'SPDP 2.0 - Senarai Log Audit', titleAttr: 'Cetak', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'SPDP 2.0 - Senarai Log Audit', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'SPDP 2.0 - Senarai Log Audit', titleAttr: 'Excel', exportOptions: mzExportOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'SPDP 2.0 - Senarai Log Audit', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
			aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'auditTimestamp'},
                {mData: 'userName'},
                {mData: 'userFirstName'},
                {mData: 'auditIp'},
                {mData: 'auditIp2', width: '10%'},
                {mData: 'auditIp3'},
                {mData: 'ss', width: '54px'}
			]				
        });			
		console.log($('#dtDwrData').width());
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
	};
	
    this.getClassName = function () {
        return className;
    };
}