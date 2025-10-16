function MainLicense(){
  const className = 'MainLicense';
  let self = this;
  let oTable;
  let modalLicenseClass;
  let statusFilter = 'all';
  let rowsCache = [];
  // list-only view

  const isWarning = (row)=>{
    const n = Number(row.daysToExpire);
    if (!Number.isFinite(n)) return false;
    if (n < 0) return false; // expired handled separately
    const today = moment().format('YYYY-MM-DD');
    if (row.warningDate && moment(today).isSameOrAfter(row.warningDate, 'day')) return true;
    const thr = Number.isFinite(Number(row.warningDays)) ? Number(row.warningDays) : 30;
    return n <= thr;
  };
  const badge = (row)=>{
    const n = Number(row.daysToExpire);
    if (!Number.isFinite(n)) return '<span class="badge badge-secondary">N/A</span>';
    if (n < 0) return '<span class="badge badge-danger">Expired</span>';
    if (isWarning(row)) return '<span class="badge badge-warning">Expiring</span>';
    return '<span class="badge badge-success">Valid</span>';
  };
  const humanizeDays = (days)=>{
    const n = Number(days);
    if (!Number.isFinite(n)) return '-';
    if (n >= 31) {
      const months = Math.floor(n / 30);
      const years = Math.floor(months / 12);
      const remMonths = months % 12;
      const parts = [];
      if (years > 0) parts.push(years + ' year' + (years > 1 ? 's' : ''));
      if (remMonths > 0) parts.push(remMonths + ' mth' + (remMonths > 1 ? 's' : ''));
      return parts.join(' ');
    }
    const abs = Math.abs(n);
    return abs + ' day' + (abs === 1 ? '' : 's');
  };
  const fileLink = (row)=> {
    if(!row.uploadId) return '';
    const link = mzAjaxRequest2('document/upload_link/'+row.uploadId, 'GET');
    return `<a class="btn btn-outline-secondary btn-sm" target="_blank" href="${link}">Open</a>`;
  };
  const fmtDate = (d)=>{
    if (!d) return '-';
    try {
      if (typeof moment === 'function') {
        const m = moment(d, 'YYYY-MM-DD', true);
        if (m.isValid()) return m.format('DD-MMM-YYYY');
        const m2 = moment(d);
        return m2.isValid() ? m2.format('DD-MMM-YYYY') : d;
      }
      return d;
    } catch(e){ return d; }
  };
  const deriveStatus = (row)=>{
    const n = Number(row.daysToExpire);
    if (!Number.isFinite(n)) return 'na';
    if (n < 0) return 'expired';
    if (isWarning(row)) return 'expiring';
    return 'valid';
  };
  const updateMetrics = ()=>{
    const total = rowsCache.length;
    let expiring = 0, expired = 0, valid = 0, na = 0;
    rowsCache.forEach(row=>{
      const st = deriveStatus(row);
      if (st === 'valid') valid++;
      else if (st === 'expiring') expiring++;
      else if (st === 'expired') expired++;
      else na++;
    });
    $('#metricTotal').text(total);
    $('#metricExpiring').text(expiring);
    $('#metricExpired').text(expired);
    $('#metricNoExpiry').text(na);
  };
  // no cards/toggle needed in list-only mode
  const updateResultsSummary = ()=>{
    if (!oTable) { return; }
    const info = oTable.page.info();
    if (!info) { return; }
    const showing = info.recordsDisplay;
    const total = info.recordsTotal;
    $('#lblLicenseCount').text(`Showing ${showing} of ${total} license${total === 1 ? '' : 's'}`);
  };
  const updateLastRefreshed = ()=>{
    const label = $('#lblLicenseUpdated span');
    if (!label.length) { return; }
    const now = typeof moment === 'function' ? moment().format('DD MMM YYYY, h:mm A') : new Date().toLocaleString();
    label.text(now);
  };

  // Custom filter hook for DataTables (status filter)
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
    if (!oTable || settings.nTable !== oTable.table().node()) {
      return true;
    }
    if (statusFilter === 'all') {
      return true;
    }
    const rowData = settings.aoData[dataIndex] ? settings.aoData[dataIndex]._aData : null;
    if (!rowData) { return true; }
    return deriveStatus(rowData) === statusFilter;
  });

  this.init = function(){
    oTable = $('#dtLcnData').DataTable({
      bLengthChange:false,
      searching:true,
      aaSorting:[[3,'asc']],
      language:_DATATABLE_LANGUAGE,
      pageLength:25,
      autoWidth:false,
      dom:"<'row d-none'<'col-sm-12'f>>"+
          "<'row'<'col-sm-12'tr>>"+
          "<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
      fnRowCallback: function(nRow, aData, iDisplayIndex){
        const info = $(this).DataTable().page.info();
        $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
        const n = Number(aData.daysToExpire);
        // Clear any previous coloring to avoid stale classes on redraw/reuse
        $(nRow).removeClass('table-danger table-warning table-success table-secondary');
        if (Number.isFinite(n)) {
          if (n < 0) {
            $(nRow).addClass('table-danger');
          } else if (isWarning(aData)) {
            $(nRow).addClass('table-warning');
          }
        }
        // Attach responsive labels for mobile card view
        const $cells = $('td', nRow);
        $cells.eq(0).attr('data-label', '#');
        $cells.eq(1).attr('data-label', 'Title');
        $cells.eq(2).attr('data-label', 'Start Date');
        $cells.eq(3).attr('data-label', 'End Date');
        $cells.eq(4).attr('data-label', 'Days Left');
        $cells.eq(5).attr('data-label', 'Status');
        $cells.eq(6).attr('data-label', 'File');
        $cells.eq(7).attr('data-label', 'Actions');
      },
      columnDefs:[
        { bSortable:false, targets:[0,6,7] },
        { className:'text-center', targets:[0,4,5,6,7] },
        { className:'noVis', targets:[0,7] }
      ],
      aoColumns:[
        { mData:null },
        { mData:'licenseTitle' },
  { mData:'licenseStartDate', mRender:function(d, type){ if (type==='display' || type==='filter') return fmtDate(d); return d; } },
  { mData:'licenseEndDate',   mRender:function(d, type){ if (type==='display' || type==='filter') return fmtDate(d); return d; } },
        { mData:'daysToExpire', mRender:function(d){ return humanizeDays(d); } },
  { mData:null, mRender:function(row){ return badge(row); } },
        { mData:null, mRender:function(row){ return fileLink(row); } },
        { mData:null, mRender:function(row, type, full, meta){
            return '<a><i class="fas fa-edit lnkLcnEdit" id="lnkLcnEdit_'+meta.row+'" data-toggle="tooltip" title="Edit"></i></a>&nbsp;'
                 + '<a><i class="fas fa-trash-alt lnkLcnDelete" id="lnkLcnDelete_'+meta.row+'" data-toggle="tooltip" title="Delete"></i></a>';
        }}
      ]
    });

  $('#btnLicenseAdd').on('click', ()=> modalLicenseClass.add());
    $('#btnLicenseRefresh').on('click', ()=> self.genTable());
  // no view toggle

    $('#txtLicenseSearch').on('input', function(){
      const term = this.value || '';
      oTable.search(term).draw();
    });

    $('#optLicenseStatus').on('change', function(){
      statusFilter = this.value || 'all';
      oTable.draw();
      updateResultsSummary();
    });

    oTable.on('draw', function(){
      updateResultsSummary();
    });

    const tbody = $('#dtLcnData tbody');
    tbody.on('click', '.lnkLcnEdit', function(){
      const data = mzGetLinkRow($(this), oTable); if(!data) return; modalLicenseClass.edit(data['licenseId']);
    });
    tbody.on('click', '.lnkLcnDelete', function(){
      const id = mzGetLinkId($(this), oTable, 'licenseId'); if(!id) return; modalLicenseClass.delete(id);
    });

    self.genTable();
  };

  this.genTable = function(){
    const dataDb = mzAjaxRequest2('license', 'GET');
    rowsCache = Array.isArray(dataDb) ? dataDb : [];
    oTable.clear().rows.add(rowsCache).draw();
    updateMetrics();
    updateResultsSummary();
    updateLastRefreshed();
  };

  this.setModalLicenseClass = function(_modal){ modalLicenseClass = _modal; };
  this.getClassName = function(){ return className; };
}
