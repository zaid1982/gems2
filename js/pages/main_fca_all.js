function MainFcaAll () {

    const className = 'MainFcaAll';
    let self = this;
    let isAuditor = false;
    let oTable;
    let refStatus;
    let refUser;
    let refSite;
    let refAssetGroup;
    let refDefectCategory;
    let refFcaZone;
    let refConditionScale;
    let refEvaluationType;
    let modalFcaAddClass;
    let sectionFcaInfoClass;
    let statusFilterValue = '';
    let tableDataCache = [];

    function getStatusLabel (statusId) {
        if (statusId === undefined || statusId === null || !refStatus) {
            return '';
        }
        const key = String(statusId);
        if (refStatus[key] && refStatus[key].statusAction) {
            return refStatus[key].statusAction;
        }
        if (Array.isArray(refStatus)) {
            const found = refStatus.find(function (row) {
                return row && String(row.statusId) === key;
            });
            if (found && found.statusAction) {
                return found.statusAction;
            }
        }
        return '';
    }

    function updateMetric (selector, value) {
        const el = $(selector);
        if (!el.length) {
            return;
        }
        const numericValue = isNaN(value) ? 0 : value;
        el.text(numericValue.toLocaleString());
    }

    function updateMetrics (data) {
        const metrics = {
            total: 0,
            pendingRecommendation: 0,
            pendingValidation: 0,
            validated: 0
        };
        if (!Array.isArray(data)) {
            data = [];
        }
        metrics.total = data.length;
        data.forEach(function (row) {
            const statusId = parseInt(row.fcaTaskStatus, 10);
            if (statusId === 55) {
                metrics.pendingRecommendation += 1;
                return;
            }
            if (statusId === 56 || statusId === 57) {
                metrics.pendingValidation += 1;
                return;
            }
            if (statusId === 19) {
                metrics.validated += 1;
                return;
            }
            metrics.pendingValidation += 1;
        });
        updateMetric('#metricFcaAllTotal', metrics.total);
        updateMetric('#metricFcaAllPendingRecommendation', metrics.pendingRecommendation);
        updateMetric('#metricFcaAllPendingValidation', metrics.pendingValidation);
        updateMetric('#metricFcaAllValidated', metrics.validated);
    }

    function updateStatusOptions (data) {
        const select = $('#optFcaAllStatus');
        if (!select.length) {
            return;
        }
        const uniqueStatuses = [];
        if (Array.isArray(data)) {
            data.forEach(function (row) {
                if (!row || row.fcaTaskStatus === undefined || row.fcaTaskStatus === null) {
                    return;
                }
                const id = String(row.fcaTaskStatus);
                if (uniqueStatuses.some(function (item) { return item.id === id; })) {
                    return;
                }
                uniqueStatuses.push({
                    id: id,
                    label: getStatusLabel(id) || id
                });
            });
        }
        uniqueStatuses.sort(function (a, b) {
            return a.label.localeCompare(b.label);
        });
        try {
            select.materialSelect('destroy');
        } catch (err) {
            // ignore destroy errors when component is not initialised
        }
        select.empty();
        select.append('<option value="" selected>All statuses</option>');
        uniqueStatuses.forEach(function (item) {
            select.append('<option value="' + item.id + '">' + item.label + '</option>');
        });
        if (statusFilterValue && !uniqueStatuses.some(function (item) { return item.id === statusFilterValue; })) {
            statusFilterValue = '';
        }
        select.val(statusFilterValue || '');
        try {
            select.materialSelect();
        } catch (err2) {
            // ignore initialisation errors
        }
    }

    function updateSummary () {
        if (!oTable) {
            return;
        }
    const info = typeof oTable.page === 'function' ? oTable.page.info() : null;
        const total = info ? info.recordsTotal : 0;
        const filtered = info ? info.recordsDisplay : 0;
        $('#lblFcaAllCount').text('Showing ' + filtered + ' of ' + total + ' records');
    }

    function updateTimestamp () {
        const el = $('#lblFcaAllUpdated');
        if (!el.length) {
            return;
        }
        const timestamp = typeof moment === 'function'
            ? moment().format('DD MMM YYYY, hh:mm A')
            : new Date().toLocaleString();
        el.text('Updated ' + timestamp);
    }

    function setDataLabels (row, apiInstance) {
        const api = apiInstance || oTable;
        if (!api) {
            return;
        }
        const visibleIndexes = api.columns(':visible').indexes().toArray();
        $('td', row).each(function (cellIdx) {
            const columnIndex = visibleIndexes[cellIdx];
            if (columnIndex === undefined) {
                return;
            }
            const headerText = $(api.column(columnIndex).header()).text().trim();
            $(this).attr('data-label', headerText);
        });
    }

    function handleAddNew () {
        if (!isAuditor) {
            toastr['error']('You don\'t have permission as FCA Auditor role to perform this task!', _ALERT_TITLE_ERROR);
            return false;
        }
        if (modalFcaAddClass && typeof modalFcaAddClass.setClassFrom === 'function') {
            modalFcaAddClass.setClassFrom(self);
        }
        if (modalFcaAddClass && typeof modalFcaAddClass.add === 'function') {
            modalFcaAddClass.add();
        }
        return true;
    }

    this.init = function () {
        isAuditor = mzIsRoleExist('22');
        if (!isAuditor) {
            $('#btnFcaAllAddNew').addClass('d-none');
        } else {
            $('#btnFcaAllAddNew').removeClass('d-none');
        }

        oTable = $('#dtFcaData').DataTable({
            bLengthChange: false,
            bFilter: false,
            aaSorting: [[17, 'desc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "Brt<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0] },
                { visible: false, targets: [4, 12, 13, 14, 15, 16, 18, 19] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'three-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnFcaObserveHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnFcaObserveHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnFcaObserveHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Observation List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnFcaObserveHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Observation List', titleAttr: 'PDF', orientation: 'landscape', exportOptions: mzExportOpt}
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const api = $(this).DataTable();
                const info = api.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                setDataLabels(nRow, api);
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                const api = $(this).DataTable();
                if ($('#divFcaPageWidth').width() < 546) {
                    api.column(1).visible(false);
                    api.column(2).visible(false);
                    api.column(7).visible(false);
                    api.column(8).visible(false);
                    api.column(18).visible(false);
                }
            },
            aoColumns: [
                { mData: null },
                { mData: 'fcaTaskNo' },
                { mData: 'siteId', mRender: function (data) {
                        return refSite[data] ? refSite[data]['siteCode'] : '';
                    } },
                { mData: 'assetGroupId', mRender: function (data) {
                        return refAssetGroup[data] ? refAssetGroup[data]['assetGroupName'] : '';
                    } },
                { mData: 'fcaTaskAssetNo' },
                { mData: 'fcaTaskAssetEvaluated' },
                { mData: 'fcaTaskDefectItem' },
                { mData: 'fcaDefectCategoryId', mRender: function (data) {
                        if (data === null || data === undefined) {
                            return '';
                        }
                        return refDefectCategory[data] ? refDefectCategory[data]['fcaDefectCategoryName'] : '';
                    } },
                { mData: 'fcaZoneId', mRender: function (data) {
                        if (data === null || data === undefined) {
                            return '';
                        }
                        return refFcaZone[data] ? refFcaZone[data]['fcaZoneName'] : '';
                    } },
                { mData: 'fcaTaskArea' },
                { mData: 'fcaTaskConditionScale', mRender: function (data) {
                        return mzReplaceNull(refConditionScale[data]);
                    } },
                { mData: 'fcaTaskEvaluationType', mRender: function (data) {
                        return mzReplaceNull(refEvaluationType[data]);
                    } },
                { mData: 'fcaTaskObservation' },
                { mData: 'fcaTaskRecommendation' },
                { mData: 'fcaTaskValidation' },
                { mData: 'fcaTaskCreatedBy', mRender: function (data) {
                        return data !== null && refUser[data] ? refUser[data]['userFirstName'] : '';
                    } },
                { mData: 'fcaTaskValidateBy', mRender: function (data) {
                        return data !== null && refUser[data] ? refUser[data]['userFirstName'] : '';
                    } },
                { mData: 'fcaTaskTimeCreated' },
                { mData: 'fcaTaskTimeRecommended' },
                { mData: 'fcaTaskTimeValidated' },
                { mData: 'fcaTaskStatus', mRender: function (data) {
                        return getStatusLabel(data);
                    } }
            ]
        });

        oTable.buttons().container().appendTo('#btnDtFcaAllExport');

        $.fn.dataTable.ext.search.push(function (settings, dataArr, dataIndex) {
            if (!oTable || settings.nTable !== $('#dtFcaData')[0]) {
                return true;
            }
            if (!statusFilterValue) {
                return true;
            }
            const rowData = oTable.row(dataIndex).data();
            const rowStatus = rowData && rowData.fcaTaskStatus !== undefined ? String(rowData.fcaTaskStatus) : '';
            return rowStatus === statusFilterValue;
        });

        $('#dtFcaData').on('draw.dt', function () {
            updateSummary();
        });

        $('#txtFcaAllSearch').on('keyup change', function () {
            const term = $(this).val();
            oTable.search(term).draw();
        });

        $('#optFcaAllStatus').on('change', function () {
            statusFilterValue = $(this).val();
            oTable.draw();
            updateSummary();
        });

        $('#btnFcaAllAddNew').off('click').on('click', handleAddNew);

        $('#btnFcaAllRefresh').off('click').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTable();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        const oTableTbody = $('#dtFcaData tbody');
        oTableTbody.on('click', 'tr', function () {
            const data = oTable.row(this).data();
            if (!data || !sectionFcaInfoClass || typeof sectionFcaInfoClass.load !== 'function') {
                return;
            }
            sectionFcaInfoClass.load(data['fcaTaskId'], 'Main');
        });

        oTableTbody.on('mouseenter', 'tr', function (evt) {
            const data = oTable.row(this).data();
            if (!data) {
                return;
            }
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to see ' + data['fcaTaskNo'] + ' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('fca_task', 'GET') || [];
        tableDataCache = Array.isArray(dataDb) ? dataDb : [];
        oTable.clear().rows.add(tableDataCache).draw();
        updateMetrics(tableDataCache);
        updateStatusOptions(tableDataCache);
        updateTimestamp();
        updateSummary();
    };

    this.getClassName = function () {
        return className;
    };

    this.showMain = function () {
        $('.sectionFcaMain').show();
    };

    this.hideMain = function () {
        $('.sectionFcaMain').hide();
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefDefectCategory = function (_refDefectCategory) {
        refDefectCategory = _refDefectCategory;
    };

    this.setRefFcaZone = function (_refFcaZone) {
        refFcaZone = _refFcaZone;
    };

    this.setRefConditionScale = function (_refConditionScale) {
        refConditionScale = _refConditionScale;
    };

    this.setRefEvaluationType = function (_refEvaluationType) {
        refEvaluationType = _refEvaluationType;
    };

    this.setModalFcaAddClass = function (_modalFcaAddClass) {
        modalFcaAddClass = _modalFcaAddClass;
    };

    this.setSectionFcaInfoClass = function (_sectionFcaInfoClass) {
        sectionFcaInfoClass = _sectionFcaInfoClass;
    };

}