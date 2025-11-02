
(function(){
  'use strict';
  let dt; let editingId = null;
  function headers(){ const t=sessionStorage.getItem('token'); return t?{'Authorization':'Bearer '+t}:{ }; }
  function statusBadge(v){ const cls = v? 'badge-modern badge-success-dark':'badge-modern badge-danger-dark'; const txt=v? 'Active':'Inactive'; return '<span class="'+cls+'">'+txt+'</span>'; }

  function initDataTable(){
    dt = $('#dtLoc').DataTable({ language:_DATATABLE_LANGUAGE, searching:true, ordering:true, responsive:true, paging:true, dom:'Bfrtip', buttons:[{extend:'csv', title:'space_locations'},{extend:'excel', title:'space_locations'},{extend:'print', title:'space_locations'}],
      columnDefs:[{targets:0, orderable:false, searchable:false},{targets:-1, orderable:false, searchable:false}], order:[[1,'asc']], data:[],
      columns:[
        { data:null, render:function(){ return ''; } },
        { data:'spaceLocationName', defaultContent:'' },
        { data:'spaceLocationDesc', defaultContent:'' },
        { data:'spaceLocationStatus', render:function(d){ return statusBadge(parseInt(d||0)); } },
        { data:null, render:function(row){ 
            let label = '<div class="action-btn-group">';
            label += '<button type="button" class="btn-action btn-edit btnLocEdit" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></button>';
            if(parseInt(row.spaceLocationStatus)){ 
                label += '<button type="button" class="btn-action btn-deactivate btnLocDeactivate" data-toggle="tooltip" title="Deactivate"><i class="fas fa-toggle-off"></i></button>'; 
            } else { 
                label += '<button type="button" class="btn-action btn-activate btnLocActivate" data-toggle="tooltip" title="Activate"><i class="fas fa-toggle-on"></i></button>'; 
            }
            label += '</div>';
            return label;
        } }
      ]
    });
    dt.on('order.dt search.dt', function () { dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) { cell.innerHTML = i + 1; }); }).draw();
    const $btns=$(dt.buttons().container()); $('#dtLocButtons').append($btns).addClass('d-none');
    $('#locExportToggle .segmented-btn').on('click', function(){ const type=$(this).data('export'); if(type==='csv'){ dt.button(0).trigger(); } else if(type==='excel'){ dt.button(1).trigger(); } else if(type==='print'){ dt.button(2).trigger(); } });
  }

  function loadList(){
    ShowLoader();
    let qs = '';
    const $statusEl = $('#optLocStatus');
    if ($statusEl.length) {
      const status = $statusEl.val();
      if (status === '0' || status === '1') {
        qs = '?status=' + encodeURIComponent(status);
      }
    }
    $.ajax({ url:'api/space_location.php'+qs, method:'GET', dataType:'json', headers:headers()})
    .done(function(resp){ if(resp&&resp.success){ dt.clear().rows.add(resp.result||[]).draw(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
    .fail(function(j){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
  }

  function openModal(row){ editingId = (row&&row.spaceLocationId)? parseInt(row.spaceLocationId): null; $('#frmLoc')[0].reset(); $('#hidLocId').val(editingId||''); $('#txtLocName').val(row?row.spaceLocationName:''); $('#txtLocName').focus(); $('#txtLocDesc').val(row?row.spaceLocationDesc:''); $('#txtLocDesc').focus(); try{$('#optLocStatusForm').materialSelect('destroy');}catch(e){} $('#optLocStatusForm').val(row? String(parseInt(row.spaceLocationStatus||0)):'1'); $('#optLocStatusForm').materialSelect(); $('#modalLocFormLabel').text(editingId? 'Edit Location':'Add Location'); $('#modalLocForm').modal('show'); }

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

  function initFilters(){
    const $status = $('#optLocStatus');
    if ($status.length) {
      try { $status.materialSelect('destroy'); } catch(e){}
      $status.materialSelect();
      $status.on('change', loadList);
    }
    $('#txtLocSearch').on('keyup', function(){ dt.search(this.value).draw(); });
  }

  function initButtons(){ $('#btnDtLocRefresh').on('click', loadList); $('#btnLocAdd').on('click', function(){ openModal(null); }); $('#btnLocSave').on('click', save); $('#dtLoc').on('click','.btnLocEdit', function(){ const row = dt.row($(this).closest('tr')).data(); openModal(row); }); $('#dtLoc').on('click','.btnLocActivate', function(){ const row = dt.row($(this).closest('tr')).data(); setStatus(row,true); }); $('#dtLoc').on('click','.btnLocDeactivate', function(){ const row = dt.row($(this).closest('tr')).data(); setStatus(row,false); }); }

  $(document).ready(function(){ let pending=$('.includeHtml').length; if(pending===0){ try{ if(typeof initiatePages==='function'){ initiatePages(); } }catch(e){} $('[data-toggle="popover"]').popover(); $('[data-toggle="tooltip"]').tooltip(); initDataTable(); initFilters(); initButtons(); loadList(); } else { $('.includeHtml').each(function(){ const id=$(this).attr('id'); const target='html/'+id.substr(2)+'.html?'+new Date().valueOf(); $('#'+id).load(target, function(){ pending--; if(pending===0){ try{ if(typeof initiatePages==='function'){ initiatePages(); } }catch(e){} $('[data-toggle="popover"]').popover(); $('[data-toggle="tooltip"]').tooltip(); initDataTable(); initFilters(); initButtons(); loadList(); } }); }); } });
})();
