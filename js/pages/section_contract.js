function SectionContract() {

    const className = 'SectionContract';
    let self = this;
    let modalConfirmDeleteClass;
    let contractId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refClient;
    let refSite;
    let refAssetGroup;
    let refUser;
    let oTableLocationCode;
    let oTableLocationUser;
    let modalLocationCodeClass;
    let modalContractUserClass;

    function displayText(value, type) {
        const text = value || '';
        if (type !== 'display') {
            return text;
        }
        return GemsUI.escape(text);
    }

    function statusLabel(statusId, fallback) {
        if (refStatus && refStatus[statusId] && refStatus[statusId]['statusDesc']) {
            return refStatus[statusId]['statusDesc'];
        }
        switch (String(statusId)) {
            case '1':
                return 'Active';
            case '2':
                return 'Inactive';
            default:
                return fallback || 'Unknown';
        }
    }

    function statusBadgeKind(status) {
        return String(status) === '1' ? 'success' : 'secondary';
    }

    function statusBadge(statusId, type) {
        const label = statusLabel(statusId, 'Unknown');
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
    }

    function getUserName(userId) {
        if (refUser && refUser[userId] && refUser[userId]['userFullName']) {
            return refUser[userId]['userFullName'];
        }
        return 'Unknown Technician';
    }

    function getAssetGroupName(assetGroupId) {
        if (refAssetGroup && refAssetGroup[assetGroupId] && refAssetGroup[assetGroupId]['assetGroupName']) {
            return refAssetGroup[assetGroupId]['assetGroupName'];
        }
        return 'Unknown Asset Group';
    }

    function rowIdFromLink(el) {
        const linkId = $(el).attr('id') || '';
        const linkIndex = linkId.indexOf('_');
        return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
    }

    function exportButtons(title, columns) {
        let exportCounter = 1;
        const exportOpt = {
            columns: columns,
            orthogonal: 'export',
            format: {
                body: function (data, row, column) {
                    if (row === 0 && column === 0) {
                        exportCounter = 1;
                    }
                    if (column === 0) {
                        return exportCounter++;
                    }
                    return data;
                }
            }
        };
        return GemsUI.dtButtons(title).map(function (src) {
            if (src.extend === 'colvis') {
                return src;
            }
            const btn = $.extend(true, {}, src);
            btn.exportOptions = exportOpt;
            return btn;
        });
    }

    this.init = function () {
        $('.toHide').hide();
        $('.sectionContract').hide();

        $('#btnSctBack').on('click', function () {
            $('.sectionContract').hide();
            if (classFrom.getClassName() === 'MainContract') {
                $('.sectionCcrMain').show();
            }
            $(window).scrollTop(0);
        });

        oTableLocationCode = $('#dtSctLocationCode').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [[1, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: exportButtons('GEMS 2.0 - Location Code List', [0, 1, 2]),
            language: GemsUI.dtEmpty('fa-map-marker-alt', 'No location codes recorded yet.', 'No location codes match the current search.'),
            pagingType: 'simple_numbers',
            columnDefs: [
                { targets: [0, 2, 3], orderable: false, className: 'text-center' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableLocationCode && oTableLocationCode.page && typeof oTableLocationCode.page.info === 'function')
                    ? oTableLocationCode.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            aoColumns: [
                { mData: null, bSortable: false },
                { mData: 'locationCodeName',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                { mData: 'locationCodeStatus',
                    mRender: function (data, type) {
                        return statusBadge(data, type);
                    }
                },
                { mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        let html = GemsUI.actionBtn({
                            tint: 'gems-btn-action-edit',
                            cls: 'lnkSctLocationCodeEdit',
                            id: 'lnkSctLocationCodeEdit_' + meta.row,
                            title: 'Edit',
                            icon: 'fas fa-pen-to-square'
                        });
                        if (row['locationCodeStatus'] === '1') {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-delete',
                                cls: 'lnkSctLocationCodeDeactivate',
                                id: 'lnkSctLocationCodeDeactivate_' + meta.row,
                                title: 'Deactivate',
                                icon: 'fas fa-toggle-off'
                            });
                        } else {
                            html += GemsUI.actionBtn({
                                tint: 'gems-btn-action-view',
                                cls: 'lnkSctLocationCodeActivate',
                                id: 'lnkSctLocationCodeActivate_' + meta.row,
                                title: 'Activate',
                                icon: 'fas fa-toggle-on'
                            });
                        }
                        html += GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkSctLocationCodeDelete',
                            id: 'lnkSctLocationCodeDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                        return html;
                    }
                }
            ]
        });
        oTableLocationCode.buttons().container().appendTo($('#btnDtSctLocationCodeExport'));
        GemsUI.bindDtTooltips('#dtSctLocationCode');

        const tbodyCode = $('#dtSctLocationCode tbody');
        tbodyCode.on('click', '.lnkSctLocationCodeEdit', function () {
            const rowId = rowIdFromLink(this);
            const currentRow = oTableLocationCode.row(parseInt(rowId, 10)).data();
            if (currentRow) {
                modalLocationCodeClass.edit(currentRow['locationCodeId'], contractId, rowId);
            }
        });
        tbodyCode.on('click', '.lnkSctLocationCodeDeactivate', function () {
            const rowId = rowIdFromLink(this);
            const currentRow = oTableLocationCode.row(parseInt(rowId, 10)).data();
            if (currentRow) {
                modalLocationCodeClass.deactivate(currentRow['locationCodeId'], rowId);
            }
        });
        tbodyCode.on('click', '.lnkSctLocationCodeActivate', function () {
            const rowId = rowIdFromLink(this);
            const currentRow = oTableLocationCode.row(parseInt(rowId, 10)).data();
            if (currentRow) {
                modalLocationCodeClass.activate(currentRow['locationCodeId'], rowId);
            }
        });
        tbodyCode.on('click', '.lnkSctLocationCodeDelete', function () {
            const rowId = rowIdFromLink(this);
            const currentRow = oTableLocationCode.row(parseInt(rowId, 10)).data();
            if (currentRow) {
                modalConfirmDeleteClass.delete(currentRow['locationCodeId'], modalLocationCodeClass);
            }
        });

        $('#txtSctLocationCodeSearch').on('keyup change', function () {
            oTableLocationCode.search($(this).val()).draw();
        });

        $('#btnSctLocationCodeAdd').on('click', function () {
            modalLocationCodeClass.add(contractId);
        });

        $('#btnDtSctLocationCodeRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableLocationCode();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableLocationUser = $('#dtSctLocationUser').DataTable({
            bLengthChange: false,
            searching: true,
            autoWidth: false,
            aaSorting: [[1, 'asc']],
            dom: GemsUI.dtDomButtons,
            buttons: exportButtons('GEMS 2.0 - Technician Assigned List', [0, 1, 2, 3]),
            language: GemsUI.dtEmpty('fa-users', 'No technicians assigned yet.', 'No technicians match the current search.'),
            pagingType: 'simple_numbers',
            columnDefs: [
                { targets: [0, 4], orderable: false, className: 'text-center' }
            ],
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = (oTableLocationUser && oTableLocationUser.page && typeof oTableLocationUser.page.info === 'function')
                    ? oTableLocationUser.page.info()
                    : null;
                const rowNumber = info ? (info.start + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber);
            },
            aoColumns: [
                { mData: null, bSortable: false },
                { mData: 'locationCodeName',
                    mRender: function (data, type) {
                        return displayText(data, type);
                    }
                },
                { mData: 'userId',
                    mRender: function (data, type) {
                        return displayText(getUserName(data), type);
                    }
                },
                { mData: 'assetGroupId',
                    mRender: function (data, type) {
                        return displayText(getAssetGroupName(data), type);
                    }
                },
                { mData: null, bSortable: false, sClass: 'text-center text-nowrap noVis',
                    mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({
                            tint: 'gems-btn-action-delete',
                            cls: 'lnkSctLocationUserDelete',
                            id: 'lnkSctLocationUserDelete_' + meta.row,
                            title: 'Delete',
                            icon: 'fas fa-trash-alt'
                        });
                    }
                }
            ]
        });
        oTableLocationUser.buttons().container().appendTo($('#btnDtSctLocationUserExport'));
        GemsUI.bindDtTooltips('#dtSctLocationUser');

        $('#dtSctLocationUser tbody').on('click', '.lnkSctLocationUserDelete', function () {
            const rowId = rowIdFromLink(this);
            const currentRow = oTableLocationUser.row(parseInt(rowId, 10)).data();
            if (currentRow) {
                modalConfirmDeleteClass.delete(currentRow['contractUserId'], modalContractUserClass);
            }
        });

        $('#txtSctLocationUserSearch').on('keyup change', function () {
            oTableLocationUser.search($(this).val()).draw();
        });

        $('#btnSctLocationUserAdd').on('click', function () {
            modalContractUserClass.add(contractId);
        });

        $('#btnDtSctLocationUserRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableLocationUser();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.load = function (_contractId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                contractId = _contractId;
                rowRefresh = _rowRefresh;

                const dataMcr = mzAjaxRequest('contract.php?contractId=' + contractId, 'GET');
                const siteId = dataMcr['siteId'];
                const clientId = refSite[siteId]['clientId'];
                const contractStatus = dataMcr['contractStatus'];
                mzSetFieldValue('SctClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('SctSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('SctContractName', dataMcr['contractName'], 'text');
                mzSetFieldValue('SctContractDesc', dataMcr['contractDesc'], 'textarea');
                mzSetFieldValue('SctContractDateStart', dataMcr['contractDateStart'], 'text');
                mzSetFieldValue('SctContractDateEnd', dataMcr['contractDateEnd'], 'text');
                mzSetFieldValue('SctContractStatus', refStatus[contractStatus]['statusDesc'], 'text');

                self.genTableLocationCode();
                self.genTableLocationUser();

                const versionLocal = mzGetDataVersion();
                const refContract = mzGetLocalArray('gems_contract', versionLocal, 'contractId', [], 'contract');
                modalLocationCodeClass.setRefContract(refContract);
                modalContractUserClass.setRefContract(refContract);

                $('.sectionContract').show();
                if (classFrom.getClassName() === 'MainContract') {
                    $('.sectionCcrMain').hide();
                }
                if (oTableLocationCode) {
                    oTableLocationCode.columns.adjust();
                }
                if (oTableLocationUser) {
                    oTableLocationUser.columns.adjust();
                }
                $(window).scrollTop(0);
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTableLocationCode = function () {
        const dataLocationCode = mzAjaxRequest('location_code.php?contractId=' + contractId, 'GET');
        oTableLocationCode.clear().rows.add(dataLocationCode).draw();
    };

    this.genTableLocationUser = function () {
        const dataLocationUser = mzAjaxRequest('contract_user.php?contractId=' + contractId, 'GET');
        oTableLocationUser.clear().rows.add(dataLocationUser).draw();
    };

    this.addTableLocationCode = function (_dataAdd) {
        oTableLocationCode.row.add(_dataAdd).draw();
    };

    this.updateTableLocationCode = function (_dataEdit, _rowEdit) {
        const currentRow = oTableLocationCode.row(_rowEdit).data();
        if (typeof _dataEdit['locationCodeName'] !== 'undefined') {
            currentRow['locationCodeName'] = _dataEdit['locationCodeName'];
        }
        if (typeof _dataEdit['locationCodeStatus'] !== 'undefined') {
            currentRow['locationCodeStatus'] = _dataEdit['locationCodeStatus'];
        }
        oTableLocationCode.row(_rowEdit).data(currentRow).draw();
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

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setRefAssetGroup = function (_refAssetGroup) {
        refAssetGroup = _refAssetGroup;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setModalLocationCodeClass = function (_modalLocationCodeClass) {
        modalLocationCodeClass = _modalLocationCodeClass;
    };

    this.setModalContractUserClass = function (_modalContractUserClass) {
        modalContractUserClass = _modalContractUserClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };
}
