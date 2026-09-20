function ModalFcaReport () {
    
    const className = 'ModalFcaZone';
    let self = this;
    let formValidate;
    let classFrom;
    let refSite;
    let refAssetGroup;

    this.init = function () {
        mzDateFromTo('txtMfrDateFrom', 'txtMfrDateTo');
        const siteRows = [];
        $.each(refSite, function (id, row) {
            if (row && parseInt(row.siteStatus, 10) === 1) {
                const item = Object.assign({}, row);
                if (item.siteId == null) { item.siteId = id; }
                siteRows.push(item);
            }
        });
        GemsUI.fillSelect('optMfrSite', siteRows, 'siteId', function (r) { return r.siteName; }, 'Select Site *');

        const groupRows = [];
        $.each(refAssetGroup, function (id, row) {
            if (row && parseInt(row.assetGroupStatus, 10) === 1) {
                const item = Object.assign({}, row);
                if (item.assetGroupId == null) { item.assetGroupId = id; }
                groupRows.push(item);
            }
        });
        GemsUI.fillSelect('optMfrAssetGroup', groupRows, 'assetGroupId', function (r) { return r.assetGroupName; }, 'Select Asset Group');

        const vData = [
            {
                field_id: 'optMfrSite',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMfrName',
                type: 'text',
                name: 'Zone Name',
                validator: {
                    notEmpty: true,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtMfrDateFrom',
                type: 'text',
                name: 'Date From',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMfrDateTo',
                type: 'text',
                name: 'Date To',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMfrAssetGroup',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'optMfrSort',
                type: 'select',
                name: 'Sort By',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'radMfrExclude',
                type: 'radio',
                name: 'Action',
                validator: {
                    notEmptyCheck: true
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
                            fcaReportName: $('#txtMfrName').val(),
                            fcaReportDateFrom: mzConvertDate($('#txtMfrDateFrom').val()),
                            fcaReportDateTo: mzConvertDate($('#txtMfrDateTo').val()),
                            siteId: $('#optMfrSite').val(),
                            assetGroupId: $('#optMfrAssetGroup').val(),
                            fcaReportExcludeList: $("input[name='radMfrExclude']:checked").val(),
                            fcaReportSortBy: $('#optMfrSort').val()
                        };
                        mzAjaxRequest2('fca_report', 'POST', data);
                        classFrom.genTable();
                        $('#modal_fca_report').modal('hide');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
    };

    this.add = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                formValidate.clearValidation();
                mzDateFromToReset('txtMfrDateFrom', 'txtMfrDateTo');
                $('#modal_fca_report').modal({backdrop: 'static', keyboard: false}).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_fcaReportId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_fcaReportId]);
                mzAjaxRequest2('fca_report/'+_fcaReportId, 'DELETE');
                classFrom.genTable();
                $('#modal_fca_report').modal('hide');
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

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };
}
