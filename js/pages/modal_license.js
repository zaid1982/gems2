// js/pages/modal_license.js
function ModalLicense() {
  const className = 'ModalLicense';
  let self = this;
  let formValidate;
  let classFrom;
  let licenseId = '';
  let modalConfirmDeleteClass;

  // --- helpers ---------------------------------------------------------------
  const ensureValidation = () => {
    if (formValidate) return;
    const vData = [
      { field_id:'txtMlcTitle',     type:'text', name:'License Title', validator:{ notEmpty:true, maxLength:255 } },
      { field_id:'txtMlcStartDate', type:'text', name:'Start Date',    validator:{ notEmpty:true } },
      { field_id:'txtMlcEndDate',   type:'text', name:'End Date',      validator:{ notEmpty:true } },
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
    $('#txtMlcTitle, #txtMlcStartDate, #txtMlcEndDate, #txfMlcFile, #txfMlcFileBlob, #txfMlcFileWidth, #txfMlcFileHeight').val('');
    $('#lnkMlcCurrentFile').attr('href', '#').text('N/A');
    $('#btnMlcSubmit').show();
    $('#btnMlcSave, #btnMlcDelete').hide();
    $('#lblMlcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add License');
  };

  // --- public ---------------------------------------------------------------
  this.init = function() {
    // Bind delegated DOM events (safe even if modal HTML loads later)
    $(document).on('change', '#txfMlcFile', function() {
      try {
        mzHandleFileSelect.call(this);
        mzHandleFileDimension.call(this);
      } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
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
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR); }
    });
  };

  this.add = function() {
    ShowLoader();
    setTimeout(function() {
      try {
        resetForm();
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
