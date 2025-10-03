(function(){
  'use strict';
  let dt; let editingId = null;
  function headers(){ const t=sessionStorage.getItem('token'); return t?{'Authorization':'Bearer '+t}:{ }; }
  function statusBadge(v){ const cls = v? 'success':'danger'; const txt=v? 'Active':'Inactive'; return '<span class="badge badge-modern badge-'+cls+'">'+txt+'</span>'; }

  function initDataTable(){
    dt = $('#dtLoc').DataTable({ language:_DATATABLE_LANGUAGE, searching:true, ordering:true, responsive:true, paging:true,
      columnDefs:[{targets:0, orderable:false, searchable:false},{targets:-1, orderable:false, searchable:false}], order:[[1,'asc']], data:[],
      columns:[
        { data:null, render:function(){ return ''; } },
        { data:'spaceLocationName', defaultContent:'' },
        { data:'spaceLocationDesc', defaultContent:'' },
        { data:'spaceLocationStatus', render:function(d){ return statusBadge(parseInt(d||0)); } },
        { data:null, render:function(row){ var actBtn = parseInt(row.spaceLocationStatus)?'<button class="btn btn-outline-warning btn-sm btnLocDeactivate" title="Deactivate"><i class="fas fa-ban"></i></button>':'<button class="btn btn-outline-success btn-sm btnLocActivate" title="Activate"><i class="fas fa-check"></i></button>'; return '<div class="btn-group btn-group-sm" role="group">'+
            '<button type="button" class="btn btn-outline-secondary btnLocEdit" title="Edit"><i class="fas fa-pen"></i></button>'+ actBtn +'</div>'; } }
      ]
    });
    dt.on('order.dt search.dt', function () { dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) { cell.innerHTML = i + 1; }); }).draw();
  }

  function loadList(){ ShowLoader(); const status = $('#optLocStatus').val(); const qs = status!==''?('?status='+encodeURIComponent(status)) : ''; $.ajax({ url:'api/space_location.php'+qs, method:'GET', dataType:'json', headers:headers()})
    .done(function(resp){ if(resp&&resp.success){ dt.clear().rows.add(resp.result||[]).draw(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
    .fail(function(j){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
    .always(function(){ HideLoader(); }); }

  function openModal(row){ editingId = (row&&row.spaceLocationId)? parseInt(row.spaceLocationId): null; $('#frmLoc')[0].reset(); $('#hidLocId').val(editingId||''); $('#txtLocName').val(row?row.spaceLocationName:''); $('#txtLocDesc').val(row?row.spaceLocationDesc:''); try{$('#optLocStatusForm').materialSelect('destroy');}catch(e){} $('#optLocStatusForm').val(row? String(parseInt(row.spaceLocationStatus||0)):'1'); $('#optLocStatusForm').materialSelect(); $('#modalLocFormLabel').text(editingId? 'Edit Location':'Add Location'); $('#modalLocForm').modal('show'); }

  function save(){ const name=$('#txtLocName').val().trim(); if(!name){ toastr['error'](_ALERT_MSG_VALIDATION,_ALERT_TITLE_ERROR); return; } const payload={ name:name, description:($('#txtLocDesc').val()||null), status: parseInt($('#optLocStatusForm').val()||'1')}; ShowLoader(); if(editingId){ $.ajax({ url:'api/space_location.php/'+editingId, method:'PUT', data: JSON.stringify(payload), contentType:'application/json', dataType:'json', headers:headers()})
      .done(function(resp){ if(resp&&resp.success){ $('#modalLocForm').modal('hide'); toastr['success'](resp.errmsg||'Updated', _ALERT_TITLE_SUCCESS); loadList(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
    } else {
      $.ajax({ url:'api/space_location.php', method:'POST', data: JSON.stringify(payload), contentType:'application/json', dataType:'json', headers:headers()})
      .done(function(resp){ if(resp&&resp.success){ $('#modalLocForm').modal('hide'); toastr['success'](resp.errmsg||'Created', _ALERT_TITLE_SUCCESS); loadList(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
    }
  }

  function setStatus(row, status){ if(!row||!row.spaceLocationId){ return; } const payload={ status: status?1:0 }; ShowLoader(); $.ajax({ url:'api/space_location.php/'+row.spaceLocationId, method:'PUT', data: JSON.stringify(payload), contentType:'application/json', dataType:'json', headers:headers()})
    .done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Updated', _ALERT_TITLE_SUCCESS); loadList(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
    .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
    .always(function(){ HideLoader(); }); }

  function initFilters(){ try{$('#optLocStatus').materialSelect('destroy');}catch(e){} $('#optLocStatus').materialSelect(); $('#optLocStatus').on('change', loadList); $('#txtLocSearch').on('keyup', function(){ dt.search(this.value).draw(); }); }

  function initButtons(){ $('#btnDtLocRefresh').on('click', loadList); $('#btnLocAdd').on('click', function(){ openModal(null); }); $('#btnLocSave').on('click', save); $('#dtLoc').on('click','.btnLocEdit', function(){ const row = dt.row($(this).closest('tr')).data(); openModal(row); }); $('#dtLoc').on('click','.btnLocActivate', function(){ const row = dt.row($(this).closest('tr')).data(); setStatus(row,true); }); $('#dtLoc').on('click','.btnLocDeactivate', function(){ const row = dt.row($(this).closest('tr')).data(); setStatus(row,false); }); }

  $(document).ready(function(){ let pending=$('.includeHtml').length; if(pending===0){ try{ if(typeof initiatePages==='function'){ initiatePages(); } }catch(e){} initDataTable(); initFilters(); initButtons(); loadList(); } else { $('.includeHtml').each(function(){ const id=$(this).attr('id'); const target='html/'+id.substr(2)+'.html?'+new Date().valueOf(); $('#'+id).load(target, function(){ pending--; if(pending===0){ try{ if(typeof initiatePages==='function'){ initiatePages(); } }catch(e){} initDataTable(); initFilters(); initButtons(); loadList(); } }); }); } });
})();
