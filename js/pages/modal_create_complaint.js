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
    let oTableCurrentTask;

    function rowsFromRef(ref, idKey, labelKey, predicate) {
        const rows = [];
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            if (predicate && !predicate(row)) {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''), 'en', {numeric: true});
        });
        return rows;
    }

    function userHasRole(row, roleCur) {
        const roles = row['roles'];
        if (roles === null || roles === undefined || roles === '') {
            return false;
        }
        const parts = String(roles).split(',');
        return jQuery.inArray(String(roleCur), parts) >= 0;
    }

    this.init = function () {
        $('.divMccHideInitial').hide();
        const arrSeverity = mzAjaxRequest('wo.php?type=severity_list_by_site&siteId=' + siteId, 'GET');

        GemsUI.fillSelect(
            'optMccCreatedBy',
            rowsFromRef(refUser, 'userId', 'userFirstName', function (row) {
                if (String(row['userStatus']) !== '1') {
                    return false;
                }
                if (String(row['siteId']) !== String(siteId)) {
                    return false;
                }
                return userHasRole(row, '6');
            }),
            'userId',
            function (row) { return row['userFirstName'] || ''; },
            'Select Complainer'
        );
        GemsUI.fillSelect(
            'optMccSeverity',
            rowsFromRef(arrSeverity, 'severityId', 'severityName'),
            'severityId',
            function (row) { return row['severityName'] || ''; },
            'Select Severity'
        );
        GemsUI.fillSelect(
            'optMccPpmGroupId',
            rowsFromRef(refPpmGroup, 'ppmGroupId', 'ppmGroupName', function (row) {
                if (String(row['ppmGroupStatus']) !== '1') {
                    return false;
                }
                if (String(row['siteId']) !== String(siteId)) {
                    return false;
                }
                return String(row['roleId']) === '8';
            }),
            'ppmGroupId',
            function (row) { return row['ppmGroupName'] || ''; },
            'Select Executor Group'
        );

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
                field_id: 'txtMccLocation',
                type: 'text',
                name: 'Location',
                validator: {
                    notEmpty: true,
                    maxLength: 150
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
            },
            {
                field_id: 'txfMccImage1',
                type: 'file',
                name: 'Image 1',
                validator: {
                    notEmptyFile: true
                }
            },
            {
                field_id: 'txfMccImage2',
                type: 'file',
                name: 'Image 2',
                validator: {
                }
            },
            {
                field_id: 'txfMccImage3',
                type: 'file',
                name: 'Image 3',
                validator: {
                }
            },
            {
                field_id: 'txaMccImgDesc1',
                type: 'text',
                name: 'Image Description',
                validator: {
                    notEmpty: true,
                    maxLength: 500
                }
            },
            {
                field_id: 'txaMccImgDesc2',
                type: 'text',
                name: 'Image Description',
                validator: {
                    notEmpty: true,
                    maxLength: 500
                }
            },
            {
                field_id: 'txaMccImgDesc3',
                type: 'text',
                name: 'Image Description',
                validator: {
                    notEmpty: true,
                    maxLength: 500
                }
            }
        ];

        formValidate = new MzValidate('formMcc');
        formValidate.registerFields(vData);

        $('#modal_create_complaint').on('hidden.bs.modal', function () {
            formValidate.clearValidation();
            $('.divMccHideInitial').hide();
            $('#imgMccImage1, #imgMccImage2, #imgMccImage3').attr('src', 'img/background/upload_placeholder.png');
            $('#lblMccFile1, #lblMccFile2, #lblMccFile3').text('No file chosen');
        });

        $('#optMccPpmGroupId').on('change', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    arrPpmGroupUser = mzAjaxRequest('wo.php?type=ppm_group_user_list&ppmGroupId=' + $('#optMccPpmGroupId').val(), 'GET');
                    GemsUI.fillSelect(
                        'optMccAssignedTo',
                        rowsFromRef(arrPpmGroupUser, 'userId', 'userFirstName'),
                        'userId',
                        function (row) { return row['userFirstName'] || ''; },
                        'Select Executor'
                    );
                    $('#divMccExecutor').show();
                    $('#divMccAssist, .divMccExecutorDetails').hide();
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
                    const assignedTo = $('#optMccAssignedTo').val();
                    let arrAssistant = [];
                    for (let i = 0; i < arrPpmGroupUser.length; i++) {
                        if (arrPpmGroupUser[i]['userId'] !== assignedTo) {
                            arrAssistant.push(arrPpmGroupUser[i]);
                        }
                    }
                    GemsUI.fillSelect(
                        'optMccAssist',
                        rowsFromRef(arrAssistant, 'userId', 'userFirstName'),
                        'userId',
                        function (row) { return row['userFirstName'] || ''; },
                        null
                    );
                    mzSetFieldValue('MccUserName', refUser[assignedTo]['userFirstName'], 'text');
                    mzSetFieldValue('MccUserContactNo', refUser[assignedTo]['userContactNo'], 'text');
                    mzSetFieldValue('MccUserEmail', refUser[assignedTo]['userEmail'], 'text');

                    const dataResult = mzAjaxRequest('wo.php?type=technician_current_task&userId=' + $('#optMccAssignedTo').val(), 'GET');
                    oTableCurrentTask.clear().rows.add(dataResult).draw();
                    $('#divMccAssist, .divMccExecutorDetails').show();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#txfMccImage1').on('change', function () {
            $('#imgMccImage1').attr('src', 'img/background/upload_placeholder.png');
            mzDisplayImageFileInput(this, 'imgMccImage1');
            $('#lblMccFile1').text(this.files && this.files[0] ? this.files[0].name : 'No file chosen');
            if ($('#txfMccImage1').val() !== '') {
                formValidate.enableField('txaMccImgDesc1');
                $('#divMccImgDesc1').show();
            } else {
                formValidate.disableField('txaMccImgDesc1');
                $('#txaMccImgDesc1').val('').removeClass('invalid is-invalid');
                $('#lblMccImgDesc1').removeClass('active');
                $('#txaMccImgDesc1Err').html('');
                $('#divMccImgDesc1').hide();
                $('#lblMccFile1').text('No file chosen');
            }
        });

        $('#txfMccImage2').on('change', function () {
            $('#imgMccImage2').attr('src', 'img/background/upload_placeholder.png');
            mzDisplayImageFileInput(this, 'imgMccImage2');
            $('#lblMccFile2').text(this.files && this.files[0] ? this.files[0].name : 'No file chosen');
            if ($('#txfMccImage2').val() !== '') {
                formValidate.enableField('txaMccImgDesc2');
                $('#divMccImgDesc2').show();
            } else {
                formValidate.disableField('txaMccImgDesc2');
                $('#txaMccImgDesc2').val('').removeClass('invalid is-invalid');
                $('#lblMccImgDesc2').removeClass('active');
                $('#txaMccImgDesc2Err').html('');
                $('#divMccImgDesc2').hide();
                $('#lblMccFile2').text('No file chosen');
            }
        });

        $('#txfMccImage3').on('change', function () {
            $('#imgMccImage3').attr('src', 'img/background/upload_placeholder.png');
            mzDisplayImageFileInput(this, 'imgMccImage3');
            $('#lblMccFile3').text(this.files && this.files[0] ? this.files[0].name : 'No file chosen');
            if ($('#txfMccImage3').val() !== '') {
                formValidate.enableField('txaMccImgDesc3');
                $('#divMccImgDesc3').show();
            } else {
                formValidate.disableField('txaMccImgDesc3');
                $('#txaMccImgDesc3').val('').removeClass('invalid is-invalid');
                $('#lblMccImgDesc3').removeClass('active');
                $('#txaMccImgDesc3Err').html('');
                $('#divMccImgDesc3').hide();
                $('#lblMccFile3').text('No file chosen');
            }
        });

        document.getElementById('txfMccImage1').addEventListener('change', mzHandleFileSelect, false);
        document.getElementById('txfMccImage2').addEventListener('change', mzHandleFileSelect, false);
        document.getElementById('txfMccImage3').addEventListener('change', mzHandleFileSelect, false);

        $('#btnMccSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        let fileUpload = [];
                        if ($('#txfMccImage1').val() !== '') {
                            fileUpload.push({
                                name: 'Complaint Image',
                                filename: $('#txfMccImage1').prop('files')[0].name,
                                size: $('#txfMccImage1').prop('files')[0].size,
                                type: $('#txfMccImage1').prop('files')[0].type,
                                data: $('#txfMccImage1Blob').val(),
                                description: $('#txaMccImgDesc1').val()
                            });
                        }
                        if ($('#txfMccImage2').val() !== '') {
                            fileUpload.push({
                                name: 'Complaint Image',
                                filename: $('#txfMccImage2').prop('files')[0].name,
                                size: $('#txfMccImage2').prop('files')[0].size,
                                type: $('#txfMccImage2').prop('files')[0].type,
                                data: $('#txfMccImage2Blob').val(),
                                description: $('#txaMccImgDesc2').val()
                            });
                        }
                        if ($('#txfMccImage3').val() !== '') {
                            fileUpload.push({
                                name: 'Complaint Image',
                                filename: $('#txfMccImage3').prop('files')[0].name,
                                size: $('#txfMccImage3').prop('files')[0].size,
                                type: $('#txfMccImage3').prop('files')[0].type,
                                data: $('#txfMccImage3Blob').val(),
                                description: $('#txaMccImgDesc3').val()
                            });
                        }
                        const data = {
                            action: 'submit_helpdesk_complaint',
                            siteId: siteId,
                            createdBy: $('#optMccCreatedBy').val(),
                            location: $('#txtMccLocation').val(),
                            complaint: $('#txaMccComplaint').val(),
                            complaintImages: fileUpload,
                            taskType: $('#optMccType').val(),
                            severity: $('#optMccSeverity').val(),
                            ppmGroupId: $('#optMccPpmGroupId').val(),
                            assignedTo: $('#optMccAssignedTo').val(),
                            taskAssist: $('#optMccAssist').val()
                        };
                        console.log(data);
                        mzAjaxRequest('wo.php', 'POST', data);
                        if (classFrom.getClassName() === 'MainHelpdesk') {
                            classFrom.genTableHdkWo();
                        }
                        $('#modal_create_complaint').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableCurrentTask = $('#dtMccCurrentTask').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            ordering: false,
            bPaginate: false,
            language: GemsUI.dtEmpty('fa-tasks', 'No current tasks.'),
            dom: "<'d-none'f>r<'table-responsive't>",
            aaSorting: [2, 'asc'],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                $('td', nRow).eq(0).html(iDisplayIndex + 1);
            },
            aoColumns: [
                {mData: null, sClass: 'text-center'},
                {mData: 'woTaskNo', sClass: 'text-center'},
                {mData: 'dateReceived', sClass: 'text-center'}
            ]
        });
        $('#dtMccCurrentTask_filter').hide();
    };

    this.add = function () {
        ppmGroupId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                $('.divMccView').hide();
                mzSetFieldValue('MccSiteName', refSite[siteId]['siteName'], 'text');
                $('#modal_create_complaint').modal({backdrop: 'static', keyboard: false});
                formValidate.disableField('txaMccImgDesc1');
                formValidate.disableField('txaMccImgDesc2');
                formValidate.disableField('txaMccImgDesc3');
                $('#divMccImgDesc1, #divMccImgDesc2, #divMccImgDesc3').hide();
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
