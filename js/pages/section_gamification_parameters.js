function SectionGamificationParameters () {

    const className = 'SectionGamificationParameters';
    let self = this;
    let classFrom; // Reference to the calling class (MainGamification)
    let mzValidateForm; // For client-side validation
    let gamificationParameters = {}; // To store fetched parameters

    // Map config_key from DB to HTML element IDs (without 'txt' prefix)
    const parameterFieldMap = {
        'point_completed_coeff': 'PointCompletedCoeff',
        'point_on_time_coeff': 'PointOnTimeCoeff',
        'point_late_coeff': 'PointLateCoeff',
        'point_self_finding_multiplier': 'PointSelfFindingMultiplier',
        'monthly_mbv_tier_1_threshold': 'MonthlyMbvTier1Threshold',
        'monthly_mbv_tier_1_divider': 'MonthlyMbvTier1Divider',
        'monthly_mbv_tier_2_threshold': 'MonthlyMbvTier2Threshold',
        'monthly_mbv_tier_2_divider': 'MonthlyMbvTier2Divider',
        'monthly_mbv_tier_3_divider': 'MonthlyMbvTier3Divider',
        'productivity_level_base': 'ProductivityLevelBase',
        'point_scale_multiplier_monthly': 'PointScaleMultiplierMonthly',
        'wo_on_time_weight_monthly': 'WoOnTimeWeightMonthly',
        'point_scale_multiplier_weekly': 'PointScaleMultiplierWeekly',
        'wo_on_time_weight_weekly': 'WoOnTimeWeightWeekly',
        'weekly_mbv_tier_1_threshold': 'WeeklyMbvTier1Threshold',
        'weekly_mbv_tier_1_divider': 'WeeklyMbvTier1Divider',
        'weekly_mbv_tier_2_threshold': 'WeeklyMbvTier2Threshold',
        'weekly_mbv_tier_2_divider': 'WeeklyMbvTier2Divider',
        'weekly_mbv_tier_3_divider': 'WeeklyMbvTier3Divider'
    };

    this.init = function () {
        // Initially hide this section
        $('.sectionGamificationParameters').hide();

        // Initialize form validator
        mzValidateForm = new MzValidate('frmGamificationParameters');
        mzValidateForm.registerFields([
            {field_id: 'txtPointCompletedCoeff', name: 'Completed Points Coefficient', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtPointOnTimeCoeff', name: 'On-Time Points Coefficient', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtPointLateCoeff', name: 'Late Points Coefficient', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtPointSelfFindingMultiplier', name: 'Self-Finding Bonus Multiplier', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtProductivityLevelBase', name: 'Productivity Level Base', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtPointScaleMultiplierMonthly', name: 'Overall Point Scale Multiplier Monthly', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWoOnTimeWeightMonthly', name: 'WO On-Time Weight Monthly', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtPointScaleMultiplierWeekly', name: 'Point Scale Multiplier Weekly', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWoOnTimeWeightWeekly', name: 'WO On-Time Weight Weekly', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWeeklyMbvTier1Threshold', name: 'Weekly MBV Tier 1 Threshold', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWeeklyMbvTier1Divider', name: 'Weekly MBV Tier 1 Divider', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWeeklyMbvTier2Threshold', name: 'Weekly MBV Tier 2 Threshold', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWeeklyMbvTier2Divider', name: 'Weekly MBV Tier 2 Divider', type: 'text', validator: {notEmpty: true, numeric: true}},
            {field_id: 'txtWeeklyMbvTier3Divider', name: 'Weekly MBV Tier 3 Divider', type: 'text', validator: {notEmpty: true, numeric: true}}
        ]);

        // Event listener for Back button
        $('#btnGpsBack').on('click', function () {
            ShowLoader(); //
            setTimeout(function () { //
                try {
                    $('.sectionGamificationParameters').hide(); //
                    classFrom.showMain(); // Show the main leaderboard page
                    $(window).scrollTop(0); //
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR); //
                }
                HideLoader(); //
            }, 200); //
        });

        // Event listener for Save button
        $('#btnGpsSave').on('click', function (e) {
            e.preventDefault(); // Prevent default form submission
            ShowLoader(); //
            setTimeout(function () { //
                try {
                    if (mzValidateForm.validateNow()) { // Perform client-side validation
                        self.saveParameters(); //
                    } else {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_VALIDATION_ERROR); //
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR); //
                }
                HideLoader(); //
            }, 200); //
        });

        // Event listener for Cancel button
        $('#btnGpsCancel').on('click', function () {
            self.loadParameters(); // Reload original values on cancel
            toastr['info']('Changes discarded.', _ALERT_TITLE_WARNING); //
        });

        // --- NEW BUTTON HANDLERS START ---

        // Placeholder for Upload GEMS
        $('#btnGpsUploadGems').on('click', function() {
            toastr['info']('Upload GEMS functionality is pending development.', _ALERT_TITLE_WARNING);
            // Future: Trigger file upload and backend processing (URS GEMS-GF-02)
        });

        // Placeholder for Upload CWORK
        $('#btnGpsUploadCwork').on('click', function() {
            toastr['info']('Upload CWORK functionality is pending development.', _ALERT_TITLE_WARNING);
            // Future: Trigger file upload and backend processing (URS GEMS-GF-02)
        });

        // Placeholder for Upload TOMMS
        $('#btnGpsUploadTomms').on('click', function() {
            toastr['info']('Upload TOMMS functionality is pending development.', _ALERT_TITLE_WARNING);
            // Future: Trigger file upload and backend processing (URS GEMS-GF-02)
        });

        // Handle Sync Data from Config page
        $('#btnGpsSyncData').on('click', function() {
            ShowLoader();
            setTimeout(function () {
                try {
                    // This logic is copied from main_gamification.js for monthly sync
                    const dateCtr = new Date(); // Get current date for month/year params
                    const currentYear = dateCtr.getFullYear();
                    const currentMonth = dateCtr.getMonth(); // JavaScript month (0-11)
                    
                    const runTypeApi = 'gamification/run_monthly';
                    const periodParam1 = currentMonth + 1; // Adjust JS 0-indexed month to DB 1-indexed
                    const periodParam2 = currentYear;

                    mzAjaxRequest2(runTypeApi + '/' + periodParam2 + '/' + periodParam1, 'PUT');
                    
                    toastr['success']('Sync Data process triggered successfully!', _ALERT_TITLE_SUCCESS);

                    // Optionally, after sync, automatically navigate back to main leaderboard or reload leaderboard data
                    // For now, let's just show success and allow user to go back manually.
                    // classFrom.genTableTop5(); // Not directly available here if not passed, requires re-navigation
                    // classFrom.genTableStats();
                    
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        // --- NEW BUTTON HANDLERS END ---
    };

    /**
     * Shows the parameters section and loads data.
     */
    this.show = function () {
        ShowLoader(); //
        setTimeout(function () { //
            try {
                $('.sectionGamificationParameters').show(); //
                self.loadParameters(); // Load parameters from backend
                $(window).scrollTop(0); //
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR); //
            }
            HideLoader(); //
        }, 200); //
    };

    /**
     * Fetches parameters from backend and populates the form.
     */
    this.loadParameters = function () {
        // Call backend API to get parameters
        const dataDb = mzAjaxRequest2('gamification/parameters', 'GET', null); //
        gamificationParameters = {}; // Clear previous
        if (dataDb && dataDb.length > 0) { //
            dataDb.forEach(param => { //
                gamificationParameters[param.configKey] = { //
                    configId: param.configId, //
                    value: param.configValue, //
                    dataType: param.dataType //
                };
                // Populate form fields
                const elementId = parameterFieldMap[param.configKey]; //
                if (elementId) { //
                    mzSetFieldValue(elementId, param.configValue, 'text'); //
                }
            });
        } else { //
            toastr['warning']('No gamification parameters found in database.', _ALERT_TITLE_WARNING); //
        }
    };

    /**
     * Collects data from the form and saves it to the backend.
     */
    this.saveParameters = function () {
        const userId = mzGetUserId(); // Get current user ID from common.js
        const updatedParams = {}; //

        // Collect values from form fields
        for (const key in parameterFieldMap) { //
            if (parameterFieldMap.hasOwnProperty(key)) { //
                const elementId = parameterFieldMap[key]; //
                let value = $('#txt' + elementId).val(); //
                
                // Convert value to its expected type based on dataType from fetched parameters
                // This assumes gamificationParameters has been loaded
                const paramInfo = gamificationParameters[key]; //
                if (paramInfo) { //
                    if (paramInfo.dataType === 'int') { //
                        value = parseInt(value); //
                        if (isNaN(value)) value = null; //
                    } else if (paramInfo.dataType === 'float') { //
                        value = parseFloat(value); //
                        if (isNaN(value)) value = null; //
                    }
                    // For string, no conversion needed
                }
                
                updatedParams[key] = value; //
            }
        }

        // Call backend API to save parameters
        mzAjaxRequest3('gamification/parameters', 'PUT', updatedParams); //
        toastr['success']('Gamification parameters saved successfully!', _ALERT_TITLE_SUCCESS); //
        // After saving, reload to ensure local cache is consistent or for immediate visual feedback
        self.loadParameters(); //
    };

    this.getClassName = function () { //
        return className; //
    };

    this.setClassFrom = function (_classFrom) { //
        classFrom = _classFrom; //
    };
}