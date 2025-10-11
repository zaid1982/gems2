function MainSpacePreview(){
  const className='MainSpacePreview';
  let self=this; let oAssetTable; let oResvTable; let spaceId=0;
  let currentData=null;
  // View state
  let upcomingData=[];
  let calRefDate=new Date(); // month anchor
  let wkRefDate=new Date(); // week anchor

  this.init=function(){
    spaceId = self.getQueryInt('id');
    if(!spaceId){ toastr['error']('Missing space id','Error'); return; }
    // Hide Reserve button if space not bookable? Keep always visible for normal users; admins also can use it.
    // Assets table
  oAssetTable=$('#dtSpcAssets').DataTable({ bLengthChange:false,bFilter:true,aaSorting:[[2,'asc']], language:_DATATABLE_LANGUAGE, fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=oAssetTable.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); }, aoColumns:[ {mData:null,bSortable:false}, {mData:'assetNo'}, {mData:null, mRender:function(d,t,row){ const name=row.assetName||''; const id=row.assetId; return id?('<a href="asset.html?assetId='+id+'" title="Open asset">'+name+'</a>'):name; }}, {mData:'assetSerialNo'}, {mData:'linkedAt', mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; }} ] });
  // Upcoming reservations table
  oResvTable=$('#dtSpcResv').DataTable({ bLengthChange:false,bFilter:false,aaSorting:[[1,'asc']], language:_DATATABLE_LANGUAGE, fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=oResvTable.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); }, aoColumns:[ {mData:null,bSortable:false}, {mData:'reservationStart', mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; }}, {mData:'reservationEnd', mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; }}, {mData:'reservationStatus'} ] });
    $('#btnSpcPrevRefresh').on('click', function(){ self.load(); });
  $('#btnSpcPrevReserve').on('click', function(){ self.openReserveModal(); });
    $('#btnSpcPrevCreate').on('click', function(){ self.createReservation(); });
    // View toggles
    $('#spcResvViewToggle .segmented-btn').on('click', function(){
      const v=$(this).data('view');
      $('#spcResvViewToggle .segmented-btn').removeClass('chip-active'); $(this).addClass('chip-active');
      $('#spcResvTableWrap,#spcCalendarView,#spcTimelineView,#spcWeekView').addClass('d-none');
      if(v==='table'){ $('#spcResvTableWrap').removeClass('d-none'); }
      else if(v==='calendar'){ $('#spcCalendarView').removeClass('d-none'); self.renderCalendarView(); }
      else if(v==='timeline'){ $('#spcTimelineView').removeClass('d-none'); self.renderRangeTimeline(); }
      else if(v==='week'){ $('#spcWeekView').removeClass('d-none'); self.renderWeekView(); }
    });
    // Calendar nav
    $('#spcCalPrev').on('click', function(){ calRefDate.setMonth(calRefDate.getMonth()-1); self.renderCalendarView(); });
    $('#spcCalNext').on('click', function(){ calRefDate.setMonth(calRefDate.getMonth()+1); self.renderCalendarView(); });
    // Week nav
    $('#spcWkPrev').on('click', function(){ wkRefDate.setDate(wkRefDate.getDate()-7); self.renderWeekView(); });
    $('#spcWkNext').on('click', function(){ wkRefDate.setDate(wkRefDate.getDate()+7); self.renderWeekView(); });
    self.load();
  };

  this.load=function(){
    ShowLoader();
    const token=sessionStorage.getItem('token');
    const headers= token?{'Authorization':'Bearer '+token}:{ };
    $.ajax({ url:'api/space.php/'+spaceId, method:'GET', dataType:'json', headers}).done(function(resp){
      if(resp&&resp.success){ const d=resp.result||{}; currentData=d; self.render(d); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} 
    }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); });
  };

  this.render=function(d){
    $('#lblSpcTitle').text('Space: '+(d.spaceName||'-'));
    $('#spcName').text(d.spaceName||'-');
  const rawStatus=(d.spaceStatus||'').toUpperCase();
  const badgeText = (rawStatus==='DISABLED' || rawStatus==='INACTIVE' || rawStatus==='DECOMMISSIONED') ? 'DISABLED' : 'AVAILABLE';
  const cls = badgeText==='AVAILABLE' ? 'badge-modern badge-success-dark' : 'badge-modern badge-danger-dark';
  $('#spcStatusBadge').html('<span class="'+cls+'">'+badgeText+'</span>');
  $('#spcSite').text(d.siteName||d.siteId||'-');
    $('#spcLocation').text(d.locationName||'-');
    $('#spcCategory').text(d.categoryName||'-');
    $('#spcType').text(d.typeName||'-');
    $('#spcArea').text(d.spaceArea||'-');
    $('#spcCapacity').text(d.spaceCapacity||'-');
    $('#spcDesc').text(d.spaceDesc||'-');
    $('#spcCreatedAt').text(d.createdAt?moment(d.createdAt).format('YYYY-MM-DD HH:mm'):'-');
    $('#spcUpdatedAt').text(d.updatedAt?moment(d.updatedAt).format('YYYY-MM-DD HH:mm'):'-');
    // assets list
    oAssetTable.clear().rows.add(d.assets||[]).draw();
    // media gallery
    self.renderMedia(d.media||[]);
    // upcoming reservations
    upcomingData = d.upcomingReservations||[];
    oResvTable.clear().rows.add(upcomingData).draw();
    // Ensure default table view visible
    $('#spcResvTableWrap').removeClass('d-none');
    $('#spcCalendarView,#spcTimelineView,#spcWeekView').addClass('d-none');
  };

  this.openReserveModal=function(){
    if(!spaceId){ toastr['error']('Missing space id','Error'); return; }
    const url = new URL(window.location.origin + window.location.pathname.replace(/[^/]+$/, 'space_calendar.html'));
    url.searchParams.set('id', spaceId);
    url.searchParams.set('from', 'preview');
    window.location.href = url.toString();
  };

  this.createReservation=function(){
    const start=$('#dtSpcPrevStart').val();
    const end=$('#dtSpcPrevEnd').val();
    if(!start||!end){ toastr['warning']('Please set Start and End'); return; }
    const payload={
      startDateTime: start.replace('T',' ')+':00',
      endDateTime: end.replace('T',' ')+':00',
      requestedByName: ($('#txtSpcPrevName').val()||'').trim(),
      requestedByContact: ($('#txtSpcPrevEmail').val()||'').trim(),
      specialRequest: ($('#txaSpcPrevNotes').val()||'').trim()
    };
    const token=sessionStorage.getItem('token'); const headers= token?{'Authorization':'Bearer '+token}:{ };
    $('#btnSpcPrevCreate').prop('disabled', true);
    ShowLoader();
    $.ajax({ url:'api/space.php/'+spaceId+'/reservation', method:'POST', data:JSON.stringify(payload), contentType:'application/json', dataType:'json', headers})
      .done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Reserved', _ALERT_TITLE_SUCCESS); $('#modalSpcPrevReserve').modal('hide'); // refresh upcoming
        // reload upcoming reservations only
        $.ajax({ url:'api/space.php/'+spaceId, method:'GET', dataType:'json', headers}).done(function(r2){ if(r2&&r2.success){ oResvTable.clear().rows.add((r2.result||{}).upcomingReservations||[]).draw(); }});
      } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); } })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); $('#btnSpcPrevCreate').prop('disabled', false); });
  };

  this.renderMedia=function(list){
    const $grid=$('#spcMediaGrid'); const $empty=$('#spcMediaEmpty');
    $grid.empty();
    if(!list || list.length===0){ $empty.show(); return; }
    $empty.hide();
    list.forEach(function(m){
      const url=m.downloadUrl; const caption = (m.mediaType||'').toUpperCase();
      const $col = $('<div class="col-6 col-md-4 col-lg-3 mb-3"></div>');
      const $a = $('<a>', { href: url, target: '_blank', class: 'd-block' });
      const $view = $('<div>', { class: 'view overlay z-depth-1 rounded' });
      const $img = $('<img>', { src: url, class: 'img-fluid', alt: 'media' });
      $img.on('error', function(){ $(this).attr('src', 'img/background/no-image.png'); });
      const $mask = $('<div>', { class: 'mask flex-center rgba-black-strong text-white-50', style: 'font-size:12px;' }).text(caption);
      $view.append($img).append($mask);
      $a.append($view);
      $col.append($a);
      $grid.append($col);
    });
  };

  // Helpers
  function getDayStart(date){ const d=new Date(date); d.setHours(0,0,0,0); return d; }
  function getWeekStart(date){ const d=new Date(date); const day=d.getDay(); const diff=d.getDate()-day+(day===0?-6:1); d.setDate(diff); d.setHours(0,0,0,0); return d; } // ISO week start (Mon)
  function clamp(n,min,max){ return Math.max(min, Math.min(max, n)); }

  // Calendar (month grid + day timeline)
  this.renderCalendarView=function(){
    const monthStart=new Date(calRefDate.getFullYear(), calRefDate.getMonth(), 1); const monthEnd=new Date(calRefDate.getFullYear(), calRefDate.getMonth()+1, 0);
    $('#spcCalMonthLabel').text(moment(monthStart).format('MMMM YYYY'));
    const $grid=$('#spcCalGrid'); $grid.empty();
    // Build array of days including leading blanks to start on Monday
    const firstDayIdx=(monthStart.getDay()+6)%7; // Monday=0
    for(let i=0;i<firstDayIdx;i++){ $grid.append('<div class="cal-cell" aria-hidden="true"></div>'); }
    const daysInMonth=monthEnd.getDate();
    for(let d=1; d<=daysInMonth; d++){
      const cur=new Date(monthStart.getFullYear(), monthStart.getMonth(), d);
      const curStr=moment(cur).format('YYYY-MM-DD');
      const items=upcomingData.filter(x=> moment(x.reservationStart).format('YYYY-MM-DD')===curStr );
      const $cell=$('<div class="cal-cell"></div>');
      $cell.append('<div class="date">'+d+'</div>');
      if(items.length){ $cell.append('<div class="badge">'+items.length+' upcoming</div>'); }
      $grid.append($cell);
    }
    // Day timeline for today within selected month
    self.renderDayTimelineForDate(new Date());
  };

  this.renderDayTimelineForDate=function(date){
    const $tl=$('#spcCalDayTimeline'); $tl.empty(); const width=$tl.width()||600;
    // Ticks every 2 hours
    const $ticks=$('<div class="tl-ticks"></div>');
    for(let h=0; h<=24; h+=2){ const x=(h/24)*width; const $t=$('<div class="tl-tick"></div>').css({ left:x+'px' }).text(h.toString().padStart(2,'0')+':00'); $ticks.append($t); }
    $tl.append($ticks);
    const dayStart=getDayStart(date);
    const dayItems=upcomingData.filter(x=>{ const s=moment(x.reservationStart).toDate(); return s>=dayStart && s< new Date(dayStart.getTime()+24*3600*1000); });
    dayItems.forEach(function(it){
      const s=moment(it.reservationStart).toDate(); const e=moment(it.reservationEnd).toDate();
      const startH=s.getHours()+s.getMinutes()/60; const endH=e.getHours()+e.getMinutes()/60; const left=clamp((startH/24)*width,0,width-10);
      const w=clamp(((endH-startH)/24)*width, 20, width-left);
      const $b=$('<div class="tl-block"></div>').css({ left:left+'px', width:w+'px' }).text(moment(it.reservationStart).format('HH:mm')+'-'+moment(it.reservationEnd).format('HH:mm'));
      $tl.append($b);
    });
  };

  // Timeline view (compact range per reservation)
  this.renderRangeTimeline=function(){
    const $wrap=$('#spcRangeTimeline'); $wrap.empty();
    if(!upcomingData.length){ $wrap.append('<div class="text-muted">No upcoming reservations</div>'); return; }
    // Group by day label
    const groups={};
    upcomingData.forEach(function(it){ const key=moment(it.reservationStart).format('YYYY-MM-DD'); if(!groups[key]) groups[key]=[]; groups[key].push(it); });
    Object.keys(groups).sort().forEach(function(day){
      const items=groups[day]; const $row=$('<div class="range-day"></div>'); $row.append('<div class="range-label">'+moment(day).format('ddd, MMM D')+'</div>');
      const $bar=$('<div class="range-bar"></div>'); const width=600; // fallback width; CSS will size
      items.forEach(function(it){ const s=moment(it.reservationStart).toDate(); const e=moment(it.reservationEnd).toDate(); const startH=s.getHours()+s.getMinutes()/60; const endH=e.getHours()+e.getMinutes()/60; const left=(startH/24)*100; const w=((endH-startH)/24)*100; const $b=$('<div class="range-block"></div>').css({ left:left+'%', width:w+'%' }); $bar.append($b); });
      $row.append($bar); $wrap.append($row);
    });
  };

  // Week view
  this.renderWeekView=function(){
    const start=getWeekStart(wkRefDate); const end=new Date(start.getTime()+7*24*3600*1000);
    $('#spcWkLabel').text(moment(start).format('MMM D')+' - '+moment(new Date(end.getTime()-1)).format('MMM D'));
    const $timeCol=$('#spcWkTimeCol'); const $days=$('#spcWkDays'); $timeCol.empty(); $days.empty();
    for(let h=0; h<24; h++){ $timeCol.append('<div class="week-time-slot">'+(h.toString().padStart(2,'0'))+':00</div>'); }
    for(let i=0;i<7;i++){ const d=new Date(start.getTime()+i*24*3600*1000); const $col=$('<div class="week-day-col"></div>'); $col.append('<div class="week-day-header">'+moment(d).format('ddd, MMM D')+'</div>'); for(let h=0; h<24; h++){ $col.append('<div class="week-slot"></div>'); } $days.append($col); }
    // Place blocks
    upcomingData.forEach(function(it){ const s=moment(it.reservationStart).toDate(); const e=moment(it.reservationEnd).toDate(); if(s<end && e>start){ const dayIdx=Math.floor((getDayStart(s).getTime()-start.getTime())/(24*3600*1000)); if(dayIdx>=0 && dayIdx<7){ const $col=$days.children().eq(dayIdx); const totalH=24*40; const top=( (s.getHours()+s.getMinutes()/60) /24 )*totalH; const height=Math.max(24, ((e - s)/(3600*1000))/24*totalH ); const $b=$('<div class="wk-block"></div>').css({ top:top+'px', height:height+'px' }).text(moment(s).format('HH:mm')+'-'+moment(e).format('HH:mm')); $col.append($b); } } });
  };

  this.getQueryInt=function(key){ const urlParams=new URLSearchParams(window.location.search); const v=parseInt(urlParams.get(key)); return isNaN(v)?0:v; };
  this.getClassName=function(){ return className; };
}

document.addEventListener('DOMContentLoaded', function(){
  ShowLoader();
  let pending = $('.includeHtml').length;
  function boot(){
    try{ if (typeof initiatePages === 'function') { initiatePages(); } const main=new MainSpacePreview(); main.init(); }catch(e){ toastr['error'](e.message,_ALERT_TITLE_ERROR);} HideLoader();
  }
  if (pending === 0) { setTimeout(boot, 100); }
  else {
    $('.includeHtml').each(function(){
      const id=$(this).attr('id');
      $('#'+id).load('html/'+id.substr(2)+'.html?'+new Date().valueOf(), function(){ pending--; if (pending===0) { setTimeout(boot, 50); } });
    });
  }
});
