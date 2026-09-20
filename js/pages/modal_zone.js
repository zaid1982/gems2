function ModalZone() {

    const className = 'ModalZone';
    let self = this;
    let formValidate;
    let classFrom;
    let refSite;
    let zoneId;
    let modalConfirmDeleteClass;
    let qrCodeImg;

    function siteRows(activeOnly) {
        const rows = [];
        $.each(refSite, function (key, site) {
            if (!site || typeof site !== 'object') {
                return true;
            }
            const row = $.extend({}, site);
            if (!row['siteId']) {
                row['siteId'] = key;
            }
            if (!row['siteId'] && !row['siteName']) {
                return true;
            }
            if (activeOnly && String(row['siteStatus']) !== '1') {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['siteName'] || '').localeCompare(b['siteName'] || '');
        });
        return rows;
    }

    function fillSiteSelect(activeOnly, selected) {
        GemsUI.fillSelect(
            'optMznSite',
            siteRows(activeOnly),
            'siteId',
            function (row) {
                return row['siteName'] || '';
            },
            'Choose Site',
            selected
        );
    }

    function setSiteSelectDisabled(disabled) {
        $('#optMznSite').prop('disabled', !!disabled);
    }

    function syncPrimaryButtons() {
        const valid = formValidate.validateForm();
        $('#btnMznSubmit').attr('disabled', !valid);
        $('#btnMznSave').attr('disabled', !valid);
    }

    this.init = function () {
        const qrContainer = document.getElementById('divMznQrCodeImg');
        if (qrContainer) {
            qrCodeImg = new QRCode(qrContainer, {});
        }

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

        $('#formMzn').on('keyup change', function () {
            syncPrimaryButtons();
        });

        $('#modal_zone').on('hidden.bs.modal', function () {
            formValidate.clearValidation();
            $('#btnMznSubmit').attr('disabled', true);
            $('#btnMznSave').attr('disabled', true);
            setSiteSelectDisabled(false);
        });

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
                        mzAjaxRequest2('zone/' + zoneId, 'PUT', data);
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
                fillSiteSelect(true);
                setSiteSelectDisabled(false);
                $('#btnMznSubmit').show();
                $('#btnMznDelete, #btnMznSave, .divMznQr').hide();
                $('#h4MznTitle').html('<i class="fas fa-plus me-2"></i>Add Zone');
                syncPrimaryButtons();
                $('#modal_zone').modal({backdrop: 'static', keyboard: false});
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
                const data = mzAjaxRequest2('zone/' + zoneId, 'GET');
                const qrLink = classFrom.getUrlLinkBase() + zoneId;
                fillSiteSelect(false, data['siteId']);
                mzSetFieldValue('MznType', data['zoneType'], 'text');
                mzSetFieldValue('MznName', data['zoneName'], 'text');
                mzSetFieldValue('MznCode', data['zoneCode'], 'text');
                mzSetFieldValue('MznLink', qrLink, 'text');
                mzSetFieldValue('MznStatus', data['zoneStatus'], 'radio');
                setSiteSelectDisabled(true);
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

                $('#btnMznSubmit').hide();
                $('#btnMznDelete, #btnMznSave, .divMznQr').show();
                $('#h4MznTitle').html('<i class="far fa-edit me-2"></i>Edit Zone');
                syncPrimaryButtons();
                $('#modal_zone').modal({backdrop: 'static', keyboard: false});
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
                mzAjaxRequest2('zone/' + _zoneId, 'DELETE');
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
