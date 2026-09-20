function MainPpmReschedule() {

    const className = 'MainPpmReschedule';
    let self = this;
    let refSite;
    let refPpmGroup;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refStatus;
    let modalPpmRescheduleClass;
    let yearId;
    let userClient;
    let currentRole = '';
    let oTablePpm;
    const monthFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const rescheduleHeaders = ['#', 'Work Order No', 'Asset No', 'Asset Group', 'Asset Category', 'Asset Type', 'Scheduled Date', 'Final Date', 'Frequency', 'PPM Group', 'Reschedule Status', 'Status', 'Actions'];
    const statusChipMap = {
        '': '#linkPrsAll',
        'Rescheduled': '#linkPrsRescheduled',
        'Not Yet': '#linkPrsNotYet',
        'Disallowed': '#linkPrsDisallowed'
    };
    let dataPpmCache = [];
    let lastListUpdatedText = '—';

    const applyTableDataLabels = function (tableSelector, headers) {
        $(`${tableSelector} tbody tr`).each(function () {
            $('td', this).each(function (index) {
                if (headers[index]) {
                    $(this).attr('data-label', headers[index]);
                }
            });
        });
    };

    const getNowStamp = function () {
        return (typeof moment !== 'undefined' && moment) ? moment().format('MMM D, YYYY h:mm A') : new Date().toLocaleString();
    };

    const refreshListSummary = function () {
        if (!oTablePpm) {
            return;
        }
        const info = oTablePpm.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblPrsFilterCount').text(summaryText);
        $('#lblPrsListCount').text(summaryText);
        $('#lblPrsFilterUpdated').text(lastListUpdatedText);
        $('#lblPrsListUpdated').text(lastListUpdatedText);
    };

    const updateRescheduleMetrics = function (dataSet) {
        const total = dataSet.length;
        let rescheduled = 0;
        let disallowed = 0;
        let pending = 0;
        dataSet.forEach(function (item) {
            const statusFlag = item['ppmTaskIsScheduled'];
            const frequencyName = item['frequency'] || '';
            if (statusFlag === '1') {
                rescheduled += 1;
            } else if (frequencyName === 'Daily' || frequencyName.indexOf(', ') !== -1) {
                disallowed += 1;
            } else {
                pending += 1;
            }
        });
        $('#metricPrsTotal').text(mzFormatNumber(total, 0));
        $('#metricPrsRescheduled').text(mzFormatNumber(rescheduled, 0));
        $('#metricPrsPending').text(mzFormatNumber(pending, 0));
        $('#metricPrsDisallowed').text(mzFormatNumber(disallowed, 0));
        $('#metricPrsPending').toggleClass('text-warning', pending > 0);
        $('#metricPrsDisallowed').toggleClass('text-danger', disallowed > 0);
    };

    const setActiveStatusChip = function (value) {
        $.each(statusChipMap, function (key, selector) {
            if (key === value) {
                $(selector).addClass('active');
            } else {
                $(selector).removeClass('active');
            }
        });
    };

    const setStatusFilter = function (value, fromSelect) {
        oTablePpm.column(10).search(value, false, true, false).draw();
        setActiveStatusChip(value);
        if (!fromSelect) {
            $('#optPrsRescheduleStatus').val(value);
        }
    };

    this.init = function () {
        yearId = '2019';
        userClient = mzGetUserInfoByParam('clientId');
        if (mzIsRoleExist('1')) {
            currentRole = 1;
        } else if (mzIsRoleExist('2')) {
            currentRole = 2;
        }

        let arrYear = [];
        let arrMonth = [];
        let dt = new Date();
        const currentYear = dt.getFullYear();
        const currentMonth = dt.getMonth();
        let currentSite = '';

        for (let year = 2019; year <= currentYear+3; year++) {
            arrYear.push({yearId:year.toString(), yearDesc:year.toString()});
        }
        for (let i = 0; i <= monthFull.length; i++) {
            arrMonth.push({monthId:i, monthDesc:monthFull[i]});
        }
        const siteRows = [];
        if (refSite) {
            $.each(refSite, function (id, rec) {
                if (!rec || typeof rec !== 'object') {
                    return;
                }
                const row = $.extend({}, rec);
                if (row['siteId'] === undefined) {
                    row['siteId'] = String(id);
                }
                if (!currentSite && String(row['clientId']) === String(userClient)) {
                    currentSite = row['siteId'];
                }
                if (typeof row['siteStatus'] !== 'undefined' && String(row['siteStatus']) !== '1') {
                    return;
                }
                if (currentRole === '2' && String(row['clientId']) !== String(userClient)) {
                    return;
                }
                siteRows.push(row);
            });
        }
        GemsUI.fillSelect('optPrsYear', arrYear, 'yearId', function (row) { return row['yearDesc'] || ''; }, 'Select Year');
        GemsUI.fillSelect('optPrsMonth', arrMonth, 'monthId', function (row) { return row['monthDesc'] || ''; }, 'Select Month');
        GemsUI.fillSelect('optPrsSiteId', siteRows, 'siteId', function (row) { return row['siteName'] || ''; }, 'Select Site');

        $('#optPrsYear').val(yearId);
        $('#optPrsMonth').val(currentMonth);
        $('#optPrsSiteId').val(currentSite);

        oTablePpm =  $('#dtPrsList').DataTable({
            bLengthChange: false,
            bFilter: true,
            "aaSorting": [6, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTablePpm.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                applyTableDataLabels('#dtPrsList', rescheduleHeaders);
                refreshListSummary();
            },
            language: GemsUI.dtEmpty('fa-calendar-check', 'No PPM tasks for this period.'),
            dom: GemsUI.dtDom,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'ppmTaskNo'},
                    {mData: 'assetNo'},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetGroupId'] !== '' ? refAssetGroup[row['assetGroupId']]['assetGroupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetCategoryId'] !== '' ? refAssetCategory[row['assetCategoryId']]['assetCategoryName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['assetTypeId'] !== '' ? refAssetType[row['assetTypeId']]['assetTypeName'] : '';
                        }},
                    {mData: 'ppmTaskStartDate'},
                    {mData: 'ppmTaskScheduleDate'},
                    {mData: 'frequency'},
                    {mData: null, mRender: function (data, type, row){
                            return row['ppmGroupId'] !== '' ? refPpmGroup[row['ppmGroupId']]['ppmGroupName'] : '';
                        }},
                    {mData: 'ppmTaskIsScheduled', sClass: 'text-center', mRender: function (data, type, row){
                            const frequency = row['frequency'];
                            if (data === '1') {
                                return 'Rescheduled';
                            } else if (frequency === 'Daily' || frequency.search(', ') !== -1) {
                                return 'Disallowed';
                            } else {
                                return 'Not Yet';
                            }
                        }},
                    {mData: null, sClass: 'text-center',
                        mRender: function (data, type, row) {
                            const rec = refStatus && refStatus[row['ppmTaskStatus']];
                            const label = rec ? rec['statusDesc'] : String(row['ppmTaskStatus']);
                            if (type !== 'display') {
                                return label;
                            }
                            const colorMap = {
                                'badge-primary': 'info',
                                'badge-info': 'info',
                                'badge-success': 'success',
                                'badge-danger': 'danger',
                                'badge-warning': 'warning',
                                'badge-secondary': 'secondary'
                            };
                            return GemsUI.badge(colorMap[rec && rec['statusColor']] || 'secondary', GemsUI.escape(label));
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            if (type !== 'display') {
                                return '';
                            }
                            let label = GemsUI.actionBtn({id: 'lnkPrsPpmPdf_' + meta.row, cls: 'lnkPrsPpmPdf', icon: 'far fa-file-pdf', title: 'PPM PDF'});
                            const frequency = row['frequencyIds'];
                            if (frequency === '1' || frequency === '2' || frequency === '3' || frequency === '4' || frequency === '6') {
                                label += GemsUI.actionBtn({id: 'lnkPrsPpmReschedule_' + meta.row, cls: 'lnkPrsPpmReschedule', icon: 'far fa-calendar-check', title: 'Reschedule PPM Date'});
                            }
                            return label;
                        }
                    },
                    {mData: 'ppmTaskId', visible: false},
                    {mData: 'siteId', visible: false},
                    {mData: 'ppmTaskStatus', visible: false},
                    {mData: 'pdfId', visible: false},
                    {mData: 'frequencyIds', visible: false}
                ]
        });
        $("#dtPrsList_filter").hide();
        GemsUI.bindDtTooltips('#dtPrsList');
        $('#dtPrsList').on('click', '.lnkPrsPpmPdf', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTablePpm.row(parseInt(rowId, 10)).data();
                        let pdfId = currentRow['pdfId'];
                        if (currentRow['pdfId'] === '') {
                            pdfId = mzAjaxRequest('ppm.php', 'POST', {action: 'generate_pdf', ppmTaskId:currentRow['ppmTaskId']});
                        }
                        const pdfSrc = mzAjaxRequest('pdf.php?pdfId='+pdfId, 'GET');
                        $('#mpdf_title').html('<i class="far fa-file-pdf text-white"></i> &nbsp;PPM Report: '+currentRow['ppmTaskNo']);
                        $('#mpdf_iframe').attr('src', pdfSrc);
                        $('#modal_pdf').modal('show');
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
        $('#dtPrsList').on('click', '.lnkPrsPpmReschedule', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                ShowLoader();
                setTimeout(function () {
                    try {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTablePpm.row(parseInt(rowId, 10)).data();
                        const passParam = {
                            ppmTaskNo:currentRow['ppmTaskNo'],
                            ppmTaskStartDate:currentRow['ppmTaskStartDate'],
                            ppmTaskScheduleDate:currentRow['ppmTaskScheduleDate'],
                            frequency:currentRow['frequency'],
                            frequencyIds:currentRow['frequencyIds']
                        };
                        modalPpmRescheduleClass.edit(currentRow['ppmTaskId'], rowId, passParam);
                    } catch (e) {
                        toastr['error'](e.message, _ALERT_TITLE_ERROR);
                    }
                    HideLoader();
                }, 200);
            }
        });
        $('#txtPrsListSearch').on('keyup change', function () {
            oTablePpm.search($(this).val()).draw();
        });

        $('#optPrsRescheduleStatus').on('change', function () {
            const value = $(this).val() || '';
            setStatusFilter(value, true);
        });

        $.each(statusChipMap, function (statusValue, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(statusValue, false);
            });
        });
        setActiveStatusChip('');

        let cntPpm;
        let btnPpmOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntPpm = 1;
                        }
                        if (column === 11) {
                            const tmp = document.createElement('div');
                            tmp.innerHTML = data;
                            return tmp.textContent || tmp.innerText || '';
                        }
                        return column === 0 ? cntPpm++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTablePpm, {
            buttons: [
                $.extend( true, {}, btnPpmOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - PPM List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-secondary btn-sm'
                }),
                $.extend( true, {}, btnPpmOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - PPM List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-secondary btn-sm'
                })
            ]
        }).container().appendTo($('#btnDtPrsListExport'));

        $('#btnDtPrsListRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePrsPpm();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        $('#optPrsSiteId, #optPrsYear, #optPrsMonth').on('change', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTablePrsPpm();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        self.genTablePrsPpm();
    };

    this.genTablePrsPpm = function () {
        dataPpmCache = mzAjaxRequest('ppm.php?type=dashboard_list&clientId=&siteId='+$('#optPrsSiteId').val()+'&year='+$('#optPrsYear').val()+'&month='+$('#optPrsMonth').val(), 'GET');
        updateRescheduleMetrics(dataPpmCache);
        lastListUpdatedText = getNowStamp();
        oTablePpm.clear().rows.add(dataPpmCache).draw();
        refreshListSummary();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
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

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setModalPpmRescheduleClass = function (_modalPpmRescheduleClass) {
        modalPpmRescheduleClass = _modalPpmRescheduleClass;
    };
}