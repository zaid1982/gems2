function ModalCreatePtw() {

    const className = 'ModalCreatePtw';
    let self = this;
    let classFrom;
    let siteId;
    let refUser;
    let refSite;
    let refPpmGroup;

    function rowsFromRef(ref, idKey, predicate) {
        const rows = [];
        $.each(ref || {}, function (id, rec) {
            if (!rec || typeof rec !== 'object') {
                return;
            }
            const row = $.extend({}, rec);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = String(id);
            }
            if (predicate && !predicate(row)) {
                return;
            }
            rows.push(row);
        });
        return rows;
    }

    function fillNamed(id, rows, valueKey, labelKey, placeholder) {
        const sorted = (rows || []).slice().sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''));
        });
        GemsUI.fillSelect(id, sorted, valueKey, function (row) {
            return row[labelKey] || '';
        }, placeholder);
    }

    this.init = function () {
        $('.divMcpHideInitial').hide();

        fillNamed(
            'optMcpApplicant',
            rowsFromRef(refUser, 'userId', function (user) {
                return String(user.userStatus) === '1' && String(user.siteId) === String(siteId);
            }),
            'userId',
            'userFirstName',
            'Select Applicant *'
        );

        fillNamed(
            'optMcpWorkType',
            [
                {workTypeId: 'HOT_WORK', workTypeName: 'Hot Work'},
                {workTypeId: 'COLD_WORK', workTypeName: 'Cold Work'},
                {workTypeId: 'ELECTRICAL', workTypeName: 'Electrical Work'},
                {workTypeId: 'MECHANICAL', workTypeName: 'Mechanical Work'},
                {workTypeId: 'CONFINED_SPACE', workTypeName: 'Confined Space'},
                {workTypeId: 'HEIGHT_WORK', workTypeName: 'Work at Height'},
                {workTypeId: 'EXCAVATION', workTypeName: 'Excavation Work'}
            ],
            'workTypeId',
            'workTypeName',
            'Select Work Type *'
        );

        fillNamed(
            'optMcpRiskLevel',
            [
                {riskId: 'LOW', riskName: 'Low Risk'},
                {riskId: 'MEDIUM', riskName: 'Medium Risk'},
                {riskId: 'HIGH', riskName: 'High Risk'},
                {riskId: 'CRITICAL', riskName: 'Critical Risk'}
            ],
            'riskId',
            'riskName',
            'Select Risk Level *'
        );

        $('#frmMcp').on('submit', function (e) {
            e.preventDefault();

            let isValid = true;
            const requiredFields = [
                { id: 'txtMcpDescription', name: 'PTW Description' },
                { id: 'txtMcpWorkArea', name: 'Work Area' },
                { id: 'optMcpWorkType', name: 'Work Type' },
                { id: 'optMcpRiskLevel', name: 'Risk Level' },
                { id: 'dtMcpValidFrom', name: 'Valid From' },
                { id: 'dtMcpValidTo', name: 'Valid To' },
                { id: 'txtMcpApplicantName', name: 'Applicant Name' }
            ];

            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            requiredFields.forEach(function (field) {
                const element = $('#' + field.id);
                const value = element.val();
                if (!value || String(value).trim() === '') {
                    isValid = false;
                    element.addClass('is-invalid');
                    element.after('<div class="invalid-feedback">' + field.name + ' is required</div>');
                }
            });

            if (isValid) {
                self.save();
            }
        });

        $('#btnMcpSave').off('click').on('click', function () {
            $('#frmMcp').trigger('submit');
        });

        $('#btnMcpCancel').off('click').on('click', function () {
            self.hide();
        });

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
        self.addWorkerRow();
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    };

    this.addWorkerRow = function () {
        const workerRowHtml =
            '<div class="row worker-row g-2 mb-2">' +
                '<div class="col-md-3">' +
                    '<label class="form-label">Worker Name *</label>' +
                    '<input type="text" class="form-control worker-name" name="workerName[]" required>' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<label class="form-label">IC Number</label>' +
                    '<input type="text" class="form-control worker-ic" name="workerIc[]">' +
                '</div>' +
                '<div class="col-md-2">' +
                    '<label class="form-label">Phone</label>' +
                    '<input type="text" class="form-control worker-phone" name="workerPhone[]">' +
                '</div>' +
                '<div class="col-md-3">' +
                    '<label class="form-label">Company</label>' +
                    '<input type="text" class="form-control worker-company" name="workerCompany[]">' +
                '</div>' +
                '<div class="col-md-1 d-flex align-items-end">' +
                    '<button type="button" class="btn btn-outline-danger btn-sm btnMcpRemoveWorker" aria-label="Remove worker">' +
                        '<i class="fas fa-minus"></i>' +
                    '</button>' +
                '</div>' +
            '</div>';
        $('.worker-container').append(workerRowHtml);
    };

    this.save = function () {
        ShowLoader();

        try {
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

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (value) { classFrom = value; };
    this.setSiteId = function (value) { siteId = value; };
    this.setRefSite = function (value) { refSite = value; };
    this.setRefUser = function (value) { refUser = value; };
    this.setRefPpmGroup = function (value) { refPpmGroup = value; };
}
