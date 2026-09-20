/**
 * section_asset_details.js — Tabler Asset Details (P5-4 Commit 6).
 *
 * Shared by pages/asset.html and pages/ppm_management.html.
 * The _tabler fork was collapsed after both consumers were Tabler-verified.
 *
 * function SectionAssetDetails() and className stay unchanged so
 * modal_confirm_delete.js case 'SectionAssetDetails' still works.
 *
 * Dates: native <input type="date">. Read lifespan/disposal via
 * dateInputOrNull (NOT mzConvertDate2 — that returns null for YYYY-MM-DD).
 * Warranty still uses mzConvertDate, which already accepts YYYY-MM-DD.
 * DataTables: GemsUI.dtDomButtons / dtButtons (no copy button, same as
 * Contract/User). Status cells use GemsUI.badge, not DB statusColor.
 */
function SectionAssetDetails() {

    const className = 'SectionAssetDetails';
    let self = this;
    let assetId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refAssetBrand;
    let refAssetModel;
    let refUser;
    let refContract;
    let refSite;
    let refClient;
    let refPpmGroup;
    let refZone;
    let refSeverity;
    let formValidate;
    let formValidate2;
    let formValidateLifespan;
    let formValidateValue;
    let versionLocal;
    let qrCodeImg;
    let oTableSszWo;
    let oTableSszPpm;
    let assetStatus;
    let refWoType = {
        1: 'Client Complaint',
        2: 'Self Finding',
        3: 'Request',
        4: 'Breakdown',
        5: 'Defect',
        6: 'Public Complaint'
    };
    let cntLifeCycleCost = 0;
    let cntMeanTime = 0;
    let contractId;
    let woTaskHistory;
    let lifespanStartDate;


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

    function dateInputOrNull(id) {
        const value = $('#' + id).val();
        if (value === undefined || value === null || String(value).trim() === '') {
            return null;
        }
        return String(value).trim();
    }

    function setDateInput(id, value) {
        if (value === undefined || value === null || value === '') {
            $('#' + id).val('');
            return;
        }
        $('#' + id).val(String(value).substr(0, 10).replace(/\//g, '-'));
    }

    function setSelectDisabled(id, disabled) {
        $('#' + id).prop('disabled', !!disabled);
    }

    function statusBadgeKind(statusId) {
        switch (String(statusId)) {
            case '1':
                return 'success';
            case '2':
                return 'secondary';
            case '5':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function statusBadge(statusId) {
        const label = (refStatus && refStatus[statusId] && refStatus[statusId]['statusDesc'])
            ? refStatus[statusId]['statusDesc']
            : 'Unknown';
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
    }

    function fillGroupSelect(selected) {
        GemsUI.fillSelect(
            'optSszAssetGroupId',
            rowsFromRef(refAssetGroup, 'assetGroupId', 'assetGroupName', function (row) {
                return String(row['assetGroupStatus']) === '1';
            }),
            'assetGroupId',
            function (row) { return row['assetGroupName'] || ''; },
            'Choose Asset Group',
            selected
        );
    }

    function fillCategorySelect(groupId, selected) {
        const rows = groupId
            ? rowsFromRef(refAssetCategory, 'assetCategoryId', 'assetCategoryName', function (row) {
                return String(row['assetGroupId']) === String(groupId) && String(row['assetCategoryStatus']) === '1';
            })
            : [];
        GemsUI.fillSelect(
            'optSszAssetCategoryId',
            rows,
            'assetCategoryId',
            function (row) { return row['assetCategoryName'] || ''; },
            'Choose Asset Category',
            selected
        );
    }

    function fillTypeSelect(categoryId, selected) {
        const rows = categoryId
            ? rowsFromRef(refAssetType, 'assetTypeId', 'assetTypeName', function (row) {
                return String(row['assetCategoryId']) === String(categoryId) && String(row['assetTypeStatus']) === '1';
            })
            : [];
        GemsUI.fillSelect(
            'optSszAssetTypeId',
            rows,
            'assetTypeId',
            function (row) { return row['assetTypeName'] || ''; },
            'Choose Asset Type',
            selected
        );
    }

    function fillBrandSelect(typeId, selected) {
        let rows = [];
        if (typeId) {
            const refAssetBrandGroup = mzGetLocalArray('gems_assetBrandGroup', versionLocal, 'assetBrandId', {assetTypeId: typeId});
            rows = rowsFromRef(refAssetBrandGroup, 'assetBrandId', 'assetBrandName', function (row) {
                return String(row['assetBrandStatus']) === '1';
            });
        }
        GemsUI.fillSelect(
            'optSszAssetBrandId',
            rows,
            'assetBrandId',
            function (row) { return row['assetBrandName'] || ''; },
            'Choose Asset Brand',
            selected
        );
    }

    function fillModelSelect(brandId, typeId, selected) {
        const rows = (brandId && typeId)
            ? rowsFromRef(refAssetModel, 'assetModelId', 'assetModelName', function (row) {
                return String(row['assetBrandId']) === String(brandId)
                    && String(row['assetTypeId']) === String(typeId)
                    && String(row['assetModelStatus']) === '1';
            })
            : [];
        GemsUI.fillSelect(
            'optSszAssetModelId',
            rows,
            'assetModelId',
            function (row) { return row['assetModelName'] || ''; },
            'Choose Asset Model',
            selected
        );
    }

    function fillPpmGroupSelect(siteId, selected) {
        const rows = siteId
            ? rowsFromRef(refPpmGroup, 'ppmGroupId', 'ppmGroupName', function (row) {
                return String(row['siteId']) === String(siteId)
                    && String(row['roleId']) === '5'
                    && String(row['ppmGroupStatus']) === '1';
            })
            : [];
        GemsUI.fillSelect(
            'optSszPpmGroupId',
            rows,
            'ppmGroupId',
            function (row) { return row['ppmGroupName'] || ''; },
            'Choose PPM Group',
            selected
        );
    }

    function fillZoneSelect(siteId, selected) {
        const rows = [];
        const siteKey = parseInt(siteId, 10);
        $.each(refZone || {}, function (key, zone) {
            if (!zone || typeof zone !== 'object') {
                return true;
            }
            const row = $.extend({}, zone);
            row['zoneId'] = key;
            if (parseInt(row['siteId'], 10) !== siteKey) {
                return true;
            }
            if (parseInt(row['zoneStatus'], 10) !== 1) {
                return true;
            }
            rows.push(row);
            return true;
        });
        rows.sort(function (a, b) {
            return String(a['zoneCode'] || '').localeCompare(String(b['zoneCode'] || ''), 'en', {numeric: true});
        });
        GemsUI.fillSelect(
            'optSszZoneId',
            rows,
            'zoneId',
            function (row) { return row['zoneCode'] || ''; },
            'Choose Zone',
            selected
        );
    }


    this.init = function () {
        $('.sectionAssetDetails').hide();

        $('#btnSszBack').on('click', function () {
            $('.sectionAssetDetails').hide();
            if (classFrom.getClassName() === 'MainAsset') {
                $('.sectionAszMain').show();
            } else if (classFrom.getClassName() === 'MainPpmManagement') {
                $('.sectionPmgMain').show();
            }
            $(window).scrollTop(0);
        });

        const vData = [
            {
                field_id: 'txtSszAssetName',
                type: 'text',
                name: 'Asset Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtSszAssetNo',
                type: 'text',
                name: 'Asset No',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetSerialNo',
                type: 'text',
                name: 'Asset Serial No.',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetDesc',
                type: 'text',
                name: 'Asset Description',
                validator: {
                    maxLength: 1000
                }
            },
            {
                field_id: 'optSszAssetGroupId',
                type: 'select',
                name: 'Asset Group',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetCategoryId',
                type: 'select',
                name: 'Asset Category',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetTypeId',
                type: 'select',
                name: 'Asset Type',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optSszAssetBrandId',
                type: 'select',
                name: 'Asset Brand',
                validator: {
                }
            },
            {
                field_id: 'optSszAssetModelId',
                type: 'select',
                name: 'Asset Model',
                validator: {
                }
            },
            {
                field_id: 'txtSszAssetCapacity',
                type: 'text',
                name: 'Capacity',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'optSszPpmGroupId',
                type: 'select',
                name: 'PPM Group',
                validator: {
                }
            },
            {
                field_id: 'optSszZoneId',
                type: 'select',
                name: 'Zone',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'txtSszAssetLocationCode',
                type: 'text',
                name: 'Location Code',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetLocationDesc',
                type: 'text',
                name: 'Location Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'txtSszAssetBlock',
                type: 'text',
                name: 'Asset Block',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetLevel',
                type: 'text',
                name: 'Asset Level',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetManufacturer',
                type: 'text',
                name: 'Manufacturer',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetSupplier',
                type: 'text',
                name: 'Supplier',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetAgency',
                type: 'text',
                name: 'Agency',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetDepartment',
                type: 'text',
                name: 'Department',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetConstructionZone',
                type: 'text',
                name: 'Construction Zone',
                validator: {
                    maxLength: 50
                }
            },
            {
                field_id: 'txtSszAssetOperationZone',
                type: 'text',
                name: 'Operation Zone',
                validator: {
                    maxLength: 50
                }
            },
            {
                field_id: 'txtSszAssetRoom',
                type: 'text',
                name: 'Room',
                validator: {
                    maxLength: 50
                }
            },
            {
                field_id: 'txtSszAssetCompartment',
                type: 'text',
                name: 'Compartment',
                validator: {
                    maxLength: 50
                }
            },
            {
                field_id: 'txtSszAssetAuthEmployee',
                type: 'text',
                name: 'Authentication Employee',
                validator: {
                    maxLength: 150
                }
            },
            {
                field_id: 'txtSszAssetCriticality',
                type: 'text',
                name: 'Asset Criticality',
                validator: {
                    maxLength: 50
                }
            },
            {
                field_id: 'txtSszAssetContractor',
                type: 'text',
                name: 'Contractor',
                validator: {
                    maxLength: 100
                }
            },
            {
                field_id: 'txtSszAssetWarranty',
                type: 'text',
                name: 'Warranty / Contract',
                validator: {
                    maxLength: 50
                }
            },
            {
                field_id: 'txtSszAssetWarrantyExpDate',
                type: 'text',
                name: 'Warranty Expired Date',
                validator: {
                    maxLength: 30
                }
            },
            /*{
                field_id: 'txtSszAssetLifeCycle',
                type: 'text',
                name: 'Life Cycle',
                validator: {
                    numeric: true,
                    maxLength: 3
                }
            },*/
            {
                field_id: 'txtSszAssetWarrantyNotes',
                type: 'text',
                name: 'Warranty / Contract Notes',
                validator: {
                    maxLength: 1000
                }
            },
            {
                field_id: 'txtSszAssetTechnicianNotes',
                type: 'text',
                name: 'Note to Technician',
                validator: {
                    maxLength: 1000
                }
            },
            /*{
                field_id: 'txtSszAssetPurchasePrice',
                type: 'text',
                name: 'Purchase Price (RM)',
                validator: {
                    numeric: true,
                    maxLength: 12
                }
            },
            {
                field_id: 'txtSszAssetCommissionedDate',
                type: 'text',
                name: 'Commissioned Date',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetDisposedDate',
                type: 'text',
                name: 'Disposed Date',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetCurrentValue',
                type: 'text',
                name: 'Current Value (RM)',
                validator: {
                    numeric: true,
                    maxLength: 12
                }
            },
            {
                field_id: 'txtSszAssetEstimatedLife',
                type: 'text',
                name: 'Estimated Life',
                validator: {
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszAssetLifetimeDate',
                type: 'text',
                name: 'Lifetime Date',
                validator: {
                    maxLength: 30
                }
            },*/
        ];

        const vData2 = [
            {
                field_id: 'txtSszRepairCost',
                type: 'text',
                name: 'Repair Cost',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'txtSszRepairAlert',
                type: 'text',
                name: 'Cost Alert',
                validator: {
                    notEmpty: false,
                    numeric: true,
                    max: 100000000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszRunningHours',
                type: 'text',
                name: 'Running Hours',
                validator: {
                    notEmpty: false,
                    digit: true,
                    max: 10000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszLifeCycleCost',
                type: 'text',
                name: 'Life Cycle Cost',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'chkSszDisposalStatus',
                type: 'checkSingle',
                name: 'Disposed Status',
                validator: {
                    notEmptyCheck: false
                }
            },
            {
                field_id: 'txtSszDisposalServiceCost',
                type: 'text',
                name: 'Service Cost',
                validator: {
                    notEmpty: false,
                    numeric: true,
                    max: 100000000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszDisposalItemCost',
                type: 'text',
                name: 'Item Cost',
                validator: {
                    notEmpty: false,
                    numeric: true,
                    max: 100000000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszDisposalDate',
                type: 'text',
                name: 'Disposal Date',
                validator: {
                    notEmpty: false,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszMtbfValue',
                type: 'text',
                name: 'MTBF',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'txtSszMtbfAlert',
                type: 'text',
                name: 'MTBF Alert',
                validator: {
                    notEmpty: false,
                    digit: true,
                    max: 10000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszMttrValue',
                type: 'text',
                name: 'MTTR',
                validator: {
                    notEmpty: false
                }
            },
            {
                field_id: 'txtSszMttrAlert',
                type: 'text',
                name: 'MTTR Alert',
                validator: {
                    notEmpty: false,
                    digit: true,
                    max: 100000,
                    min: 0
                }
            }
        ];

        const vDataLifespan = [
            {
                field_id: 'txtSszLifespanYear',
                type: 'text',
                name: 'Lifespan Years',
                validator: {
                    notEmpty: false,
                    numeric: true,
                    max: 100
                }
            },
            {
                field_id: 'txtSszLifespanAlert',
                type: 'text',
                name: 'Lifespan Alert',
                validator: {
                    notEmpty: false,
                    numeric: true,
                    lower: {
                        id: 'txtSszLifespanYear',
                        label: 'Lifespan Years'
                    },
                    min: 0
                }
            },
            {
                field_id: 'txtSszLifespanStartDate',
                type: 'text',
                name: 'Lifespan Start Date',
                validator: {
                    notEmpty: false,
                    maxLength: 30
                }
            },
            {
                field_id: 'txtSszLifespanRemaining',
                type: 'text',
                name: 'Remaining Lifespan',
                validator: {
                    notEmpty: false
                }
            }
        ];

        const vDataValue = [
            {
                field_id: 'txtSszValuePurchasePrice',
                type: 'text',
                name: 'Purchase Price',
                validator: {
                    notEmpty: false,
                    numeric: true,
                    max: 100000000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszValueDepreciation',
                type: 'text',
                name: 'Lifespan Alert',
                validator: {
                    notEmpty: false,
                    digit: true,
                    max: 100,
                    min: 0
                }
            },
            {
                field_id: 'txtSszValueAlert',
                type: 'text',
                name: 'Value Alert',
                validator: {
                    numeric: true,
                    lower: {
                        id: 'txtSszValuePurchasePrice',
                        label: 'Purchase Price'
                    },
                    max: 100000000,
                    min: 0
                }
            },
            {
                field_id: 'txtSszValueRemaining',
                type: 'text',
                name: 'Remaining Lifespan',
                validator: {
                    notEmpty: false
                }
            }
        ];

        formValidate = new MzValidate('formAsz');
        formValidate.registerFields(vData);

        formValidate2 = new MzValidate('');
        formValidate2.registerFields(vData2);

        formValidateLifespan = new MzValidate('formSszLifespan');
        formValidateLifespan.registerFields(vDataLifespan);

        formValidateValue = new MzValidate('formSszValue');
        formValidateValue.registerFields(vDataValue);

        $('#formSsz').on('keyup change', function () {
            $('#btnSszSubmit, #btnSszUpdate').attr('disabled', !formValidate.validateForm());
        });

        $('#optSszAssetGroupId').on('change', function () {
            fillCategorySelect($(this).val(), '');
            fillTypeSelect('', '');
            fillBrandSelect('', '');
            fillModelSelect('', '', '');
        });

        $('#optSszAssetCategoryId').on('change', function () {
            fillTypeSelect($(this).val(), '');
            fillBrandSelect('', '');
            fillModelSelect('', '', '');
        });

        $('#optSszAssetTypeId').on('change', function () {
            fillBrandSelect($(this).val(), '');
            fillModelSelect('', '', '');
        });

        $('#optSszAssetBrandId').on('change', function () {
            fillModelSelect($(this).val(), $('#optSszAssetTypeId').val(), '');
        });

        const qrHost = document.getElementById("divSszQrCodeImg");
        if (qrHost) {
            qrCodeImg = new QRCode(qrHost, {});
        }

        $('#btnSszSave').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    const data = self.setFieldData();
                    data['action'] = 'save';
                    mzAjaxRequest('asset.php?assetId='+assetId, 'PUT', data);
                    if (classFrom.getClassName() === 'MainAsset') {
                        classFrom.updateTableAsz(data, rowRefresh);
                        $('.sectionAszMain').show();
                    }
                    $('.sectionAssetDetails').hide();
                    $(window).scrollTop(0);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSszSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = self.setFieldData();
                        data['action'] = 'submit';
                        mzAjaxRequest('asset.php?assetId='+assetId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainAsset') {
                            classFrom.updateTableAsz(data, rowRefresh);
                            $('.sectionAszMain').show();
                        }
                        $('.sectionAssetDetails').hide();
                        $(window).scrollTop(0);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSszUpdate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const data = self.setFieldData();
                        data['action'] = 'update';
                        mzAjaxRequest('asset.php?assetId='+assetId, 'PUT', data);
                        if (classFrom.getClassName() === 'MainAsset') {
                            classFrom.updateTableAsz(data, rowRefresh);
                            //$('.sectionAssetDetails').hide();
                            //$('.sectionAszMain').show();
                            //$(window).scrollTop(0);
                        }
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnSszUpdateCosting').on('click', function () {
            try {
                let validate = formValidate2.validateNow();
                validate = formValidateLifespan.validateNow() && validate;
                validate = formValidateValue.validateNow() && validate;
                if (!validate) {
                    toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                } else {
                    const data = {
                        assetLifespanYear: mzNullInt('txtSszLifespanYear'),
                        assetLifespanAlert: mzNullInt('txtSszLifespanAlert'),
                        assetLifespanStartDate: dateInputOrNull('txtSszLifespanStartDate'),
                        assetPurchasePrice: mzNullFloat('txtSszValuePurchasePrice'),
                        assetValueDepreciation: mzNullInt('txtSszValueDepreciation'),
                        assetValueAlert: mzNullFloat('txtSszValueAlert'),
                        assetRepairAlert: mzNullFloat('txtSszRepairAlert'),
                        assetRunningHours: mzNullInt('txtSszRunningHours'),
                        assetDisposalStatus: $("input[name='chkSszDisposalStatus']:checkbox").is(":checked") ? 1 : null,
                        assetDisposalDate: dateInputOrNull('txtSszDisposalDate'),
                        assetDisposalItemCost: mzNullFloat('txtSszDisposalItemCost'),
                        assetDisposalServiceCost: mzNullFloat('txtSszDisposalServiceCost'),
                        assetMtbfAlert: mzNullInt('txtSszMtbfAlert'),
                        assetMttrAlert: mzNullInt('txtSszMttrAlert')
                    };
                    ShowLoader(); setTimeout(function () {
                        mzFetch('ast_asset/'+assetId, 'PUT', data).then(res => {
                            //
                        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
                    }, 200);
                }
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszLifespanYear, #txtSszLifespanAlert').on('keyup', function () {
            try {
                self.calculateLifespan();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszLifespanStartDate').on('change', function () {
            try {
                self.calculateLifespan();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszValuePurchasePrice, #txtSszValueDepreciation, #txtSszValueAlert').on('keyup', function () {
            try {
                self.calculateDepreciation();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszRepairAlert').on('keyup', function () {
            try {
                self.calculateRepair();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $("input[name='chkSszDisposalStatus']:checkbox").on('click', function () {
            const statusChecked = $(this).is(":checked");
            try {
                self.checkDisposal(statusChecked);
                self.calculateLifeCycleCost();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszDisposalServiceCost').on('keyup', function () {
            try {
                self.calculateLifeCycleCost();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszMtbfAlert').on('keyup', function () {
            try {
                self.calculateMtbf(false);
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        $('#txtSszMttrAlert').on('keyup', function () {
            try {
                self.calculateMttr();
            } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); }
        });

        oTableSszWo = $('#dtSszWo').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[5, 'desc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-clipboard-list', 'No work orders recorded yet.', 'No work orders match the current search.'),
            pageLength: 10,
            autoWidth: false,
            pagingType: 'simple_numbers',
            dom: GemsUI.dtDomButtons,
            columnDefs: [
                { bSortable: false, targets: [0, 7] },
                { className: 'text-center', targets: [0, 1, 5, 6, 7] },
                { className: 'noVis', targets: [0, 7] }
            ],
            buttons: GemsUI.dtButtons('GEMS - Work Order List'),
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('.lnkSszWoPdf').off('click').on('click', function () {
                    const woTask = mzGetLinkRow($(this), oTableSszWo);
                    ShowLoader(); setTimeout(function () {
                        try {
                            let pdfId = woTask['pdfId'];
                            if (woTask['pdfId'] === null || woTask['woTaskIsPdf'] === 1) {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf', woTaskId:woTask['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Order Report: '+woTask['woTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
                $('.lnkSszWoPdfWr').off('click').on('click', function () {
                    const woTask = mzGetLinkRow($(this), oTableSszWo);
                    ShowLoader(); setTimeout(function () {
                        try {
                            let pdfId = woTask['pdfIdWr'];
                            if (woTask['pdfIdWr'] === null || woTask['woTaskIsPdfWr'] === 1) {
                                const resultRequest = mzAjaxRequest('wo.php', 'POST', {action: 'generate_pdf_wr', woTaskId:woTask['woTaskId']});
                                pdfId = resultRequest['pdfId'];
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;Work Request Report: '+woTask['woTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'woTaskNo'},
                { mData: 'woTaskType', mRender: function (data) {
                        return data !== null ? refWoType[data] : '';
                    }},
                { mData: 'woTaskSeverity', mRender: function (data) {
                        return data !== null ? refSeverity[data]['severityName'] : '';
                    }},
                { mData: 'woTaskAssignedTo', mRender: function (data) {
                        return data !== null ? refUser[data]['userFirstName'] : '';
                    }},
                { mData: 'woTaskTimeCreated'},
                { mData: 'woTaskStatus', mRender: function (data) {
                        return statusBadge(data);
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        let label = '';
                        if (row['woTaskIsWr'] === 1) {
                            label += GemsUI.actionBtn({id:'lnkSszWoPdfWr_'+meta.row, cls:'lnkSszWoPdfWr', icon:'far fa-file-alt', title:'Work Request PDF'});
                        }
                        if (row['woTaskIsWr'] !== 1 || row['woTaskTimeWrVerified'] !== null) {
                            label += GemsUI.actionBtn({id:'lnkSszWoPdf_'+meta.row, cls:'lnkSszWoPdf', icon:'far fa-file-pdf', title:'Work Order PDF'});
                        }
                        return label;
                    }}
            ]
        });

        oTableSszPpm = $('#dtSszPpm').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[2, 'asc']],
            ordering: true,
            language: GemsUI.dtEmpty('fa-calendar-check', 'No PPM tasks recorded yet.', 'No PPM tasks match the current search.'),
            pageLength: 10,
            autoWidth: false,
            pagingType: 'simple_numbers',
            dom: GemsUI.dtDomButtons,
            columnDefs: [
                { bSortable: false, targets: [0, 6] },
                { className: 'text-center', targets: [0, 1, 2, 4, 5, 6] },
                { className: 'noVis', targets: [0, 6] }
            ],
            buttons: GemsUI.dtButtons('GEMS - PPM Task List'),
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('.lnkSszPpmPdf').off('click').on('click', function () {
                    const row = mzGetLinkRow($(this), oTableSszPpm);
                    ShowLoader();
                    setTimeout(function () {
                        try {
                            let pdfId = row['pdfId'];
                            if (row['pdfId'] === null) {
                                pdfId = mzAjaxRequest('ppm.php', 'POST', {action: 'generate_pdf', ppmTaskId:row['ppmTaskId']});
                            }
                            const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                            $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;PPM Report: '+row['ppmTaskNo']);
                            $('#mpdf_iframe').attr('src', pdfSrc);
                            $('#modal_pdf').modal('show');
                        } catch (e) {
                            toastr['error'](e.message, _ALERT_TITLE_ERROR);
                        }
                        HideLoader();
                    }, 200);
                });
            },
            aoColumns: [
                { mData: null},
                { mData: 'ppmTaskNo'},
                { mData: 'ppmTaskStartDate'},
                { mData: 'ppmTaskAssignedTo'},
                { mData: 'ppmTaskTimeServiced'},
                { mData: 'ppmTaskStatus', mRender: function (data) {
                        return statusBadge(data);
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({id:'lnkSszPpmPdf_'+meta.row, cls:'lnkSszPpmPdf', icon:'far fa-file-pdf', title:'PPM PDF'});
                    }}
            ]
        });

        oTableSszWo.buttons().container().appendTo($('#btnDtSszWoExport'));
        oTableSszPpm.buttons().container().appendTo($('#btnDtSszPpmExport'));
        GemsUI.bindDtTooltips('#dtSszWo');
        GemsUI.bindDtTooltips('#dtSszPpm');
    };

    this.setFieldData = function () {
        const assetGroupId = $('#optSszAssetGroupId').val();
        const assetCategoryId = $('#optSszAssetCategoryId').val();
        const assetTypeId = $('#optSszAssetTypeId').val();
        const assetBrandId = $('#optSszAssetBrandId').val();
        const assetModelId = $('#optSszAssetModelId').val();
        const ppmGroupId = $('#optSszPpmGroupId').val();
        const zoneId = $('#optSszZoneId').val();
        return {
            action: '',
            assetId: assetId,
            assetName: $('#txtSszAssetName').val(),
            assetNo: $('#txtSszAssetNo').val(),
            assetSerialNo: $('#txtSszAssetSerialNo').val(),
            assetDesc: $('#txtSszAssetDesc').val(),
            assetGroupId: assetGroupId !== null ? assetGroupId : '',
            assetCategoryId: assetCategoryId !== null ? assetCategoryId : '',
            assetTypeId: assetTypeId !== null ? assetTypeId : '',
            assetBrandId: assetBrandId !== null ? assetBrandId : '',
            assetModelId: assetModelId !== null ? assetModelId : '',
            ppmGroupId: ppmGroupId !== null ? ppmGroupId : '',
            zoneId: zoneId !== null ? zoneId : '',
            assetCapacity: $('#txtSszAssetCapacity').val(),
            assetLocationCode: $('#txtSszAssetLocationCode').val(),
            assetLocationDesc: $('#txtSszAssetLocationDesc').val(),
            assetBlock: $('#txtSszAssetBlock').val(),
            assetLevel: $('#txtSszAssetLevel').val(),
            assetManufacturer: $('#txtSszAssetManufacturer').val(),
            assetSupplier: $('#txtSszAssetSupplier').val(),
            assetAgency: $('#txtSszAssetAgency').val(),
            assetDepartment: $('#txtSszAssetDepartment').val(),
            assetConstructionZone: $('#txtSszAssetConstructionZone').val(),
            assetOperationZone: $('#txtSszAssetOperationZone').val(),
            assetRoom: $('#txtSszAssetRoom').val(),
            assetCompartment: $('#txtSszAssetCompartment').val(),
            assetAuthEmployee: $('#txtSszAssetAuthEmployee').val(),
            assetCriticality: $('#txtSszAssetCriticality').val(),
            assetContractor: $('#txtSszAssetContractor').val(),
            assetWarranty: $('#txtSszAssetWarranty').val(),
            assetWarrantyExpDate: mzConvertDate($('#txtSszAssetWarrantyExpDate').val()),
            //assetLifeCycle: $('#txtSszAssetLifeCycle').val(),
            assetWarrantyNotes: $('#txtSszAssetWarrantyNotes').val(),
            assetTechnicianNotes: $('#txtSszAssetTechnicianNotes').val(),
            /*assetPurchasePrice: $('#txtSszAssetPurchasePrice').val(),
            assetCommissionedDate: mzConvertDate($('#txtSszAssetCommissionedDate').val()),
            assetDisposedDate: mzConvertDate($('#txtSszAssetDisposedDate').val()),
            assetCurrentValue: $('#txtSszAssetCurrentValue').val(),
            assetEstimatedLife: $('#txtSszAssetEstimatedLife').val(),
            assetLifetimeDate: mzConvertDate($('#txtSszAssetLifetimeDate').val()),*/
            assetStatus: assetStatus
        };
    };

    this.getDetails = function () {
        self.ensureDetailLookups();
        formValidate.clearValidation();
        formValidate2.clearValidation();
        formValidateLifespan.clearValidation();
        formValidateValue.clearValidation();

        const dataSsz = mzAjaxRequest('asset.php?assetId='+assetId, 'GET');
        const registeredBy = dataSsz['assetRegisteredBy']!=='' && refUser && refUser[dataSsz['assetRegisteredBy']]
            ? refUser[dataSsz['assetRegisteredBy']]['userFullName']
            : '';
        const assetGroupId = dataSsz['assetGroupId'];
        const assetCategoryId = dataSsz['assetCategoryId'];
        const assetTypeId = dataSsz['assetTypeId'];
        const assetBrandId = dataSsz['assetBrandId'];
        const assetModelId = dataSsz['assetModelId'];
        const ppmGroupId = dataSsz['ppmGroupId'];
        contractId = dataSsz['contractId'];
        assetStatus = dataSsz['assetStatus'];
        const siteId = refContract[contractId]['siteId'];
        const clientId = refSite[siteId]['clientId'];
        const zoneId = dataSsz['zoneId'];

        fillGroupSelect(assetGroupId);
        fillCategorySelect(assetGroupId, assetCategoryId);
        fillTypeSelect(assetCategoryId, assetTypeId);
        fillBrandSelect(assetTypeId, assetBrandId);
        fillModelSelect(assetBrandId, assetTypeId, assetModelId);
        fillPpmGroupSelect(siteId, ppmGroupId);
        fillZoneSelect(siteId, zoneId);
        setSelectDisabled('optSszAssetGroupId', false);
        setSelectDisabled('optSszAssetCategoryId', false);
        setSelectDisabled('optSszAssetTypeId', false);
        setSelectDisabled('optSszAssetBrandId', false);
        setSelectDisabled('optSszAssetModelId', false);
        setSelectDisabled('optSszZoneId', false);

        formValidate.enableField('optSszAssetGroupId');
        formValidate.enableField('optSszAssetCategoryId');
        formValidate.enableField('optSszAssetTypeId');
        formValidate.enableField('optSszAssetBrandId');
        formValidate.enableField('optSszAssetModelId');
        formValidate.enableField('optSszZoneId');

        mzSetFieldValue('SszAssetName', dataSsz['assetName'], 'text');
        mzSetFieldValue('SszAssetNo', dataSsz['assetNo'], 'text');
        mzSetFieldValue('SszAssetSerialNo', dataSsz['assetSerialNo'], 'text');
        mzSetFieldValue('SszAssetDesc', dataSsz['assetDesc'], 'text');
        mzSetFieldValue('SszAssetCapacity', dataSsz['assetCapacity'], 'text');
        mzSetFieldValue('SszAssetRegisteredBy', registeredBy, 'text');
        mzSetFieldValue('SszAssetTimeRegistered', mzConvertDateDisplay(dataSsz['assetTimeRegistered']), 'text');
        mzSetFieldValue('SszAssetStatus', refStatus[assetStatus]['statusDesc'], 'text');
        mzSetFieldValue('SszAssetLocationCode', dataSsz['assetLocationCode'], 'text');
        mzSetFieldValue('SszAssetLocationDesc', dataSsz['assetLocationDesc'], 'text');
        mzSetFieldValue('SszAssetManufacturer', dataSsz['assetManufacturer'], 'text');
        mzSetFieldValue('SszAssetSupplier', dataSsz['assetSupplier'], 'text');
        mzSetFieldValue('SszAssetAgency', dataSsz['assetAgency'], 'text');
        mzSetFieldValue('SszAssetDepartment', dataSsz['assetDepartment'], 'text');
        mzSetFieldValue('SszAssetConstructionZone', dataSsz['assetConstructionZone'], 'text');
        mzSetFieldValue('SszAssetOperationZone', dataSsz['assetOperationZone'], 'text');
        mzSetFieldValue('SszAssetRoom', dataSsz['assetRoom'], 'text');
        mzSetFieldValue('SszAssetCompartment', dataSsz['assetCompartment'], 'text');
        mzSetFieldValue('SszAssetAuthEmployee', dataSsz['assetAuthEmployee'], 'text');
        mzSetFieldValue('SszAssetCriticality', dataSsz['assetCriticality'], 'text');
        mzSetFieldValue('SszAssetContractor', dataSsz['assetContractor'], 'text');
        mzSetFieldValue('SszAssetWarranty', dataSsz['assetWarranty'], 'text');
        setDateInput('txtSszAssetWarrantyExpDate', dataSsz['assetWarrantyExpDate']);
        //mzSetFieldValue('SszAssetLifeCycle', dataSsz['assetLifeCycle'], 'text');
        mzSetFieldValue('SszAssetWarrantyNotes', dataSsz['assetWarrantyNotes'], 'text');
        mzSetFieldValue('SszAssetTechnicianNotes', dataSsz['assetTechnicianNotes'], 'text');
        /*mzSetFieldValue('SszAssetPurchasePrice', dataSsz['assetPurchasePrice'], 'text');
        mzSetFieldValue('SszAssetCommissionedDate', mzConvertDateDisplay(dataSsz['assetCommissionedDate']), 'text');
        mzSetFieldValue('SszAssetDisposedDate', mzConvertDateDisplay(dataSsz['assetDisposedDate']), 'text');
        mzSetFieldValue('SszAssetCurrentValue', dataSsz['assetCurrentValue'], 'text');
        mzSetFieldValue('SszAssetEstimatedLife', dataSsz['assetEstimatedLife'], 'text');
        mzSetFieldValue('SszAssetLifetimeDate', mzConvertDateDisplay(dataSsz['assetLifetimeDate']), 'text');*/
        mzSetFieldValue('SszContactName', refContract[contractId]['contractName'], 'text');
        mzSetFieldValue('SszSiteName', refSite[siteId]['siteName'], 'text');
        mzSetFieldValue('SszClientName', refClient[clientId]['clientName'], 'text');

        if (!qrCodeImg) {
            const qrHost = document.getElementById("divSszQrCodeImg");
            if (qrHost) {
                qrCodeImg = new QRCode(qrHost, {});
            }
        }
        if (qrCodeImg) {
            qrCodeImg.makeCode(dataSsz['assetNo']);
        }

        cntLifeCycleCost = 0;
        cntMeanTime = 0;
        lifespanStartDate = null;
        mzFetch('ast_asset/'+assetId).then(res => {
            mzSetFieldValue('txtSszLifespanYear', res['assetLifespanYear']);
            mzSetFieldValue('txtSszLifespanAlert', res['assetLifespanAlert']);
            setDateInput('txtSszLifespanStartDate', res['assetLifespanStartDate']);
            lifespanStartDate = res['assetLifespanStartDate'];
            self.calculateLifespan();
            mzSetFieldValue('txtSszValuePurchasePrice', res['assetPurchasePrice']);
            mzSetFieldValue('txtSszValueDepreciation', res['assetValueDepreciation']);
            mzSetFieldValue('txtSszValueAlert', res['assetValueAlert']);
            self.calculateDepreciation();
            mzSetFieldValue('txtSszRepairAlert', res['assetRepairAlert']);
            mzSetFieldValue('txtSszRunningHours', res['assetRunningHours']);
            mzSetFieldValue('SszDisposalStatus', res['assetDisposalStatus'], 'checkSingle', 1);
            setDateInput('txtSszDisposalDate', res['assetDisposalDate']);
            mzSetFieldValue('txtSszDisposalItemCost', res['assetDisposalItemCost']);
            mzSetFieldValue('txtSszDisposalServiceCost', res['assetDisposalServiceCost']);
            self.checkDisposal(res['assetDisposalStatus'] === 1);
            cntLifeCycleCost++;
            self.calculateLifeCycleCost();
            mzSetFieldValue('txtSszMtbfAlert', res['assetMtbfAlert']);
            mzSetFieldValue('txtSszMttrAlert', res['assetMttrAlert']);
            cntMeanTime++;
            self.calculateMtbf(true);
            self.calculateMttr();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });

        mzFetch('ast_asset/repairCost/'+assetId).then(res => {
            if (res !== null) {
                mzSetFieldValue('txtSszRepairCost', mzFormatNumber(res, 2));
                mzSetFieldValue('txtSszLifeCycleCost', mzFormatNumber(res, 2)); // to be put in disposal calculation function
            }
            self.calculateRepair();
            cntLifeCycleCost++;
            self.calculateLifeCycleCost();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); });
    };

    this.calculateLifespan = function () {
        try {
            let value = '';
            $('#spanSszLifespanAlert').hide();
            if (formValidateLifespan.validateNow()) {
                const years = mzNullInt('txtSszLifespanYear');
                const start = dateInputOrNull('txtSszLifespanStartDate');
                const alert = mzNullInt('txtSszLifespanAlert');
                if (start !== null && years !== null) {
                    const dateStart = moment(dateInputOrNull('txtSszLifespanStartDate'));
                    const dateCurrent = moment();
                    const lifespanYear = years - dateCurrent.diff(dateStart, 'year');
                    value = lifespanYear.toString();
                    if (lifespanYear < 0 || (alert !== null && lifespanYear - alert < 0)) {
                        $('#spanSszLifespanAlert').show();
                    }
                }
            }
            mzSetFieldValue('txtSszLifespanRemaining', value);
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.calculateDepreciation = function () {
        try {
            mzSetFieldValue('txtSszValueRemaining','');
            $('#spanSszDepreciationAlert').hide();
            if (formValidateValue.validateNow()) {
                const purchase = mzNullFloat('txtSszValuePurchasePrice', true);
                const alert = mzNullInt('txtSszValueAlert');
                const depreciation = mzNullFloat('txtSszValueDepreciation', true);
                const start = dateInputOrNull('txtSszLifespanStartDate');
                if (purchase > 0) {
                    let remaining = 0;
                    if (start !== null) {
                        const dateStart = moment(dateInputOrNull('txtSszLifespanStartDate'));
                        const dateCurrent = moment();
                        const totalYear = dateCurrent.diff(dateStart, 'year') + 1;
                        remaining = purchase - (purchase*totalYear*depreciation/100);
                    } else {
                        remaining = purchase - (purchase*depreciation/100);
                    }
                    const value = remaining.toString();
                    if (remaining < alert) {
                        $('#spanSszDepreciationAlert').show();
                    }
                    mzSetFieldValue('txtSszValueRemaining', mzFormatNumber(value, 2));
                }
            }
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.calculateRepair = function () {
        try {
            $('#spanSszRepairAlert').hide();
            const repairCost = mzNullFloat('txtSszRepairCost', true);
            const repairAlert = mzNullFloat('txtSszRepairAlert', true);
            if (repairCost > 0 && repairAlert > 0 && repairCost > repairAlert) {
                $('#spanSszRepairAlert').show();
            }
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.calculateLifeCycleCost = function () {
        try {
            if (cntLifeCycleCost === 2) {
                const repairCost = mzNullFloat('txtSszRepairCost', true);
                const disposalCost = mzNullFloat('txtSszDisposalServiceCost', true);
                mzSetFieldValue('txtSszLifeCycleCost', mzFormatNumber(repairCost + disposalCost, 2));
            }
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.checkDisposal = function (isChecked) {
        try {
            $('#txtSszDisposalDate').prop('disabled', !isChecked);
            $('#txtSszDisposalItemCost').prop('disabled', !isChecked);
            $('#txtSszDisposalServiceCost').prop('disabled', !isChecked);
            if (!isChecked) {
                setDateInput('txtSszDisposalDate', '');
                mzSetFieldValue('txtSszDisposalItemCost', '');
                mzSetFieldValue('txtSszDisposalServiceCost', '');
            }
            formValidate2.disableField('txtSszDisposalDate', !isChecked);
            formValidate2.disableField('txtSszDisposalItemCost', !isChecked);
            formValidate2.disableField('txtSszDisposalServiceCost', !isChecked);
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.calculateMtbf = function (isInit) {
        try {
            const contractDateStartStr = (refContract[contractId]['contractDateStart']).replaceAll('/', '-');
            const contractDateEndStr = (refContract[contractId]['contractDateEnd']).replaceAll('/', '-');
            if (cntMeanTime === 2) {
                $('#spanSszMtbfAlert').hide();
                const dateStart = lifespanStartDate !== null && lifespanStartDate > contractDateStartStr ? moment(lifespanStartDate) :  moment(contractDateStartStr);
                const dateEnd = moment(contractDateEndStr);
                const dateDiff = dateEnd.diff(dateStart, 'day')/(woTaskHistory.length+1);
                if (isInit) {
                    mzSetFieldValue('txtSszMtbfValue', dateDiff);
                }
                const mtbfAlert = mzNullFloat('txtSszMtbfAlert', true);
                if (mtbfAlert > dateDiff) {
                    $('#spanSszMtbfAlert').show();
                }
            }
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.calculateMttr = function () {
        try {
            if (cntMeanTime === 2) {
                $('#spanSszMttrAlert').hide();
                const mttralert = mzNullFloat('txtSszMttrAlert', true);
                let totalMttr = 0;
                let cntMttr = 0;
                for (const row of woTaskHistory) {
                    if (row['woTaskTimeAssigned'] !== null) { //  woTaskTimeExecuted
                        console.log(row);
                        const dateStart = moment(row['woTaskTimeCreated']);
                        const dateEnd = moment(row['woTaskTimeAssigned']);
                        totalMttr += dateEnd.diff(dateStart, 'hour');
                        cntMttr++;
                    }
                }
                mzSetFieldValue('txtSszMttrValue', totalMttr/cntMttr);
                if (cntMttr > 0 && totalMttr/cntMttr > mttralert) {
                    $('#spanSszMttrAlert').show();
                }
            }
        } catch (e) { toastr['error'](e.message, _ALERT_TITLE_ERROR); throw new Error(e.message)}
    }

    this.ensureDetailLookups = function () {
        if (!versionLocal) {
            return;
        }
        if (!refAssetBrand) {
            refAssetBrand = mzGetLocalArray('gems_assetBrand', versionLocal, 'assetBrandId', [], 'asset_brand');
        }
        if (!refAssetModel) {
            refAssetModel = mzGetLocalArray('gems_assetModel', versionLocal, 'assetModelId', [], 'asset_model');
        }
        if (!refUser) {
            refUser = mzGetLocalArray('gems_user', versionLocal, 'userId');
        }
        if (!refZone) {
            refZone = mzGetLocalArrayV2('gems_siteZone', versionLocal, 'zone/ref');
        }
        if (!refSeverity) {
            refSeverity = mzGetLocalArray('gems_severity', versionLocal, 'severityId', [], 'severity');
        }
    };

    this.add = function (_contractId, _assetGroupId, _assetCategoryId, _assetTypeId) {
        ShowLoader();
        setTimeout(function () {
            try {
                self.ensureDetailLookups();
                mzCheckFuncParam([_contractId]);

                const data = {
                    contractId: _contractId,
                    assetGroupId: _assetGroupId,
                    assetCategoryId: _assetCategoryId,
                    assetTypeId: _assetTypeId
                };
                assetId = mzAjaxRequest('asset.php', 'POST', data);

                self.getDetails();
                oTableSszWo.clear().draw();
                $('#divSszQrCode').hide();
                $('#txtSszAssetName, #txtSszAssetNo, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetCapacity, #txtSszAssetBlock, #txtSszAssetLevel').prop('disabled', false);

                $('#btnSszSubmit').prop('disabled', true);
                $('.divSszRegisterInfo, #btnSszUpdate, #btnSszQr, #btnSszPrint').hide();
                $('.sectionAssetDetails, #btnSszSubmit, #btnSszSave').show();
                if (oTableSszWo) { oTableSszWo.columns.adjust(); }
                if (oTableSszPpm) { oTableSszPpm.columns.adjust(); }

                if (classFrom.getClassName() === 'MainAsset') {
                    $('.sectionAszMain').hide();
                    const tempRow = self.setFieldData();
                    rowRefresh = classFrom.addTableAsz(tempRow);
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                self.ensureDetailLookups();
                mzCheckFuncParam([_assetId, _rowRefresh]);
                assetId = _assetId;
                rowRefresh = _rowRefresh;

                self.getDetails();
                if (assetStatus === '5') {
                    $('#divSszQrCode, #btnSszUpdate, #btnSszQr, #btnSszPrint, .divSszRegisterInfo').hide();
                    $('#btnSszSave, #btnSszSubmit').show();
                    $('#btnSszSubmit').prop('disabled', !formValidate.validateForm());
                } else {
                    $('#divSszQrCode, #btnSszUpdate, #btnSszQr, #btnSszPrint, .divSszRegisterInfo').show();
                    $('#btnSszSave, #btnSszSubmit').hide();

                    setSelectDisabled('optSszAssetGroupId', true);
                    setSelectDisabled('optSszAssetCategoryId', true);
                    setSelectDisabled('optSszAssetTypeId', true);

                    formValidate.disableField('optSszAssetGroupId');
                    formValidate.disableField('optSszAssetCategoryId');
                    formValidate.disableField('optSszAssetTypeId');
                }
                self.genTableWo();
                self.genTablePpm();

                $('#txtSszAssetName, #txtSszAssetNo, #txtSszSerialNo, #txtSszAssetDesc, #txtSszAssetCapacity, #txtSszAssetBlock, #txtSszAssetLevel').prop('disabled', false);
                $('#btnSszUpdate').prop('disabled', true);
                $('.sectionAssetDetails').show();
                if (oTableSszWo) { oTableSszWo.columns.adjust(); }
                if (oTableSszPpm) { oTableSszPpm.columns.adjust(); }

                if (classFrom.getClassName() === 'MainAsset') {
                    $('.sectionAszMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.view = function (_assetId) {
        ShowLoader();
        setTimeout(function () {
            try {
                self.ensureDetailLookups();
                mzCheckFuncParam([_assetId]);
                assetId = _assetId;

                self.getDetails();
                $('#divSszQrCode').show();
                $('#txtSszAssetName, #txtSszAssetNo, #txtSszSerialNo, #txtSszAssetSerialNo, #txtSszAssetDesc, #txtSszAssetCapacity, #txtSszAssetBlock, #txtSszAssetLevel').prop('disabled', true);

                setSelectDisabled('optSszAssetGroupId', true);
                setSelectDisabled('optSszAssetCategoryId', true);
                setSelectDisabled('optSszAssetTypeId', true);
                setSelectDisabled('optSszAssetBrandId', true);
                setSelectDisabled('optSszAssetModelId', true);
                self.genTableWo();
                self.genTablePpm();

                $('#btnSszSubmit, #btnSszSave, #btnSszUpdate').hide();
                $('.sectionAssetDetails, .divSszRegisterInfo, #btnSszQr, #btnSszPrint').show();
                if (oTableSszWo) { oTableSszWo.columns.adjust(); }
                if (oTableSszPpm) { oTableSszPpm.columns.adjust(); }

                if (classFrom.getClassName() === 'MainAsset') {
                    $('.sectionAszMain').hide();
                } else if (classFrom.getClassName() === 'MainPpmManagement') {
                    $('.sectionPmgMain').hide();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.deactivate = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                mzAjaxRequest('asset.php?assetId='+_assetId, 'PUT', {action: 'deactivate'});
                const tempRow = {assetStatus:'2'};
                if (classFrom.getClassName() === 'MainAsset') {
                    classFrom.updateTableAsz(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.activate = function (_assetId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId, _rowRefresh]);
                mzAjaxRequest('asset.php?assetId='+_assetId, 'PUT', {action: 'activate'});
                const tempRow = {assetStatus:'1'};
                if (classFrom.getClassName() === 'MainAsset') {
                    classFrom.updateTableAsz(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.delete = function (_assetId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_assetId]);
                mzAjaxRequest('asset.php?assetId='+_assetId, 'DELETE');
                if (classFrom.getClassName() === 'MainAsset') {
                    classFrom.deleteTableAsz();
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.genTableWo = function () {
        ShowLoader(); setTimeout(function () { mzFetch('wo_v3/by_assetId/'+assetId).then(res => {
            oTableSszWo.clear().rows.add(res).draw();
            woTaskHistory = res;
            cntMeanTime++;
            self.calculateMtbf(true);
            self.calculateMttr();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.genTablePpm = function () {
        ShowLoader(); setTimeout(function () { mzFetch('ppm_task/listByAsset/'+assetId).then(res => {
            oTableSszPpm.clear().rows.add(res).draw();
        }).catch((e) => { toastr['error'](e.message, _ALERT_TITLE_ERROR); }); }, 200);
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefAssetCategory = function (_refAssetCategory) {
        refAssetCategory = _refAssetCategory;
    };

    this.setRefAssetType = function (_refAssetType) {
        refAssetType = _refAssetType;
    };

    this.setRefAssetBrand = function (_refAssetBrand) {
        refAssetBrand = _refAssetBrand;
    };

    this.setRefAssetModel = function (_refAssetModel) {
        refAssetModel = _refAssetModel;
    };

    this.setRefContract = function (_refContract) {
        refContract = _refContract;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };

    this.setRefZone = function (_refZone) {
        refZone = _refZone;
    };

    this.setVersionLocal = function (_versionLocal) {
        versionLocal = _versionLocal;
    };

    this.setRefSeverity = function (_refSeverity) {
        refSeverity = _refSeverity;
    };
}