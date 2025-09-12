function MainLicense(){
  const className = 'MainLicense';
  let self = this;
  let oTable;
  let modalLicenseClass;

  const badge = (days)=>{
    const n = Number(days);
    if (!Number.isFinite(n)) return '<span class="badge badge-secondary">N/A</span>';
    if(n < 0) return '<span class="badge badge-danger">Expired</span>';
    if(n <= 30) return '<span class="badge badge-warning">Expiring</span>';
    return '<span class="badge badge-success">Valid</span>';
  };
  const fileLink = (row)=> {
    if(!row.uploadId) return '';
    const link = mzAjaxRequest2('document/upload_link/'+row.uploadId, 'GET');
    return `<a class="btn btn-outline-secondary btn-sm" target="_blank" href="${link}">Open</a>`;
  };

  this.init = function(){
    oTable = $('#dtLcnData').DataTable({
      bLengthChange:false,
      bFilter:true,
      aaSorting:[[3,'asc']],
      language:_DATATABLE_LANGUAGE,
      pageLength:25,
      autoWidth:false,
      fnRowCallback: function(nRow, aData, iDisplayIndex){
        const info = $(this).DataTable().page.info();
        $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
        const n = Number(aData.daysToExpire);
        if (Number.isFinite(n)) {
          if(n < 0) $(nRow).addClass('table-danger');
          else if(n <= 30) $(nRow).addClass('table-warning');
        }
      },
      dom: "<'row'<'col-5 px-0'B><'col-7 pb-0'f>>"+
           "<'row'<'col-sm-12'tr>>"+
           "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
      buttons:[
        { text:'<i class="fas fa-sync"></i>', className:'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr:'Refresh', action: ()=> self.genTable() },
        { text:'<i class=\"fas fa-plus\"></i> Add', className:'btn btn-outline-primary btn-sm px-2 ml-2', titleAttr:'Add License', action: ()=> modalLicenseClass.add() }
      ],
      columnDefs:[
        { bSortable:false, targets:[0,6] },
        { className:'text-center', targets:[0,4,6] },
        { className:'noVis', targets:[0,6] }
      ],
      aoColumns:[
        { mData:null },
        { mData:'licenseTitle' },
        { mData:'licenseStartDate' },
        { mData:'licenseEndDate' },
  { mData:'daysToExpire', mRender:function(d){ const txt = Number.isFinite(Number(d)) ? d : '-'; return txt+' '+badge(d);} },
        { mData:null, mRender:function(row){ return fileLink(row); } },
        { mData:null, mRender:function(row, type, full, meta){
            return '<a><i class="fas fa-edit lnkLcnEdit" id="lnkLcnEdit_'+meta.row+'" data-toggle="tooltip" title="Edit"></i></a>&nbsp;'
                 + '<a><i class="fas fa-trash-alt lnkLcnDelete" id="lnkLcnDelete_'+meta.row+'" data-toggle="tooltip" title="Delete"></i></a>';
        }}
      ]
    });

    $('#btnLcnAdd').on('click', ()=> modalLicenseClass.add());

    const tbody = $('#dtLcnData tbody');
    tbody.on('click', '.lnkLcnEdit', function(){
      const data = mzGetLinkRow($(this), oTable); if(!data) return; modalLicenseClass.edit(data['licenseId']);
    });
    tbody.on('click', '.lnkLcnDelete', function(){
      const id = mzGetLinkId($(this), oTable, 'licenseId'); if(!id) return; modalLicenseClass.delete(id);
    });

    self.genTable();
  };

  this.genTable = function(){
    const dataDb = mzAjaxRequest2('license', 'GET');
    oTable.clear().rows.add(dataDb).draw();
  };

  this.setModalLicenseClass = function(_modal){ modalLicenseClass = _modal; };
  this.getClassName = function(){ return className; };
}
