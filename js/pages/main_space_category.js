function MainSpaceCategory() {
  const className = 'MainSpaceCategory';
  let self = this;
  let oTable;
  let modalSpaceCategoryClass;
  let modalConfirmDeleteClass;

  this.init = function(){
    oTable = $('#dtSpcCat').DataTable({ bLengthChange:false, bFilter:true, aaSorting:[[1,'asc']], language:_DATATABLE_LANGUAGE, dom:'Bfrtip', buttons:[{extend:'csv', title:'space_categories'},{extend:'excel', title:'space_categories'},{extend:'print', title:'space_categories'}], fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=oTable.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); }, drawCallback:function(){ $('[data-toggle="tooltip"]').tooltip(); $('.lnkSpcCatEdit').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalSpaceCategoryClass.edit(current['spaceCategoryId'], rowId); } }); $('.lnkSpcCatDeactivate').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalSpaceCategoryClass.deactivate(current['spaceCategoryId'], rowId); } }); $('.lnkSpcCatActivate').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalSpaceCategoryClass.activate(current['spaceCategoryId'], rowId); } }); $('.lnkSpcCatDelete').off('click').on('click', function(){ const id=$(this).attr('id'); const idx=id.indexOf('_'); if(idx>0){ const rowId=id.substr(idx+1); const current=oTable.row(parseInt(rowId)).data(); modalConfirmDeleteClass.delete(current['spaceCategoryId'], modalSpaceCategoryClass); } }); }, aoColumns:[ {mData:null,bSortable:false}, {mData:'spaceCategoryName'}, {mData:'spaceCategoryDesc'}, {mData:null, mRender:function(data,type,row){ const isActive = parseInt(row['spaceCategoryStatus'])===1; const cls = isActive ? 'badge-modern badge-success-dark' : 'badge-modern badge-danger-dark'; const text = isActive ? 'Active' : 'Inactive'; return '<span class="'+cls+'">'+text+'</span>'; } }, {mData:null,bSortable:false,sClass:'text-center', mRender:function(data,type,row,meta){ let label = '<a><i class="fas fa-edit lnkSpcCatEdit" id="lnkSpcCatEdit_'+meta.row+'" data-toggle="tooltip" title="Edit"></i></a>&nbsp;&nbsp;'; if (parseInt(row['spaceCategoryStatus'])===1) { label += '<a><i class="fas fa-toggle-off lnkSpcCatDeactivate" id="lnkSpcCatDeactivate_'+meta.row+'" data-toggle="tooltip" title="Deactivate"></i></a>&nbsp;&nbsp;'; } else { label += '<a><i class="fas fa-toggle-on lnkSpcCatActivate" id="lnkSpcCatActivate_'+meta.row+'" data-toggle="tooltip" title="Activate"></i></a>&nbsp;&nbsp;'; } label += '<a><i class="fas fa-trash-alt lnkSpcCatDelete" id="lnkSpcCatDelete_'+meta.row+'" data-toggle="tooltip" title="Delete"></i></a>'; return label; } }, {mData:'spaceCategoryId', visible:false} ] });
    $("#dtSpcCat_filter").hide();
    // Move DT buttons to container and wire segmented export
    const $btns = $(oTable.buttons().container()); $('#dtSpcCatButtons').append($btns).addClass('d-none');
    $('#spcCatExportToggle .segmented-btn').on('click', function(){ const type=$(this).data('export'); if(type==='csv'){ oTable.button(0).trigger(); } else if(type==='excel'){ oTable.button(1).trigger(); } else if(type==='print'){ oTable.button(2).trigger(); } });
    $('#txtSpcCatSearch').on('keyup change', function(){ oTable.search($(this).val()).draw(); });
    $('#btnSpcCatAdd').on('click', function(){ modalSpaceCategoryClass.add(); });
    $('#btnSpcCatRefresh').on('click', function(){ ShowLoader(); setTimeout(function(){ try{ self.genTable(1); } catch(e){ toastr['error'](e.message,_ALERT_TITLE_ERROR);} HideLoader(); },200); });
    self.genTable(0);
  };

  this.genTable = function(refresh){ const token = sessionStorage.getItem('token'); const headers = token ? { 'Authorization':'Bearer '+token } : {}; $.ajax({ url:'api/ref_space_category.php', method:'GET', dataType:'json', headers}).done(function(resp){ if(resp && resp.success){ oTable.clear().rows.add(resp.result||[]).draw(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR); }); };

  this.addTableAct = function(row){ oTable.row.add(row).draw(); };

  this.updateTableAct = function(row, idx){ const current = oTable.row(idx).data(); if (typeof row['spaceCategoryName'] !== 'undefined') current['spaceCategoryName']=row['spaceCategoryName']; if (typeof row['spaceCategoryDesc'] !== 'undefined') current['spaceCategoryDesc']=row['spaceCategoryDesc']; if (typeof row['spaceCategoryStatus'] !== 'undefined') current['spaceCategoryStatus']=row['spaceCategoryStatus']; oTable.row(idx).data(current).draw(); };

  this.getClassName = function(){ return className; };
  this.setModalSpaceCategoryClass = function(modal){ modalSpaceCategoryClass = modal; };
  this.setModalConfirmDeleteClass = function(modal){ modalConfirmDeleteClass = modal; };
}

document.addEventListener('DOMContentLoaded', function(){
  ShowLoader(); setTimeout(function(){ try { initiatePages(); const mcd = new ModalConfirmDelete(); const msc = new ModalSpaceCategory(); const main = new MainSpaceCategory(); msc.setClassFrom(main); main.setModalSpaceCategoryClass(msc); main.setModalConfirmDeleteClass(mcd); main.init(); msc.init(); } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} HideLoader(); }, 200);
});
