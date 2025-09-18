// js/pages/modal_license.js
function ModalLicense() {
  const className = 'ModalLicense';
  let self = this;
  let formValidate;
  let classFrom;
  let licenseId = '';
  let modalConfirmDeleteClass;

  // --- helpers ---------------------------------------------------------------
  const normalizeDateStr = (v) => {
    if (!v) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
    const dt = new Date(v);
    return isNaN(dt.getTime()) ? '' : dt.toISOString().split('T')[0];
  };

  const getDateFromInput = (selector) => {
    if (typeof $(selector).pickadate === 'function') {
      const p = $(selector).pickadate('picker');
      if (p && p.get('value')) return p.get('select', 'yyyy-mm-dd');
    }
    return normalizeDateStr($(selector).val());
  };
  const ensureValidation = () => {
    if (formValidate) return;
    const vData = [
      { field_id:'txtMlcTitle',     type:'text', name:'License Title', validator:{ notEmpty:true, maxLength:255 } },
      { field_id:'txtMlcStartDate', type:'text', name:'Start Date',    validator:{ notEmpty:true } },
      { field_id:'txtMlcEndDate',   type:'text', name:'End Date',      validator:{ notEmpty:true } },
      { field_id:'txtMlcWarningDate', type:'text', name:'Warning Date', validator:{ allowEmpty:true } },
      { field_id:'txfMlcFile',      type:'file', name:'License File',  validator:{ /* optional */ } }
    ];
    formValidate = new MzValidate('formMlc');
    formValidate.registerFields(vData);
  };

  const initDatePickersIfAvailable = () => {
    // If pickadate is present, initialize (idempotent guard).
    if (typeof $('#txtMlcStartDate').pickadate === 'function') {
      if (!$('#txtMlcStartDate').data('pickadate')) {
        $('#txtMlcStartDate').pickadate({ format:'yyyy-mm-dd', formatSubmit:'yyyy-mm-dd' });
      }
      if (!$('#txtMlcEndDate').data('pickadate')) {
        $('#txtMlcEndDate').pickadate({ format:'yyyy-mm-dd', formatSubmit:'yyyy-mm-dd' });
      }
      if (!$('#txtMlcWarningDate').data('pickadate')) {
        $('#txtMlcWarningDate').pickadate({ format:'yyyy-mm-dd', formatSubmit:'yyyy-mm-dd' });
      }
    }
  };

  const refreshWarningDateBounds = () => {
    const startStr = getDateFromInput('#txtMlcStartDate');
    const endStr   = getDateFromInput('#txtMlcEndDate');
    const hasRange = !!(startStr && endStr);

    const $wd = $('#txtMlcWarningDate');
    const usingPicker = typeof $wd.pickadate === 'function' && $wd.data('pickadate');

    if (!hasRange) {
      // Disable and clear limits
      if (usingPicker) {
        const wp = $wd.pickadate('picker');
        if (wp) {
          wp.clear();
          wp.set('min', false);
          wp.set('max', false);
        }
      } else {
        $wd.val('');
        $wd.removeAttr('min').removeAttr('max');
      }
      $wd.prop('disabled', true);
      return;
    }

    // Enable and constrain within range
    $wd.prop('disabled', false);
    if (usingPicker) {
      const wp = $wd.pickadate('picker');
      if (wp) {
        const s = startStr.split('-').map(Number);
        const e = endStr.split('-').map(Number);
        wp.set('min', [s[0], s[1]-1, s[2]]);
        wp.set('max', [e[0], e[1]-1, e[2]]);
        if (wp.get('value')) {
          const cur = wp.get('select', 'yyyy-mm-dd');
          if (cur < startStr || cur > endStr) wp.clear();
        }
      }
    } else {
      $wd.attr('min', startStr).attr('max', endStr);
      const cur = normalizeDateStr($wd.val());
      if (cur && (cur < startStr || cur > endStr)) {
        $wd.val('');
      }
    }
  };

  const buildPayload = () => {
    // Resolve dates from pickadate if available; else from native inputs
    let startDate = $('#txtMlcStartDate').val();
    let endDate   = $('#txtMlcEndDate').val();

    if (typeof $('#txtMlcStartDate').pickadate === 'function') {
      const sp = $('#txtMlcStartDate').pickadate('picker');
      const ep = $('#txtMlcEndDate').pickadate('picker');
      if (sp && sp.get('value')) startDate = sp.get('select', 'yyyy-mm-dd');
      if (ep && ep.get('value')) endDate   = ep.get('select', 'yyyy-mm-dd');
    } else {
      // Normalize free-text dates to YYYY-MM-DD
      const norm = (v)=>{
        if (!v || /^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
        const dt = new Date(v);
        return isNaN(dt.getTime()) ? v : dt.toISOString().split('T')[0];
      };
      startDate = norm(startDate);
      endDate   = norm(endDate);
    }

    const payload = {
      licenseTitle: $('#txtMlcTitle').val().trim(),
      licenseStartDate: startDate,
      licenseEndDate: endDate
    };

    // Warning date (if set)
    let warningDate = $('#txtMlcWarningDate').val();
    if (typeof $('#txtMlcWarningDate').pickadate === 'function') {
      const wp = $('#txtMlcWarningDate').pickadate('picker');
      if (wp && wp.get('value')) warningDate = wp.get('select', 'yyyy-mm-dd');
    }
    if (warningDate && /^\d{4}-\d{2}-\d{2}$/.test(warningDate)) {
      payload.warningDate = warningDate;
    }

    const file = $('#txfMlcFile').prop('files')?.[0];
    if (file) {
      payload.fileUpload = {
        name: $('#txtMlcTitle').val().trim() || file.name,
        filename: file.name,
        size: file.size,
        type: file.type,
        data: $('#txfMlcFileBlob').val(),
        width: $('#txfMlcFileWidth').val(),
        height: $('#txfMlcFileHeight').val()
      };
    }
    return payload;
  };

  const resetForm = () => {
    if (formValidate) formValidate.clearValidation();
    licenseId = '';
  $('#txtMlcTitle, #txtMlcStartDate, #txtMlcEndDate, #txtMlcWarningDate, #txfMlcFile, #txfMlcFileBlob, #txfMlcFileWidth, #txfMlcFileHeight').val('');
    $('#lnkMlcCurrentFile').attr('href', '#').text('N/A');
    $('#btnMlcSubmit').show();
    $('#btnMlcSave, #btnMlcDelete').hide();
    $('#lblMlcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add License');
  };

  // --- public ---------------------------------------------------------------
  this.init = function() {
    // Bind delegated DOM events (safe even if modal HTML loads later)
    $(document).on('change', '#txfMlcFile', function(e) {
      try {
        // Pass the event so helpers can access e.target
        mzHandleFileSelect(e);
        mzHandleFileDimension(e);
      } catch (err) { toastr['error'](err.message, _ALERT_TITLE_ERROR); }
    });

    $(document).on('click', '#btnMlcSubmit', function() { self.submit(); });
    $(document).on('click', '#btnMlcSave',   function() { self.save(); });
    $(document).on('click', '#btnMlcDelete', function() {
      try { $('#modal_license').modal('hide'); modalConfirmDeleteClass.delete(licenseId, self); }
      catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    });

    // Initialize validation & pickers when the modal is actually shown
    $(document).on('shown.bs.modal', '#modal_license', function() {
      try {
        ensureValidation();
        initDatePickersIfAvailable();
        refreshWarningDateBounds();
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    });

    // Constrain warning date as start/end change
    $(document).on('change', '#txtMlcStartDate, #txtMlcEndDate', function(){
      try { refreshWarningDateBounds(); } catch(_e) {}
    });
  };

  this.add = function() {
    ShowLoader();
    setTimeout(function() {
      try {
        resetForm();
        // Initially disable warning date until both start & end are set
        $('#txtMlcWarningDate').prop('disabled', true);
        $('#modal_license').modal({ backdrop:'static', keyboard:false }).scrollTop(0);
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
      HideLoader();
    }, 50);
  };

  this.edit = function(_licenseId) {
    ShowLoader();
    setTimeout(function() {
      try {
        mzCheckFuncParam([_licenseId]);
        licenseId = _licenseId;
        if (formValidate) formValidate.clearValidation();

        initDatePickersIfAvailable();
        const data = mzAjaxRequest2('license/'+licenseId, 'GET');

        mzSetFieldValue('MlcTitle', data['licenseTitle'], 'text');
        if (typeof $('#txtMlcStartDate').pickadate === 'function') {
          mzSetFieldValue('MlcStartDate', data['licenseStartDate'], 'date');
          mzSetFieldValue('MlcEndDate',   data['licenseEndDate'],   'date');
        } else {
          $('#txtMlcStartDate').val(data['licenseStartDate']);
          $('#txtMlcEndDate').val(data['licenseEndDate']);
          $('#lblMlcStartDate, #lblMlcEndDate').addClass('active');
        }

        // Set bounds before applying warning date value
        refreshWarningDateBounds();
        if (typeof data['warningDate'] !== 'undefined' && data['warningDate']) {
          if (typeof $('#txtMlcWarningDate').pickadate === 'function') {
            mzSetFieldValue('MlcWarningDate', data['warningDate'], 'date');
          } else {
            $('#txtMlcWarningDate').val(data['warningDate']);
            $('#lblMlcWarningDate').addClass('active');
          }
        } else {
          $('#txtMlcWarningDate').val('');
          $('#lblMlcWarningDate').addClass('active');
        }

        if (data['uploadId']) {
          const link = mzAjaxRequest2('document/upload_link/'+data['uploadId'], 'GET');
          $('#lnkMlcCurrentFile').attr('href', link).text('Open');
        } else {
          $('#lnkMlcCurrentFile').attr('href', '#').text('N/A');
        }

        $('#btnMlcSubmit').hide();
        $('#btnMlcSave, #btnMlcDelete').show();
        $('#lblMlcTitle').html('<i class="fas fa-edit text-white"></i> &nbsp;Edit License');
        $('#modal_license').modal({ backdrop:'static', keyboard:false }).scrollTop(0);
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
      HideLoader();
    }, 50);
  };

  this.submit = function() {
    if (!formValidate || !formValidate.validateNow()) {
      toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR); return;
    }
    ShowLoader();
    setTimeout(function() {
      try {
        const data = buildPayload();
        mzAjaxRequest2('license', 'POST', data);
        classFrom.genTable();
        $('#modal_license').modal('hide');
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
      HideLoader();
    }, 50);
  };

  this.save = function() {
    if (!formValidate || !formValidate.validateNow()) {
      toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR); return;
    }
    ShowLoader();
    setTimeout(function() {
      try {
        const data = buildPayload();
        mzAjaxRequest2('license/'+licenseId, 'PUT', data);
        classFrom.genTable();
        $('#modal_license').modal('hide');
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
      HideLoader();
    }, 50);
  };

  this.delete = function(_licenseId) {
    ShowLoader();
    setTimeout(function() {
      try {
        mzCheckFuncParam([_licenseId]);
        mzAjaxRequest2('license/'+_licenseId, 'DELETE');
        classFrom.genTable();
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
      HideLoader();
    }, 100);
  };

  this.getClassName = function(){ return className; };
  this.setClassFrom = function(_classFrom){ classFrom = _classFrom; };
  this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) { modalConfirmDeleteClass = _modalConfirmDeleteClass; };
}
