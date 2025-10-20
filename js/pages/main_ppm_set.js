function MainPpmSet () {

    const className = 'MainPpmSet';
    let self = this;
    let dtPsm;
    let refStatus;
    let refUser;
    let refSite;
    let refContract;
    let refAssetGroup;
    let refAssetCategory;
    let refAssetType;
    let refPpmGroup;
    let modalPpmSetClass;
    let modalPpmSetSelectAssetClass;
    let contractId;
    let contractList = [];
    let dataPsmCache = [];
    let statusFilterValue = '';
    let lastListUpdatedText = '—';

    const tableHeaders = ['#', 'PPM Set Name', 'Description', 'Asset Type', 'PPM Executor Group', 'Total Assets in Set', 'Status', 'Actions'];
    const statusChipMap = {
        '': '#linkPsmAll',
        '1': '#linkPsmActive',
        '2': '#linkPsmInactive',
        '5': '#linkPsmArchived'
    };

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
        if (!dtPsm) {
            return;
        }
        const info = dtPsm.page.info();
        const showing = info ? info.recordsDisplay : 0;
        const total = info ? info.recordsTotal : 0;
        const summaryText = `Showing ${mzFormatNumber(showing, 0)} of ${mzFormatNumber(total, 0)}`;
        $('#lblPsmFilterCount').text(summaryText);
        $('#lblPsmListCount').text(summaryText);
        $('#lblPsmFilterUpdated').text(lastListUpdatedText);
        $('#lblPsmListUpdated').text(lastListUpdatedText);
    };

    const updatePpmSetMetrics = function (dataSet) {
        const total = dataSet.length;
        let active = 0;
        let assets = 0;
        dataSet.forEach(function (item) {
            const status = String(item['ppmSetStatus'] || '');
            if (status === '1') {
                active += 1;
            }
            const assetCount = parseInt(item['totalAssets'] || 0, 10);
            assets += isNaN(assetCount) ? 0 : assetCount;
        });
        const pending = total - active;

        $('#metricPsmTotal').text(mzFormatNumber(total, 0));
        $('#metricPsmActive').text(mzFormatNumber(active, 0));
        $('#metricPsmPending').text(mzFormatNumber(pending, 0)).toggleClass('text-warning', pending > 0);
        $('#metricPsmAssets').text(mzFormatNumber(assets, 0));
    };

    const initMaterialSelect = function (selector) {
        const $element = $(selector);
        if (!$element.length || typeof $element.materialSelect !== 'function') {
            return;
        }
        try {
            $element.materialSelect('destroy');
        } catch (e) {
            // ignore destroy errors when not yet initialised
        }
        const $parentWrapper = $element.parent('.select-wrapper');
        if ($parentWrapper.length) {
            $parentWrapper.before($element);
            $parentWrapper.remove();
        }
        $element.siblings('.select-dropdown').remove();
        $element.materialSelect();
    };

    const findContractById = function (id) {
        const contractIdInt = parseInt(id, 10);
        for (let i = 0; i < contractList.length; i++) {
            if (parseInt(contractList[i].contractId, 10) === contractIdInt) {
                return contractList[i];
            }
        }
        return null;
    };

    const updateContractBadge = function () {
        const contract = findContractById(contractId);
        $('#lblPsmContractName').text(contract ? contract.contractName : '—');
        if (contract && modalPpmSetClass) {
            modalPpmSetClass.setContractId(contract.contractId);
            modalPpmSetClass.setSiteId(contract.siteId);
        }
    };

    const setActiveStatusChip = function (value) {
        $.each(statusChipMap, function (status, selector) {
            const $chip = $(selector);
            if (!$chip.length) {
                return;
            }
            if (status === value) {
                $chip.addClass('active');
            } else {
                $chip.removeClass('active');
            }
        });
    };

    const setStatusFilter = function (value, fromSelect) {
        statusFilterValue = value || '';
        setActiveStatusChip(statusFilterValue);
        if (!fromSelect) {
            const $statusSelect = $('#optPsmStatus');
            if ($statusSelect.length) {
                try {
                    $statusSelect.materialSelect('destroy');
                    $statusSelect.val(statusFilterValue);
                    $statusSelect.materialSelect();
                } catch (e) {
                    $statusSelect.val(statusFilterValue);
                }
            }
        }
        if (dtPsm) {
            dtPsm.draw();
            refreshListSummary();
        }
    };

    const populateContractSelect = function (isAdmin) {
        const $select = $('#optPsmContract');
        if (!$select.length) {
            return;
        }
        const disabled = !isAdmin && contractList.length <= 1;
        try {
            $select.materialSelect('destroy');
        } catch (e) {
            // ignore
        }
        $select.prop('disabled', disabled);
        let optionsHtml = '';
        contractList.forEach(function (contract) {
            const selectedAttr = String(contract.contractId) === String(contractId) ? ' selected' : '';
            optionsHtml += `<option value="${contract.contractId}"${selectedAttr}>${contract.contractName}</option>`;
        });
        if (optionsHtml === '') {
            optionsHtml = '<option value="" selected>No contract available</option>';
        }
        $select.html(optionsHtml);
        initMaterialSelect('#optPsmContract');
    };

    const statusFilterFn = function (settings, data, dataIndex) {
        if (!dtPsm || settings.nTable.id !== 'dtPsm') {
            return true;
        }
        if (!statusFilterValue) {
            return true;
        }
        const rowData = dtPsm.row(dataIndex).data();
        if (!rowData) {
            return true;
        }
        return String(rowData['ppmSetStatus']) === statusFilterValue;
    };

    this.init = function () {
        $.fn.dataTable.ext.search.push(statusFilterFn);

        const isAdmin = mzIsRoleExist('1');
        const userSiteId = mzGetUserInfoByParam('siteId');

        contractList = [];
        $.each(refContract, function (id, contract) {
            if (contract && typeof contract === 'object') {
                contractList.push({
                    contractId: parseInt(contract.contractId || id, 10),
                    contractName: contract.contractName || ('Contract ' + id),
                    siteId: contract.siteId
                });
            }
        });
        contractList.sort(function (a, b) {
            return (a.contractName || '').localeCompare(b.contractName || '');
        });

        if (!contractList.length) {
            contractId = null;
        } else if (!isAdmin) {
            let matchedContract = null;
            for (let i = 0; i < contractList.length; i++) {
                if (String(contractList[i].siteId) === String(userSiteId)) {
                    matchedContract = contractList[i];
                    break;
                }
            }
            contractId = matchedContract ? matchedContract.contractId : contractList[0].contractId;
        } else {
            contractId = contractList[0].contractId;
        }

        populateContractSelect(isAdmin);
        updateContractBadge();

        $('#optPsmContract').off('change').on('change', function () {
            const newId = $(this).val();
            if (!newId) {
                return;
            }
            contractId = parseInt(newId, 10);
            updateContractBadge();
            self.genTable();
        });

        $('#btnPsmRefresh').off('click').on('click', function () {
            self.genTable();
        });

        $('#btnPsmAdd').off('click').on('click', function () {
            if (modalPpmSetClass) {
                modalPpmSetClass.setClassFrom(self);
                modalPpmSetClass.add();
            } else {
                window.location.href = 'ppm_set_form.html';
            }
        });

        $('#txtPsmSearch').off('keyup change').on('keyup change', function () {
            const value = $(this).val();
            if (dtPsm) {
                dtPsm.search(value).draw();
            }
        });

        $('#optPsmStatus').off('change').on('change', function () {
            setStatusFilter($(this).val() || '', true);
        });

        initMaterialSelect('#optPsmStatus');

        $.each(statusChipMap, function (statusValue, selector) {
            $(selector).off('click').on('click', function () {
                setStatusFilter(statusValue, false);
            });
        });
        setStatusFilter('', true);

        dtPsm = $('#dtPsm').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: 't',
            columnDefs: [
                { targets: [0, 7], orderable: false, className: 'text-center noVis' },
                { targets: [5], className: 'text-right' },
                { targets: [6], className: 'text-center' },
                { targets: [2], visible: false }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = dtPsm.page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkPsmEdit').off('click').on('click', function () {
                    const ppmSetId = mzGetLinkId($(this), dtPsm, 'ppmSetId');
                    if (modalPpmSetClass) {
                        modalPpmSetClass.setClassFrom(self);
                        modalPpmSetClass.edit(ppmSetId);
                    } else {
                        window.location.href = 'ppm_set_form.html?ppmSetId=' + ppmSetId;
                    }
                });
                applyTableDataLabels('#dtPsm', tableHeaders);
                refreshListSummary();
            },
            aoColumns: [
                { mData: null },
                { mData: 'ppmSetName' },
                { mData: 'ppmSetDesc' },
                { mData: null, mRender: function (data, type, row) {
                        return self.getRefName(row.assetTypeId, refAssetType, 'assetTypeName');
                    }},
                { mData: null, mRender: function (data, type, row) {
                        return self.getRefName(row.ppmGroupId, refPpmGroup, 'ppmGroupName');
                    }},
                { mData: 'totalAssets', width: '5%' },
                { mData: 'ppmSetStatus', mRender: function (data) {
                        if (typeof refStatus === 'undefined' || !refStatus[data]) {
                            return '<span class="badge badge-pill badge-secondary">Unknown</span>';
                        }
                        return '<h6 class="mb-0"><span class="badge badge-pill '+refStatus[data]['statusColor']+'">'+refStatus[data]['statusDesc']+'</span></h6>';
                    }},
                { mData: null, bSortable: false, mRender: function (data, type, row, meta) {
                        const safeName = (row.ppmSetName || '')
                            .replace(/\\/g, "\\\\")
                            .replace(/'/g, "\\'");
                        let html = '<div class="btn-group" role="group" aria-label="Action buttons">';
                        html += '<a class="btn btn-sm btn-primary waves-effect waves-light p-2 lnkPsmEdit" id="lnkPsmEdit_' + meta.row + '" href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="Edit">';
                        html += '<i class="fas fa-edit"></i>';
                        html += '</a>';
                        html += `<a class="btn btn-sm btn-danger waves-effect waves-light p-2" href="javascript:void(0);" onclick="mainPpmSetClass_.deletePpmSetTrigger(${row.ppmSetId}, '${safeName}')" data-toggle="tooltip" data-placement="top" title="Delete">`;
                        html += '<i class="fas fa-trash-alt"></i>';
                        html += '</a>';
                        html += '</div>';
                        return html;
                    }}
            ]
        });
        $('#dtPsm_filter').hide();

        new $.fn.dataTable.Buttons(dtPsm, {
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', text: '<i class="fas fa-columns"></i>', className: 'btn btn-outline-white btn-rounded btn-sm px-2', titleAttr: 'Column Visibility' },
                { extend: 'print', text: '<i class="fas fa-print"></i>', title: 'GEMS 2.0 - PPM Set List', titleAttr: 'Print', className: 'btn btn-outline-white btn-rounded btn-sm px-2', exportOptions: mzExportOpt },
                { extend: 'copy', text: '<i class="fas fa-copy"></i>', title: 'GEMS 2.0 - PPM Set List', titleAttr: 'Copy', className: 'btn btn-outline-white btn-rounded btn-sm px-2', exportOptions: mzExportOpt },
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', title: 'GEMS 2.0 - PPM Set List', titleAttr: 'Excel', className: 'btn btn-outline-white btn-rounded btn-sm px-2', exportOptions: mzExportExcelOpt },
                { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i>', title: 'GEMS 2.0 - PPM Set List', titleAttr: 'PDF', orientation: 'landscape', className: 'btn btn-outline-white btn-rounded btn-sm px-2', exportOptions: mzExportOpt }
            ]
        }).container().appendTo($('#btnDtPsmExport'));

        self.genTable();
    };

    this.deletePpmSetConfirmed = function (ppmSetId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzAjaxRequest(
                    'ppm.php',
                    'DELETE',
                    { action: 'delete_ppm_set', ppmSetId: ppmSetId }
                );
                toastr['success']('PPM Set successfully deleted!', _ALERT_TITLE_SUCCESS);
                self.genTable();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.deletePpmSetTrigger = function (ppmSetId, ppmSetName) {
        if (window.confirm('Are you sure you want to delete PPM Set: "' + ppmSetName + '" (ID: ' + ppmSetId + ')? This action cannot be undone.')) {
            self.deletePpmSetConfirmed(ppmSetId);
        } else {
            toastr['info']('Deletion cancelled.', 'Info');
        }
    };

    this.getRefName = function (id, refArray, key) {
        if (id && refArray && refArray[id] && refArray[id][key]) {
            return refArray[id][key];
        }
        return '';
    };

    this.genTable = function () {
        ShowLoader();
        setTimeout(function () {
            mzFetch('api/ppm.php?type=ppm_set_list', 'GET').then(function (res) {
                let data = Array.isArray(res) ? res : [];
                if (contractId !== null && contractId !== undefined && data.length) {
                    if (typeof data[0]['contractId'] !== 'undefined') {
                        data = data.filter(function (item) {
                            return String(item['contractId']) === String(contractId);
                        });
                    } else if (typeof data[0]['ppmContractId'] !== 'undefined') {
                        data = data.filter(function (item) {
                            return String(item['ppmContractId']) === String(contractId);
                        });
                    }
                }
                dataPsmCache = data;
                updatePpmSetMetrics(dataPsmCache);
                lastListUpdatedText = getNowStamp();
                dtPsm.clear().rows.add(dataPsmCache).draw();
            }).catch(function (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }).finally(function () {
                HideLoader();
            });
        }, 200);
    };

    this.showMain = function () {
        $('.sectionPsmMain').show();
    };

    this.hideMain = function () {
        $('.sectionPsmMain').hide();
    };

    this.getClassName = function () {
        return className;
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
    this.setRefContract = function (_refContract) {
        refContract = _refContract;
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
    this.setRefPpmGroup = function (_refPpmGroup) {
        refPpmGroup = _refPpmGroup;
    };
    this.setModalPpmSetClass = function (_modalPpmSetClass) {
        modalPpmSetClass = _modalPpmSetClass;
    };
    this.setModalPpmSetSelectAssetClass = function (_modalPpmSetSelectAssetClass) {
        modalPpmSetSelectAssetClass = _modalPpmSetSelectAssetClass;
    };
}