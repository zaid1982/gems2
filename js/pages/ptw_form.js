/**
 * PTW Form Management Class
 * Handles PTW form creation, validation, and submission
 */
class PtwForm {
    constructor() {
        this.apiUrl = 'api/ptw.php';
        this.userSite = null;
        this.refSite = [];
        this.refUser = [];
        this.ptwId = null;
        this.workerCounter = 0;
        this.checklistCache = new Map(); // Performance cache for rendered checklists
        this.userMode = null; // Will be set based on URL params (she_review, fm_review, etc.)
        this.workTypeChecklists = {
            'HOT_WORK': [
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
        // Immediate setup - no setTimeout needed
        this.setupEventHandlers();
        this.initializeForm();
        this.setupValidation();
        this.loadUrlParams();
        
        // Initialize with optimized checklist loading
        this.loadSpecificChecklistOptimized();
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

        // Save draft button
        $('#btnSaveDraft').on('click', (e) => {
            e.preventDefault(); // Prevent form submission
            console.log('Save Draft button clicked');
            this.savePtw('DRAFT');
        });

        // Submit for approval button
        $('#btnSubmitApproval').on('click', (e) => {
            e.preventDefault(); // Prevent form submission
            console.log('Submit for Approval button clicked');
            if (this.validateForm()) {
                this.savePtw('PENDING_APPROVAL');
            } else {
                console.log('Form validation failed');
            }
        });

        // Risk level change
        $('#optPtwRiskLevel').on('change', (e) => {
            this.updateRiskDisplay();
        });

        // Form validation handlers
        $('#txtPtwDescription, #txtPtwWorkArea, #txtPtwApplicantName').on('blur', function() {
            $(this).removeClass('is-invalid');
        });
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
        console.log('Risk Level dropdown found:', $('#optPtwRiskLevel').length > 0);
        
        // Force refresh dropdown styling
        this.refreshDropdowns();
    }

    refreshDropdowns() {
        // Ensure dropdowns are properly styled and functional
        console.log('Refreshing dropdown functionality...');
        
        const workTypeSelect = document.getElementById('optPtwWorkType');
        const riskLevelSelect = document.getElementById('optPtwRiskLevel');
        
        [workTypeSelect, riskLevelSelect].forEach(select => {
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
        ShowLoader();
        
        $.ajax({
            url: this.apiUrl,
            type: 'GET',
            data: {
                action: 'get_permit',
                permit_id: ptwId
            },
            headers: {
                'Authorization': 'Bearer ' + sessionStorage.getItem('token')
            },
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    this.populateForm(response.data);
                } else {
                    showError(response.message || 'Failed to load PTW data');
                }
                HideLoader();
            },
            error: (xhr, status, error) => {
                console.error('Load PTW Error:', error);
                showError('Failed to load PTW data');
                HideLoader();
            }
        });
    }

    populateForm(data) {
        // Basic information
        $('#txtPtwDescription').val(data.description);
        $('#txtPtwWorkArea').val(data.work_area);
        $('#optPtwWorkType').val(data.work_type);
        $('#dtPtwValidFrom').val(data.valid_from);
        $('#dtPtwValidTo').val(data.valid_to);

        // Risk assessment
        $('#optPtwRiskLevel').val(data.risk_level);
        $('#txtPtwHazards').val(data.hazards);
        $('#txtPtwControlMeasures').val(data.control_measures);

        // Applicant information
        $('#txtPtwApplicantName').val(data.applicant_name);
        $('#txtPtwApplicantContact').val(data.applicant_contact);
        $('#txtPtwApplicantDept').val(data.applicant_department);
        $('#txtPtwContractorCompany').val(data.contractor_company);
        $('#txtPtwRemarks').val(data.remarks);

        // Update selects - removed MDB select initialization since we're using standard selects now

        // Load workers
        if (data.workers && data.workers.length > 0) {
            $('#workersContainer').empty();
            this.workerCounter = 0;
            data.workers.forEach(worker => {
                this.addWorkerRow(worker);
            });
        }

        // Load checklist
        if (data.checklist_data) {
            try {
                const checklistData = JSON.parse(data.checklist_data);
                this.loadChecklistData(checklistData);
            } catch (e) {
                console.error('Error parsing checklist data:', e);
            }
        }

        // Load specific checklist for work type
        this.loadSpecificChecklistOptimized();
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
                toastr['warning']('Valid from date cannot be in the past', 'Date Validation');
                $('#dtPtwValidFrom').val('');
                return false;
            }

            if (toDate <= fromDate) {
                toastr['warning']('Valid to date must be after valid from date', 'Date Validation');
                $('#dtPtwValidTo').val('');
                return false;
            }
        }
        return true;
    }

    updateRiskDisplay() {
        const riskLevel = $('#optPtwRiskLevel').val();
        // Add visual feedback for risk level if needed
        console.log('Risk level changed to:', riskLevel);
    }

    validateForm() {
        let isValid = true;
        const errors = [];

        // Basic validation
        if (!$('#txtPtwDescription').val().trim()) {
            errors.push('PTW Description is required');
            $('#txtPtwDescription').addClass('is-invalid');
            isValid = false;
        }

        if (!$('#txtPtwWorkArea').val().trim()) {
            errors.push('Work Area is required');
            $('#txtPtwWorkArea').addClass('is-invalid');
            isValid = false;
        }

        if (!$('#optPtwWorkType').val()) {
            errors.push('Work Type is required');
            isValid = false;
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

        if (!$('#txtPtwApplicantName').val().trim()) {
            errors.push('Applicant Name is required');
            $('#txtPtwApplicantName').addClass('is-invalid');
            isValid = false;
        }

        if (!$('#optPtwRiskLevel').val()) {
            errors.push('Risk Level is required');
            isValid = false;
        }

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

        // Date validation
        if (!this.validateDates()) {
            isValid = false;
        }

        if (!isValid) {
            toastr['error']('Please fix the following errors:\n' + errors.join('\n'), 'Validation Error');
        }

        return isValid;
    }

    getWorkersData() {
        const workers = [];
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
        return workers;
    }

    getChecklistData() {
        const checklist = {};

        // General checklist
        $('.form-check-input').each(function() {
            if ($(this).attr('id') && $(this).attr('id').startsWith('chk') && !$(this).hasClass('specific-checklist') && !$(this).hasClass('worker-certified')) {
                checklist[$(this).attr('id')] = $(this).is(':checked');
            }
        });

        // Specific checklist
        const specific = {};
        $('.specific-checklist').each(function() {
            const item = $(this).data('item');
            specific[item] = $(this).is(':checked');
        });
        
        if (Object.keys(specific).length > 0) {
            checklist.specific = specific;
        }

        return checklist;
    }

    savePtw(status) {
        console.log('savePtw called with status:', status);
        
        if (status !== 'DRAFT' && !this.validateForm()) {
            console.log('Validation failed, returning');
            return;
        }

        console.log('Starting PTW save process...');
        ShowLoader();

        const formData = {
            action: this.ptwId ? 'update_permit' : 'create_permit',
            permit_id: this.ptwId,
            description: $('#txtPtwDescription').val(),
            work_area: $('#txtPtwWorkArea').val(),
            work_type: $('#optPtwWorkType').val(),
            valid_from: $('#dtPtwValidFrom').val(),
            valid_to: $('#dtPtwValidTo').val(),
            risk_level: $('#optPtwRiskLevel').val(),
            hazards: $('#txtPtwHazards').val(),
            control_measures: $('#txtPtwControlMeasures').val(),
            applicant_name: $('#txtPtwApplicantName').val(),
            applicant_contact: $('#txtPtwApplicantContact').val(),
            applicant_department: $('#txtPtwApplicantDept').val(),
            contractor_company: $('#txtPtwContractorCompany').val(),
            remarks: $('#txtPtwRemarks').val(),
            status: status,
            workers: this.getWorkersData(),
            checklist_data: JSON.stringify(this.getChecklistData())
        };

        console.log('Form data prepared:', formData);
        console.log('API URL:', this.apiUrl);
        console.log('Starting PTW submission process...');

        // Disable submit buttons to prevent double submission
        $('#btnSaveDraft, #btnSubmitApproval').prop('disabled', true);

        // Get auth token
        let authToken = sessionStorage.getItem('token');
        
        // For testing: if no token available, use a test token
        if (!authToken) {
            authToken = 'test_token_123';
            sessionStorage.setItem('token', authToken);
            console.log('Using test auth token for development');
        }
        
        console.log('Auth token available:', !!authToken);
        
        const headers = {};
        if (authToken) {
            headers['Authorization'] = 'Bearer ' + authToken;
        }
        
        $.ajax({
            url: this.apiUrl,
            type: 'POST',
            data: formData,
            headers: headers,
            success: (response) => {
                console.log('AJAX Success Response:', response);
                if (response.success === true) {
                    const message = response.result.status === 'DRAFT' ? 
                        'PTW saved as draft successfully!' : 
                        'PTW submitted for approval successfully!';
                    showSuccess(message);
                    
                    console.log('PTW submission successful! Redirecting to ptw_management.html...');
                    
                    // Immediate redirect for better user experience
                    setTimeout(() => {
                        console.log('Redirecting to PTW Management...');
                        window.location.href = 'ptw_management.html';
                    }, 1000); // Reduced to 1 second for faster response
                } else {
                    console.log('API returned error:', response.message);
                    showError(response.message || 'Failed to save PTW');
                    // Re-enable buttons on error
                    $('#btnSaveDraft, #btnSubmitApproval').prop('disabled', false);
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
                
                // Re-enable buttons on error
                $('#btnSaveDraft, #btnSubmitApproval').prop('disabled', false);
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
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                $('#txtPtwDescriptionErr').text('PTW Description is required');
            }
        });

        $('#txtPtwWorkArea').on('blur', function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                $('#txtPtwWorkAreaErr').text('Work Area is required');
            }
        });

        $('#txtPtwApplicantName').on('blur', function() {
            if (!$(this).val().trim()) {
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
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
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
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
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
