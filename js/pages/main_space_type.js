function MainSpaceType(){
  const className='MainSpaceType';
  let self=this; let oTable; let modalSpaceTypeClass; let modalConfirmDeleteClass;
  let categoryLookup = {}; // { spaceCategoryId: spaceCategoryName }
  this.init=function(){
  oTable=$('#dtSpcTyp').DataTable({ bLengthChange:false,bFilter:true,aaSorting:[[1,'asc']], language:_DATATABLE_LANGUAGE, dom:'Bfrtip', buttons:[{extend:'csv', title:'space_types'},{extend:'excel', title:'space_types'},{extend:'print', title:'space_types'}], fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=oTable.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); }, drawCallback:function(){ $('[data-toggle="tooltip"]').tooltip(); $('.lnkSpcTypEdit').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalSpaceTypeClass.edit(current['spaceTypeId'], rowId);} }); $('.lnkSpcTypDeactivate').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalSpaceTypeClass.deactivate(current['spaceTypeId'], rowId);} }); $('.lnkSpcTypActivate').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalSpaceTypeClass.activate(current['spaceTypeId'], rowId);} }); $('.lnkSpcTypDelete').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalConfirmDeleteClass.delete(current['spaceTypeId'], modalSpaceTypeClass);} }); }, aoColumns:[ {mData:null,bSortable:false}, {mData:'spaceTypeName'}, {mData:null, mRender:function(data,type,row){ const id=parseInt(row['spaceCategoryId']); const name = categoryLookup[id] || '-'; return '<span>'+name+'</span>'; } }, {mData:'spaceTypeDesc'}, {mData:null, mRender:function(data,type,row){ const status = parseInt(row['spaceTypeStatus'])===1; const cls=status?'badge-modern badge-success-dark':'badge-modern badge-danger-dark'; const text=status?'Active':'Inactive'; return '<span class="'+cls+'">'+text+'</span>'; } }, {mData:null,bSortable:false,sClass:'text-center', mRender:function(data,type,row,meta){ let label='<div class="action-btn-group">'; label+='<button type="button" class="btn-action btn-edit lnkSpcTypEdit" id="lnkSpcTypEdit_'+meta.row+'" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></button>'; if(parseInt(row['spaceTypeStatus'])===1){ label+='<button type="button" class="btn-action btn-deactivate lnkSpcTypDeactivate" id="lnkSpcTypDeactivate_'+meta.row+'" data-toggle="tooltip" title="Deactivate"><i class="fas fa-toggle-off"></i></button>'; } else { label+='<button type="button" class="btn-action btn-activate lnkSpcTypActivate" id="lnkSpcTypActivate_'+meta.row+'" data-toggle="tooltip" title="Activate"><i class="fas fa-toggle-on"></i></button>'; } label+='<button type="button" class="btn-action btn-delete lnkSpcTypDelete" id="lnkSpcTypDelete_'+meta.row+'" data-toggle="tooltip" title="Delete"><i class="fas fa-trash-alt"></i></button>'; label+='</div>'; return label; } }, {mData:'spaceTypeId', visible:false} ] });
  $("#dtSpcTyp_filter").hide();
  $('[data-toggle="popover"]').popover();
    // Move DT buttons and wire segmented export
    const $btns=$(oTable.buttons().container()); $('#dtSpcTypButtons').append($btns).addClass('d-none');
    $('#spcTypExportToggle .segmented-btn').on('click', function(){ const type=$(this).data('export'); if(type==='csv'){ oTable.button(0).trigger(); } else if(type==='excel'){ oTable.button(1).trigger(); } else if(type==='print'){ oTable.button(2).trigger(); } });
    $('#txtSpcTypSearch').on('keyup change', function(){ oTable.search($(this).val()).draw(); });
    $('#btnSpcTypAdd').on('click', function(){ modalSpaceTypeClass.add(); });
    $('#btnSpcTypRefreshTable').on('click', function(){ ShowLoader(); setTimeout(function(){ try{ self.genTable(1);} catch(e){ toastr['error'](e.message,_ALERT_TITLE_ERROR);} HideLoader(); }, 200); });
    // Preload category list for lookup mapping
    self.loadCategories().then(function(){ self.genTable(0); }).catch(function(){ self.genTable(0); });
  };

  this.genTable=function(refresh){ const token=sessionStorage.getItem('token'); const headers= token?{'Authorization':'Bearer '+token}:{ }; $.ajax({ url:'api/ref_space_type.php', method:'GET', dataType:'json', headers}).done(function(resp){ if(resp&&resp.success){ oTable.clear().rows.add(resp.result||[]).draw(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }); };

  this.loadCategories=function(){ return new Promise(function(resolve,reject){ const token=sessionStorage.getItem('token'); const headers= token?{'Authorization':'Bearer '+token}:{ }; $.ajax({ url:'api/ref_space_category.php', method:'GET', dataType:'json', headers}).done(function(resp){ if(resp&&resp.success){ categoryLookup={}; (resp.result||[]).forEach(function(c){ categoryLookup[parseInt(c['spaceCategoryId'])]=c['spaceCategoryName']; }); resolve(categoryLookup); } else { reject(new Error((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT)); } }).fail(function(){ reject(new Error(_ALERT_MSG_ERROR_DEFAULT)); }); }); };

  this.addTableAct=function(row){ oTable.row.add(row).draw(); };
  this.updateTableAct=function(row, idx){ const current=oTable.row(idx).data(); if(typeof row['spaceTypeName']!=='undefined') current['spaceTypeName']=row['spaceTypeName']; if(typeof row['spaceTypeDesc']!=='undefined') current['spaceTypeDesc']=row['spaceTypeDesc']; if(typeof row['spaceTypeStatus']!=='undefined') current['spaceTypeStatus']=row['spaceTypeStatus']; oTable.row(idx).data(current).draw(); };

  this.getClassName=function(){ return className; };
  this.setModalSpaceTypeClass=function(modal){ modalSpaceTypeClass=modal; };
  this.setModalConfirmDeleteClass=function(modal){ modalConfirmDeleteClass=modal; };
}

document.addEventListener('DOMContentLoaded', function(){
  ShowLoader();
  let pending = $('.includeHtml').length;
  function boot(){
    try{
      if (typeof initiatePages === 'function') { initiatePages(); }
      const mcd=new ModalConfirmDelete();
      const mst=new ModalSpaceType();
      const main=new MainSpaceType();
      mst.setClassFrom(main);
      main.setModalSpaceTypeClass(mst);
      main.setModalConfirmDeleteClass(mcd);
      main.init();
      mst.init();
    } catch(e){ toastr['error'](e.message,_ALERT_TITLE_ERROR); }
    HideLoader();
  }
  if (pending === 0) { setTimeout(boot, 200); }
  else {
    $('.includeHtml').each(function(){
      const id=$(this).attr('id');
      $('#'+id).load('html/'+id.substr(2)+'.html?'+new Date().valueOf(), function(){
        pending--; if (pending===0) { setTimeout(boot, 50); }
      });
    });
  }
});
