function ModalWoReassign () {
    
    const className = 'ModalZone';
    let self = this;
    let formValidate;
    let classFrom;
    let refUser;
    let refPpmGroup;
    let oTableCurrentTask;
    let woTaskId;
    let currentTechnician;
    
    const vData = [
        {
            field_id: 'optMwrPpmGroup',
            type: 'select',
            name: 'Executor Group',
            validator: {
                notEmpty: true
            }
        },
        {
            field_id: 'optMwrAssignedTo',
            type: 'select',
            name: 'New Executor',
            validator: {
                notEmpty: true
            }
        },
    ];
    
    this.init = function () {
        formValidate = new MzValidate('formMzn');
        formValidate.registerFields(vData);
        
        $('#optMwrPpmGroup').on('change', function () {
            const ppmGroupId = $(this).val();
            ShowLoader(); setTimeout(function () { try {
                const arrPpmGroupUser = mzAjaxRequest('wo.php?type=ppm_group_user_list&ppmGroupId='+ppmGroupId, 'GET');
                mzOptionStop('optMwrAssignedTo', arrPpmGroupUser, 'Select Executor', 'userId', 'userFirstName', {}, 'required');
                mzDisableSelect('optMwrAssignedTo', false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader(); }, 200);
        });
        
        $('#optMwrAssignedTo').on('change', function () {
            const assignedTo = parseInt($(this).val());
            ShowLoader(); setTimeout(function () { try {
                mzSetFieldValue('MwrUserName', refUser[assignedTo]['userFirstName'], 'text');
                mzSetFieldValue('MwrUserContactNo', refUser[assignedTo]['userContactNo'], 'text');
                mzSetFieldValue('MwrUserEmail', refUser[assignedTo]['userEmail'], 'text');
                const dataResult = mzAjaxRequest('wo.php?type=technician_current_task&userId='+assignedTo, 'GET');
                oTableCurrentTask.clear().rows.add(dataResult).draw();
                $('.divMwrExecutorDetails').show();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
            HideLoader(); }, 200);
        });
        
        $('#btnMwrSubmit').on('click', function () {
            try {
                if (!formValidate.validateNow()) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    const assignedTo = mzNullInt('optMwrAssignedTo');
                    if (assignedTo === currentTechnician) {
                        throw new Error('The selected executor already assigned to current task. Please select another executor!');
                    }
                    const data = {
                        ppmGroupId: mzNullInt('optMwrPpmGroup'),
                        woTaskAssignedTo: assignedTo
                    };
                    ShowLoader(); setTimeout(function () {
                        mzFetch('wo_v3/reassign/'+woTaskId, 'PUT', data).then(res => {
                            classFrom.genTableHmeDataWo(woTaskId);
                            $('#modal_wo_reassign').modal('hide');
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });
        
        oTableCurrentTask =  $('#dtMwrCurrentTask').DataTable({
            bLengthChange: false,
            bFilter: false,
            bInfo: false,
            ordering: false,
            bPaginate: true,
            language: _DATATABLE_LANGUAGE,
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                $('td', nRow).eq(0).html(iDisplayIndex + 1);
            },
            aoColumns: [
                { mData: null, sClass: 'text-center'},
                { mData: 'woTaskNo', sClass: 'text-center'},
                { mData: 'dateReceived', sClass: 'text-center'}
            ]
        });
    };
    
    this.load = function (_woTaskId) {
        ShowLoader(); setTimeout(function () { try {
            mzCheckFuncParam([_woTaskId]);
            formValidate.clearValidation();
            woTaskId = _woTaskId;
            mzFetch('wo_v3/'+woTaskId).then(woTask => {
                if (woTask['woTaskStatus'] !== 13) {
                    throw new Error('Reassign Work Order only allowed in Execution Checkpoint. Please refresh list and try again!');
                }
                currentTechnician = woTask['woTaskAssignedTo'];
                $('#pMwrPpmGroup').text(refPpmGroup[woTask['ppmGroupId']]['ppmGroupName']);
                $('#pMwrExecutor').text(refUser[currentTechnician]['userFirstName']);
                mzOptionStop('optMwrPpmGroup', refPpmGroup, 'Select Executor Group', 'ppmGroupId', 'ppmGroupName', {ppmGroupStatus: '1', siteId: woTask['siteId'].toString(), roleId:'8'}, 'required');
                mzOptionStopClear('optMwrAssignedTo', 'Select New Executor', 'required');
                $('#h4MwrTitle').html('<i class="fas fa-user-pen text-white"></i> &nbsp;Reassign New Executor');
                $('#modal_wo_reassign').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }}, 200);
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
}