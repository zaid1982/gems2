function MainDrawingRecords () {
	
	const className = 'MainDrawingRecords';
    let self = this;
	let oTableDwr;
	let modalDrawingClass;
    let refAssetGroup;
    let modalConfirmDeleteClass;
	
	this.init = function () {
        let exportOpt = Object.assign({}, mzExportOpt);
        exportOpt['columns'] = [0, 1, 2, 3, 4, 5, 6, 7];
        let exportExcel = Object.assign({}, mzExportOpt);
        exportExcel['columns'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
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
                $('.lnkDwrEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableDwr.row(parseInt(rowId)).data();
                        modalDrawingClass.edit(currentRow['drawingId']);
                    }
                });
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
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-print"></i>', title:'GEMS - Drawing Records List', titleAttr: 'Print', exportOptions: exportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-copy"></i>', title:'GEMS - Drawing Records List', titleAttr: 'Copy', exportOptions: exportExcel},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 mb-1', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - Drawing Records List', titleAttr: 'Excel', exportOptions: exportExcel},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-0 mb-1', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - Drawing Records List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: exportOpt}
            ],
			aoColumns: [
                {mData: null, bSortable: false},
                {mData: 'drawingIdNo'},
                {mData: 'drawingTitle'},
                {mData: 'drawingVersion'},
                {mData: 'drawingPublishedBy'},
                {mData: 'drawingPublishedDate', width: '10%'},
                {mData: 'assetGroupId', mRender: function (data){
                    return data !== '' ? refAssetGroup[data]['assetGroupName'] : '';
                }},
                {mData: 'drawingRemark'},
                {mData: 'uploadBy'},
                {mData: 'drawingTimeCreated'},
                {mData: null, width: '96px',
                    mRender: function (data, type, row, meta) {
                        let label = '<a onclick="mzOpenUpload('+row['drawingDwg']+')"><i class="fas fa-download" data-toggle="tooltip" data-placement="top" title="Download DWG File"></i></a>&nbsp;';
                        label += '<a onclick="mzOpenPdfUpload('+row['drawingPdf']+')"><i class="fas fa-file-pdf" data-toggle="tooltip" data-placement="top" title="View PDF"></i></a>&nbsp;';
                        label += '<a><i class="fas fa-share-alt lnkDwrShare" id="lnkDwrShare_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Get Shared Link"></i></a>&nbsp;';
                        label += '<a><i class="fas fa-edit lnkDwrEdit" id="lnkDwrEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;';
                        label += '<a><i class="fas fa-trash-alt lnkDwrDelete" id="lnkDwrDelete_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
                        return label;
                    }}
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

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };
}