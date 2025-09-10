function ModalLicense() {
  const className = 'ModalLicense';
  let self = this;
  let formValidate;
  let classFrom;
  let licenseId;
  let modalConfirmDeleteClass;

  this.init = function(){
    const vData = [
      { field_id:'txtMlcTitle', type:'text', name:'License Title', validator:{ notEmpty:true, maxLength:255 } },
      { field_id:'txtMlcStartDate', type:'text', name:'Start Date', validator:{ notEmpty:true } },
      { field_id:'txtMlcEndDate', type:'text', name:'End Date', validator:{ notEmpty:true } },
      { field_id:'txfMlcFile', type:'file', name:'License File', validator:{ /* optional file */ } }
    ];
  // initialize date pickers for standard helpers
  if (typeof $('#txtMlcStartDate').pickadate === 'function') { $('#txtMlcStartDate').pickadate(); }
  if (typeof $('#txtMlcEndDate').pickadate === 'function') { $('#txtMlcEndDate').pickadate(); }
    formValidate = new MzValidate('formMlc');
    formValidate.registerFields(vData);
    document.getElementById('txfMlcFile').addEventListener('change', mzHandleFileSelect, false);
    document.getElementById('txfMlcFile').addEventListener('change', mzHandleFileDimension, false);

    $('#btnMlcSubmit').on('click', function(){ self.submit(); });
    $('#btnMlcSave').on('click', function(){ self.save(); });
    $('#btnMlcDelete').on('click', function(){ try { $('#modal_license').modal('hide'); modalConfirmDeleteClass.delete(licenseId, self); } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} });
  };

  this.add = function(){
    ShowLoader();
    setTimeout(function(){
      try{
        formValidate.clearValidation();
        licenseId = '';
  $('#txtMlcTitle, #txtMlcStartDate, #txtMlcEndDate, #txfMlcFile, #txfMlcFileBlob, #txfMlcFileWidth, #txfMlcFileHeight').val('');
        $('#lnkMlcCurrentFile').attr('href', '#').text('N/A');
        $('#btnMlcSubmit').show();
        $('#btnMlcSave, #btnMlcDelete').hide();
        $('#lblMlcTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add License');
        $('#modal_license').modal({backdrop:'static', keyboard:false}).scrollTop(0);
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} HideLoader();
    }, 200);
  };

  this.edit = function(_licenseId){
    ShowLoader();
    setTimeout(function(){
      try{
        mzCheckFuncParam([_licenseId]);
        licenseId = _licenseId;
        formValidate.clearValidation();
  if (typeof $('#txtMlcStartDate').pickadate === 'function') { $('#txtMlcStartDate').pickadate(); }
  if (typeof $('#txtMlcEndDate').pickadate === 'function') { $('#txtMlcEndDate').pickadate(); }
        const data = mzAjaxRequest2('license/'+licenseId, 'GET');
        mzSetFieldValue('MlcTitle', data['licenseTitle'], 'text');
        // If pickadate not present, set values directly
        if (typeof $('#txtMlcStartDate').pickadate === 'function') {
          mzSetFieldValue('MlcStartDate', data['licenseStartDate'], 'date');
          mzSetFieldValue('MlcEndDate', data['licenseEndDate'], 'date');
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
        $('#modal_license').modal({backdrop:'static', keyboard:false}).scrollTop(0);
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} HideLoader();
    }, 200);
  };

  const buildPayload = ()=>{
    const payload = {
      licenseTitle: $('#txtMlcTitle').val().trim(),
      licenseStartDate: $('#txtMlcStartDate').val(),
      licenseEndDate: $('#txtMlcEndDate').val()
    };
    const file = $('#txfMlcFile').prop('files')[0];
    if (file) {
      payload['fileUpload'] = {
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

  this.submit = function(){
    if (!formValidate.validateNow()) { toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR); return; }
    ShowLoader();
    setTimeout(function(){
      try{
        const data = buildPayload();
        mzAjaxRequest2('license', 'POST', data);
        classFrom.genTable();
        $('#modal_license').modal('hide');
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} HideLoader();
    }, 200);
  };

  this.save = function(){
    if (!formValidate.validateNow()) { toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR); return; }
    ShowLoader();
    setTimeout(function(){
      try{
        const data = buildPayload();
        mzAjaxRequest2('license/'+licenseId, 'PUT', data);
        classFrom.genTable();
        $('#modal_license').modal('hide');
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} HideLoader();
    }, 200);
  };

  this.delete = function(_licenseId){
    ShowLoader();
    setTimeout(function(){
      try{
        mzCheckFuncParam([_licenseId]);
        mzAjaxRequest2('license/'+_licenseId, 'DELETE');
        classFrom.genTable();
      } catch(e){ toastr['error'](e.message, _ALERT_TITLE_ERROR);} HideLoader();
    }, 300);
  };

  this.getClassName = function(){ return className; };
  this.setClassFrom = function(_classFrom){ classFrom = _classFrom; };
  this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) { modalConfirmDeleteClass = _modalConfirmDeleteClass; };
}
