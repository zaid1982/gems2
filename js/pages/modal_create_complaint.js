function ModalCreateComplaint() {

    const className = 'ModalCreateComplaint';
    let self = this;
    let ppmGroupId = '';
    let rowRefresh = '';
    let classFrom;
    let siteId;
    let refUser;
    let refSite;
    let refPpmGroup;
    let formValidate;
    let arrPpmGroupUser;

    this.init = function () {
        $('.divMccHideInitial').hide();
        const arrSeverity = mzAjaxRequest('wo.php?type=severity_list_by_site&siteId='+siteId, 'GET');
        mzOption('optMccCreatedBy', refUser, 'Select Complainer *', 'userId', 'userFirstName', {userStatus: '1', siteId: siteId}, 'required');
        mzOption('optMccSeverity', arrSeverity, 'Select Severity *', 'severityId', 'severityName', {}, 'required');
        mzOption('optMccPpmGroupId', refPpmGroup, 'Select Executor Group *', 'ppmGroupId', 'ppmGroupName', {ppmGroupStatus: '1', siteId: siteId, roleId:'8'}, 'required');

        $('#optMccPpmGroupId').on('change', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    arrPpmGroupUser = mzAjaxRequest('wo.php?type=ppm_group_user_list&ppmGroupId='+$('#optMccPpmGroupId').val(), 'GET');
                    mzOptionStop('optMccAssignedTo', arrPpmGroupUser, 'Select Executor *', 'userId', 'userFirstName', {}, 'required');
                    $('#divMccExecutor').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#optMccAssignedTo').on('change', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const arrAssistant = arrPpmGroupUser;
                    mzOptionStop('optMccAssist', arrAssistant, 'Select Technician Assistant', 'userId', 'userFirstName');
                    $('#divMccExecutor').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        const vData = [
            {
                field_id: 'optMccCreatedBy',
                type: 'select',
                name: 'Complainer',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txaMccComplaint',
                type: 'text',
                name: 'Complaint Details',
                validator: {
                    notEmpty: true,
                    maxLength: 1000
                }
            },
            {
                field_id: 'optMccType',
                type: 'select',
                name: 'Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMccSeverity',
                type: 'select',
                name: 'Severity',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMccPpmGroupId',
                type: 'select',
                name: 'Executor Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMccAssignedTo',
                type: 'select',
                name: 'Executor',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMccAssist',
                type: 'select',
                name: 'Technician Assistant',
                validator: {
                }
            }
        ];

        formValidate = new MzValidate('formMcc');
        formValidate.registerFields(vData);

        $('#modal_create_complaint').on('hidden.bs.modal', function(){
            formValidate.clearValidation();
            $('.divMccHideInitial').hide();
        });

        $('#btnMccSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const txtName = $('#txtMccName').val();
                        const data = {
                            siteId: siteId,
                            roleId: roleId,
                            ppmGroupName: txtName,
                            reportTo: reportTo
                        };

                        //mzAjaxRequest('ppm_group.php', 'POST', data);
                        //if (classFrom.getClassName() === 'MainPpmGroup') {
                        //    classFrom.genTableHdkWo();
                        //}
                        $('#modal_create_complaint').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.add = function () {
        ppmGroupId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                $('.divMccView').hide();
                mzSetFieldValue('MccSiteName', refSite[siteId]['siteName'], 'text');

                /*const refPpmGroup = mzGetLocalArray('gems_ppmGroup', versionLocal, 'ppmGroupId', [], 'ppm_group');
                mzOptionStop('optMccReportTo', refPpmGroup, defaultText, 'ppmGroupId', 'ppmGroupName', {roleId:roleCur, siteId:siteId, ppmGroupStatus: '1'}, 'required');

                const clientId = refSite[siteId]['clientId'];
                mzSetFieldValue('MccSite', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('MccRole', refUser[roleId]['roleDesc'], 'text');*/

                $('#modal_create_complaint').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setSiteId = function (_siteId) {
        siteId = _siteId;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
}