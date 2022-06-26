function ModalFcaRecommend () {

    const className = 'ModalFcaRecommend';
    let self = this;
    let formValidate;
    let classFrom;
    let fcaTaskId;
    let fcaTask;

    this.init = function () {
        const vData = [
            {
                 field_id: 'txtMfrAssetNo',
                 type: 'text',
                 name: 'Asset No.',
                 validator: {
                     notEmpty: false,
                     maxLength: 50
                 }
             },
            {
                field_id: 'radMfrCondition',
                type: 'radio',
                name: 'Condition Scale',
                validator: {
                    notEmptyCheck: true
                }
            },
            {
                field_id: 'radMfrEvaluationType',
                type: 'radio',
                name: 'Evaluation Type',
                validator: {
                    notEmptyCheck: true
                }
            },
            {
                field_id: 'txaMfrRecommendation',
                type: 'text',
                name: 'Observation',
                validator: {
                    notEmpty: true,
                    maxLength: 2000
                }
            }
        ];

        formValidate = new MzValidate('formMfr');
        formValidate.registerFields(vData);

        $('#btnMfrSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            fcaTaskAssetNo: $('#txtMfrAssetNo').val(),
                            fcaTaskConditionScale: $("input[name='radMfrCondition']:checked").val(),
                            fcaTaskEvaluationType: $("input[name='radMfrEvaluationType']:checked").val(),
                            fcaTaskRecommendation: $('#txaMfrRecommendation').val()
                        };
                        mzAjaxRequest2('fca_task/recommend/'+fcaTaskId, 'POST', data);
                        classFrom.setIsUpdated(true);
                        classFrom.displayInfo(false, false, true);
                        $('#modal_fca_recommend').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.load = function (_fcaTaskId, _fcaTask) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaTaskId]);
                formValidate.clearValidation();
                fcaTaskId = _fcaTaskId;
                fcaTask = _fcaTask;
                mzSetFieldValue('MfrAssetNo', fcaTask['fcaTaskAssetNo'], 'text');
                mzSetFieldValue('MfrCondition', fcaTask['fcaTaskConditionScale'], 'radio');
                mzSetFieldValue('MfrEvaluationType', fcaTask['fcaTaskEvaluationType'], 'radio');
                mzSetFieldValue('MfrRecommendation', fcaTask['fcaTaskRecommendation'], 'textarea');
                $('#modal_fca_recommend').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}