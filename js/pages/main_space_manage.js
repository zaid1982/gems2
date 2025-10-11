function MainSpaceManage(){
  const className='MainSpaceManage';
  let self=this; let spaceId=0; let oAssets; let oResv; let currentData=null; let cancelResvId=null;
  let selectedMediaIds=new Set();
  let currentCoverId=0;

  this.init=function(){
    spaceId=self.getQueryInt('id'); if(!spaceId){ toastr['error']('Missing space id','Error'); return; }
    // Tables
    oAssets=$('#dtSpmAssets').DataTable({ bLengthChange:false,bFilter:true,aaSorting:[[2,'asc']], language:_DATATABLE_LANGUAGE, fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=oAssets.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); }, aoColumns:[ {mData:null,bSortable:false}, {mData:'assetNo'}, {mData:null, mRender:function(d,t,row){ const name=row.assetName||''; const id=row.assetId; return id?('<a href="asset.html?assetId='+id+'" title="Open asset">'+name+'</a>'):name; }}, {mData:'assetSerialNo'}, {mData:'linkedAt', mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; }}, {mData:null,bSortable:false,sClass:'text-center', mRender:function(d,t,row,meta){ return '<button class="btn btn-sm btn-outline-danger btnSpmUnlink" data-row="'+meta.row+'"><i class="fas fa-unlink"></i></button>'; }} ] });
    oResv=$('#dtSpmResv').DataTable({ bLengthChange:false,bFilter:false,aaSorting:[[1,'asc']], language:_DATATABLE_LANGUAGE, fnRowCallback:function(nRow,aData,iDisplayIndex){ const info=oResv.page.info(); $('td',nRow).eq(0).html(info.page*info.length + (iDisplayIndex+1)); }, aoColumns:[ 
      {mData:null,bSortable:false}, 
      {mData:'reservationStart', mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; }}, 
      {mData:'reservationEnd', mRender:function(d){ return d?moment(d).format('YYYY-MM-DD HH:mm'):'-'; }}, 
      {mData:null, mRender:function(d,t,row){
        var name = row.requestedByName || '';
        var contact = row.requestedByContact || '';
        var userId = row.requestedBy || 0;
        var display = '';
        if (userId && name) {
          display = '<div>'+name+' <small class="text-muted">(User #'+userId+')</small></div>';
        } else if (name) {
          display = '<div>'+name+'</div>';
        }
        if (contact) { display += '<div><small class="text-muted">'+contact+'</small></div>'; }
        return display || '-';
      }}, 
      {mData:'reservationStatus'}, 
      {mData:null,bSortable:false,sClass:'text-center', mRender:function(d,t,row){ const disabled = (row.reservationStatus||'')==='CANCELED'; return '<button class="btn btn-sm btn-outline-warning btnSpmCancelResv" '+(disabled?'disabled':'')+' data-id="'+row.reservationId+'"><i class="fas fa-ban"></i> Cancel</button>'; }} 
    ] });
    // Events
    $('#btnSpcMngRefresh').on('click', self.load);
    $('#btnSpmSaveInfo').on('click', self.saveInfo);
  $('#btnSpmOpenCalendar').on('click', function(){ window.location.href = 'space_calendar.html?id='+spaceId; });
  // Open asset picker modal
  $('#btnSpmAttachAsset').on('click', self.openAssetPicker);
    $('#dtSpmAssets').on('click', '.btnSpmUnlink', function(){ const idx=parseInt($(this).data('row')); const row=oAssets.row(idx).data(); if(row&&row.spaceAssetId){ self.unlinkAsset(row.spaceAssetId); } });
    $('#btnSpmRefreshResv').on('click', self.loadReservations);
    $('#dtSpmResv').on('click', '.btnSpmCancelResv', function(){ cancelResvId=parseInt($(this).data('id')); $('#txaSpmCancelReason').val(''); $('#lblSpmCancelReason').addClass('active'); $('#modalCancelResv').modal('show'); });
    $('#btnSpmCancelConfirm').on('click', self.cancelReservation);
  // Media actions
  $('#btnSpmUploadPhotos').on('click', function(){ $('#inpSpmPhotos').val(''); $('#inpSpmPhotos').click(); });
  $('#btnSpmUploadFloor').on('click', function(){ $('#inpSpmFloor').val(''); $('#inpSpmFloor').click(); });
  $('#btnSpmDeleteMedia').on('click', self.deleteSelectedMedia);
  $('#inpSpmPhotos').on('change', function(e){ self.handlePhotoUpload(e.target.files); });
  $('#inpSpmFloor').on('change', function(e){ self.handleFloorUpload(e.target.files); });
    // Load initial data
    self.load();
  };

  this.headers=function(){ const token=sessionStorage.getItem('token'); return token?{'Authorization':'Bearer '+token}:{ }; };

  // region Asset Picker Modal
  let oPick; let pickContractId=null; let selectedAssetIds=new Set();
  this.openAssetPicker=function(){
    // reset previous selections
    selectedAssetIds.clear(); self.updatePickCount(); $('#chkSpmPickAll').prop('checked', false);
    // Ensure DataTable exists
    if(!oPick){
      oPick=$('#dtSpmPickAsset').DataTable({ bLengthChange:false,bFilter:true,aaSorting:[[2,'asc']], language:_DATATABLE_LANGUAGE, responsive:false,
        columnDefs:[{targets:0, orderable:false, searchable:false, className:'text-center', width:'32px'}],
        data:[], columns:[
          { data:null, orderable:false, searchable:false, render:function(d,t,row){
              const id=row.assetId||''; const checked=selectedAssetIds.has(id)?'checked':''; return '<input type="checkbox" class="chkSpmPick" data-id="'+id+'" '+checked+' />';
          }},
          { data:'assetNo', defaultContent:'' },
          { data:'assetName', defaultContent:'' },
          { data:'assetSerialNo', defaultContent:'' },
          { data:'assetGroupName', defaultContent:'' },
          { data:'assetCategoryName', defaultContent:'' },
          { data:'assetTypeName', defaultContent:'' }
        ]
      });
      // hide default filter to use our own search box
      $('#dtSpmPickAsset_filter').hide();
      // search box wire
      $('#txtSpmPickSearch').on('keyup change', function(){ oPick.search($(this).val()).draw(); });
      // select all
      $('#chkSpmPickAll').on('change', function(){ const ch=this.checked; $('#dtSpmPickAsset').find('.chkSpmPick').each(function(){ $(this).prop('checked', ch).trigger('change'); }); });
      // row checkbox
      $('#dtSpmPickAsset').on('change','.chkSpmPick', function(){ const id=parseInt($(this).data('id')); if($(this).is(':checked')) selectedAssetIds.add(id); else selectedAssetIds.delete(id); self.updatePickCount(); });
      // toggle by clicking row (except when clicking a control)
      $('#dtSpmPickAsset tbody').on('click','tr', function(e){
        if($(e.target).is('input,button,select,label')) return; // ignore direct control clicks
        const $chk=$(this).find('.chkSpmPick'); if($chk.length){ $chk.prop('checked', !$chk.is(':checked')).trigger('change'); }
      });
      // attach
      $('#btnSpmPickAttach').on('click', self.attachPickedAssets);
    }

    // Load contract options for this space's site (admin sees all, others filtered by API)
    self.loadContractsForSite(currentData?currentData.siteId:0).always(function(){
      self.refreshPickAssets();
      $('#modalSpmPickAsset').modal('show');
    });
  };

  this.updatePickCount=function(){ $('#lblSpmPickCount').text(selectedAssetIds.size + ' selected'); };

  this.loadContractsForSite=function(siteId){ // use api/contract.php and filter by site on client (admins will see many)
    const headers=self.headers();
    return $.ajax({ url:'api/contract.php', method:'GET', dataType:'json', headers}).done(function(resp){ if(resp&&resp.success){
      const list=resp.result||[]; const $opt=$('#optSpmPickContract'); try{$opt.materialSelect('destroy');}catch(e){}
      $opt.empty().append('<option value="" selected>All Contracts</option>');
      (list||[]).forEach(function(c){ if(!siteId || parseInt(c.siteId)===parseInt(siteId)){ $opt.append('<option value="'+c.contractId+'">'+(c.contractName||('Contract #'+c.contractId))+'</option>'); }});
      // pick first site-matching contract by default
      let first = $opt.find('option[value!=""]').first().val();
      pickContractId = first || null;
      if (pickContractId) { $opt.val(pickContractId); }
      $opt.materialSelect();
      $opt.off('change._spmPick').on('change._spmPick', function(){ pickContractId=$(this).val()||null; self.refreshPickAssets(); });
    }});
  };

  this.refreshPickAssets=function(){ const headers=self.headers(); const contractId=pickContractId; if(!contractId){ oPick.clear().draw(); self.updatePickCount(); return; }
    ShowLoader(); $.ajax({ url:'api/asset.php?contractId='+encodeURIComponent(contractId), method:'GET', dataType:'json', headers})
      .done(function(resp){ if(resp&&resp.success){
        const arr=(resp.result||[]).map(function(r){ return {
          assetId: parseInt(r.assetId||r.asset_id||0), assetNo: r.assetNo||r.asset_no||'', assetName: r.assetName||r.asset_name||'', assetSerialNo: r.assetSerialNo||r.asset_serial_no||'',
          assetGroupName: r.assetGroupName||r.asset_group_name||'', assetCategoryName: r.assetCategoryName||r.asset_category_name||'', assetTypeName: r.assetTypeName||r.asset_type_name||''
        }; });
        oPick.clear().rows.add(arr).draw();
        // Disable rows already linked to this space
        const linked=new Set((currentData.assets||[]).map(function(x){ return parseInt(x.assetId); }));
        $('#dtSpmPickAsset').find('.chkSpmPick').each(function(){ const id=parseInt($(this).data('id')); if(linked.has(id)){ $(this).prop('checked', true).prop('disabled', true); } });
        self.updatePickCount();
      } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
  };

  this.attachPickedAssets=function(){ if(selectedAssetIds.size===0){ toastr['warning']('No assets selected'); return; }
    const existing=(currentData.assets||[]).map(function(x){ return parseInt(x.assetId); });
    const merged=Array.from(new Set(existing.concat(Array.from(selectedAssetIds))));
    ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify({ assetIds: merged }), contentType:'application/json', dataType:'json', headers:self.headers()})
      .done(function(resp){ if(resp&&resp.success){ $('#modalSpmPickAsset').modal('hide'); toastr['success']('Assets attached', _ALERT_TITLE_SUCCESS); selectedAssetIds.clear(); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
  };
  // endregion

  this.load=function(){ ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'GET', dataType:'json', headers:self.headers() }).done(function(resp){ if(resp&&resp.success){ currentData=resp.result||{}; self.renderInfo(currentData); self.renderAssets(currentData.assets||[]); self.renderReservations(currentData.upcomingReservations||[]); self.renderMedia(currentData.media||[]);} else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); }); };

  this.renderInfo=function(d){
    $('#lblSpcMngTitle').text('Space Management: '+(d.spaceName||'-'));
    // Initialize status select cleanly, then set value and re-init so UI reflects it
    try { $('#optSpmStatus').materialSelect('destroy'); } catch(e){}
    $('#optSpmStatus').val(d.spaceStatus||'AVAILABLE');
    $('#optSpmStatus').materialSelect();
    // refs (location/category/type)
    self.loadRefs(d.categoryId).always(function(){
      $('#txtSpmName').val(d.spaceName||''); $('#txtSpmName').siblings('label').addClass('active');
      self.setSelect('#optSpmLocation','#lblSpmLocation', d.locationId);
      self.setSelect('#optSpmCategory','#lblSpmCategory', d.categoryId);
      self.setSelect('#optSpmType','#lblSpmType', d.typeId);
      $('#txtSpmArea').val(d.spaceArea||''); $('#txtSpmArea').siblings('label').addClass('active');
      $('#txtSpmCapacity').val(d.spaceCapacity||''); $('#txtSpmCapacity').siblings('label').addClass('active');
      $('#txaSpmDesc').val(d.spaceDesc||''); $('#txaSpmDesc').siblings('label').addClass('active');
    }); };

  this.loadRefs=function(categoryId){ function one(key){ let url='api/space.php/refs/'+key; if(key==='type'&&categoryId){ url+='?spaceCategoryId='+encodeURIComponent(categoryId); } return $.ajax({ url, method:'GET', dataType:'json', headers:self.headers()}); } return $.when(one('location'),one('category'),one('type')).done(function(loc,cat,typ){ const $loc=$('#optSpmLocation'),$cat=$('#optSpmCategory'),$typ=$('#optSpmType'); try{$loc.materialSelect('destroy');}catch(e){} try{$cat.materialSelect('destroy');}catch(e){} try{$typ.materialSelect('destroy');}catch(e){} $loc.empty().append('<option value="" selected>--</option>'); $cat.empty().append('<option value="" selected>--</option>'); $typ.empty().append('<option value="" selected>--</option>'); (loc[0].result||[]).forEach(function(r){ $loc.append('<option value="'+r.spaceLocationId+'">'+r.spaceLocationName+'</option>'); }); (cat[0].result||[]).forEach(function(r){ $cat.append('<option value="'+r.spaceCategoryId+'">'+r.spaceCategoryName+'</option>'); }); (typ[0].result||[]).forEach(function(r){ $typ.append('<option value="'+r.spaceTypeId+'">'+r.spaceTypeName+'</option>'); }); $loc.materialSelect(); $cat.materialSelect(); $typ.materialSelect(); $cat.off('change._typeCascade').on('change._typeCascade', function(){ const cid=$(this).val(); try{$typ.materialSelect('destroy');}catch(e){} let url='api/space.php/refs/type'+(cid?('?spaceCategoryId='+encodeURIComponent(cid)):''); $.ajax({ url, method:'GET', dataType:'json', headers:self.headers()}).done(function(resp){ $typ.empty().append('<option value="" selected>--</option>'); (resp.result||[]).forEach(function(r){ $typ.append('<option value="'+r.spaceTypeId+'">'+r.spaceTypeName+'</option>'); }); $typ.materialSelect(); }); }); }); };

  this.setSelect=function(sel,label,val){
    // Recreate the MDB select to sync the selected value and label UI
    try { $(sel).materialSelect('destroy'); } catch(e){}
    if(val!==undefined && val!==null && val!==''){
      $(sel).val(String(val));
    } else {
      $(sel).val('');
    }
    $(sel).materialSelect();
  };

  this.renderAssets=function(list){ oAssets.clear().rows.add(list||[]).draw(); };
  this.renderReservations=function(list){ oResv.clear().rows.add(list||[]).draw(); };

  // region Media
  this.renderMedia=function(list){
    selectedMediaIds.clear();
    currentCoverId=0;
    const $grid=$('#spmMediaGrid'); $grid.empty();
    if(!list||list.length===0){ $grid.append('<div class="col-12 text-muted">No media yet.</div>'); return; }
    list.forEach(function(m){
      const id=m.spaceMediaId||m.space_media_id; const url=m.downloadUrl||m.download_url||'#'; const type=(m.mediaType||m.media_type||'').toUpperCase();
  const cap=m.mediaCaption||m.media_caption||'';
  const isCover = type==='PHOTO' && parseInt(m.isCover||m.is_cover||0)===1;
      if(isCover){ currentCoverId=id; }
      const badge= type==='FLOORPLAN' ? '<span class="badge badge-info badge-modern">FLOORPLAN</span>' : '<span class="badge badge-dark badge-modern">PHOTO</span>';
  const coverChip = isCover ? '<span class="badge badge-success badge-modern spm-cover-badge"><i class="fas fa-star mr-1"></i>Cover</span>' : '';
      const coverButton = type==='PHOTO'
        ? '<button type="button" class="btn btn-sm '+(isCover?'btn-primary':'btn-outline-primary')+' btnSpmSetCover" data-id="'+id+'" '+(isCover?'disabled':'')+'>'+(isCover?'<i class="fas fa-star mr-1"></i>Cover photo':'<i class="far fa-star mr-1"></i>Set cover')+'</button>'
        : '';
      const deleteButton = '<button type="button" class="btn btn-sm btn-outline-danger btnSpmDeleteOne" data-id="'+id+'"><i class="fas fa-trash mr-1"></i>Delete</button>';
      const actionRow = '<div class="spm-card-actions">'+(coverButton||'')+deleteButton+'</div>';
      const card = '<div class="col-lg-3 col-sm-4 mb-3">'
        +  '<div class="card h-100 spm-media-card'+(isCover?' spm-media-cover':'')+'">'
        +    coverChip
        +    '<div class="view overlay"><img src="'+url+'" class="card-img-top" style="object-fit:cover;height:160px"><a href="'+url+'" target="_blank"><div class="mask rgba-white-slight"></div></a></div>'
        +    '<div class="card-body py-2">'
        +      '<div class="d-flex justify-content-between align-items-center">'
        +        '<div>'+badge+'</div>'
        +        '<div><input type="checkbox" class="chkSpmMedia" data-id="'+id+'"></div>'
        +      '</div>'
        +      (cap?('<div class="small text-muted mt-1">'+cap+'</div>'):'')
        +      actionRow
        +    '</div>'
        +  '</div>'
        +'</div>';
      $grid.append(card);
    });
    // Track selection
    $grid.off('change','.chkSpmMedia').on('change','.chkSpmMedia', function(){ const id=parseInt($(this).data('id')); if($(this).is(':checked')) selectedMediaIds.add(id); else selectedMediaIds.delete(id); });
    $grid.off('click','.btnSpmSetCover').on('click','.btnSpmSetCover', function(){ const id=parseInt($(this).data('id')); if($(this).is(':disabled')){ return; } self.setCoverMedia(id); });
    $grid.off('click','.btnSpmDeleteOne').on('click','.btnSpmDeleteOne', function(){ const id=parseInt($(this).data('id')); self.confirmDeleteMedia([id]); });
    $grid.off('click','.spm-media-card').on('click','.spm-media-card', function(e){
      if($(e.target).closest('button,.btn,a,.chkSpmMedia,label').length){ return; }
      const $chk=$(this).find('.chkSpmMedia');
      if(!$chk.length){ return; }
      const isChecked=!$chk.prop('checked');
      $chk.prop('checked', isChecked).trigger('change');
    });
  };

  this.handlePhotoUpload=function(files){ if(!files||files.length===0) return; self.filesToPayload(files, false).then(function(payload){ self.syncMedia(payload); }); };
  this.handleFloorUpload=function(files){ if(!files||files.length===0) return; // only first image used
    self.filesToPayload([files[0]], true).then(function(payload){ self.syncMedia(payload); }); };

  this.filesToPayload=function(files, isFloor){ return new Promise(function(resolve){
    const items=[]; let count=0; Array.from(files).forEach(function(file){ const reader=new FileReader(); reader.onload=function(e){ items.push({ name:file.name, filename:file.name, type:file.type, size:file.size, data:e.target.result.split(',')[1], caption:'' }); count++; if(count===files.length){ const p= isFloor? { floorplan: items[0] } : { photos: items }; resolve(p); } }; reader.readAsDataURL(file); });
  }); };

  this.syncMedia=function(extra){ const payload=Object.assign({}, extra||{}); if(selectedMediaIds.size>0){ payload.photosRemove=Array.from(selectedMediaIds); }
    ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify(payload), contentType:'application/json', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Updated', _ALERT_TITLE_SUCCESS); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); });
  };

  this.deleteSelectedMedia=function(){ if(selectedMediaIds.size===0){ toastr['warning']('Select media to delete'); return; } self.confirmDeleteMedia(Array.from(selectedMediaIds)); };
  
  this.confirmDeleteMedia=function(ids){ ids=(ids||[]).filter(function(v){ return parseInt(v)>0; }); if(ids.length===0){ return; }
    const msg= ids.length>1 ? 'Delete '+ids.length+' selected media items?' : 'Delete this media item?';
    if(!confirm(msg)){ return; }
    self.deleteMediaByIds(ids);
  };
  
  this.deleteMediaByIds=function(ids){ selectedMediaIds.clear(); const payload={ photosRemove: ids }; ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify(payload), contentType:'application/json', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ toastr['success']('Media deleted', _ALERT_TITLE_SUCCESS); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); }); };
  
  this.setCoverMedia=function(mediaId){ if(!mediaId){ return; }
    ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify({ coverMediaId: mediaId }), contentType:'application/json', dataType:'json', headers:self.headers()})
      .done(function(resp){ if(resp&&resp.success){ toastr['success']('Cover photo updated', _ALERT_TITLE_SUCCESS); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} })
      .fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); })
      .always(function(){ HideLoader(); });
  };
  // endregion

  this.saveInfo=function(){ const payload={}; const n=$('#txtSpmName').val().trim(); if(n) payload.spaceName=n; const s=$('#optSpmStatus').val(); if(s) payload.status=s; const l=$('#optSpmLocation').val(); if(l) payload.locationId=parseInt(l); const c=$('#optSpmCategory').val(); if(c) payload.categoryId=parseInt(c); const t=$('#optSpmType').val(); if(t) payload.typeId=parseInt(t); const a=$('#txtSpmArea').val(); if(a!=='') payload.area=a; const cp=$('#txtSpmCapacity').val(); if(cp!=='') payload.capacity=cp; const d=$('#txaSpmDesc').val(); if(d!=='') payload.description=d; ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify(payload), contentType:'application/json', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ toastr['success'](resp.errmsg||'Updated', _ALERT_TITLE_SUCCESS); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); }); };

  this.attachAsset=function(){ const assetId=parseInt($('#txtSpmAttachAssetId').val()); if(!assetId){ toastr['error']('Asset ID required', _ALERT_TITLE_ERROR); return; } ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify({ assetIds:[...(currentData.assets||[]).map(function(x){return x.assetId;}), assetId] }), contentType:'application/json', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ $('#modalAttachAsset').modal('hide'); toastr['success']('Asset attached', _ALERT_TITLE_SUCCESS); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); }); };

  this.unlinkAsset=function(spaceAssetId){ // remove by spaceAssetId; backend sync uses assetIds list, so filter it out
    const remainingIds=(currentData.assets||[]).filter(function(x){ return x.spaceAssetId!==spaceAssetId; }).map(function(x){ return x.assetId; }); ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId, method:'PUT', data:JSON.stringify({ assetIds: remainingIds }), contentType:'application/json', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ toastr['success']('Asset unlinked', _ALERT_TITLE_SUCCESS); self.load(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); }); };

  this.loadReservations=function(){ ShowLoader(); $.ajax({ url:'api/space.php/'+spaceId+'/reservation', method:'GET', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ self.renderReservations(resp.result||[]);} else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); }); };

  this.cancelReservation=function(){ if(!cancelResvId){ return; } const reason=$('#txaSpmCancelReason').val()||null; ShowLoader(); $.ajax({ url:'api/space.php/reservation/'+cancelResvId+'/cancel', method:'PUT', data: JSON.stringify({ reason: reason }), contentType:'application/json', dataType:'json', headers:self.headers()}).done(function(resp){ if(resp&&resp.success){ $('#modalCancelResv').modal('hide'); toastr['success'](resp.errmsg||'Canceled', _ALERT_TITLE_SUCCESS); self.loadReservations(); } else { toastr['error']((resp&&resp.errmsg)||_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR);} }).fail(function(){ toastr['error'](_ALERT_MSG_ERROR_DEFAULT,_ALERT_TITLE_ERROR); }).always(function(){ HideLoader(); cancelResvId=null; }); };

  this.getQueryInt=function(key){ const urlParams=new URLSearchParams(window.location.search); const v=parseInt(urlParams.get(key)); return isNaN(v)?0:v; };
  this.getClassName=function(){ return className; };
}

document.addEventListener('DOMContentLoaded', function(){ ShowLoader(); let pending=$('.includeHtml').length; function boot(){ try{ if (typeof initiatePages === 'function') { initiatePages(); } const main=new MainSpaceManage(); main.init(); }catch(e){ toastr['error'](e.message,_ALERT_TITLE_ERROR);} HideLoader(); } if(pending===0){ setTimeout(boot,100);} else { $('.includeHtml').each(function(){ const id=$(this).attr('id'); $('#'+id).load('html/'+id.substr(2)+'.html?'+new Date().valueOf(), function(){ pending--; if(pending===0){ setTimeout(boot,50);} }); }); } });
