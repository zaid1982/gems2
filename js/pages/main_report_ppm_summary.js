function MainReportPpmSummary() {

    const className = 'MainReportPpmSummary';
    let self = this;
    let refClient;
    let refSite;
    const yearArr = mzGetYearArray();
    const monthArr = mzGetMonthArray();
    let oTablePpmSummary;
    let clientId;
    let siteId;
    let selectedYear;
    let selectedMonth;
    let userSite;

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

    function fillSiteSelect(clientKey, labelKey, selected) {
        GemsUI.fillSelect(
            'optRpsSiteId',
            rowsFromRef(refSite, 'siteId', labelKey, function (row) {
                if (clientKey && String(row['clientId']) !== String(clientKey)) {
                    return false;
                }
                return String(row['siteStatus']) === '1';
            }),
            'siteId',
            labelKey,
            'Choose Site',
            selected
        );
    }

    this.init = function () {
        userSite = mzGetUserInfoByParam('siteId');

        clientId = '1';
        siteId = !mzIsRoleExist('1,10') ? userSite : '1';
        GemsUI.fillSelect('optRpsClientId', rowsFromRef(refClient, 'clientId', 'clientName'), 'clientId', 'clientName', 'Choose Client', clientId);
        fillSiteSelect('1', 'siteDesc', siteId);

        if (!mzIsRoleExist('1,10')) {
            $('#optRpsSiteId').prop('disabled', true);
        }

        let dateCurrent = new Date();
        selectedMonth = dateCurrent.getMonth()+1;
        selectedYear = dateCurrent.getFullYear();

        GemsUI.fillSelect('optRpsYearId', yearArr, 'yearId', 'yearName', 'Choose Year', selectedYear);
        GemsUI.fillSelect('optRpsMonthId', monthArr, 'monthId', 'monthName', 'Choose Month', selectedMonth);

        $('#optRpsClientId').on('change', function () {
            clientId = $(this).val();
            fillSiteSelect($(this).val(), 'siteName');
        });

        const vData = [
            {
                field_id: 'optRpsClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRpsYearId',
                type: 'select',
                name: 'Year',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optRpsMonthId',
                type: 'select',
                name: 'Month',
                validator: {
                    notEmpty: true
                }
            }
        ];

        let formValidate = new MzValidate('formRpsSearch');
        formValidate.registerFields(vData);

        $('#btnRpsSearch').attr('disabled', !formValidate.validateForm());
        $('#formRpsSearch').on('keyup change', function () {
            $('#btnRpsSearch').attr('disabled', !formValidate.validateForm());
        });

        oTablePpmSummary = $('#dtRpsPpmSummary').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            "aaSorting": [6, 'desc'],
            language: GemsUI.dtEmpty('fa-clipboard-check', 'No PPM summary for this period.', 'No asset types match the current search.'),
            dom: "<'d-none'f>rt",
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTablePpmSummary.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
            },
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'assetTypeName'},
                    {mData: 'frequency'},
                    {mData: 'noAsset', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data);
                        }},
                    {mData: 'totalPpm', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data);
                        }},
                    {mData: 'ppmDone', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data);
                        }},
                    {mData: 'totalPercDone', sClass: 'text-right',
                        mRender: function (data) {
                            return mzFormatNumber(data, 2)+'%';
                        }}
                ]
        });
        GemsUI.bindDtTooltips('#dtRpsPpmSummary');

        let cntPpmSummary;
        let btnPpmSummaryOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntPpmSummary = 1;
                        }
                        return column === 0 ? cntPpmSummary++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTablePpmSummary, {
            buttons: [
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - PPM Summary',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - PPM Summary',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnPpmSummaryOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - PPM Summary',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-secondary btn-sm'
                })
            ]
        }).container().appendTo($('#btnDtRpsPpmSummaryExport'));

        $('#btnDtRpsPpmSummaryRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePpmSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnRpsSearch').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    siteId = $('#optRpsSiteId').val();
                    selectedYear = $('#optRpsYearId').val();
                    selectedMonth = $('#optRpsMonthId').val();
                    self.genTablePpmSummary();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTablePpmSummary();
    };

    this.genTablePpmSummary = function () {
        const dataPpmSummary = mzAjaxRequest('ppm.php?type=report_ppm_summary&siteId='+siteId+'&year='+selectedYear+'&month='+selectedMonth, 'GET');
        oTablePpmSummary.clear().rows.add(dataPpmSummary).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };
}