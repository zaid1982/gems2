function ModalWoEdit () {
    
    const className = 'ModalWoEdit';
    let self = this;
    let formValidate;
    let classFrom;
    let refUser;
    let refPpmGroup;
    let refStatus;
    let woTaskId;
    
    const vData = [
        {
            field_id: 'optMweType',
            type: 'select',
            name: 'Category',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'txtMweAssetNo',
            type: 'text',
            name: 'Asset No',
            validator: {
                notEmpty: false,
                maxLength: 100
            }
        },
        {
            field_id: 'txaMweComplaint',
            type: 'textarea',
            name: 'Complaint Description',
            validator: {
                notEmpty: true,
                maxLength: 1000
            }
        }
    ];
    
    this.init = function () {
        formValidate = new MzValidate('formMwe');
        formValidate.registerFields(vData);
        
        $('#btnMweSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    const data = {
                        woTaskType: mzNullInt('optMweType'),
                        assetNo: mzNullString('txtMweAssetNo'),
                        woTaskComplaint: mzNullString('txaMweComplaint')
                    };
                    console.log(data);
                    ShowLoader(); setTimeout(function () {
                        mzFetch('wo_v3/update_by_admin/'+woTaskId, 'PUT', data).then(res => {
                            if (res === false) {
                                $('#txtMweAssetNo').addClass('invalid');
                                $('#txtMweAssetNoErr').text('Invalid Asset No. registered to this site. Please make sure Asset No. is correct!');
                                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                            } else {
                                classFrom.genTableHmeDataWo();
                                $('#modal_wo_edit').modal('hide');
                            }
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
    };
    
    this.load = function (_woTask) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_woTask]);
            formValidate.clearValidation();
            woTaskId = _woTask['woTaskId'];
            $('#pMweWoNo').text(_woTask['woTaskNo'] !== '-' ? _woTask['woTaskNo'] : _woTask['woTaskRequestNo']);
            $('#pMweType').text(_woTask['woTaskTypeDesc']);
            $('#pMweSeverity').text(mzNullToValue(_woTask['woTaskSeverity'], '-'));
            const ppmGroupId = _woTask['ppmGroupId'];
            $('#pMwePpmGroup').text(ppmGroupId !== '' ? refPpmGroup[ppmGroupId]['ppmGroupName'] : '-');
            const currentTechnician = _woTask['woTaskAssignedTo'];
            $('#pMweExecutor').text(currentTechnician !== '' ? refUser[currentTechnician]['userFirstName'] : '-');
            $('#pMweStatus').text(refStatus[_woTask['woTaskStatus']]['statusDesc']);
            mzSetFieldValue('MweType', _woTask['woTaskType'], 'select');
            mzSetFieldValue('MweAssetNo', _woTask['assetNo'], 'text');
            mzSetFieldValue('MweComplaint', _woTask['woTaskComplaint'], 'textarea');
            mzDisableSelect('optMweType', _woTask['woTaskType'] === '2' || _woTask['woTaskType'] === '6');
            $('#h4MweTitle').html('<i class="fas fa-file-edit text-white"></i> &nbsp;Edit WO Information');
            $('#modal_wo_edit').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            HideLoader();
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); HideLoader(); }}, 200);
    };
    
    this.getClassName = function () {
        return className;
    };
    
    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
    
    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };
    
    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
    
    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };
}