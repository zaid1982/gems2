(function(){
  'use strict';

  let spaceId = 0;
  let calendar = null;
  let pendingRange = null; // { startStr, endStr }
  let currentSpace = null;
  let cancelResvId = null;

  function headers(){ const t=sessionStorage.getItem('token'); return t?{'Authorization':'Bearer '+t}:{ }; }
  function getQueryInt(key){ const urlParams=new URLSearchParams(window.location.search); const v=parseInt(urlParams.get(key)); return isNaN(v)?0:v; }

  function fetchSpace(){ return $.ajax({ url:'api/space.php/'+spaceId, method:'GET', dataType:'json', headers: headers()}); }
  function fetchReservations(start, end){ const qs = '?from='+encodeURIComponent(start)+'&to='+encodeURIComponent(end); return $.ajax({ url:'api/space.php/'+spaceId+'/reservation'+qs, method:'GET', dataType:'json', headers: headers()}); }
  function createReservation(payload){ return $.ajax({ url:'api/space.php/'+spaceId+'/reservation', method:'POST', data:JSON.stringify(payload), contentType:'application/json', dataType:'json', headers: headers()}); }
  function cancelReservation(reservationId, reason){ return $.ajax({ url:'api/space.php/reservation/'+reservationId+'/cancel', method:'PUT', data: JSON.stringify({ reason: reason||null }), contentType:'application/json', dataType:'json', headers: headers()}); }

  function statusColors(status){
    switch((status||'').toUpperCase()){
      case 'RESERVED': return { backgroundColor:'#10b981', borderColor:'#059669', textColor:'#fff' }; // green
      case 'CANCELED': return { backgroundColor:'#ef4444', borderColor:'#b91c1c', textColor:'#fff' }; // red
      default: return { backgroundColor:'#64748b', borderColor:'#475569', textColor:'#fff' }; // slate
    }
  }

  function toEvent(resv){
    const title = (resv.requestedByName? (resv.requestedByName+ ' — ') : '') + (resv.reservationStatus||'');
    const colors = statusColors(resv.reservationStatus);
    return Object.assign({
      id: String(resv.reservationId),
      title: title,
      start: resv.reservationStart,
      end: resv.reservationEnd,
      allDay: false
    }, colors, { extendedProps: resv });
  }

  function loadTitle(){ if(!currentSpace) return; $('#lblSpcCalTitle').text('Space Calendar: ' + (currentSpace.spaceName||('- #'+spaceId))); $('#lblSpcCalSubtitle').text('Site ' + (currentSpace.siteId||'-') + ' • ' + (currentSpace.locationName||'') ); }

  function initCalendar(){
    const calendarEl = document.getElementById('spcCalendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'timeGridWeek',
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
      height: 'auto',
      selectable: true,
      selectMirror: true,
      navLinks: true,
      nowIndicator: true,
      locale: 'en',
      firstDay: 1,
      slotMinTime: '07:00:00',
      slotMaxTime: '20:00:00',
      eventOverlap: false,
      select: function(info){
        pendingRange = { startStr: info.startStr, endStr: info.endStr };
        // preset modal fields
        $('#dtSpcStart').val(info.startStr.substring(0,16));
        $('#dtSpcEnd').val(info.endStr.substring(0,16));
        // prefill requester name/email from current user
        try {
          const fullName = (function(){
            try {
              const first = (typeof mzGetUserInfoByParam === 'function') ? (mzGetUserInfoByParam('userFirstName')||'') : '';
              const last = (typeof mzGetUserInfoByParam === 'function') ? (mzGetUserInfoByParam('userLastName')||'') : '';
              const name = (first + ' ' + last).trim();
              if (name) return name;
            } catch(e) {}
            // Fallback to raw sessionStorage
            try {
              let ui = sessionStorage.getItem('userInfo');
              if (ui) { ui = JSON.parse(ui); const n = ((ui.userFirstName||'')+' '+(ui.userLastName||'')).trim(); if (n) return n; }
            } catch(e) {}
            return '';
          })();
          const email = (function(){
            try { if (typeof mzGetUserInfoByParam === 'function') return mzGetUserInfoByParam('userEmail')||''; } catch(e) {}
            try { let ui = sessionStorage.getItem('userInfo'); if (ui) { ui = JSON.parse(ui); return ui.userEmail||''; } } catch(e) {}
            return '';
          })();
          $('#txtSpcReqName').val(fullName);
          $('#txtSpcReqContact').val(email);
        } catch(e) { $('#txtSpcReqName').val(''); $('#txtSpcReqContact').val(''); }
        $('#txaSpcReqNotes').val('');
        $('#modalSpcCreateResv').modal('show');
      },
      eventClick: function(arg){
        const r = arg.event.extendedProps || {};
        cancelResvId = parseInt(r.reservationId||arg.event.id);
        const start = moment(r.reservationStart||arg.event.start).format('YYYY-MM-DD HH:mm');
        const end = moment(r.reservationEnd||arg.event.end).format('YYYY-MM-DD HH:mm');
        const name = r.requestedByName || '-';
        const contact = r.requestedByContact || '';
        const status = r.reservationStatus || arg.event.title || '';
        let html = '<div><strong>'+status+'</strong></div>'
          + '<div>'+start+' — '+end+'</div>'
          + '<div class="mt-1">'+name+(contact?(' <small class="text-muted">'+contact+'</small>'):'')+'</div>';
        $('#spcEvtSummary').html(html);
        const isCanceled = (status||'').toUpperCase()==='CANCELED';
        $('#btnSpcCancelEvt').prop('disabled', isCanceled);
        $('#txaSpcCancelReason').val('');
        $('#modalSpcEvent').modal('show');
      },
      events: function(fetchInfo, success, failure){
        // fetchInfo.startStr/endStr are ISO strings
        fetchReservations(fetchInfo.startStr, fetchInfo.endStr)
          .done(function(resp){ if(resp&&resp.success){ const events=(resp.result||[]).map(toEvent); success(events); } else { failure((resp&&resp.errmsg)||'Failed to load reservations'); } })
          .fail(function(jq){ failure(jq.responseText||'Network error'); });
      }
    });
    calendar.render();
  }

  function wireButtons(){
    $('#btnSpcCalToday').on('click', function(){ if(calendar) calendar.today(); });
    $('#btnSpcCalManage').on('click', function(){ window.location.href = 'space_manage.html?id='+spaceId; });
    $('#btnSpcCreateSubmit').on('click', function(){
      if(!pendingRange){ $('#modalSpcCreateResv').modal('hide'); return; }
      const start = $('#dtSpcStart').val();
      const end = $('#dtSpcEnd').val();
      if(!start || !end){ toastr['warning']('Start/End required'); return; }
      const payload = {
        startDateTime: start.replace('T',' ') + ':00',
        endDateTime: end.replace('T',' ') + ':00',
        requestedByName: ($('#txtSpcReqName').val()||'').trim(),
        requestedByContact: ($('#txtSpcReqContact').val()||'').trim(),
        specialRequest: ($('#txaSpcReqNotes').val()||'').trim()
      };
      $('#btnSpcCreateSubmit').prop('disabled', true);
      createReservation(payload)
        .done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Reserved'); $('#modalSpcCreateResv').modal('hide'); if(calendar){ calendar.refetchEvents(); } } else { toastr['error']((resp&&resp.errmsg)||'Failed to reserve'); } })
        .fail(function(){ toastr['error']('Failed to reserve'); })
        .always(function(){ $('#btnSpcCreateSubmit').prop('disabled', false); pendingRange=null; });
    });
    $('#btnSpcCancelEvt').on('click', function(){ if(!cancelResvId){ return; } const reason=$('#txaSpcCancelReason').val()||null; $('#btnSpcCancelEvt').prop('disabled', true); cancelReservation(cancelResvId, reason)
      .done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Canceled'); $('#modalSpcEvent').modal('hide'); if(calendar){ calendar.refetchEvents(); } } else { toastr['error']((resp&&resp.errmsg)||'Failed to cancel'); } })
      .fail(function(){ toastr['error']('Failed to cancel'); })
      .always(function(){ $('#btnSpcCancelEvt').prop('disabled', false); cancelResvId=null; }); });
  }

  $(document).ready(function(){
    spaceId = getQueryInt('id');
    if(!spaceId){ toastr['error']('Missing space id','Error'); return; }
    // Load shared includes then boot
    let pending = $('.includeHtml').length;
    function boot(){
      fetchSpace()
        .done(function(resp){ if(resp&&resp.success){ currentSpace = resp.result||{}; loadTitle(); initCalendar(); wireButtons(); } else { toastr['error']((resp&&resp.errmsg)||'Failed to load space'); } })
        .fail(function(){ toastr['error']('Failed to load space'); });
    }
    if(pending===0){ boot(); }
    else {
      $('.includeHtml').each(function(){ const id=$(this).attr('id'); $('#'+id).load('html/'+id.substr(2)+'.html?'+new Date().valueOf(), function(){ pending--; if(pending===0){ boot(); } }); });
    }
  });
})();
