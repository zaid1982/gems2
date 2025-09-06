function MainZone () {

    const className = 'MainZone';
    let self = this;
    let oTableZne;
    let refStatus;
    let refSite;
    let modalZoneClass;
    let urlLinkBase;
    let baseAppPath;

    this.init = function () {
    const urlParams = window.location.href.split('/p_');
    baseAppPath = urlParams[0]; // base including protocol, host and application path before /p_ segment
    urlLinkBase = baseAppPath + '/p_complaint?z=';
        
        oTableZne = $('#dtZneData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 3, 6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'one-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnZneHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnZneHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnZneHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnZneHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Zone List', titleAttr: 'PDF', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-plus mr-2"></i>Add New Zone', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnZneAdd' }, titleAttr: 'Add New FCA Zone'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnZneAdd').off('click').on('click', function () {
                    modalZoneClass.add();
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: 'zoneType'},
                {mData: 'zoneCode'},
                {mData: 'zoneName'},
        {mData: null, mRender: function(row) {
            const zoneId = row.zoneId;
            const siteId = row.siteId;
            const complaintLink = urlLinkBase + zoneId; // already full path
            const ptwLink = baseAppPath + '/ptw_form.html?site_id=' + encodeURIComponent(siteId || '');
            return '<div class="small" style="font-family:monospace; line-height:1.2; word-break:break-all;">'
                + complaintLink + '<br>' + ptwLink + '</div>';
            }},
                {mData: 'zoneStatus', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }}
            ]
        });
        let oTableZneTbody = $('#dtZneData tbody');
        oTableZneTbody.delegate('tr', 'click', function () {
            const data = $('#dtZneData').DataTable().row(this).data();
            modalZoneClass.edit(data['zoneId']);
        });
        oTableZneTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtZneData').DataTable().row(this).data();
            const zoneName = typeof data['zoneName'] !== 'undefined' ? data['zoneName'] : '';
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to edit '+zoneName+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('zone', 'GET');
        console.log(dataDb);
        oTableZne.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalZoneClass = function (_modalZoneClass) {
        modalZoneClass = _modalZoneClass;
    };
    
    this.getUrlLinkBase = function () {
        return urlLinkBase;
    };

    this.getBaseAppPath = function () {
        return baseAppPath;
    };
}