/**
 * PTW Form Management Class
 * Handles PTW form creation, validation, and submission
 */
class PtwForm {
    constructor() {
        this.apiUrl = 'api/ptw.php';
        this.userSite = null;
    this.siteId = null; // site binding for public submissions
        this.refSite = [];
        this.refUser = [];
        this.ptwId = null;
        this.workerCounter = 0;
        this.checklistCache = new Map(); // Performance cache for rendered checklists
        this.userMode = null; // Will be set based on URL params (she_review, fm_review, etc.)
        this.isPublicMode = false; // Flag for public form mode
        this.workTypeChecklists = {
            'HOT_WORK': [
                'Fire extinguishers available and checked',
                'Hot work permit displayed',
                'Fire watch assigned',
                'Combustible materials removed',
                'Ventilation adequate',
                'Gas monitors operational'
            ],
            'hot_work': [ // HTML form value
                'Fire extinguishers available and checked',
                'Hot work permit displayed',
                'Fire watch assigned',
                'Combustible materials removed',
                'Ventilation adequate',
                'Gas monitors operational'
            ],
            'COLD_WORK': [
                'Tools inspected and in good condition',
                'Work area clear of hazards',
                'Lockout/Tagout procedures followed',
                'Mechanical isolation confirmed'
            ],
            'cold_work': [ // HTML form value
                'Tools inspected and in good condition',
                'Work area clear of hazards',
                'Lockout/Tagout procedures followed',
                'Mechanical isolation confirmed'
            ],
            'ELECTRICAL': [
                'Electrical isolation verified',
                'Lockout/Tagout procedures applied',
                'Voltage testing completed',
                'Qualified electrician assigned',
                'Electrical tools inspected',
                'Arc flash protection available'
            ],
            'CONFINED_SPACE': [
                'Atmospheric testing completed',
                'Ventilation system operational',
                'Entry supervisor assigned',
                'Rescue equipment available',
                'Communication system established',
                'Emergency response plan activated'
            ],
            'confined_space': [ // HTML form value
                'Atmospheric testing completed',
                'Ventilation system operational',
                'Entry supervisor assigned',
                'Rescue equipment available',
                'Communication system established',
                'Emergency response plan activated'
            ],
            'HEIGHT_WORK': [
                'Fall protection equipment inspected',
                'Scaffold inspection completed',
                'Weather conditions suitable',
                'Rescue plan in place',
                'Safety harness properly fitted',
                'Anchor points verified'
            ],
            'EXCAVATION': [
                'Underground services located',
                'Shoring/sloping adequate',
                'Soil analysis completed',
                'Entry/exit routes provided',
                'Protective systems in place',
                'Competent person assigned'
            ],
            'CHEMICAL': [
                'Material Safety Data Sheets available',
                'Chemical compatibility checked',
                'Spill response equipment ready',
                'Respiratory protection available',
                'Chemical storage secure',
                'Waste disposal plan ready'
            ],
            'LIFTING': [
                'Lifting equipment certified',
                'Lift plan approved',
                'Ground conditions suitable',
                'Exclusion zone established',
                'Signalman assigned',
                'Load weight verified'
            ],
            'MECHANICAL': [
                'Machine isolation completed',
                'Stored energy released',
                'Mechanical tools inspected',
                'Work area secured',
                'Safety guards in place',
                'Maintenance procedures followed'
            ]
        };
    }

    setPublicMode(isPublic = true) {
        this.isPublicMode = isPublic;
        console.log('PTW Form set to public mode:', isPublic);
    }

    setUserSite(userSite) {
        this.userSite = userSite;
    }

    setRefSite(refSite) {
        this.refSite = refSite;
    }

    setRefUser(refUser) {
        this.refUser = refUser;
    }

    init() {
        console.log('Initializing PTW Form - Public Mode:', this.isPublicMode);
        
        // For public mode, skip user authentication and site loading
        if (this.isPublicMode) {
            // Enforce site_id presence for public form
            this.siteId = this.getSiteIdFromUrl();
            if (!this.siteId) {
                console.warn('Public PTW form is missing required site_id in URL. Disabling form.');
                this.setupEventHandlers();
                this.showMissingSiteOverlay();
                return; // stop further init
            }
            this.setupEventHandlers();
            this.initializeFormForPublic();
            this.setupValidation();
            this.loadSpecificChecklistOptimized();
            this.populateWorkTypeDropdown();
        } else {
            // Original initialization for authenticated users
            this.setupEventHandlers();
            this.initializeForm();
            this.setupValidation();
            this.loadUrlParams();
            this.loadSpecificChecklistOptimized();
        }
    }

    setupEventHandlers() {
        // Add worker button
        $('#btnAddWorker').on('click', () => {
            this.addWorkerRow();
        });

        // Work type change handler - single optimized handler
        $('#optPtwWorkType').on('change', (e) => {
            this.loadSpecificChecklistOptimized(e.target.value);
        });

        // Date validation
        $('#dtPtwValidFrom, #dtPtwValidTo').on('change', () => {
            this.validateDates();
        });

        // Submit for approval button (only option for public forms)
        $('#btnSubmitApproval').on('click', (e) => {
            e.preventDefault(); // Prevent form submission
            console.log('Submit PTW button clicked');
            if (this.validateForm()) {
                this.savePtw('PENDING_APPROVAL');
            } else {
                console.log('Form validation failed');
            }
        });



    // Risk level removed

        // Form validation handlers
        $('#txtPtwDescription, #txtPtwWorkArea, #txtPtwApplicantName').on('blur', function() {
            $(this).removeClass('is-invalid');
        });
    }

    // Read site_id from URL query string (supports site_id or site)
    getSiteIdFromUrl() {
        try {
            const params = new URLSearchParams(window.location.search);
            const sid = params.get('site_id') || params.get('site') || '';
            const normalized = (sid || '').trim();
            if (!normalized) return '';
            // Allow numeric strings only (minimal validation)
            if (!/^\d+$/.test(normalized)) {
                console.warn('Invalid site_id format in URL:', normalized);
                return '';
            }
            return normalized;
        } catch (e) {
            console.error('Failed to parse site_id from URL:', e);
            return '';
        }
    }

    // Disable public form with a full-page overlay when site_id is missing
    showMissingSiteOverlay() {
        try {
            // Disable all interactive controls
            const inputs = document.querySelectorAll('input, textarea, select, button');
            inputs.forEach(el => {
                // Keep nav/tab buttons visible but disabled
                el.disabled = true;
                el.style.cursor = 'not-allowed';
            });

            // Target the main form container so we can hide it
            const container = document.querySelector('#divPtwPageWidth');
            if (container) {
                container.style.display = 'none';
            }

            // Prevent page scrolling behind the overlay
            try { document.body.style.overflow = 'hidden'; } catch (_) {}

            // Add full-page overlay with explanation
            const overlay = document.createElement('div');
            overlay.id = 'missingSiteOverlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(248,249,250,0.92);
                z-index: 99999;
                display: flex; align-items: center; justify-content: center;
                padding: 24px;
            `;
            const box = document.createElement('div');
            box.style.cssText = `
                background: #fff; border-left: 4px solid #dc3545; border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.08);
                max-width: 640px; width: 100%; padding: 20px;
            `;
            box.innerHTML = `
                <div style="display:flex;gap:12px;align-items:flex-start;">
                    <i class="fas fa-map-marker-alt text-danger" style="font-size:28px;line-height:1.2;"></i>
                    <div>
                        <h5 class="mb-2">This PTW form is not linked to a site</h5>
                        <p class="mb-2 text-muted">Please access this public PTW form using a site-specific link that GFM has provided.</p>
                        <small class="text-muted">If you believe this is an error, contact your site administrator for the correct link.</small>
                    </div>
                </div>`;
            // Attach overlay to body so it fully covers the viewport
            document.body.appendChild(overlay);
            overlay.appendChild(box);

            // Optionally adjust header subtitle
            const subtitle = document.getElementById('formSubtitle');
            if (subtitle) subtitle.textContent = 'Public PTW submission is disabled until a site is specified.';
        } catch (e) {
            console.error('Failed to render missing site overlay:', e);
        }
    }

    initializeForm() {
        // Set default dates
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        $('#dtPtwValidFrom').val(today.toISOString().split('T')[0]);
        $('#dtPtwValidTo').val(tomorrow.toISOString().split('T')[0]);

        // Add first worker row
        this.addWorkerRow();
        
        // Test dropdown accessibility
    console.log('Work Type dropdown found:', $('#optPtwWorkType').length > 0);
        
        // Force refresh dropdown styling
        this.refreshDropdowns();
    }

    refreshDropdowns() {
        // Ensure dropdowns are properly styled and functional
        console.log('Refreshing dropdown functionality...');
        
    const workTypeSelect = document.getElementById('optPtwWorkType');
        
    [workTypeSelect].forEach(select => {
            if (select) {
                // Remove any MDB interference
                select.classList.remove('mdb-select', 'md-form', 'browser-default');
                select.removeAttribute('data-mdb-select-init');
                select.removeAttribute('data-mdb-select-initialized');
                
                // Force native behavior
                select.style.appearance = 'none';
                select.style.webkitAppearance = 'none';
                select.style.mozAppearance = 'none';
                select.style.cursor = 'pointer';
                select.style.pointerEvents = 'auto';
                select.disabled = false;
                
                // Add click handler to test functionality
                select.addEventListener('click', function(e) {
                    console.log('Dropdown clicked:', this.id, 'Event:', e);
                    e.stopPropagation();
                    
                    // Force the dropdown to open programmatically if needed
                    this.focus();
                    if (this.size === 1) {
                        this.size = Math.min(this.options.length, 10);
                        setTimeout(() => {
                            this.size = 1;
                        }, 100);
                    }
                }, true);
                
                // Add focus handler
                select.addEventListener('focus', function() {
                    console.log('Dropdown focused:', this.id);
                });
                
                // Add mousedown handler for additional control
                select.addEventListener('mousedown', function(e) {
                    console.log('Dropdown mousedown:', this.id);
                    e.stopPropagation();
                });
                
                console.log('Refreshed dropdown:', select.id);
            }
        });
    }

    initializeFormForPublic() {
        console.log('Initializing form for public use...');
        
        // Set default dates
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        $('#dtPtwValidFrom').val(today.toISOString().split('T')[0]);
        $('#dtPtwValidTo').val(tomorrow.toISOString().split('T')[0]);

        // Add first worker row
        this.addWorkerRow();
        
        // Force refresh dropdown styling
        this.refreshDropdowns();
        
        console.log('Public form initialization completed');
    }

    populateWorkTypeDropdown() {
        console.log('Populating work type dropdown for public form...');
        
        const workTypes = [
            { value: 'HOT_WORK', text: 'Hot Work (Welding, Cutting, Grinding)' },
            { value: 'COLD_WORK', text: 'Cold Work (Mechanical, Assembly)' },
            { value: 'ELECTRICAL', text: 'Electrical Work' },
            { value: 'CONFINED_SPACE', text: 'Confined Space Entry' },
            { value: 'HEIGHT_WORK', text: 'Work at Height' },
            { value: 'LIFTING', text: 'Lifting Operations' },
            { value: 'MECHANICAL', text: 'Mechanical Maintenance' }
        ];
        
        const select = document.getElementById('optPtwWorkType');
        if (select) {
            // Clear existing options
            select.innerHTML = '<option value="">Select Work Type</option>';
            
            // Add work type options
            workTypes.forEach(workType => {
                const option = document.createElement('option');
                option.value = workType.value;
                option.textContent = workType.text;
                select.appendChild(option);
            });
            
            console.log('Work type dropdown populated with', workTypes.length, 'options');
        } else {
            console.error('Work type dropdown not found');
        }
    }

    loadUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const ptwId = urlParams.get('id');
        const mode = urlParams.get('mode');
        
        if (mode) {
            this.userMode = mode;
            this.configureFormForMode(mode);
        }
        
        if (ptwId) {
            this.ptwId = ptwId;
            this.loadPtwData(ptwId);
            
            // Update title based on mode
            if (mode === 'she_review') {
                $('#h5PtwFormTitle').html('<i class="fa fa-shield-alt mr-1"></i> SHE Review - PTW Permit');
            } else if (mode === 'fm_review') {
                $('#h5PtwFormTitle').html('<i class="fa fa-shield-alt mr-1"></i> FM Review - PTW Permit');
            } else {
                $('#h5PtwFormTitle').html('<i class="fa fa-shield-alt mr-1"></i> Edit PTW Permit');
            }
        }
    }

    configureFormForMode(mode) {
        if (mode === 'she_review') {
            this.configureSheReviewMode();
        } else if (mode === 'fm_review') {
            this.configureFmReviewMode();
        }
    }

    configureSheReviewMode() {
        // Make most fields read-only except SHE approval section
        this.setFormReadOnly(['section1', 'section2', 'section3', 'section4']);
        
        // Show approval section
        $('#approvalSection').show();
        
        // Enable SHE approval controls
        $('#sheApprovalBlock').removeClass('d-none');
        $('#sheApprovalInputs').prop('disabled', false);
        
        // Keep supervisor block visible but disabled
        $('#supervisorApprovalBlock').addClass('readonly-section');
        
        // Keep FM block disabled
        $('#fmApprovalBlock').addClass('d-none');
        
        // Update action buttons for SHE
        this.updateActionButtonsForShe();
    }

    configureFmReviewMode() {
        // Similar to SHE but for FM review
        this.setFormReadOnly(['section1', 'section2', 'section3', 'section4']);
        
        $('#approvalSection').show();
        $('#supervisorApprovalBlock').addClass('readonly-section');
        $('#sheApprovalBlock').addClass('readonly-section');
        $('#fmApprovalBlock').removeClass('d-none');
        
        this.updateActionButtonsForFm();
    }

    setFormReadOnly(sections) {
        sections.forEach(section => {
            $(`#${section} input, #${section} select, #${section} textarea`).prop('readonly', true);
            $(`#${section} button`).prop('disabled', true);
        });
    }

    updateActionButtonsForShe() {
        const actionHtml = `
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h5><i class="fas fa-clipboard-check mr-2"></i>SHE Officer Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="sheRemarks">SHE Remarks:</label>
                                <textarea id="sheRemarks" class="form-control" rows="3" placeholder="Enter approval remarks or conditions..."></textarea>
                            </div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-lg" onclick="approvePtwByShe()">
                                    <i class="fas fa-check mr-2"></i>Approve PTW
                                </button>
                                <button type="button" class="btn btn-danger btn-lg" onclick="rejectPtwByShe()">
                                    <i class="fas fa-times mr-2"></i>Reject PTW
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="window.close()">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#actionButtonsSection').html(actionHtml);
    }

    updateActionButtonsForFm() {
        const actionHtml = `
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h5><i class="fas fa-clipboard-check mr-2"></i>Facility Manager Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="fmRemarks">FM Remarks:</label>
                                <textarea id="fmRemarks" class="form-control" rows="3" placeholder="Enter approval remarks or conditions..."></textarea>
                            </div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-lg" onclick="approvePtwByFm()">
                                    <i class="fas fa-check mr-2"></i>Final Approve PTW
                                </button>
                                <button type="button" class="btn btn-danger btn-lg" onclick="rejectPtwByFm()">
                                    <i class="fas fa-times mr-2"></i>Reject PTW
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="window.close()">
                                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#actionButtonsSection').html(actionHtml);
    }

    loadPtwData(ptwId) {
        // Public forms don't support loading existing data
        console.log('Loading PTW data not supported for public forms');
        showError('Loading existing PTW data is not available for public forms');
        return;
    }

    populateForm(data) {
        // Set the PTW ID for editing mode
        this.ptwId = data.ptw_permit_id;
        $('#lblPtwId').val(data.ptw_permit_id);
        
        // Basic information - map API field names to form fields
        $('#txtPtwDescription').val(data.ptw_permit_description || data.description || '');
        $('#txtPtwWorkArea').val(data.ptw_work_area || data.work_area || '');
        $('#optPtwWorkType').val(data.ptw_work_type || data.work_type || '');
        
        // Handle date format conversion if needed
        const validFrom = data.ptw_valid_from || data.valid_from || '';
        const validTo = data.ptw_valid_to || data.valid_to || '';
        if (validFrom) {
            const fromDate = new Date(validFrom);
            $('#dtPtwValidFrom').val(fromDate.toISOString().split('T')[0]);
        }
        if (validTo) {
            const toDate = new Date(validTo);
            $('#dtPtwValidTo').val(toDate.toISOString().split('T')[0]);
        }

    // Risk assessment (risk level removed)
        $('#txtPtwHazards').val(data.ptw_hazards || data.hazards || '');
        $('#txtPtwControlMeasures').val(data.ptw_control_measures || data.control_measures || '');

        // Applicant information
        $('#txtPtwApplicantName').val(data.ptw_applicant_name || data.applicant_name || '');
        $('#txtPtwApplicantContact').val(data.ptw_applicant_contact || data.applicant_contact || '');
        $('#txtPtwApplicantDept').val(data.ptw_applicant_company_dept || data.applicant_department || '');
        $('#txtPtwContractorCompany').val(data.ptw_contractor_company || data.contractor_company || '');
        $('#txtPtwRemarks').val(data.ptw_remarks || data.remarks || '');

        // Enhanced applicant/contractor fields
        $('#txtContractorSupervisor').val(data.ptw_contractor_supervisor || data.contractor_supervisor || '');
        $('#txtStaffNric').val(data.ptw_staff_nric || data.staff_nric || '');
        $('#txtSupervisorContact').val(data.ptw_supervisor_contact || data.supervisor_contact || '');
        $('#txtIdentificationNo').val(data.ptw_identification_no || data.identification_no || '');
        $('#txtLevel').val(data.ptw_level || data.level || '');
        $('#txtWorkDuration').val(data.ptw_work_duration || data.work_duration || '');

        // Load multiple work types
        if (data.ptw_work_types || data.work_types_selected) {
            this.loadMultipleWorkTypes(data.ptw_work_types || data.work_types_selected);
        }

        // Load workers if available
        if (data.workers && data.workers.length > 0) {
            $('#workersContainer').empty();
            this.workerCounter = 0;
            data.workers.forEach(worker => {
                this.addWorkerRow(worker);
            });
        }

        // Load checklist data
        this.loadChecklistFromPtwData(data);

        // Update form title for editing mode
        $('#h5PtwFormTitle').html('<i class="fas fa-edit mr-3"></i>Edit PTW Permit - ' + (data.ptw_permit_number || 'Unknown'));
        
        // Show approval section if permit has been submitted
        if (data.ptw_status && data.ptw_status !== 'DRAFT') {
            this.showApprovalSection(data);
        }
        
        console.log('PTW form populated successfully:', data);
    }

    /**
     * Load multiple work types from saved data
     */
    loadMultipleWorkTypes(workTypesData) {
        try {
            let workTypes = [];
            
            // Handle different data formats
            if (typeof workTypesData === 'string') {
                if (workTypesData.includes(',')) {
                    // Comma-separated string: "cold_work,hot_work,confined_space"
                    workTypes = workTypesData.split(',').map(type => type.trim());
                } else {
                    // JSON string
                    try {
                        workTypes = JSON.parse(workTypesData);
                    } catch (e) {
                        workTypes = [workTypesData];
                    }
                }
            } else if (Array.isArray(workTypesData)) {
                workTypes = workTypesData;
            }

            // Check the appropriate work type checkboxes using correct IDs
            workTypes.forEach(workType => {
                let checkboxId = '';
                switch(workType) {
                    case 'cold_work':
                        checkboxId = 'chkColdWork';
                        break;
                    case 'hot_work':
                        checkboxId = 'chkHotWork';
                        break;
                    case 'confined_space':
                        checkboxId = 'chkConfinedSpace';
                        break;
                }
                
                if (checkboxId) {
                    $(`#${checkboxId}`).prop('checked', true);
                    console.log(`Checked work type: ${workType} (${checkboxId})`);
                }
            });

            console.log('Loaded multiple work types:', workTypes);
        } catch (error) {
            console.error('Error loading multiple work types:', error);
        }
    }

    loadChecklistFromPtwData(data) {
        console.log('Loading checklist data from PTW:', data);

        // Load hazard checklist if available
        if (data.ptw_hazard_checklist) {
            try {
                const hazardData = JSON.parse(data.ptw_hazard_checklist);
                this.loadChecklistData(hazardData);
            } catch (e) {
                console.error('Error parsing hazard checklist:', e);
            }
        }

        // Load hot work checklist
        if (data.ptw_checklist_hot_work) {
            try {
                const hotWorkData = JSON.parse(data.ptw_checklist_hot_work);
                console.log('Loading hot work checklist data:', hotWorkData);
                
                if (typeof hotWorkData === 'object' && hotWorkData !== null) {
                    // Handle object format: {hotGasMonitoring: "Gas monitoring", ...}
                    Object.keys(hotWorkData).forEach(checkboxId => {
                        const checkbox = document.getElementById(checkboxId);
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`Restored hot work item: ${checkboxId}`);
                        } else {
                            console.warn(`Hot work checkbox not found: ${checkboxId}`);
                        }
                    });
                } else if (Array.isArray(hotWorkData)) {
                    // Handle array format for backward compatibility
                    hotWorkData.forEach(itemId => {
                        const checkbox = document.getElementById(itemId);
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`Restored hot work item: ${itemId}`);
                        }
                    });
                }
                console.log('Loaded hot work checklist successfully');
            } catch (e) {
                console.error('Error parsing hot work checklist:', e);
            }
        }

        // Load cold work checklist
        if (data.ptw_checklist_cold_work) {
            try {
                const coldWorkData = JSON.parse(data.ptw_checklist_cold_work);
                console.log('Loading cold work checklist data:', coldWorkData);
                
                if (typeof coldWorkData === 'object' && coldWorkData !== null) {
                    // Handle object format: {coldElectricalWork: "Electrical Work", ...}
                    Object.keys(coldWorkData).forEach(itemKey => {
                        const itemValue = coldWorkData[itemKey];
                        
                        if (itemKey === 'coldSpecialPrecautions') {
                            // Handle special precautions text area
                            const textArea = document.getElementById('coldSpecialPrecautions');
                            if (textArea && itemValue) {
                                textArea.value = itemValue;
                                console.log(`Restored cold work special precautions: ${itemValue}`);
                            }
                        } else {
                            // Handle checkbox items
                            const checkbox = document.getElementById(itemKey);
                            if (checkbox) {
                                checkbox.checked = true;
                                console.log(`Restored cold work item: ${itemKey}`);
                                
                                // Handle "Others" text field
                                if (itemKey === 'coldOthers') {
                                    const textField = document.getElementById('coldOthersText');
                                    if (textField) {
                                        // Use the value from the checkbox data (which should be the text)
                                        textField.value = itemValue || '';
                                        textField.disabled = false;
                                        console.log(`Restored cold work others text: ${itemValue}`);
                                    }
                                }
                            } else {
                                console.warn(`Cold work checkbox not found: ${itemKey}`);
                            }
                        }
                    });
                } else if (Array.isArray(coldWorkData)) {
                    // Handle array format for backward compatibility
                    coldWorkData.forEach(itemId => {
                        const checkbox = document.getElementById(itemId);
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`Restored cold work item: ${itemId}`);
                        }
                    });
                }
                console.log('Loaded cold work checklist successfully');
            } catch (e) {
                console.error('Error parsing cold work checklist:', e);
            }
        }

        // Load confined space checklist
        if (data.ptw_checklist_confined_space) {
            try {
                const confinedSpaceData = JSON.parse(data.ptw_checklist_confined_space);
                console.log('Loading confined space checklist data:', confinedSpaceData);
                
                if (typeof confinedSpaceData === 'object' && confinedSpaceData !== null) {
                    // Handle object format: {csRespiratoryAtmosphere: "Respiratory Atmosphere", ...}
                    Object.keys(confinedSpaceData).forEach(checkboxId => {
                        const checkbox = document.getElementById(checkboxId);
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`Restored confined space item: ${checkboxId}`);
                        } else {
                            console.warn(`Confined space checkbox not found: ${checkboxId}`);
                        }
                    });
                } else if (Array.isArray(confinedSpaceData)) {
                    // Handle array format for backward compatibility
                    confinedSpaceData.forEach(itemId => {
                        const checkbox = document.getElementById(itemId);
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`Restored confined space item: ${itemId}`);
                        }
                    });
                }
                console.log('Loaded confined space checklist successfully');
            } catch (e) {
                console.error('Error parsing confined space checklist:', e);
            }
        }

        // After loading work type specific checklists, ensure main checkboxes are checked and sections are visible
        this.syncWorkTypeSectionsAfterLoad(data);

        // Load declaration checklist
        if (data.ptw_declaration_checklist) {
            try {
                const declarationData = JSON.parse(data.ptw_declaration_checklist);
                Object.keys(declarationData).forEach(key => {
                    const value = declarationData[key];
                    if (key === 'contractorConfirmation') {
                        $(`#chkContractorConfirmation`).prop('checked', value === true || value === 'true');
                    } else {
                        // Handle yes/no declarations
                        $(`input[name="${key}"][value="${value}"]`).prop('checked', true);
                    }
                });
                console.log('Loaded declaration checklist:', declarationData);
            } catch (e) {
                console.error('Error parsing declaration checklist:', e);
            }
        }

        // Load supporting documents checklist
        if (data.ptw_supporting_docs_checklist) {
            try {
                const supportingDocsData = JSON.parse(data.ptw_supporting_docs_checklist);
                console.log('Loading supporting documents data:', supportingDocsData);
                
                if (Array.isArray(supportingDocsData)) {
                    // Create reverse mapping from value to ID
                    const valueToIdMapping = {
                        'calibration_certificate': 'docCalibrationCert',
                        'load_chart': 'docLoadChart',
                        'toolbox_talk_record': 'docToolboxTalk',
                        'safety_data_sheet': 'docSafetyDataSheet',
                        'job_method_statement': 'docJobMethodStatement',
                        'job_hazard_analysis': 'docJobHazardAnalysis',
                        'certificate_of_fitness': 'docCertificateOfFitness',
                        'cidb_green_card': 'docCidbGreenCard',
                        'aesp_card': 'docAespCard',
                        'competency_person_certificate': 'docCompetencyPersonCert',
                        'contractor_checklist': 'docContractorChecklist',
                        'medical_check_up': 'docMedicalCheckUp',
                        'hirarc_eaia': 'docHirarcEaia',
                        'traffic_management_plan': 'docTrafficManagementPlan'
                    };
                    
                    // Use setTimeout to ensure DOM is ready
                    setTimeout(() => {
                        let checkedCount = 0;
                        supportingDocsData.forEach(docValue => {
                            const checkboxId = valueToIdMapping[docValue];
                            if (checkboxId) {
                                const checkbox = document.getElementById(checkboxId);
                                if (checkbox) {
                                    checkbox.checked = true;
                                    checkedCount++;
                                    console.log(`✓ Restored supporting document: ${docValue} -> ${checkboxId}`);
                                } else {
                                    console.error(`✗ Checkbox not found: ${checkboxId} for ${docValue}`);
                                }
                            } else {
                                console.warn(`✗ Unknown supporting document value: ${docValue}`);
                            }
                        });
                        console.log(`Supporting docs loading complete: ${checkedCount}/${supportingDocsData.length} checkboxes checked`);
                    }, 100);
                }
                console.log('Loaded supporting docs checklist successfully');
            } catch (e) {
                console.error('Error parsing supporting docs checklist:', e);
            }
        }

        // Load hazardous activities
        if (data.ptw_hazardous_activities) {
            try {
                const hazardsData = JSON.parse(data.ptw_hazardous_activities);
                
                Object.keys(hazardsData).forEach(hazardId => {
                    const hazardData = hazardsData[hazardId];
                    
                    if (hazardId === 'hazOthers' && typeof hazardData === 'object') {
                        // Handle "Others" field with text input
                        $(`#${hazardId}`).prop('checked', hazardData.checked || false);
                        const textField = $('#hazOthersText');
                        if (hazardData.checked) {
                            textField.val(hazardData.value || '').prop('disabled', false);
                        } else {
                            textField.val('').prop('disabled', true);
                        }
                    } else {
                        // Handle regular checkbox fields
                        $(`#${hazardId}`).prop('checked', hazardData === true);
                    }
                });
                
                console.log('Loaded hazardous activities:', hazardsData);
            } catch (e) {
                console.error('Error parsing hazardous activities:', e);
            }
        }

        // Load certificate numbers with checkbox states
        if (data.ptw_certificate_numbers) {
            try {
                const certificateData = JSON.parse(data.ptw_certificate_numbers);
                
                // Handle both old format (direct values) and new format (checkbox + value objects)
                Object.keys(certificateData).forEach(key => {
                    const certData = certificateData[key];
                    
                    if (typeof certData === 'object' && certData !== null) {
                        // New format: {checked: boolean, value: string}
                        const checkboxId = this.getCertificateCheckboxId(key);
                        const inputId = this.getCertificateInputId(key);
                        
                        if (checkboxId) {
                            $(`#${checkboxId}`).prop('checked', certData.checked || false);
                            
                            // Only set input value if input field exists (not for sub-items)
                            if (inputId) {
                                $(`#${inputId}`).val(certData.value || '').prop('disabled', !certData.checked);
                            }
                        }
                    } else {
                        // Old format: direct value (for backward compatibility)
                        $(`#txt${key.charAt(0).toUpperCase() + key.slice(1)}`).val(certData);
                    }
                });
                
                console.log('Loaded certificate numbers:', certificateData);
            } catch (e) {
                console.error('Error parsing certificate numbers:', e);
            }
        }

        // Load specific checklist for work type
        this.loadSpecificChecklistOptimized();
    }

    showApprovalSection(data) {
        // Show the approval section with current approval status
        $('#approvalSection').show();
        
        // Update supervisor approval
        if (data.ptw_supervisor_approval === 'APPROVED') {
            $('#supervisorStatus').removeClass('badge-info').addClass('badge-success').text('Approved');
            $('#supervisorName').val(data.approved_supervisor_name || 'User ID: ' + data.ptw_supervisor_id || 'Unknown');
            $('#supervisorDate').val(data.ptw_supervisor_approval_date || 'Not available');
            $('#supervisorRemarks').val(data.ptw_supervisor_comments || 'No comments');
        } else if (data.ptw_supervisor_approval === 'REJECTED') {
            $('#supervisorStatus').removeClass('badge-info').addClass('badge-danger').text('Rejected');
        }

        // Update SHE approval
        if (data.ptw_she_approval === 'APPROVED') {
            $('#sheStatus').removeClass('badge-warning').addClass('badge-success').text('Approved');
            $('#sheName').val(data.approved_she_name || 'Unknown');
            $('#sheDate').val(data.approved_she_date || 'Not available');
            $('#sheRemarks').val(data.ptw_she_remarks || 'No remarks');
        } else if (data.ptw_she_approval === 'REJECTED') {
            $('#sheStatus').removeClass('badge-warning').addClass('badge-danger').text('Rejected');
        }

        // Update FM approval
        if (data.ptw_fm_approval === 'APPROVED') {
            $('#fmStatus').removeClass('badge-secondary').addClass('badge-success').text('Approved');
            $('#fmName').val(data.approved_fm_name || 'Unknown');
            $('#fmDate').val(data.approved_fm_date || 'Not available');
            $('#fmRemarks').val(data.ptw_fm_remarks || 'No remarks');
        } else if (data.ptw_fm_approval === 'REJECTED') {
            $('#fmStatus').removeClass('badge-secondary').addClass('badge-danger').text('Rejected');
        }
    }

    addWorkerRow(workerData = null) {
        this.workerCounter++;
        const workerId = this.workerCounter;

        const workerHtml = `
            <div class="worker-section" id="workerSection${workerId}">
                <div class="row align-items-center">
                    <div class="col-md-11">
                        <h6><strong>Worker ${workerId}</strong></h6>
                    </div>
                    <div class="col-md-1 text-right">
                        <button type="button" class="btn btn-sm btn-danger" onclick="ptwFormClass_.removeWorkerRow(${workerId})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="txtWorkerName${workerId}">Worker Name *</label>
                            <input type="text" id="txtWorkerName${workerId}" class="form-control worker-name" 
                                   value="${workerData ? workerData.name : ''}" required placeholder="Full name">
                            <div class="description">Name of the worker participating in this work</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="txtWorkerContact${workerId}">Contact Number</label>
                            <input type="text" id="txtWorkerContact${workerId}" class="form-control worker-contact" 
                                   value="${workerData ? workerData.contact_number : ''}" placeholder="Phone number">
                            <div class="description">Emergency contact number</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="txtWorkerRole${workerId}">Role/Position</label>
                            <input type="text" id="txtWorkerRole${workerId}" class="form-control worker-role" 
                                   value="${workerData ? workerData.role : ''}" placeholder="Job role">
                            <div class="description">Worker's role in this activity</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input worker-certified" id="chkWorkerCertified${workerId}"
                                       ${workerData && workerData.is_certified ? 'checked' : ''}>
                                <label class="form-check-label" for="chkWorkerCertified${workerId}">Certified</label>
                            </div>
                            <div class="description">Worker has required certifications</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#workersContainer').append(workerHtml);
    }

    removeWorkerRow(workerId) {
        $(`#workerSection${workerId}`).remove();
    }

    // Optimized function for instant checklist loading with caching
    loadSpecificChecklistOptimized(workType = null) {
        if (!workType) {
            workType = document.getElementById('optPtwWorkType').value;
        }
        
        const container = document.getElementById('specificChecklistContainer');
        
        if (!workType || !this.workTypeChecklists[workType]) {
            container.innerHTML = workType ? 
                '<p class="text-warning">No specific requirements found for this work type.</p>' :
                '<p class="text-muted">Select a work type to see specific requirements.</p>';
            return;
        }

        // Check cache first for even faster loading
        if (this.checklistCache.has(workType)) {
            container.innerHTML = '';
            container.appendChild(this.checklistCache.get(workType).cloneNode(true));
            return;
        }

        // Use DocumentFragment for efficient DOM manipulation
        const fragment = document.createDocumentFragment();
        const rowDiv = document.createElement('div');
        rowDiv.className = 'row';
        
        this.workTypeChecklists[workType].forEach((item, index) => {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-6';
            
            const checkId = `chkSpecific${workType}${index}`;
            colDiv.innerHTML = `
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input specific-checklist" id="${checkId}" data-item="${item}">
                    <label class="form-check-label" for="${checkId}">${item}</label>
                </div>`;
            
            rowDiv.appendChild(colDiv);
        });
        
        fragment.appendChild(rowDiv);
        
        // Cache the result for future use
        this.checklistCache.set(workType, rowDiv.cloneNode(true));
        
        container.innerHTML = '';
        container.appendChild(fragment);
    }

    loadSpecificChecklist() {
        // Get work type using multiple methods to ensure we get the value
        const workTypeJQuery = $('#optPtwWorkType').val();
        const workTypeNative = document.getElementById('optPtwWorkType').value;
        const workType = workTypeJQuery || workTypeNative;
        
        const container = $('#specificChecklistContainer');
        
        console.log('Loading checklist for work type (jQuery):', workTypeJQuery);
        console.log('Loading checklist for work type (Native):', workTypeNative);
        console.log('Final work type used:', workType);
        container.empty();

        if (workType && workType !== '' && this.workTypeChecklists[workType]) {
            const items = this.workTypeChecklists[workType];
            let html = '<div class="row">';
            
            items.forEach((item, index) => {
                const checkId = `chkSpecific${workType}${index}`;
                const colClass = index % 2 === 0 ? 'col-md-6' : 'col-md-6';
                
                html += `
                    <div class="${colClass}">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input specific-checklist" id="${checkId}" data-item="${item}">
                            <label class="form-check-label" for="${checkId}">${item}</label>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
            console.log('Loaded', items.length, 'checklist items for', workType);
        } else {
            console.log('No checklist items found for work type:', workType);
            if (!workType || workType === '') {
                container.html('<p class="text-muted">Select a work type to see specific requirements.</p>');
            } else {
                container.html('<p class="text-warning">No specific requirements found for this work type.</p>');
            }
        }
    }

    syncWorkTypeSectionsAfterLoad(data) {
        console.log('Syncing work type sections after data load...');
        
        // Check if any hot work items were loaded and ensure main checkbox is checked and section is visible
        if (data.ptw_checklist_hot_work) {
            try {
                const hotWorkData = JSON.parse(data.ptw_checklist_hot_work);
                if ((typeof hotWorkData === 'object' && Object.keys(hotWorkData).length > 0) || 
                    (Array.isArray(hotWorkData) && hotWorkData.length > 0)) {
                    
                    // Check the main hot work checkbox
                    const hotWorkMainCheckbox = document.getElementById('chkHotWork');
                    if (hotWorkMainCheckbox) {
                        hotWorkMainCheckbox.checked = true;
                        console.log('Checked main hot work checkbox');
                    }
                    
                    // Show the hot work section
                    const hotWorkSection = document.getElementById('hotWorkSection');
                    if (hotWorkSection) {
                        hotWorkSection.style.display = 'block';
                        hotWorkSection.classList.remove('d-none');
                        console.log('Showed hot work section');
                    }
                }
            } catch (e) {
                console.error('Error syncing hot work section:', e);
            }
        }
        
        // Check if any cold work items were loaded and ensure main checkbox is checked and section is visible
        if (data.ptw_checklist_cold_work) {
            try {
                const coldWorkData = JSON.parse(data.ptw_checklist_cold_work);
                if ((typeof coldWorkData === 'object' && Object.keys(coldWorkData).length > 0) || 
                    (Array.isArray(coldWorkData) && coldWorkData.length > 0)) {
                    
                    // Check the main cold work checkbox
                    const coldWorkMainCheckbox = document.getElementById('chkColdWork');
                    if (coldWorkMainCheckbox) {
                        coldWorkMainCheckbox.checked = true;
                        console.log('Checked main cold work checkbox');
                    }
                    
                    // Show the cold work section
                    const coldWorkSection = document.getElementById('coldWorkSection');
                    if (coldWorkSection) {
                        coldWorkSection.style.display = 'block';
                        coldWorkSection.classList.remove('d-none');
                        console.log('Showed cold work section');
                    }
                }
            } catch (e) {
                console.error('Error syncing cold work section:', e);
            }
        }
        
        // Check if any confined space items were loaded and ensure main checkbox is checked and section is visible
        if (data.ptw_checklist_confined_space) {
            try {
                const confinedSpaceData = JSON.parse(data.ptw_checklist_confined_space);
                if ((typeof confinedSpaceData === 'object' && Object.keys(confinedSpaceData).length > 0) || 
                    (Array.isArray(confinedSpaceData) && confinedSpaceData.length > 0)) {
                    
                    // Check the main confined space checkbox
                    const confinedSpaceMainCheckbox = document.getElementById('chkConfinedSpace');
                    if (confinedSpaceMainCheckbox) {
                        confinedSpaceMainCheckbox.checked = true;
                        console.log('Checked main confined space checkbox');
                    }
                    
                    // Show the confined space section
                    const confinedSpaceSection = document.getElementById('confinedSpaceSection');
                    if (confinedSpaceSection) {
                        confinedSpaceSection.style.display = 'block';
                        confinedSpaceSection.classList.remove('d-none');
                        console.log('Showed confined space section');
                    }
                }
            } catch (e) {
                console.error('Error syncing confined space section:', e);
            }
        }
        
        console.log('Work type sections sync completed');
    }

    loadChecklistData(checklistData) {
        // Load general checklist
        Object.keys(checklistData).forEach(key => {
            const checkbox = $(`#${key}`);
            if (checkbox.length && checklistData[key]) {
                checkbox.prop('checked', true);
            }
        });

        // Load specific checklist
        if (checklistData.specific) {
            $('.specific-checklist').each(function() {
                const item = $(this).data('item');
                if (checklistData.specific[item]) {
                    $(this).prop('checked', true);
                }
            });
        }
    }

    validateDates() {
        const validFrom = $('#dtPtwValidFrom').val();
        const validTo = $('#dtPtwValidTo').val();

        if (validFrom && validTo) {
            const fromDate = new Date(validFrom);
            const toDate = new Date(validTo);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (fromDate < today) {
                showError('Valid from date cannot be in the past');
                $('#dtPtwValidFrom').val('');
                return false;
            }

            if (toDate <= fromDate) {
                showError('Valid to date must be after valid from date');
                $('#dtPtwValidTo').val('');
                return false;
            }
        }
        return true;
    }

    // updateRiskDisplay removed (risk level no longer used)

    validateForm() {
        console.log('validateForm called');
        let isValid = true;
        const errors = [];

        // Check if elements exist first
        const requiredElements = [
            'txtPtwDescription', 'txtPtwWorkArea', 'txtPtwApplicantName', 
            'dtPtwValidFrom', 'dtPtwValidTo', 'optPtwWorkType'
        ];
        
        const missingElements = [];
        requiredElements.forEach(id => {
            const element = document.getElementById(id);
            if (!element) {
                missingElements.push(id);
                console.error('Missing element:', id);
            }
        });
        
        if (missingElements.length > 0) {
            console.error('Missing required elements:', missingElements);
            errors.push('Form not properly initialized. Missing elements: ' + missingElements.join(', '));
            showError('Form validation error: Missing required form elements');
            return false;
        }

        // Basic validation with safe checks
        const description = $('#txtPtwDescription').val();
        console.log('Description value:', description);
        if (!description || !description.trim()) {
            errors.push('PTW Description is required');
            $('#txtPtwDescription').addClass('is-invalid');
            isValid = false;
        }

        const workArea = $('#txtPtwWorkArea').val();
        console.log('Work Area value:', workArea);
        if (!workArea || !workArea.trim()) {
            errors.push('Work Area is required');
            $('#txtPtwWorkArea').addClass('is-invalid');
            isValid = false;
        }

        if (!$('#optPtwWorkType').val()) {
            // Check if any work type checkboxes are selected as fallback
            const anyWorkTypeSelected = $('#chkColdWork').is(':checked') || 
                                      $('#chkHotWork').is(':checked') || 
                                      $('#chkConfinedSpace').is(':checked');
            
            if (!anyWorkTypeSelected) {
                errors.push('Work Type is required - please select at least one work type');
                isValid = false;
            } else {
                // Sync the dropdown if checkboxes are selected but dropdown is empty
                if (typeof window.updateWorkTypeDropdown === 'function') {
                    window.updateWorkTypeDropdown();
                }
            }
        }

        if (!$('#dtPtwValidFrom').val()) {
            errors.push('Valid From date is required');
            $('#dtPtwValidFrom').addClass('is-invalid');
            isValid = false;
        }

        if (!$('#dtPtwValidTo').val()) {
            errors.push('Valid To date is required');
            $('#dtPtwValidTo').addClass('is-invalid');
            isValid = false;
        }

        const applicantName = $('#txtPtwApplicantName').val();
        if (!applicantName || !applicantName.trim()) {
            errors.push('Applicant Name is required');
            $('#txtPtwApplicantName').addClass('is-invalid');
            isValid = false;
        }

    // Risk level validation removed

        // Validate workers
        const workers = this.getWorkersData();
        if (workers.length === 0) {
            errors.push('At least one worker is required');
            isValid = false;
        } else {
            workers.forEach((worker, index) => {
                if (!worker.name.trim()) {
                    errors.push(`Worker ${index + 1} name is required`);
                    $(`#txtWorkerName${worker.id}`).addClass('is-invalid');
                    isValid = false;
                }
            });
        }

        // Validate contractor declarations
        let declarationsComplete = true;
        for (let i = 1; i <= 6; i++) {
            const declarationName = `declaration${i}`;
            const selectedRadio = document.querySelector(`input[name="${declarationName}"]:checked`);
            if (!selectedRadio) {
                errors.push(`Contractor Declaration ${i} must be answered (Yes or No)`);
                declarationsComplete = false;
                isValid = false;
            }
        }

        // Validate contractor confirmation checkbox
        const contractorConfirmation = document.getElementById('contractorConfirmation');
        if (!contractorConfirmation || !contractorConfirmation.checked) {
            errors.push('Contractor Confirmation agreement is required');
            isValid = false;
        }

        // Date validation
        if (!this.validateDates()) {
            isValid = false;
        }

        if (!isValid) {
            showError('Please fix the following errors:\n' + errors.join('\n'));
        }

        return isValid;
    }

    getWorkersData() {
        const workers = [];
        
        // Check if using table format (HTML form) or div format (JS class)
        const tbody = document.querySelector('#workersTable tbody');
        if (tbody && tbody.children.length > 0) {
            // Table format - get data from table rows
            Array.from(tbody.children).forEach((row, index) => {
                const nameInput = row.querySelector('.worker-name');
                const designationInput = row.querySelector('.worker-designation');
                const idInput = row.querySelector('.worker-id');
                
                if (nameInput) {
                    const worker = {
                        id: index + 1,
                        name: nameInput.value || '',
                        contact_number: '', // Table format doesn't have contact - this is expected
                        role: designationInput ? designationInput.value || '' : '',
                        identification: idInput ? idInput.value || '' : '',
                        is_certified: false // Table format doesn't have certification checkbox
                    };
                    workers.push(worker);
                    console.log('Worker from table:', worker);
                }
            });
        } else {
            // Div format - original method
            $('.worker-section').each(function() {
                const sectionId = $(this).attr('id');
                const workerId = sectionId.replace('workerSection', '');
                
                const worker = {
                    id: workerId,
                    name: $(`#txtWorkerName${workerId}`).val(),
                    contact_number: $(`#txtWorkerContact${workerId}`).val(),
                    role: $(`#txtWorkerRole${workerId}`).val(),
                    is_certified: $(`#chkWorkerCertified${workerId}`).is(':checked')
                };
                
                workers.push(worker);
            });
        }
        
        return workers;
    }

    getChecklistData() {
        const checklist = {};
        
        console.log('Starting to collect checklist data...');

        // General checklist
        let generalCount = 0;
        $('.form-check-input').each(function() {
            if ($(this).attr('id') && $(this).attr('id').startsWith('chk') && !$(this).hasClass('specific-checklist') && !$(this).hasClass('worker-certified')) {
                checklist[$(this).attr('id')] = $(this).is(':checked');
                if ($(this).is(':checked')) {
                    generalCount++;
                    console.log(`Found checked general item: ${$(this).attr('id')}`);
                }
            }
        });

        // PPE checklist
        let ppeCount = 0;
        $('.form-check-input').each(function() {
            if ($(this).attr('id') && $(this).attr('id').startsWith('ppe')) {
                checklist[$(this).attr('id')] = $(this).is(':checked');
                if ($(this).is(':checked')) {
                    ppeCount++;
                    console.log(`Found checked PPE item: ${$(this).attr('id')}`);
                }
            }
        });
        
        // Add PPE others textarea
        const ppeOthersField = document.getElementById('ppe_others');
        if (ppeOthersField && ppeOthersField.value.trim() !== '') {
            checklist['ppe_others'] = ppeOthersField.value.trim();
            console.log(`Found PPE others text: ${ppeOthersField.value.trim()}`);
        }

        // Specific checklist (work type specific items)
        const specific = {};
        let specificCount = 0;
        $('.specific-checklist').each(function() {
            const item = $(this).data('item');
            const isChecked = $(this).is(':checked');
            specific[item] = isChecked;
            if (isChecked) {
                specificCount++;
                console.log(`Found checked specific item: ${item} (ID: ${$(this).attr('id')})`);
            }
        });
        
        if (Object.keys(specific).length > 0) {
            checklist.specific = specific;
        }
        
        console.log(`Checklist collection summary:`);
        console.log(`- General checklist items checked: ${generalCount}`);
        console.log(`- PPE checklist items checked: ${ppeCount}`);
        console.log(`- Specific work type items checked: ${specificCount} out of ${Object.keys(specific).length} total`);
        console.log('All checklist data:', checklist);

        return checklist;
    }

    getContractorDeclarations() {
        const declarations = {};
        
        // Collect contractor declaration radio button values
        for (let i = 1; i <= 6; i++) {
            const declarationName = `declaration${i}`;
            const selectedRadio = document.querySelector(`input[name="${declarationName}"]:checked`);
            if (selectedRadio) {
                declarations[declarationName] = selectedRadio.value;
            } else {
                declarations[declarationName] = null; // No selection made
            }
        }
        
        // Collect contractor confirmation checkbox
        const contractorConfirmation = document.getElementById('contractorConfirmation');
        if (contractorConfirmation) {
            declarations['contractorConfirmation'] = contractorConfirmation.checked;
        }
        
        // Collect PPE others field
        const ppeOthers = document.getElementById('ppeOthersSpecify');
        if (ppeOthers) {
            declarations['ppeOthersSpecify'] = ppeOthers.value;
        }
        
        console.log('Collected contractor declarations:', declarations);
        return declarations;
    }

    getCertificateData() {
        const certificates = {};
        
        // Main certificates with text fields
        const mainCertificateFields = [
            { checkbox: 'chkExcavationCert', input: 'certExcavation', name: 'excavationCert' },
            { checkbox: 'chkLiftingCert', input: 'certLifting', name: 'liftingCert' },
            { checkbox: 'chkPhysicalIsolation', input: 'certPhysicalIsolation', name: 'physicalIsolation' },
            { checkbox: 'chkElectricalIsolation', input: 'certElectricalIsolation', name: 'electricalIsolation' },
            { checkbox: 'chkConfinedSpaceCert', input: 'certConfinedSpace', name: 'confinedSpaceCert' },
            { checkbox: 'chkOthers', input: 'certOthers', name: 'othersCert' }
        ];
        
        // Sub-items (checkbox only, no text input)
        const subItems = [
            { checkbox: 'chkExcavationPlan', name: 'excavationPlan' },
            { checkbox: 'chkLiftingPlan', name: 'liftingPlan' },
            { checkbox: 'chkLiftingChecklist', name: 'liftingChecklist' }
        ];
        
        // Collect main certificates with text fields
        mainCertificateFields.forEach(field => {
            const checkbox = $(`#${field.checkbox}`);
            const input = $(`#${field.input}`);
            
            certificates[field.name] = {
                checked: checkbox.is(':checked'),
                value: checkbox.is(':checked') ? input.val() : ''
            };
        });
        
        // Collect sub-items (checkbox only)
        subItems.forEach(item => {
            const checkbox = $(`#${item.checkbox}`);
            
            certificates[item.name] = {
                checked: checkbox.is(':checked'),
                value: '' // No text input for sub-items
            };
        });
        
        console.log('Collected certificate data:', certificates);
        return certificates;
    }

    getCertificateCheckboxId(certKey) {
        const mapping = {
            'excavationCert': 'chkExcavationCert',
            'excavationPlan': 'chkExcavationPlan',
            'liftingCert': 'chkLiftingCert',
            'liftingPlan': 'chkLiftingPlan',
            'liftingChecklist': 'chkLiftingChecklist',
            'physicalIsolation': 'chkPhysicalIsolation',
            'electricalIsolation': 'chkElectricalIsolation',
            'confinedSpaceCert': 'chkConfinedSpaceCert',
            'othersCert': 'chkOthers'
        };
        return mapping[certKey] || null;
    }

    getCertificateInputId(certKey) {
        const mapping = {
            'excavationCert': 'certExcavation',
            'excavationPlan': null, // No input field for sub-item
            'liftingCert': 'certLifting',
            'liftingPlan': null, // No input field for sub-item
            'liftingChecklist': null, // No input field for sub-item
            'physicalIsolation': 'certPhysicalIsolation',
            'electricalIsolation': 'certElectricalIsolation',
            'confinedSpaceCert': 'certConfinedSpace',
            'othersCert': 'certOthers'
        };
        return mapping[certKey] || null;
    }

    getSupportingDocumentsData() {
        const supportingDocs = [];
        
        console.log('Starting to collect supporting documents data...');
        
        // Collect all checked supporting document checkboxes using actual HTML IDs
        const supportingDocMapping = [
            { id: 'docCalibrationCert', value: 'calibration_certificate' },
            { id: 'docLoadChart', value: 'load_chart' },
            { id: 'docToolboxTalk', value: 'toolbox_talk_record' },
            { id: 'docSafetyDataSheet', value: 'safety_data_sheet' },
            { id: 'docJobMethodStatement', value: 'job_method_statement' },
            { id: 'docJobHazardAnalysis', value: 'job_hazard_analysis' },
            { id: 'docCertificateOfFitness', value: 'certificate_of_fitness' },
            { id: 'docCidbGreenCard', value: 'cidb_green_card' },
            { id: 'docAespCard', value: 'aesp_card' },
            { id: 'docCompetencyPersonCert', value: 'competency_person_certificate' },
            { id: 'docContractorChecklist', value: 'contractor_checklist' },
            { id: 'docMedicalCheckUp', value: 'medical_check_up' },
            { id: 'docHirarcEaia', value: 'hirarc_eaia' },
            { id: 'docTrafficManagementPlan', value: 'traffic_management_plan' }
        ];
        
        supportingDocMapping.forEach(doc => {
            const checkbox = document.getElementById(doc.id);
            if (checkbox && checkbox.checked) {
                supportingDocs.push(doc.value);
                console.log(`Found checked supporting document: ${doc.id} -> ${doc.value}`);
            }
        });
        
        console.log('Collected supporting documents:', supportingDocs);
        return supportingDocs;
    }

    getHazardousActivitiesData() {
        const hazards = {};
        
        console.log('Starting to collect hazardous activities data...');
        
        // Collect all hazardous activity checkboxes
        const hazardIds = [
            'hazSlipperyFloor', 'hazSharpObjects', 'hazHotSurface', 'hazMovingVehicle',
            'hazVolatileLiquid', 'hazIlluminationDeficiency', 'hazRotatingParts', 'hazDroppedObjects',
            'hazFallFromHeight', 'hazElectrical', 'hazGasFumes', 'hazSimultaneousOperation',
            'hazChemical', 'hazIonizingRadiation', 'hazUnevenGround', 'hazExtremeWeather',
            'hazFlyingDebris', 'hazWildAnimals', 'hazOxygenDeficiency', 'hazPoisonousSubstance',
            'hazInfectiousSubstance', 'hazPinchPoint', 'hazExcessiveNoise', 'hazErgonomicFatigue',
            'hazPressurizedEquipment', 'hazEngulfmentEquipment', 'hazMolds'
        ];
        
        let checkedCount = 0;
        hazardIds.forEach(hazardId => {
            const checkbox = $(`#${hazardId}`);
            if (checkbox.length) {
                const isChecked = checkbox.is(':checked');
                hazards[hazardId] = isChecked;
                if (isChecked) {
                    checkedCount++;
                    console.log(`Found checked hazard: ${hazardId}`);
                }
            } else {
                console.warn(`Hazard checkbox not found: ${hazardId}`);
            }
        });
        
        // Handle "Others" field with text input
        const othersCheckbox = $('#hazOthers');
        const othersText = $('#hazOthersText');
        
        if (othersCheckbox.length) {
            const isOthersChecked = othersCheckbox.is(':checked');
            hazards['hazOthers'] = {
                checked: isOthersChecked,
                value: isOthersChecked ? othersText.val() : ''
            };
            if (isOthersChecked) {
                checkedCount++;
                console.log(`Found checked hazard: hazOthers with value: ${othersText.val()}`);
            }
        } else {
            console.warn('Others hazard checkbox not found: hazOthers');
        }
        
        console.log(`Collected hazardous activities: ${checkedCount} items checked out of ${hazardIds.length + 1} total`);
        console.log('All hazardous activities data:', hazards);
        return hazards;
    }

    getWorkTypeSpecificChecklists() {
        const workTypeChecklists = {
            hot_work: null,
            cold_work: null,
            confined_space: null
        };
        
        console.log('Starting to collect work type specific checklists...');
        
        // Collect hot work checklist items
        const hotWorkItems = {};
        const hotWorkCheckboxes = document.querySelectorAll('#hotWorkSection input[type="checkbox"]');
        console.log(`Found ${hotWorkCheckboxes.length} hot work checkboxes`);
        hotWorkCheckboxes.forEach(checkbox => {
            console.log(`Hot work checkbox: ${checkbox.id}, checked: ${checkbox.checked}`);
            if (checkbox.checked) {
                const label = checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent.trim() : checkbox.id;
                hotWorkItems[checkbox.id] = label;
                console.log(`Added hot work item: ${checkbox.id} = ${label}`);

                // Handle "Others" text field for hot work
                if (checkbox.id === 'hotOthers') {
                    const hotOthersText = document.getElementById('hotOthersText');
                    if (hotOthersText && hotOthersText.value.trim()) {
                        hotWorkItems['hotOthersText'] = hotOthersText.value.trim();
                        console.log(`Added hot work others text: ${hotOthersText.value.trim()}`);
                    }
                }
            }
        });
        // Also collect special precautions text area for hot work
        const hotSpecialPrecautions = document.getElementById('hotSpecialPrecautions');
        if (hotSpecialPrecautions && hotSpecialPrecautions.value.trim()) {
            hotWorkItems['hotSpecialPrecautions'] = hotSpecialPrecautions.value.trim();
            console.log(`Added hot work special precautions: ${hotSpecialPrecautions.value.trim()}`);
        }
        if (Object.keys(hotWorkItems).length > 0) {
            workTypeChecklists.hot_work = hotWorkItems;
        }
        
        // Collect cold work checklist items
        const coldWorkItems = {};
        const coldWorkCheckboxes = document.querySelectorAll('#coldWorkSection input[type="checkbox"]');
        console.log(`Found ${coldWorkCheckboxes.length} cold work checkboxes`);
        coldWorkCheckboxes.forEach(checkbox => {
            console.log(`Cold work checkbox: ${checkbox.id}, checked: ${checkbox.checked}`);
            if (checkbox.checked) {
                const label = checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent.trim() : checkbox.id;
                coldWorkItems[checkbox.id] = label;
                console.log(`Added cold work item: ${checkbox.id} = ${label}`);
                
                // Handle "Others" text field for cold work
                if (checkbox.id === 'coldOthers') {
                    const othersText = document.getElementById('coldOthersText');
                    if (othersText && othersText.value.trim()) {
                        coldWorkItems[checkbox.id] = othersText.value.trim();
                        console.log(`Added cold work others text: ${othersText.value.trim()}`);
                    }
                }
            }
        });
        
        // Also collect special precautions text area for cold work
        const coldSpecialPrecautions = document.getElementById('coldSpecialPrecautions');
        if (coldSpecialPrecautions && coldSpecialPrecautions.value.trim()) {
            coldWorkItems['coldSpecialPrecautions'] = coldSpecialPrecautions.value.trim();
            console.log(`Added cold work special precautions: ${coldSpecialPrecautions.value.trim()}`);
        }
        
        if (Object.keys(coldWorkItems).length > 0) {
            workTypeChecklists.cold_work = coldWorkItems;
        }
        
        // Collect confined space checklist items
        const confinedSpaceItems = {};
        const confinedSpaceCheckboxes = document.querySelectorAll('#confinedSpaceSection input[type="checkbox"]');
        console.log(`Found ${confinedSpaceCheckboxes.length} confined space checkboxes`);
        confinedSpaceCheckboxes.forEach(checkbox => {
            console.log(`Confined space checkbox: ${checkbox.id}, checked: ${checkbox.checked}`);
            if (checkbox.checked) {
                const label = checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent.trim() : checkbox.id;
                confinedSpaceItems[checkbox.id] = label;
                console.log(`Added confined space item: ${checkbox.id} = ${label}`);
            }
        });
        // Collect confined space special precautions text
        const csSpecialPrecautions = document.getElementById('csSpecialPrecautions');
        if (csSpecialPrecautions && csSpecialPrecautions.value.trim()) {
            confinedSpaceItems['csSpecialPrecautions'] = csSpecialPrecautions.value.trim();
            console.log(`Added confined space special precautions: ${csSpecialPrecautions.value.trim()}`);
        }
        if (Object.keys(confinedSpaceItems).length > 0) {
            workTypeChecklists.confined_space = confinedSpaceItems;
        }
        
        console.log('Work type specific checklists collected:', workTypeChecklists);
        return workTypeChecklists;
    }

    resetForm() {
        console.log('Resetting form for next submission...');
        
        // Clear all form inputs
        $('#txtPtwDescription, #txtPtwWorkArea, #txtPtwHazards, #txtPtwControlMeasures').val('');
        $('#txtPtwApplicantName, #txtPtwApplicantContact, #txtPtwApplicantDept').val('');
        $('#txtPtwContractorCompany, #txtPtwRemarks').val('');
        
    // Reset select fields
    $('#optPtwWorkType').prop('selectedIndex', 0);
        
        // Reset date fields
        $('#dtPtwValidFrom, #dtPtwValidTo').val('');
        
        // Clear workers table
        $('#workersTable tbody').empty();
        
        // Reset all checkboxes
        $('input[type="checkbox"]').prop('checked', false);
        
        // Reset all radio buttons (contractor declarations)
        $('input[type="radio"]').prop('checked', false);
        
        // Clear PPE others field
        $('#ppeOthersSpecify').val('');

        // Clear signature related fields / canvases
        if (typeof window.clearContractorSignature === 'function') {
            try { window.clearContractorSignature(); } catch(e) { console.warn('clearContractorSignature error', e); }
        }
        $('#contractor_signature, #contractor_name, #contractor_designation, #contractor_date').val('');
        const sigCanvas = document.getElementById('contractorSignaturePad');
        if (sigCanvas && sigCanvas.getContext) {
            try { const ctx = sigCanvas.getContext('2d'); ctx.clearRect(0,0,sigCanvas.width,sigCanvas.height); } catch(e) {}
        }

        // Clear any locally cached uploaded documents util if exists
        if (typeof window.clearUploadedDocuments === 'function') {
            try { window.clearUploadedDocuments(); } catch(e) { console.warn('clearUploadedDocuments error', e); }
        }
        // Also clear file input elements
        $('input[type="file"]').val('');
        
        // Clear certificate input fields and disable them (only main certificates have input fields)
        $('#certExcavation, #certLifting, #certPhysicalIsolation, #certElectricalIsolation').val('').prop('disabled', true);
        $('#certConfinedSpace, #certOthers').val('').prop('disabled', true);
        
        // Clear hazardous activities others field
        $('#hazOthersText').val('').prop('disabled', true);
        
        // Clear any validation errors
        $('.is-invalid').removeClass('is-invalid');
        
        // Re-enable submit button
        $('#btnSubmitApproval').prop('disabled', false);
        
        console.log('Form reset completed');
    }

    savePtw(status = 'PENDING_APPROVAL') {
        console.log('savePtw called with status:', status);
        // Guard: site_id required for public submissions
        if (this.isPublicMode) {
            if (!this.siteId) {
                console.error('Attempted to submit without site_id. Blocking.');
                if (typeof showError === 'function') {
                    showError('This public PTW form is not linked to a site. Please use a site-specific link.');
                }
                this.showMissingSiteOverlay();
                return;
            }
        }
        
        // Ensure work type synchronization before validation
        if (typeof window.updateWorkTypeDropdown === 'function') {
            window.updateWorkTypeDropdown();
            console.log('Work type synchronized before validation:', $('#optPtwWorkType').val());
        }
        
        if (!this.validateForm()) {
            console.log('Validation failed, returning');
            return;
        }

        console.log('Starting PTW save process...');
        ShowLoader();

        // Collect all selected work types for multiple selection support
        const selectedWorkTypes = [];
        if ($('#chkColdWork').is(':checked')) {
            selectedWorkTypes.push('COLD_WORK');
        }
        if ($('#chkHotWork').is(':checked')) {
            selectedWorkTypes.push('HOT_WORK');
        }
        if ($('#chkConfinedSpace').is(':checked')) {
            selectedWorkTypes.push('CONFINED_SPACE');
        }
        
        // Final check: if work_type is empty but checkboxes are selected, fix it
        let workType = $('#optPtwWorkType').val();
        console.log('Initial work type from dropdown:', workType);
        console.log('Cold work checkbox checked:', $('#chkColdWork').is(':checked'));
        console.log('Hot work checkbox checked:', $('#chkHotWork').is(':checked'));
        console.log('Confined space checkbox checked:', $('#chkConfinedSpace').is(':checked'));
        console.log('All selected work types:', selectedWorkTypes);
        
        if (!workType) {
            // Priority order: Hot Work > Confined Space > Cold Work
            // If multiple are selected, we choose the highest priority one for the primary work type
            if ($('#chkHotWork').is(':checked')) {
                workType = 'HOT_WORK';
                $('#optPtwWorkType').val('HOT_WORK');
                console.log('Work type set to HOT_WORK (hot work checkbox checked)');
            } else if ($('#chkConfinedSpace').is(':checked')) {
                workType = 'CONFINED_SPACE';
                $('#optPtwWorkType').val('CONFINED_SPACE');
                console.log('Work type set to CONFINED_SPACE (confined space checkbox checked)');
            } else if ($('#chkColdWork').is(':checked')) {
                workType = 'COLD_WORK';
                $('#optPtwWorkType').val('COLD_WORK');
                console.log('Work type set to COLD_WORK (cold work checkbox checked)');
            }
            console.log('Work type fixed from checkboxes:', workType);
        } else {
            console.log('Work type already set from dropdown:', workType);
        }

    const formData = {
            action: 'create_permit', // Always create for public forms
            work_description: $('#txtPtwDescription').val(),
            work_area: $('#txtPtwWorkArea').val(),
            work_type: workType,
            work_types_selected: JSON.stringify(selectedWorkTypes),
            valid_from: $('#dtPtwValidFrom').val(),
            valid_to: $('#dtPtwValidTo').val(),
            hazards: $('#txtPtwHazards').val(),
            control_measures: $('#txtPtwControlMeasures').val(),
            applicant_name: $('#txtPtwApplicantName').val(),
            applicant_contact: $('#txtPtwApplicantContact').val(),
            applicant_department: $('#txtPtwApplicantDept').val(),
            contractor_company: $('#txtPtwContractorCompany').val(),
            contractor_supervisor: $('#txtContractorSupervisor').val(),
            contractor_name: $('#contractor_name').val(),
            contractor_designation: $('#contractor_designation').val(),
            contractor_date: $('#contractor_date').val(),
            // PNG data URL from the signature pad's hidden input (e.g., data:image/png;base64,...)
            contractor_signature: ($('#contractor_signature').val() || ''),
            staff_nric: $('#txtStaffNoNRIC').val(),
            supervisor_contact: $('#txtContactNoSupervisor').val(),
            identification_no: $('#txtIdentificationNo').val(),
            level: $('#txtLevel').val() || '',
            remarks: $('#txtPtwRemarks').val(),
            status: status,
            // Signal backend to submit and assign Request Number when not a draft
            submit_for_approval: (status && status !== 'DRAFT') ? 'true' : 'false',
            workers: this.getWorkersData(),
            checklist_data: JSON.stringify(this.getChecklistData()),
            declaration_checklist: JSON.stringify(this.getContractorDeclarations()),
            certificate_numbers: JSON.stringify(this.getCertificateData()),
            supporting_docs_checklist: JSON.stringify(this.getSupportingDocumentsData()),
            hazardous_activities: JSON.stringify(this.getHazardousActivitiesData()),
            public_user: 'Public User', // Assign to public user
            site_id: this.siteId // Bind to site
        };

        // Add work type specific checklists as separate fields
        const workTypeChecklists = this.getWorkTypeSpecificChecklists();
        if (workTypeChecklists.hot_work) {
            formData.checklist_hot_work = JSON.stringify(workTypeChecklists.hot_work);
        }
        if (workTypeChecklists.cold_work) {
            formData.checklist_cold_work = JSON.stringify(workTypeChecklists.cold_work);
        }
        if (workTypeChecklists.confined_space) {
            formData.checklist_confined_space = JSON.stringify(workTypeChecklists.confined_space);
        }

        console.log('Form data prepared for public submission:', formData);
        console.log('Primary work type being sent:', formData.work_type);
        console.log('All selected work types being sent:', formData.work_types_selected);
        console.log('Selected work types array:', selectedWorkTypes);
        console.log('Contractor supervisor being sent:', formData.contractor_supervisor);
        console.log('Staff NRIC being sent:', formData.staff_nric);
        console.log('Supervisor contact being sent:', formData.supervisor_contact);
        console.log('Identification number being sent:', formData.identification_no);
        console.log('Level being sent:', formData.level);
        console.log('Workers data being sent:', formData.workers);
        console.log('Checklist data being sent:', formData.checklist_data);
        console.log('Hot work checklist being sent:', formData.checklist_hot_work);
        console.log('Cold work checklist being sent:', formData.checklist_cold_work);
        console.log('Confined space checklist being sent:', formData.checklist_confined_space);
        console.log('Hazardous activities being sent:', formData.hazardous_activities);
        console.log('API URL:', this.apiUrl);
        console.log('Starting public PTW submission process...');

        // Disable submit button to prevent double submission
        $('#btnSubmitApproval').prop('disabled', true);

        // No authentication required for public forms
        const headers = {};
        
        $.ajax({
            url: this.apiUrl,
            type: 'POST',
            data: formData,
            headers: headers,
            success: (response) => {
                console.log('AJAX Success Response:', response);
                if (response.success === true) {
                    const res = response.result || response;
                    const refNo = (res.ptw_permit_number && res.ptw_permit_number !== '')
                        ? res.ptw_permit_number
                        : (res.ptw_request_number || '');
                    const suffix = refNo ? ` Reference: ${refNo}` : '';
                    const permitId = res.ptw_permit_id || res.permit_id;

                    // Build public view URL for QR
                    const origin = window.location.origin || '';
                    const basePath = window.location.pathname.replace(/[^\/]*$/, '');
                    const tokenParam = res.public_token ? `&t=${encodeURIComponent(res.public_token)}` : '';
                    const viewUrl = `${origin}${basePath}ptw_form.html?mode=view&id=${encodeURIComponent(refNo || permitId || '')}${tokenParam}`;

                    // If there are uploaded documents (from ptw_form.html), upload them now
                    const pendingDocs = (typeof window.getUploadedDocuments === 'function') ? window.getUploadedDocuments() : [];
                    if (permitId && Array.isArray(pendingDocs) && pendingDocs.length > 0) {
                        console.log(`Uploading ${pendingDocs.length} document(s) for permit ${permitId}...`);
                        const fd = new FormData();
                        fd.append('permit_id', String(permitId));
                        if (this.siteId) fd.append('site_id', String(this.siteId));
                        pendingDocs.forEach((doc) => {
                            if (doc && doc.file) {
                                fd.append('files[]', doc.file, doc.name || 'document');
                            }
                        });

                        fetch('api/ptw_document_upload.php', { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(up => {
                                if (up && up.success) {
                                    showSuccess(`PTW submitted successfully!${suffix} Documents uploaded: ${pendingDocs.length}`);
                                    if (typeof window.clearUploadedDocuments === 'function') {
                                        window.clearUploadedDocuments();
                                    }
                                } else {
                                    console.warn('Document upload failed:', up);
                                    showError('PTW submitted, but document upload failed: ' + (up && up.message ? up.message : 'Unknown'));
                                }
                            })
                            .catch(err => {
                                console.error('Upload error:', err);
                                showError('PTW submitted, but document upload failed: ' + err.message);
                            })
                            .finally(() => {
                                // Show QR code modal for tracking
                                try {
                                    this.showQrModal(refNo, viewUrl);
                                } catch (e) { console.warn('QR modal error:', e); }
                                console.log('Public PTW submission successful! Clearing form...');
                                setTimeout(() => {
                                    this.resetForm();
                                    showSuccess('Form cleared for next submission.');
                                }, 1500);
                            });
                    } else {
                        showSuccess(`PTW submitted successfully!${suffix}`);
                        // Show QR code modal for tracking
                        try {
                            this.showQrModal(refNo, viewUrl);
                        } catch (e) { console.warn('QR modal error:', e); }
                        console.log('Public PTW submission successful! Clearing form...');
                        setTimeout(() => {
                            this.resetForm();
                            showSuccess('Form cleared for next submission.');
                        }, 1500);
                    }
                } else {
                    console.log('API returned error:', response.message);
                    showError(response.message || 'Failed to save PTW');
                    // Re-enable button on error
                    $('#btnSubmitApproval').prop('disabled', false);
                }
                HideLoader();
            },
            error: (xhr, status, error) => {
                console.error('AJAX Error Details:');
                console.error('XHR:', xhr);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                showError('Failed to save PTW: ' + error);
                
                // Re-enable button on error
                $('#btnSubmitApproval').prop('disabled', false);
                HideLoader();
            }
        });
    }

    setupValidation() {
        // Remove invalid class on input
        $('.form-control').on('input change', function() {
            $(this).removeClass('is-invalid');
        });

        // Real-time validation for specific fields
        $('#txtPtwDescription').on('blur', function() {
            const value = $(this).val();
            if (!value || !value.trim()) {
                $(this).addClass('is-invalid');
                $('#txtPtwDescriptionErr').text('PTW Description is required');
            }
        });

        $('#txtPtwWorkArea').on('blur', function() {
            const value = $(this).val();
            if (!value || !value.trim()) {
                $(this).addClass('is-invalid');
                $('#txtPtwWorkAreaErr').text('Work Area is required');
            }
        });

        $('#txtPtwApplicantName').on('blur', function() {
            const value = $(this).val();
            if (!value || !value.trim()) {
                $(this).addClass('is-invalid');
                $('#txtPtwApplicantNameErr').text('Applicant Name is required');
            }
        });
    }
}

// Global functions for SHE actions
function approvePtwByShe() {
    const remarks = $('#sheRemarks').val();
    
    Swal.fire({
        title: 'Confirm SHE Approval',
        text: 'Are you sure you want to approve this PTW permit?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            processSheApprovalAction('approve', remarks);
        }
    });
}

function rejectPtwByShe() {
    const remarks = $('#sheRemarks').val();
    
    if (!remarks.trim()) {
        Swal.fire({
            title: 'Remarks Required',
            text: 'Please provide remarks for rejection.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm SHE Rejection',
        text: 'Are you sure you want to reject this PTW permit?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            processSheApprovalAction('reject', remarks);
        }
    });
}

function processSheApprovalAction(action, remarks) {
    ShowLoader();
    
    $.ajax({
        url: 'api/ptw_approve.php',
        type: 'POST',
        data: {
            action: action === 'approve' ? 'she_approve' : 'she_reject',
            permit_id: ptwFormClass_.ptwId,
            remarks: remarks
        },
        headers: {
            'Authorization': 'Bearer ' + (sessionStorage.getItem('token') || 'valid_test_token_for_fm_dashboard')
        },
        success: function(response) {
            if (response.status === 'success') {
                const message = action === 'approve' ? 
                    'PTW permit approved successfully and forwarded to Facility Manager' : 
                    'PTW permit rejected successfully';
                    
                Swal.fire({
                    title: 'Success',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.close();
                });
            } else {
                showError(response.message || `Failed to ${action} permit`);
            }
            HideLoader();
        },
        error: function(xhr, status, error) {
            console.error(`SHE ${action} Error:`, error);
            showError(`Failed to ${action} permit`);
            HideLoader();
        }
    });
}

// Global functions for FM actions (for future use)
function approvePtwByFm() {
    const remarks = $('#fmRemarks').val();
    
    Swal.fire({
        title: 'Confirm Final Approval',
        text: 'Are you sure you want to give final approval to this PTW permit?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Final Approve',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            processFmApprovalAction('approve', remarks);
        }
    });
}

function rejectPtwByFm() {
    const remarks = $('#fmRemarks').val();
    
    if (!remarks.trim()) {
        Swal.fire({
            title: 'Remarks Required',
            text: 'Please provide remarks for rejection.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm FM Rejection',
        text: 'Are you sure you want to reject this PTW permit?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            processFmApprovalAction('reject', remarks);
        }
    });
}

function processFmApprovalAction(action, remarks) {
    ShowLoader();
    
    $.ajax({
        url: 'api/ptw_approve.php',
        type: 'POST',
        data: {
            action: action === 'approve' ? 'fm_approve' : 'fm_reject',
            permit_id: ptwFormClass_.ptwId,
            remarks: remarks
        },
        headers: {
            'Authorization': 'Bearer ' + (sessionStorage.getItem('token') || 'valid_test_token_for_fm_dashboard')
        },
        success: function(response) {
            if (response.status === 'success') {
                const message = action === 'approve' ? 
                    'PTW permit given final approval and is now ready for activation' : 
                    'PTW permit rejected successfully';
                    
                Swal.fire({
                    title: 'Success',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.close();
                });
            } else {
                showError(response.message || `Failed to ${action} permit`);
            }
            HideLoader();
        },
        error: function(xhr, status, error) {
            console.error(`FM ${action} Error:`, error);
            showError(`Failed to ${action} permit`);
            HideLoader();
        }
    });
}

// Add helper to show QR modal
PtwForm.prototype.showQrModal = function(refNo, viewUrl) {
    const idOrRef = refNo || 'Your PTW';
    const containerId = 'ptwQrContainer_' + Date.now();
    const copyBtnId = 'ptwQrCopyBtn_' + Date.now();
    const downloadBtnId = 'ptwQrDownloadBtn_' + Date.now();
    const html = `
        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <div id="${containerId}" style="padding:8px;border:1px dashed #ccc;border-radius:8px;background:#fff;"></div>
            <div style="font-size:14px;word-break:break-all;text-align:center;max-width:100%;">
                <a href="${viewUrl}" target="_blank" rel="noopener">${viewUrl}</a>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:4px;">
                <button type="button" id="${copyBtnId}" class="swal2-styled" style="background:#17a2b8;border:0;">Copy link</button>
                <button type="button" id="${downloadBtnId}" class="swal2-styled" style="background:#6c757d;border:0;">Download QR</button>
            </div>
        </div>`;

    Swal.fire({
        title: `Track your PTW` + (refNo ? ` (${idOrRef})` : ''),
        html,
        width: 420,
        showCloseButton: true,
        confirmButtonText: 'Done',
        showDenyButton: true,
        denyButtonText: 'Open link',
        didOpen: () => {
            try {
                const el = document.getElementById(containerId);
                if (el && typeof QRCode !== 'undefined') {
                    // Instantiate and then call makeCode (pattern used across the app)
                    const qr = new QRCode(el, {
                        width: 220,
                        height: 220,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    if (qr && typeof qr.makeCode === 'function') {
                        qr.makeCode(viewUrl);
                    }
                } else {
                    el && (el.textContent = 'QR library unavailable');
                }

                // Wire actions
                const copyBtn = document.getElementById(copyBtnId);
                if (copyBtn) {
                    copyBtn.addEventListener('click', async () => {
                        try {
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                await navigator.clipboard.writeText(viewUrl);
                            } else {
                                const inp = document.createElement('input');
                                inp.value = viewUrl;
                                document.body.appendChild(inp);
                                inp.select();
                                document.execCommand('copy');
                                document.body.removeChild(inp);
                            }
                            Swal.showValidationMessage(''); // clear
                            Swal.update({});
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                timer: 1500,
                                showConfirmButton: false,
                                icon: 'success',
                                title: 'Link copied'
                            });
                        } catch (err) {
                            Swal.fire('Copy failed', 'Please copy the link manually.', 'info');
                        }
                    });
                }

                const downloadBtn = document.getElementById(downloadBtnId);
                if (downloadBtn) {
                    downloadBtn.addEventListener('click', () => {
                        try {
                            const container = document.getElementById(containerId);
                            if (!container) return;
                            const img = container.querySelector('img');
                            const canvas = container.querySelector('canvas');
                            let dataUrl = '';
                            if (img && img.src) {
                                dataUrl = img.src;
                            } else if (canvas && canvas.toDataURL) {
                                dataUrl = canvas.toDataURL('image/png');
                            }
                            if (dataUrl) {
                                const a = document.createElement('a');
                                const safeRef = (refNo || 'ptw').toString().replace(/[^a-z0-9_-]+/gi, '-').toLowerCase();
                                a.download = `ptw-qr-${safeRef}.png`;
                                a.href = dataUrl;
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                            }
                        } catch (e) {
                            Swal.fire('Download failed', 'Unable to download the QR image.', 'error');
                        }
                    });
                }
            } catch (e) {
                console.warn('QR render error', e);
            }
        }
    }).then((result) => {
        if (result.isDenied) {
            try { window.open(viewUrl, '_blank', 'noopener'); } catch (e) {}
        }
        // Always reload after user is done interacting with QR modal
        window.location.reload();
    });
};
