(function(){
  'use strict';

  let dt; let toCancelId=null; let masterData=[]; let filteredData=[]; let activeChip='all';
  let calCurrentMonth = moment().startOf('month');
  let wkStart = moment().startOf('week');
  let reschSpaceResv = [];

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
    // Date presets
    $('#btnPresetToday').on('click', function(){ const today=moment().format('YYYY-MM-DD'); $('#dtMyResvFrom').val(today); $('#dtMyResvTo').val(today); loadData(); });
    $('#btnPresetNext7').on('click', function(){ const from=moment().format('YYYY-MM-DD'); const to=moment().add(7,'days').format('YYYY-MM-DD'); $('#dtMyResvFrom').val(from); $('#dtMyResvTo').val(to); loadData(); });
    $('#btnPresetThisWeek').on('click', function(){ const from=moment().startOf('week').format('YYYY-MM-DD'); const to=moment().endOf('week').format('YYYY-MM-DD'); $('#dtMyResvFrom').val(from); $('#dtMyResvTo').val(to); loadData(); });
    // Status chips
    $('#myResvStatusChips .chip').on('click', function(){ $('#myResvStatusChips .chip').removeClass('chip-active'); $(this).addClass('chip-active'); activeChip=$(this).data('filter'); applyChipFilter(); });
  }

  function initTable(){
    dt = $('#dtMyResv').DataTable({ bLengthChange:false,bFilter:true,aaSorting:[[2,'desc']], language:_DATATABLE_LANGUAGE,
      dom: 'Bfrtip',
      buttons: [
        { extend:'csv', text:'CSV', className:'btn btn-sm btn-outline-white', exportOptions:{ columns:[0,1,2,3,4] } },
        { extend:'excel', text:'Excel', className:'btn btn-sm btn-outline-white', exportOptions:{ columns:[0,1,2,3,4] } },
        { extend:'print', text:'Print', className:'btn btn-sm btn-outline-white', exportOptions:{ columns:[0,1,2,3,4] } }
      ],
      fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=dt.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); },
      aoColumns:[
        { mData:null, bSortable:false, mRender:function(d,type,row,meta){ if(type==='export' || type==='print'){ return (meta.row+1).toString(); } return ''; } },
        { mData:null, mRender:function(d,type,row,meta){ const id=(row.spaceId??row.space_id); const sn=row.spaceName||('Space #'+(id||'')); if(type==='export' || type==='print'){ return sn; } return '<a href="space_preview.html?id='+(id||'')+'">'+sn+'</a>'; } },
        { mData:function(row){ return row.reservationStart||row.reservation_start||null; }, mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; } },
        { mData:function(row){ return row.reservationEnd||row.reservation_end||null; }, mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; } },
        { mData:function(row){ return row.reservationStatus||row.reservation_status||''; }, mRender:function(d,type){ const status=(d||'').toUpperCase(); if(type==='export' || type==='print'){ return status; } const cls=(status==='RESERVED'?'badge-success-dark':(status==='CANCELED'?'badge-danger-dark':'badge-warning')); return '<span class="badge-modern '+cls+'" title="'+status+'">'+status+'</span>'; } },
        { mData:null, bSortable:false, sClass:'text-center', mRender:function(d,type,row){ if(type==='export' || type==='print'){ return ''; } const status=(row.reservationStatus||row.reservation_status||''); const disabled=status==='CANCELED'; const rid=(row.reservationId??row.reservation_id); const addCalBtn='<button class="btn-action btn-view btnMyResvAddCal" data-id="'+(rid||'')+'" data-toggle="tooltip" data-placement="top" title="Add to calendar"><i class="fas fa-calendar-plus"></i></button>'; const detailsBtn='<button class="btn-action btn-view btnMyResvDetails" data-id="'+(rid||'')+'" data-toggle="tooltip" data-placement="top" title="Details"><i class="fas fa-eye"></i></button>'; const reschBtn='<button class="btn-action btn-edit btnMyResvReschedule" '+(disabled?'disabled':'')+' data-id="'+(rid||'')+'" data-toggle="tooltip" data-placement="top" title="Reschedule"><i class="fas fa-clock-rotate-left"></i></button>'; const cancelBtn='<button class="btn-action btn-delete btnMyResvCancel" '+(disabled?'disabled':'')+' data-id="'+(rid||'')+'" data-toggle="tooltip" data-placement="top" title="Cancel"><i class="fas fa-ban"></i></button>'; return addCalBtn+' '+detailsBtn+' '+reschBtn+' '+cancelBtn; } }
      ]
    });
    // Hide DataTables built-in filter
    $("#dtMyResv_filter").hide();
    // Place export buttons in header actions
  // Keep default buttons hidden; wire icon buttons
  dt.buttons().container().appendTo('#dtMyResvButtons');
  $('#dtMyResvButtons').addClass('d-none');
  $('#btnMyResvExportCsv').off('click').on('click', function(){ dt.button(0).trigger(); });
  $('#btnMyResvExportExcel').off('click').on('click', function(){ dt.button(1).trigger(); });
  $('#btnMyResvExportPrint').off('click').on('click', function(){ dt.button(2).trigger(); });
  // Wire custom search box
  $('#txtMyResvSearch').on('keyup change', function(){ dt.search($(this).val()).draw(); });
  dt.on('draw.dt', function(){ $('[data-toggle="tooltip"]').tooltip(); });
  // View toggle
  $('#myResvViewToggle .segmented-btn').on('click', function(){ const v=$(this).data('view'); toggleView(v); });
    $('#dtMyResv').on('click','.btnMyResvCancel', function(e){ e.stopPropagation(); toCancelId=parseInt($(this).data('id')); $('#txaMyResvReason').val(''); $('#modalMyResvCancel').modal('show'); });
    $('#dtMyResv').on('click','.btnMyResvDetails', function(e){ e.stopPropagation(); const rid=parseInt($(this).data('id')); const row=masterData.find(r=> (r.reservationId||r.reservation_id)==rid); if(row){ showDetails(row); } });
    $('#dtMyResv').on('click','.btnMyResvAddCal', function(e){ e.stopPropagation(); const rid=parseInt($(this).data('id')); const row=masterData.find(r=> (r.reservationId||r.reservation_id)==rid); if(row){ downloadIcs(row); }
      });
    $('#dtMyResv').on('click','.btnMyResvReschedule', function(e){ e.stopPropagation(); const rid=parseInt($(this).data('id')); const row=masterData.find(r=> (r.reservationId||r.reservation_id)==rid); if(row){ openReschedule(row); }});
    $('#btnMyResvConfirmCancel').on('click', doCancel);
    // Show reservation detail on row click (excluding button clicks)
    $('#dtMyResv tbody').on('click', 'tr', function(e){
      // ignore action buttons clicks inside row to avoid opening details
      if($(e.target).closest('.icon-btn,.btnMyResvCancel,.btnMyResvReschedule,.btnMyResvAddCal,.btnMyResvDetails').length) return;
      const data = dt.row(this).data(); if(!data) return; showDetails(data);
    });
  }

  function loadData(){
  ShowLoader(); $('#myResvLoading').removeClass('d-none');
    const qs = [];
    const siteId=$('#optMyResvSite').val(); if(siteId) qs.push('siteId='+encodeURIComponent(siteId));
    const status=$('#optMyResvStatus').val(); if(status) qs.push('status='+encodeURIComponent(status));
    const dFrom=$('#dtMyResvFrom').val(); if(dFrom) qs.push('from='+encodeURIComponent(dFrom+'T00:00:00'));
    const dTo=$('#dtMyResvTo').val(); if(dTo) qs.push('to='+encodeURIComponent(dTo+'T23:59:59'));
    const url='api/space.php/my_reservations'+(qs.length?('?'+qs.join('&')):'');
    $.ajax({ url, method:'GET', dataType:'json', headers: headers()})
  .done(function(resp){ if(resp&&resp.success){ masterData = resp.result||[]; applyChipFilter(); updateTotals(); renderCalendar(); renderRangeTimeline(); if($('#weekView').is(':visible')){ renderWeekView(); } } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
  .always(function(){ HideLoader(); $('#myResvLoading').addClass('d-none'); $('[data-toggle="tooltip"]').tooltip(); });
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
    $('#btnMyResvRefresh, #btnMyResvRefreshTable').on('click', loadData);
  });

  function showDetails(data){
    // Attach reschedule shortcut: open modal with current values
    $('#modalMyResvDetails').off('shown.bs.modal').on('shown.bs.modal', function(){
      const start = data.reservationStart || data.reservation_start || null;
      const end = data.reservationEnd || data.reservation_end || null;
      const spaceName = data.spaceName || ('Space #' + (data.spaceId || data.space_id || ''));
      $('#reschSpaceName').text(spaceName);
      if(start){ $('#reschDate').val(moment(start).format('YYYY-MM-DD')); $('#reschStart').val(moment(start).format('HH:mm')); }
      if(end){ $('#reschEnd').val(moment(end).format('HH:mm')); }
      checkConflicts();
    });
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
  }

  function openReschedule(row){
    const start = row.reservationStart || row.reservation_start || null;
    const end = row.reservationEnd || row.reservation_end || null;
    const spaceName = row.spaceName || ('Space #' + (row.spaceId || row.space_id || ''));
    $('#reschSpaceName').text(spaceName);
    if(start){ $('#reschDate').val(moment(start).format('YYYY-MM-DD')); $('#reschStart').val(moment(start).format('HH:mm')); }
    if(end){ $('#reschEnd').val(moment(end).format('HH:mm')); }
    $('#modalMyResvReschedule').data('rid', (row.reservationId||row.reservation_id)).modal('show');
    $('#modalMyResvReschedule').data('spaceId', (row.spaceId||row.space_id));
    // Default reschedule calendar week start to the date of the reservation start
    wkStart = start? moment(start).startOf('week') : moment().startOf('week');
    // Initialize reschedule mode toggle
    $('#reschModeToggle .segmented-btn').removeClass('chip-active');
    $('#reschModeToggle .segmented-btn[data-mode="inputs"]').addClass('chip-active');
    $('#reschWeekView').addClass('d-none'); $('#reschWkNav').hide();
    // Events
    $('#reschModeToggle .segmented-btn').off('click').on('click', function(){ const m=$(this).data('mode'); $('#reschModeToggle .segmented-btn').removeClass('chip-active'); $(this).addClass('chip-active'); if(m==='calendar'){ $('#reschWeekView').removeClass('d-none'); $('#reschWkNav').show(); renderReschWeekView(); } else { $('#reschWeekView').addClass('d-none'); $('#reschWkNav').hide(); } });
    $('#reschWkPrev').off('click').on('click', function(){ wkStart = wkStart.clone().subtract(1,'week'); renderReschWeekView(); });
    $('#reschWkNext').off('click').on('click', function(){ wkStart = wkStart.clone().add(1,'week'); renderReschWeekView(); });
    checkConflicts();
  }

  function applyChipFilter(){
    let filtered = masterData.slice();
    const now = moment();
    if(activeChip==='upcoming'){
      filtered = filtered.filter(r=> moment(r.reservationStart||r.reservation_start).isAfter(now) && (r.reservationStatus||r.reservation_status)!='CANCELED');
    } else if(activeChip==='past'){
      filtered = filtered.filter(r=> moment(r.reservationEnd||r.reservation_end).isBefore(now));
    } else if(activeChip==='canceled'){
      filtered = filtered.filter(r=> (r.reservationStatus||r.reservation_status)=='CANCELED');
    }
    filteredData = filtered;
    dt.clear().rows.add(filteredData).draw();
    renderCalendar(); renderRangeTimeline();
    if($('#weekView').is(':visible')){ renderWeekView(); }
  }

  function updateTotals(){
    try{
      const now = moment();
  const upcoming = masterData.filter(r=> moment(r.reservationStart||r.reservation_start).isAfter(now) && (r.reservationStatus||r.reservation_status)!='CANCELED').length;
  const past = masterData.filter(r=> moment(r.reservationEnd||r.reservation_end).isBefore(now)).length;
  const canceled = masterData.filter(r=> (r.reservationStatus||r.reservation_status)=='CANCELED').length;
  const hours = masterData.reduce((acc,r)=>{ const s=moment(r.reservationStart||r.reservation_start); const e=moment(r.reservationEnd||r.reservation_end); if(s.isValid()&&e.isValid()){ const h=e.diff(s,'minutes')/60; return acc + Math.max(h,0); } return acc; },0);
      $('#myResvTotals').html(
        '<span class="stat-pill stat-upcoming"><span class="icon"><i class="fas fa-arrow-up"></i></span>Upcoming: '+upcoming+'</span>'+
        '<span class="stat-pill stat-past"><span class="icon"><i class="fas fa-history"></i></span>Past: '+past+'</span>'+
        '<span class="stat-pill stat-canceled"><span class="icon"><i class="fas fa-ban"></i></span>Canceled: '+canceled+'</span>'+
        '<span class="stat-pill stat-hours"><span class="icon"><i class="fas fa-clock"></i></span>Total hours: '+hours.toFixed(1)+'</span>'
      );
    }catch(e){ /* ignore */ }
  }

  function toggleView(view){
    $('#myResvViewToggle .segmented-btn').removeClass('chip-active');
    $('#myResvViewToggle .segmented-btn[data-view="'+view+'"]').addClass('chip-active');
    if(view==='table'){ $('#dtMyResv').closest('.table-responsive').removeClass('d-none'); $('#calendarView, #timelineView, #weekView').addClass('d-none'); }
    else if(view==='calendar'){ $('#dtMyResv').closest('.table-responsive').addClass('d-none'); $('#timelineView, #weekView').addClass('d-none'); $('#calendarView').removeClass('d-none'); renderCalendar(); }
    else if(view==='timeline'){ $('#dtMyResv').closest('.table-responsive').addClass('d-none'); $('#calendarView, #weekView').addClass('d-none'); $('#timelineView').removeClass('d-none');
      // Default to this week if no date range is selected
      const from=$('#dtMyResvFrom').val(); const to=$('#dtMyResvTo').val();
      if(!from||!to){ $('#dtMyResvFrom').val(moment().startOf('week').format('YYYY-MM-DD')); $('#dtMyResvTo').val(moment().endOf('week').format('YYYY-MM-DD')); }
      renderRangeTimeline(); }
    else if(view==='week'){ $('#dtMyResv').closest('.table-responsive').addClass('d-none'); $('#calendarView, #timelineView').addClass('d-none'); $('#weekView').removeClass('d-none'); renderWeekView(); }
  }

  function renderCalendar(){ try{
    $('#calMonthLabel').text(calCurrentMonth.format('MMMM YYYY'));
    const start = calCurrentMonth.clone().startOf('week');
    const end = calCurrentMonth.clone().endOf('month').endOf('week');
    const days = [];
    let d = start.clone();
    while(d.isSameOrBefore(end)){ days.push(d.clone()); d.add(1,'day'); }
    const grid = days.map(day=>{
      const dayRes = masterData.filter(r=> moment(r.reservationStart||r.reservation_start).isSame(day,'day') );
      const badge = dayRes.length?('<div class="badge">'+dayRes.length+' reservation'+(dayRes.length>1?'s':'')+'</div>'):'';
      return '<div class="cal-cell" data-date="'+day.format('YYYY-MM-DD')+'"><div class="date">'+day.format('D')+'</div>'+badge+'</div>';
    }).join('');
    $('#calGrid').html(grid);
    // Day timeline for selected day (default today)
    const todayStr = moment().format('YYYY-MM-DD');
    renderDayTimeline(todayStr);
    $('#calGrid .cal-cell').on('click', function(){ const date=$(this).data('date'); renderDayTimeline(date); });
    $('#calPrev').off('click').on('click', function(){ calCurrentMonth = calCurrentMonth.clone().subtract(1,'month'); renderCalendar(); });
    $('#calNext').off('click').on('click', function(){ calCurrentMonth = calCurrentMonth.clone().add(1,'month'); renderCalendar(); });
  }catch(e){ /* ignore */ }}

  function renderDayTimeline(dateStr){ try{
    const dayStart = moment(dateStr).startOf('day'); const dayEnd = moment(dateStr).endOf('day');
    const res = masterData.filter(r=> { const s=moment(r.reservationStart||r.reservation_start); const e=moment(r.reservationEnd||r.reservation_end); return s.isSame(dayStart,'day') || e.isSame(dayStart,'day') || (s.isBefore(dayEnd)&&e.isAfter(dayStart)); });
    const width = $('#calDayTimeline').width()||600; const pxPerMin = width / (24*60);
    // Build ticks (every 2 hours)
    const ticks = []; for(let h=0; h<=24; h+=2){ const left = (h*60)*pxPerMin; ticks.push('<div class="tl-tick" style="left:'+left+'px">'+String(h).padStart(2,'0')+':00</div>'); }
    $('#calDayTimeline').html('<div class="tl-ticks">'+ticks.join('')+'</div>');
    const blocks = res.map(r=>{ const s=moment(r.reservationStart||r.reservation_start); const e=moment(r.reservationEnd||r.reservation_end); const left = Math.max(0, s.diff(dayStart,'minutes')*pxPerMin); const w = Math.max(4, e.diff(s,'minutes')*pxPerMin); const conflict = res.some(other=> other!==r && rangesOverlap(s,e, moment(other.reservationStart||other.reservation_start), moment(other.reservationEnd||other.reservation_end) )); const cls = 'tl-block'+(conflict?' conflict':''); const label = (r.spaceName||('Space #'+(r.spaceId||r.space_id||'')))+' '+s.format('HH:mm')+'-'+e.format('HH:mm'); return '<div class="'+cls+'" style="left:'+left+'px;width:'+w+'px" title="'+label+'">'+label+'</div>'; }).join('');
    $('#calDayTimeline').append(blocks);
  }catch(e){ /* ignore */ }}

  function renderRangeTimeline(){ try{
    const from = $('#dtMyResvFrom').val(); const to = $('#dtMyResvTo').val();
    if(!from||!to){ $('#rangeTimeline').html('<div class="text-muted">Select a date range to render timeline.</div>'); return; }
    const dStart = moment(from).startOf('day'); const dEnd = moment(to).endOf('day');
    const days=[]; let d=dStart.clone(); while(d.isSameOrBefore(dEnd)){ days.push(d.clone()); d.add(1,'day'); }
    const containerWidth = $('#timelineView').width() || 800; const barWidth = Math.max(300, containerWidth - 140); const pxPerMin = barWidth/(24*60);
    const html = days.map(day=>{
      const startOfDay = day.clone().startOf('day'); const endOfDay = day.clone().endOf('day');
      const res = masterData.filter(r=> { const s=moment(r.reservationStart||r.reservation_start); const e=moment(r.reservationEnd||r.reservation_end); return s.isSame(day,'day') || e.isSame(day,'day') || (s.isBefore(endOfDay)&&e.isAfter(startOfDay)); });
      const blocks = res.map(r=>{ const s=moment(r.reservationStart||r.reservation_start); const e=moment(r.reservationEnd||r.reservation_end); const left = Math.max(0, s.diff(startOfDay,'minutes')*pxPerMin); const durMin = Math.max(0, e.diff(s,'minutes')); const w = Math.max(4, durMin*pxPerMin); const label = (r.spaceName||('Space #'+(r.spaceId||r.space_id||'')))+' '+s.format('HH:mm')+'-'+e.format('HH:mm'); return '<div class="range-block" style="left:'+left+'px;width:'+w+'px" title="'+label+'"></div>'; }).join('');
      return '<div class="range-day"><div class="range-label">'+day.format('MMM D')+'</div><div class="range-bar" style="width:'+barWidth+'px">'+blocks+'</div></div>';
    }).join('');
    $('#rangeTimeline').html(html);
  }catch(e){ /* ignore */ }}

  function renderWeekView(){ try{
    // Label for current week
    const wkEnd = wkStart.clone().endOf('week');
    $('#wkLabel').text(wkStart.clone().format('MMM D')+' - '+wkEnd.format('MMM D, YYYY'));
    // Build time column (00:00 to 23:00)
    const timeColHtml = Array.from({length:24}).map((_,h)=> '<div class="week-time-slot">'+String(h).padStart(2,'0')+':00</div>').join('');
    $('#wkTimeCol').html(timeColHtml);
    // Build day columns
    const daysHtml = Array.from({length:7}).map((_,i)=>{
      const day = wkStart.clone().add(i,'day');
      const header = '<div class="week-day-header">'+day.format('ddd, MMM D')+'</div>';
      const slots = Array.from({length:24}).map(()=> '<div class="week-slot"></div>').join('');
      return '<div class="week-day-col" data-date="'+day.format('YYYY-MM-DD')+'">'+header+slots+'</div>';
    }).join('');
    $('#wkDays').html(daysHtml);
    // Place reservation blocks per day
    const slotHeight = 40; // must match CSS .week-slot height
    $('#wkDays .week-day-col').each(function(){
      const dateStr = $(this).data('date');
      const dayStart = moment(dateStr).startOf('day');
      const dayEnd = moment(dateStr).endOf('day');
      const headerH = $(this).find('.week-day-header').outerHeight() || 24;
      const resForDay = masterData.filter(r=>{
        const s = moment(r.reservationStart||r.reservation_start);
        const e = moment(r.reservationEnd||r.reservation_end);
        return s.isSame(dayStart,'day') || e.isSame(dayStart,'day') || (s.isBefore(dayEnd) && e.isAfter(dayStart));
      });
      const blocksHtml = resForDay.map(r=>{
        const s = moment(r.reservationStart||r.reservation_start);
        const e = moment(r.reservationEnd||r.reservation_end);
        const effStart = moment.max(s, dayStart);
        const effEnd = moment.min(e, dayEnd);
        const startMin = Math.max(0, effStart.diff(dayStart,'minutes'));
        const durMin = Math.max(0, effEnd.diff(effStart,'minutes'));
        const top = headerH + (startMin/60)*slotHeight;
        const height = Math.max(slotHeight/2, (durMin/60)*slotHeight);
        const conflict = resForDay.some(other=> other!==r && rangesOverlap(s,e, moment(other.reservationStart||other.reservation_start), moment(other.reservationEnd||other.reservation_end) ));
        const cls = 'wk-block'+(conflict?' conflict':'');
        const label = (r.spaceName||('Space #'+(r.spaceId||r.space_id||'')))+' '+s.format('HH:mm')+'-'+e.format('HH:mm');
        const rid = r.reservationId||r.reservation_id||'';
        return '<div class="'+cls+'" data-id="'+rid+'" style="top:'+top+'px;height:'+height+'px" title="'+label+'">'+label+'</div>';
      }).join('');
      $(this).append(blocksHtml);
      $(this).find('.wk-block').off('click').on('click', function(){ const rid=parseInt($(this).data('id')); const row=masterData.find(rr=> (rr.reservationId||rr.reservation_id)==rid); if(row){ showDetails(row); } });
    });
    // Navigation handlers
    $('#wkPrev').off('click').on('click', function(){ wkStart = wkStart.clone().subtract(1,'week'); renderWeekView(); });
    $('#wkNext').off('click').on('click', function(){ wkStart = wkStart.clone().add(1,'week'); renderWeekView(); });
  }catch(e){ /* ignore */ }}

  function renderReschWeekView(){ try{
    const spaceId = $('#modalMyResvReschedule').data('spaceId'); if(!spaceId){ return; }
    const wkEnd = wkStart.clone().endOf('week');
    $('#reschWkLabel').text(wkStart.clone().format('MMM D')+' - '+wkEnd.format('MMM D, YYYY'));
    // Build time column
    const timeColHtml = Array.from({length:24}).map((_,h)=> '<div class="week-time-slot">'+String(h).padStart(2,'0')+':00</div>').join('');
    $('#reschWkTimeCol').html(timeColHtml);
    // Build day columns
    const daysHtml = Array.from({length:7}).map((_,i)=>{
      const day = wkStart.clone().add(i,'day');
      const header = '<div class="week-day-header">'+day.format('ddd, MMM D')+'</div>';
      const slots = Array.from({length:24}).map(()=> '<div class="week-slot"></div>').join('');
      return '<div class="week-day-col" data-date="'+day.format('YYYY-MM-DD')+'">'+header+slots+'</div>';
    }).join('');
    $('#reschWkDays').html(daysHtml);
    // Fetch reservations for space within week
    const from = wkStart.clone().startOf('day').format('YYYY-MM-DD[T]00:00:00');
    const to = wkEnd.clone().endOf('day').format('YYYY-MM-DD[T]23:59:59');
    $.ajax({ url:'api/space.php/'+spaceId+'/reservation?from='+encodeURIComponent(from)+'&to='+encodeURIComponent(to), method:'GET', dataType:'json', headers: headers()})
      .done(function(resp){ if(resp&&resp.success){ reschSpaceResv = resp.result||[]; placeReschBlocks(reschSpaceResv); } })
      .fail(function(){ /* silently ignore for modal */ });
  }catch(e){ /* ignore */ }}

  function placeReschBlocks(resv){ try{
    const slotHeight = 40;
    $('#reschWkDays .week-day-col').each(function(){
      const dateStr = $(this).data('date');
      const dayStart = moment(dateStr).startOf('day');
      const dayEnd = moment(dateStr).endOf('day');
      const headerH = $(this).find('.week-day-header').outerHeight() || 24;
      const resForDay = resv.filter(r=>{
        const s = moment(r.reservationStart||r.reservation_start);
        const e = moment(r.reservationEnd||r.reservation_end);
        return s.isSame(dayStart,'day') || e.isSame(dayStart,'day') || (s.isBefore(dayEnd) && e.isAfter(dayStart));
      });
      const blocksHtml = resForDay.map(r=>{
        const s = moment(r.reservationStart||r.reservation_start);
        const e = moment(r.reservationEnd||r.reservation_end);
        const effStart = moment.max(s, dayStart);
        const effEnd = moment.min(e, dayEnd);
        const startMin = Math.max(0, effStart.diff(dayStart,'minutes'));
        const durMin = Math.max(0, effEnd.diff(effStart,'minutes'));
        const top = headerH + (startMin/60)*slotHeight;
        const height = Math.max(slotHeight/2, (durMin/60)*slotHeight);
        const label = s.format('HH:mm')+'-'+e.format('HH:mm');
        return '<div class="wk-block" style="top:'+top+'px;height:'+height+'px" title="'+label+'">'+label+'</div>';
      }).join('');
      $(this).append(blocksHtml);
      // Clicking an empty slot sets inputs to that hour range (default 1 hour)
      $(this).find('.week-slot').off('click').on('click', function(){
        const idx = $(this).index() - 1; // account for header
        const date = dateStr; const startH = Math.max(0, idx);
        const start = moment(date).hour(startH).minute(0);
        const end = start.clone().add(1,'hour');
        $('#reschDate').val(start.format('YYYY-MM-DD'));
        $('#reschStart').val(start.format('HH:mm'));
        $('#reschEnd').val(end.format('HH:mm'));
        checkConflicts();
      });
    });
  }catch(e){ /* ignore */ }}

  function rangesOverlap(s1,e1,s2,e2){ return s1.isBefore(e2) && s2.isBefore(e1); }

  function checkConflicts(){ try{
    const date = $('#reschDate').val(); const start = $('#reschStart').val(); const end = $('#reschEnd').val(); if(!date||!start||!end){ $('#reschConflict').addClass('d-none'); $('#reschSuggestion').addClass('d-none'); return; }
    const s = moment(date+'T'+start); const e = moment(date+'T'+end);
    const inModal = $('#modalMyResvReschedule').hasClass('show');
    const dataset = inModal && reschSpaceResv && reschSpaceResv.length ? reschSpaceResv : masterData;
    const conflicts = dataset.filter(r=> rangesOverlap(s,e, moment(r.reservationStart||r.reservation_start), moment(r.reservationEnd||r.reservation_end) ));
    if(conflicts.length){
      $('#reschConflict').removeClass('d-none').text('Conflict detected with '+conflicts.length+' reservation'+(conflicts.length>1?'s':'')+'.');
      // Suggest alternative slots by scanning 30-min increments
      const suggestions=[]; for(let i=0;i<48;i++){ const s2 = s.clone().startOf('day').add(i*30,'minutes'); const e2 = s2.clone().add(e.diff(s,'minutes'),'minutes'); const has = dataset.some(r=> rangesOverlap(s2,e2, moment(r.reservationStart||r.reservation_start), moment(r.reservationEnd||r.reservation_end) )); if(!has){ suggestions.push(s2.format('HH:mm')+'-'+e2.format('HH:mm')); if(suggestions.length>=3) break; } }
      $('#reschSuggestion').removeClass('d-none').html('Try: '+ suggestions.map(x=> '<span class="badge badge-info mr-1">'+x+'</span>').join(''));
    } else {
      $('#reschConflict').addClass('d-none'); $('#reschSuggestion').addClass('d-none');
    }
  }catch(e){ /* ignore */ }}

  // Save reschedule via backend API
  $(document).on('click', '#btnMyResvConfirmReschedule', function(){
    const rid = $('#modalMyResvReschedule').data('rid');
    const date = $('#reschDate').val(); const s = $('#reschStart').val(); const e = $('#reschEnd').val();
    if(!rid||!date||!s||!e){ toastr['error']('Please complete date and time', _ALERT_TITLE_ERROR); return; }
    const startDateTime = date+'T'+s; const endDateTime = date+'T'+e;
    const payload = { startDateTime, endDateTime };
    $('#btnMyResvConfirmReschedule').prop('disabled', true);
    $.ajax({ url:'api/space.php/reservation/'+rid+'/reschedule', method:'PUT', data: JSON.stringify(payload), contentType:'application/json', dataType:'json', headers: headers()})
      .done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Rescheduled', _ALERT_TITLE_SUCCESS); $('#modalMyResvReschedule').modal('hide'); loadData(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ $('#btnMyResvConfirmReschedule').prop('disabled', false); });
  });

  // Live conflict check on inputs
  $(document).on('change', '#reschDate, #reschStart, #reschEnd', checkConflicts);

  function downloadIcs(row){
    const rid = row.reservationId||row.reservation_id||'';
    const sdt = moment(row.reservationStart||row.reservation_start);
    const edt = moment(row.reservationEnd||row.reservation_end);
    const spaceName = row.spaceName||('Space #'+(row.spaceId||row.space_id||''));
    if(!sdt.isValid()||!edt.isValid()) { toastr['error']('Invalid dates for calendar export', _ALERT_TITLE_ERROR); return; }
    const uid = 'gems2-resv-'+rid+'@gems2.local';
    const dtStamp = moment().utc().format('YYYYMMDD[T]HHmmss[Z]');
    const fmt = (m)=> m.utc().format('YYYYMMDD[T]HHmmss[Z]');
    const ics = [
      'BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//GEMS2//Space Reservation//EN','BEGIN:VEVENT',
      'UID:'+uid,
      'DTSTAMP:'+dtStamp,
      'DTSTART:'+fmt(sdt),
      'DTEND:'+fmt(edt),
      'SUMMARY:'+escapeIcs(spaceName+' reservation'),
      'DESCRIPTION:'+escapeIcs('Reservation #'+rid+' for '+spaceName),
      'END:VEVENT','END:VCALENDAR'
    ].join('\r\n');
    const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href=url; a.download='reservation_'+rid+'.ics'; document.body.appendChild(a); a.click();
    setTimeout(()=>{ URL.revokeObjectURL(url); a.remove(); }, 500);
  }

  function escapeIcs(s){ return String(s).replace(/\n/g,'\\n').replace(/,/g,'\\,').replace(/;/g,'\\;'); }
})();
