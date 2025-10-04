(function(){
  'use strict';

  let dt; let toCancelId=null;

  function headers(){ const t=sessionStorage.getItem('token'); return t?{'Authorization':'Bearer '+t}:{ }; }

  function initFilters(){
    const isAdmin = mzIsRoleExist('1,10');
    const userSiteId = mzGetUserInfoByParam('siteId');
    try { $('#optMyResvSite').materialSelect('destroy'); } catch(e){}
    $('#optMyResvSite').empty();
    if(!isAdmin && userSiteId){
      $('#optMyResvSite').append('<option value="'+userSiteId+'" selected>'+ (mzGetUserInfoByParam('siteName')||('Site #'+userSiteId)) +'</option>');
      $('#optMyResvSite').prop('disabled', true);
    } else {
      $('#optMyResvSite').append('<option value="">All Sites</option>');
    }
    $('#optMyResvSite').materialSelect();
    try { $('#optMyResvStatus').materialSelect('destroy'); } catch(e){}
    $('#optMyResvStatus').materialSelect();
    $('#optMyResvSite, #optMyResvStatus, #dtMyResvFrom, #dtMyResvTo').on('change', loadData);
  }

  function initTable(){
    dt = $('#dtMyResv').DataTable({ bLengthChange:false,bFilter:true,aaSorting:[[2,'desc']], language:_DATATABLE_LANGUAGE,
      fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=dt.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); },
      aoColumns:[
        { mData:null, bSortable:false },
        { mData:null, mRender:function(d,t,row){ const id=(row.spaceId??row.space_id); const sn=row.spaceName||('Space #'+(id||'')); return '<a href="space_preview.html?id='+(id||'')+'">'+sn+'</a>'; } },
        { mData:function(row){ return row.reservationStart||row.reservation_start||null; }, mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; } },
        { mData:function(row){ return row.reservationEnd||row.reservation_end||null; }, mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; } },
        { mData:function(row){ return row.reservationStatus||row.reservation_status||''; } },
        { mData:null, bSortable:false, sClass:'text-center', mRender:function(d,t,row){ const status=(row.reservationStatus||row.reservation_status||''); const disabled=status==='CANCELED'; const rid=(row.reservationId??row.reservation_id); return '<button class="btn btn-sm btn-outline-warning btnMyResvCancel" '+(disabled?'disabled':'')+' data-id="'+(rid||'')+'"><i class="fas fa-ban"></i></button>'; } }
      ]
    });
    $('#dtMyResv').on('click','.btnMyResvCancel', function(){ toCancelId=parseInt($(this).data('id')); $('#txaMyResvReason').val(''); $('#modalMyResvCancel').modal('show'); });
    $('#btnMyResvConfirmCancel').on('click', doCancel);
    // Show reservation detail on row click (excluding button clicks)
    $('#dtMyResv tbody').on('click', 'tr', function(e){
      if($(e.target).closest('.btnMyResvCancel').length) return; // ignore cancel button
      const data = dt.row(this).data(); if(!data) return;
      const spaceId = data.spaceId || data.space_id || '';
      const spaceName = data.spaceName || ('Space #' + spaceId);
      const start = data.reservationStart || data.reservation_start || null;
      const end = data.reservationEnd || data.reservation_end || null;
      const status = (data.reservationStatus || data.reservation_status || '').toUpperCase();
      const rid = data.reservationId || data.reservation_id || '';
      const notes = data.specialRequest || data.special_request || '';
      const by = data.requestedByName || data.requested_by_name || '';
      const contact = data.requestedByContact || data.requested_by_contact || '';

      $('#lblMyResvSpaceName').text(spaceName);
      $('#lnkMyResvSpace').attr('href', 'space_preview.html?id=' + spaceId);
      $('#lblMyResvStatus').text(status).removeClass('badge-success-dark badge-warning badge-danger-dark')
        .addClass(status==='RESERVED'?'badge-success-dark':(status==='CANCELED'?'badge-danger-dark':'badge-warning'));
      $('#lblMyResvStart').text(start?moment(start).format('YYYY-MM-DD HH:mm'):'-');
      $('#lblMyResvEnd').text(end?moment(end).format('YYYY-MM-DD HH:mm'):'-');
      $('#lblMyResvId').text(rid||'-');
      $('#lblMyResvRequester').text(by||'-');
      $('#lblMyResvContact').text(contact||'-');
      $('#lblMyResvNotes').text(notes||'-');

      // Attach cancel action for details modal
      $('#btnMyResvDetailsCancel').off('click').on('click', function(){
        toCancelId = parseInt(rid);
        $('#modalMyResvDetails').modal('hide');
        $('#txaMyResvReason').val('');
        $('#modalMyResvCancel').modal('show');
      });

      $('#modalMyResvDetails').modal('show');
    });
  }

  function loadData(){
    ShowLoader();
    const qs = [];
    const siteId=$('#optMyResvSite').val(); if(siteId) qs.push('siteId='+encodeURIComponent(siteId));
    const status=$('#optMyResvStatus').val(); if(status) qs.push('status='+encodeURIComponent(status));
    const dFrom=$('#dtMyResvFrom').val(); if(dFrom) qs.push('from='+encodeURIComponent(dFrom+'T00:00:00'));
    const dTo=$('#dtMyResvTo').val(); if(dTo) qs.push('to='+encodeURIComponent(dTo+'T23:59:59'));
    const url='api/space.php/my_reservations'+(qs.length?('?'+qs.join('&')):'');
    $.ajax({ url, method:'GET', dataType:'json', headers: headers()})
      .done(function(resp){ if(resp&&resp.success){ dt.clear().rows.add(resp.result||[]).draw(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
  }

  function doCancel(){ if(!toCancelId){ return; } const reason=$('#txaMyResvReason').val()||null; $('#btnMyResvConfirmCancel').prop('disabled', true);
    $.ajax({ url:'api/space.php/reservation/'+toCancelId+'/cancel', method:'PUT', data: JSON.stringify({ reason }), contentType:'application/json', dataType:'json', headers: headers()})
      .done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Canceled', _ALERT_TITLE_SUCCESS); $('#modalMyResvCancel').modal('hide'); loadData(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ $('#btnMyResvConfirmCancel').prop('disabled', false); toCancelId=null; });
    // restore modal to cancel mode
    $('#modalMyResvCancel .modal-title').html('<i class="fas fa-ban"></i> Cancel Reservation');
    $('#modalMyResvCancel .primary-color').removeClass('primary-color').addClass('warning-color');
    $('#txaMyResvReason').closest('.md-form').show();
    $('#btnMyResvConfirmCancel').show();
  }

  $(document).ready(function(){
    let pending=$('.includeHtml').length;
    function boot(){ try{ if (typeof initiatePages === 'function') { initiatePages(); } initFilters(); initTable(); loadData(); } catch(e){ toastr['error'](e.message,_ALERT_TITLE_ERROR);} }
    if(pending===0){ boot(); } else { $('.includeHtml').each(function(){ const id=$(this).attr('id'); $('#'+id).load('html/'+id.substr(2)+'.html?'+new Date().valueOf(), function(){ pending--; if(pending===0){ boot(); } }); }); }
    $('#btnMyResvRefresh').on('click', loadData);
  });
})();
