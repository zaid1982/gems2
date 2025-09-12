function ModalZone () {

    const className = 'ModalZone';
    let self = this;
    let formValidate;
    let classFrom;
    let refSite;
    let zoneId;
    let modalConfirmDeleteClass;
    let qrCodeImg;

    this.init = function () {
    qrCodeImg = new QRCode(document.getElementById("divMznQrCodeImg"), { });
        
        mzOptionV2('optMznSite', refSite, 'Select Site *', 'siteName', {siteStatus: 1}, 'required');

        const vData = [
            {
                field_id: 'optMznSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMznType',
                type: 'text',
                name: 'Zone Type',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtMznCode',
                type: 'text',
                name: 'Zone Code',
                validator: {
                    notEmpty: true,
                    maxLength: 20
                }
            },
            {
                field_id: 'txtMznName',
                type: 'text',
                name: 'Zone Name',
                validator: {
                    notEmpty: true,
                    maxLength: 200
                }
            },
            {
                field_id: 'radMznStatus',
                type: 'radio',
                name: 'Status',
                validator: {
                    notEmptyCheck: true
                }
            }
        ];

        formValidate = new MzValidate('formMzn');
        formValidate.registerFields(vData);

        $('#btnMznSubmit').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            siteId: parseInt($('#optMznSite').val()),
                            zoneType: $('#txtMznType').val(),
                            zoneName: $('#txtMznName').val(),
                            zoneCode: $('#txtMznCode').val(),
                            zoneStatus: $("input[name='radMznStatus']:checked").val()
                        };
                        mzAjaxRequest2('zone', 'POST', data);
                        classFrom.genTable();
                        $('#modal_zone').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMznSave').on('click', function () {
            if (!formValidate.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
            } else {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const data = {
                            siteId: parseInt($('#optMznSite').val()),
                            zoneType: $('#txtMznType').val(),
                            zoneName: $('#txtMznName').val(),
                            zoneCode: $('#txtMznCode').val(),
                            zoneStatus: $("input[name='radMznStatus']:checked").val()
                        };
                        mzAjaxRequest2('zone/'+zoneId, 'PUT', data);
                        classFrom.genTable();
                        $('#modal_zone').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });

        $('#btnMznDelete').on('click', function () {
            try {
                $('#modal_zone').modal('hide');
                modalConfirmDeleteClass.delete(zoneId, self);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                formValidate.clearValidation();
                mzDisableSelect('optMznSite', false);
                $('#btnMznSubmit').show();
                $('#btnMznDelete, #btnMznSave, .divMznQr').hide();
                $('#h4MznTitle').html('<i class="fas fa-plus text-white"></i> &nbsp;Add New FCA Zone');
                $('#modal_zone').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_zoneId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_zoneId]);
                zoneId = _zoneId;
                formValidate.clearValidation();
                const data = mzAjaxRequest2('zone/'+zoneId, 'GET');
                const qrLink = classFrom.getUrlLinkBase() + zoneId; // already full
                const basePath = (typeof classFrom.getBaseAppPath === 'function') ? classFrom.getBaseAppPath() : '';
                // PTW link/QR removed from Zone modal (now handled at Site level)
                mzSetFieldValue('MznSite', data['siteId'], 'select');
                mzSetFieldValue('MznType', data['zoneType'], 'text');
                mzSetFieldValue('MznName', data['zoneName'], 'text');
                mzSetFieldValue('MznCode', data['zoneCode'], 'text');
                mzSetFieldValue('MznLink', qrLink, 'text');
                // Removed PTW link field population
                mzSetFieldValue('MznStatus', data['zoneStatus'], 'radio');
                mzDisableSelect('optMznSite', true);
                // Generate / regenerate Complaint QR
                if (!qrCodeImg) {
                    const complaintQrContainer = document.getElementById('divMznQrCodeImg');
                    if (complaintQrContainer) {
                        qrCodeImg = new QRCode(complaintQrContainer, {});
                    }
                } else if (typeof qrCodeImg.clear === 'function') {
                    qrCodeImg.clear();
                }
                if (qrCodeImg) {
                    qrCodeImg.makeCode(qrLink);
                }

                // Removed PTW QR generation
                $('#btnMznSubmit').hide();
                $('#btnMznDelete, #btnMznSave, .divMznQr').show();
                $('#h4MznTitle').html('<i class="fas fa-edit text-white"></i> &nbsp;Edit FCA Zone');
                $('#modal_zone').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_zoneId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_zoneId]);
                mzAjaxRequest2('zone/'+_zoneId, 'DELETE');
                classFrom.genTable();
                $('#modal_zone').modal('hide');
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

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}