function ModalCreatePtw() {

    const className = 'ModalCreatePtw';
    let self = this;
    let classFrom;
    let siteId;
    let refUser;
    let refSite;
    let refPpmGroup;
    let formValidate;

    this.init = function () {
        $('.divMcpHideInitial').hide();
        
        // Initialize form options
        const arrUsers = [];
        $.each(refUser, function (n, user) {
            if (typeof user !== 'undefined' && user.userStatus === '1' && user.siteId === siteId) {
                arrUsers.push(user);
            }
        });

        mzOption('optMcpApplicant', arrUsers, 'Select Applicant *', 'userId', 'userFirstName', {userStatus: '1', siteId: siteId}, 'required');
        
        // Work type options
        const arrWorkType = [
            {workTypeId: 'HOT_WORK', workTypeName: 'Hot Work'},
            {workTypeId: 'COLD_WORK', workTypeName: 'Cold Work'},
            {workTypeId: 'ELECTRICAL', workTypeName: 'Electrical Work'},
            {workTypeId: 'MECHANICAL', workTypeName: 'Mechanical Work'},
            {workTypeId: 'CONFINED_SPACE', workTypeName: 'Confined Space'},
            {workTypeId: 'HEIGHT_WORK', workTypeName: 'Work at Height'},
            {workTypeId: 'EXCAVATION', workTypeName: 'Excavation Work'}
        ];
        
        mzOption('optMcpWorkType', arrWorkType, 'Select Work Type *', 'workTypeId', 'workTypeName', {}, 'required');
        
        // Risk level options
        const arrRiskLevel = [
            {riskId: 'LOW', riskName: 'Low Risk'},
            {riskId: 'MEDIUM', riskName: 'Medium Risk'},
            {riskId: 'HIGH', riskName: 'High Risk'},
            {riskId: 'CRITICAL', riskName: 'Critical Risk'}
        ];
        
        mzOption('optMcpRiskLevel', arrRiskLevel, 'Select Risk Level *', 'riskId', 'riskName', {}, 'required');

        // Form validation
        const vData = [
            {
                field_id: 'txtMcpDescription',
                type: 'text',
                name: 'PTW Description',
                validator: {
                    notEmpty: true,
                    maxLength: 500
                }
            },
            {
                field_id: 'txtMcpWorkArea',
                type: 'text',
                name: 'Work Area',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'optMcpWorkType',
                type: 'select',
                name: 'Work Type',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optMcpRiskLevel',
                type: 'select',
                name: 'Risk Level',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'dtMcpValidFrom',
                type: 'text',
                name: 'Valid From',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'dtMcpValidTo',
                type: 'text',
                name: 'Valid To',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMcpApplicantName',
                type: 'text',
                name: 'Applicant Name',
                validator: {
                    notEmpty: true,
                    maxLength: 100
                }
            }
        ];

        formValidate = $('#frmMcp').bootstrapValidator({
            excluded: [':disabled'],
            feedbackIcons: {
                valid: 'fa fa-check',
                invalid: 'fa fa-times',
                validating: 'fa fa-refresh'
            },
            fields: mzGetValidateField(vData)
        }).on('success.form.bv', function (e) {
            e.preventDefault();
            self.save();
        });

        // Initialize date pickers
        $('.mcp-datepicker').pickadate({
            format: 'yyyy-mm-dd',
            today: 'Today',
            clear: 'Clear',
            close: 'Done',
            min: new Date()
        });

        // Event handlers
        $('#btnMcpSave').off('click').on('click', function () {
            $('#frmMcp').bootstrapValidator('validate');
        });

        $('#btnMcpCancel').off('click').on('click', function () {
            self.hide();
        });

        // Worker management
        $('#btnMcpAddWorker').off('click').on('click', function () {
            self.addWorkerRow();
        });

        $(document).off('click', '.btnMcpRemoveWorker').on('click', '.btnMcpRemoveWorker', function () {
            $(this).closest('.worker-row').remove();
        });
    };

    this.show = function () {
        self.reset();
        $('#modalMcp').modal('show');
    };

    this.hide = function () {
        $('#modalMcp').modal('hide');
    };

    this.reset = function () {
        $('#frmMcp')[0].reset();
        $('.worker-container').empty();
        self.addWorkerRow(); // Add one default worker row
        
        if (formValidate) {
            $('#frmMcp').bootstrapValidator('resetForm', true);
        }
    };

    this.addWorkerRow = function () {
        const workerRowHtml = `
            <div class="row worker-row">
                <div class="col-md-3">
                    <div class="md-form">
                        <input type="text" class="form-control worker-name" name="workerName[]" required>
                        <label>Worker Name *</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="md-form">
                        <input type="text" class="form-control worker-ic" name="workerIc[]">
                        <label>IC Number</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="md-form">
                        <input type="text" class="form-control worker-phone" name="workerPhone[]">
                        <label>Phone</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="md-form">
                        <input type="text" class="form-control worker-company" name="workerCompany[]">
                        <label>Company</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm btnMcpRemoveWorker" style="margin-top: 1.5rem;">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        `;
        $('.worker-container').append(workerRowHtml);
    };

    this.save = function () {
        ShowLoader();
        
        try {
            // Collect worker data
            const workers = [];
            $('.worker-row').each(function () {
                const workerName = $(this).find('.worker-name').val().trim();
                if (workerName) {
                    workers.push({
                        workerName: workerName,
                        workerIcNumber: $(this).find('.worker-ic').val().trim(),
                        workerPhoneNumber: $(this).find('.worker-phone').val().trim(),
                        workerCompany: $(this).find('.worker-company').val().trim()
                    });
                }
            });

            const formData = {
                action: 'create',
                ptwPermitDescription: $('#txtMcpDescription').val().trim(),
                ptwWorkArea: $('#txtMcpWorkArea').val().trim(),
                ptwWorkType: $('#optMcpWorkType').val(),
                ptwRiskLevel: $('#optMcpRiskLevel').val(),
                ptwValidFrom: $('#dtMcpValidFrom').val(),
                ptwValidTo: $('#dtMcpValidTo').val(),
                ptwApplicantName: $('#txtMcpApplicantName').val().trim(),
                ptwApplicantContact: $('#txtMcpApplicantContact').val().trim(),
                ptwApplicantCompanyDept: $('#txtMcpApplicantDept').val().trim(),
                ptwContractorCompany: $('#txtMcpContractorCompany').val().trim(),
                ptwRemarks: $('#txtMcpRemarks').val().trim(),
                workers: workers
            };

            const resultRequest = mzAjaxRequest('ptw.php', 'POST', formData);
            
            if (resultRequest.success) {
                toastr['success']('PTW permit created successfully', _ALERT_TITLE_SUCCESS);
                self.hide();
                if (classFrom && typeof classFrom.refreshPtwData === 'function') {
                    classFrom.refreshPtwData();
                }
            } else {
                toastr['error'](resultRequest.error, _ALERT_TITLE_ERROR);
            }
        } catch (e) {
            toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        
        HideLoader();
    };

    // Setters
    this.setClassFrom = function(value) { classFrom = value; };
    this.setSiteId = function(value) { siteId = value; };
    this.setRefSite = function(value) { refSite = value; };
    this.setRefUser = function(value) { refUser = value; };
    this.setRefPpmGroup = function(value) { refPpmGroup = value; };
}
