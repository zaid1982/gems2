function MainSpacePreview(){
  const className='MainSpacePreview';
  let self=this; let oAssetTable; let oResvTable; let spaceId=0;
  let currentData=null;

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
    const status=(d.spaceStatus||'').toUpperCase();
    const color = status==='ACTIVE'?'success':(status==='AVAILABLE'?'primary':(status==='RESERVED'?'warning':'secondary'));
    $('#spcStatusBadge').html('<h6><span class="badge badge-pill '+color+' z-depth-2">'+(status||'-')+'</span></h6>');
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
    oResvTable.clear().rows.add(d.upcomingReservations||[]).draw();
  };

  this.openReserveModal=function(){
    // default times: next full hour for 1 hour slot
    const now = new Date(); now.setMinutes(0,0,0); now.setHours(now.getHours()+1);
    const startIso = new Date(now).toISOString().slice(0,16);
    const endIso = new Date(now.getTime()+60*60*1000).toISOString().slice(0,16);
    $('#dtSpcPrevStart').val(startIso);
    $('#dtSpcPrevEnd').val(endIso);
    // prefill name/email from user info
    try {
      const fullName = (function(){
        try {
          const first = (typeof mzGetUserInfoByParam === 'function') ? (mzGetUserInfoByParam('userFirstName')||'') : '';
          const last = (typeof mzGetUserInfoByParam === 'function') ? (mzGetUserInfoByParam('userLastName')||'') : '';
          const name = (first + ' ' + last).trim(); if (name) return name;
        } catch(e){}
        try { let ui=sessionStorage.getItem('userInfo'); if(ui){ ui=JSON.parse(ui); const n=((ui.userFirstName||'')+' '+(ui.userLastName||'')).trim(); if(n) return n; } } catch(e){}
        return '';
      })();
      const email = (function(){
        try { if (typeof mzGetUserInfoByParam === 'function') return mzGetUserInfoByParam('userEmail')||''; } catch(e){}
        try { let ui=sessionStorage.getItem('userInfo'); if(ui){ ui=JSON.parse(ui); return ui.userEmail||''; } } catch(e){}
        return '';
      })();
      $('#txtSpcPrevName').val(fullName);
      $('#txtSpcPrevEmail').val(email);
    } catch(e) { $('#txtSpcPrevName').val(''); $('#txtSpcPrevEmail').val(''); }
    $('#txaSpcPrevNotes').val('');
    $('#modalSpcPrevReserve').modal('show');
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
      $img.on('error', function(){ $(this).attr('src', 'img/no-image.png'); });
      const $mask = $('<div>', { class: 'mask flex-center rgba-black-strong text-white-50', style: 'font-size:12px;' }).text(caption);
      $view.append($img).append($mask);
      $a.append($view);
      $col.append($a);
      $grid.append($col);
    });
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
