function MainPpmGroup() {

    const className = 'MainPpmGroup';
    let self = this;
    let refStatus;
    let refRole;
    let refUser;
    let refClient;
    let refSite;
    let clientId = '';
    let siteId = '';
    let roleId;
    let ppmGroupId = '';
    let rowRefresh = '';
    let formValidateInfo;
    let formValidateSearch;
    let isGlobalAdmin = false;

    let modalConfirmDeleteClass;
    let modalPpmGroupClass;
    let modalPpmUserClass;

    let oTableTechnician;
    let oTableSupervisor;
    let oTableEngineer;
    let oTableWoTechnician;
    let oTableUser;

    let dataTechnician = [];
    let dataSupervisor = [];
    let dataEngineer = [];
    let dataWoTechnician = [];

    let $mainColumn;
    let $detailsColumn;

    const tableLabelsTechnician = ['#', 'Group Name', 'Report To', 'Total User', 'Status', 'Actions'];
    const tableLabelsSupervisor = ['#', 'Group Name', 'Report To', 'Total User', 'Status', 'Actions'];
    const tableLabelsEngineer = ['#', 'Group Name', 'Total User', 'Status', 'Actions'];
    const tableLabelsWoTechnician = ['#', 'Group Name', 'Total User', 'Status', 'Actions'];
    const tableLabelsUser = ['#', 'Name', 'Status', 'Actions'];


    function rowsFromRef(ref, idKey, labelKey, predicate, selectedId) {
        const rows = [];
        let hasSelected = false;
        $.each(ref || {}, function (key, item) {
            if (!item || typeof item !== 'object') {
                return true;
            }
            const row = $.extend({}, item);
            if (row[idKey] === undefined || row[idKey] === null || row[idKey] === '') {
                row[idKey] = key;
            }
            const isSelected = selectedId !== undefined && selectedId !== null && selectedId !== ''
                && String(row[idKey]) === String(selectedId);
            if (predicate && !predicate(row) && !isSelected) {
                return true;
            }
            if (isSelected) {
                hasSelected = true;
            }
            rows.push(row);
            return true;
        });
        if (selectedId !== undefined && selectedId !== null && selectedId !== '' && !hasSelected && ref && ref[selectedId]) {
            const extra = $.extend({}, ref[selectedId]);
            if (extra[idKey] === undefined || extra[idKey] === null || extra[idKey] === '') {
                extra[idKey] = selectedId;
            }
            rows.push(extra);
        }
        rows.sort(function (a, b) {
            return String(a[labelKey] || '').localeCompare(String(b[labelKey] || ''), 'en', {numeric: true});
        });
        return rows;
    }

    function fillClientSelect(selected) {
        GemsUI.fillSelect(
            'optPgrClientId',
            rowsFromRef(refClient, 'clientId', 'clientName'),
            'clientId',
            function (row) { return row['clientName'] || ''; },
            'Choose Client',
            selected
        );
    }

    function fillSiteSelect(clientKey, selected) {
        GemsUI.fillSelect(
            'optPgrSiteId',
            rowsFromRef(refSite, 'siteId', 'siteName', function (row) {
                if (clientKey && String(row['clientId']) !== String(clientKey)) {
                    return false;
                }
                return String(row['siteStatus']) === '1';
            }),
            'siteId',
            function (row) { return row['siteName'] || ''; },
            'Choose Site',
            selected
        );
    }

    function fillReportToSelect(roleCur, siteKey, placeholder, selected) {
        const versionLocal = mzGetDataVersion();
        const refPpmGroup = mzGetLocalArray('gems_ppmGroup', versionLocal, 'ppmGroupId', [], 'ppm_group');
        GemsUI.fillSelect(
            'optPgrInfoReportTo',
            rowsFromRef(refPpmGroup, 'ppmGroupId', 'ppmGroupName', function (row) {
                if (String(row['roleId']) !== String(roleCur)) {
                    return false;
                }
                if (String(row['siteId']) !== String(siteKey)) {
                    return false;
                }
                return String(row['ppmGroupStatus']) === '1';
            }, selected),
            'ppmGroupId',
            function (row) { return row['ppmGroupName'] || ''; },
            placeholder,
            selected
        );
    }

    function statusLabel(statusId, fallback) {
        if (refStatus && refStatus[statusId] && refStatus[statusId]['statusDesc']) {
            return refStatus[statusId]['statusDesc'];
        }
        return fallback || 'Unknown';
    }

    function statusBadgeKind(status) {
        switch (String(status)) {
            case '1':
                return 'success';
            case '5':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function statusBadge(statusId, type) {
        const label = statusLabel(statusId, 'Unknown');
        if (type !== 'display') {
            return label;
        }
        return GemsUI.badge(statusBadgeKind(statusId), GemsUI.escape(label));
    }

    const dtPlainDom = "<'d-none'f>r<'table-responsive't>";


    const toStr = function (value) {
        return value === null || typeof value === 'undefined' ? '' : String(value);
    };

    const refreshMaterialSelect = function (selector) {
        // Disabled to prevent double rendering - using plain select instead
        // const $element = $(selector);
        // if (!$element.length || typeof $element.materialSelect !== 'function') {
        //     return;
        // }
        // try {
        //     $element.materialSelect('destroy');
        // } catch (err) {
        //     // ignore destroy errors when component not initialised
        // }
        // $element.materialSelect();
    };

    const findFirstActiveClientId = function () {
        if (!refClient) {
            return '';
        }
        const keys = Object.keys(refClient);
        for (let i = 0; i < keys.length; i++) {
            const entry = refClient[keys[i]];
            if (!entry) {
                continue;
            }
            const status = toStr(entry['clientStatus']);
            if (status && status !== '1') {
                continue;
            }
            return toStr(entry['clientId'] || keys[i]);
        }
        if (!keys.length) {
            return '';
        }
        const fallback = refClient[keys[0]];
        return fallback ? toStr(fallback['clientId'] || keys[0]) : '';
    };

    const findFirstActiveSiteId = function (clientKey) {
        if (!refSite) {
            return '';
        }
        const targetClient = toStr(clientKey);
        const keys = Object.keys(refSite);
        let fallback = '';
        for (let i = 0; i < keys.length; i++) {
            const entry = refSite[keys[i]];
            if (!entry) {
                continue;
            }
            if (targetClient && toStr(entry['clientId']) !== targetClient) {
                continue;
            }
            const siteIdCandidate = toStr(entry['siteId'] || keys[i]);
            const status = toStr(entry['siteStatus']);
            if (status === '1') {
                return siteIdCandidate;
            }
            if (!fallback) {
                fallback = siteIdCandidate;
            }
        }
        if (!fallback && keys.length) {
            const entry = refSite[keys[0]];
            fallback = entry ? toStr(entry['siteId'] || keys[0]) : '';
        }
        return fallback;
    };

    const getNowStamp = function () {
        if (typeof moment !== 'undefined' && typeof moment === 'function') {
            return moment().format('DD MMM YYYY, h:mm A');
        }
        return new Date().toLocaleString();
    };

    const numberFormat = function (value) {
        if (typeof mzFormatNumber === 'function') {
            return mzFormatNumber(value, 0);
        }
        const num = Number(value);
        if (!isFinite(num)) {
            return value;
        }
        return num.toLocaleString();
    };

    const applyRowLabels = function (nRow, labels) {
        if (!labels || !labels.length) {
            return;
        }
        $('td', nRow).each(function (index) {
            if (labels[index]) {
                $(this).attr('data-label', labels[index]);
            }
        });
    };

    const setDetailsVisible = function (isVisible) {
        if (!$mainColumn || !$mainColumn.length || !$detailsColumn || !$detailsColumn.length) {
            return;
        }
        if (isVisible) {
            $detailsColumn.removeClass('d-none');
            $mainColumn.removeClass('col-xl-12 col-lg-12').addClass('col-xl-8 col-lg-7');
        } else {
            $detailsColumn.addClass('d-none');
            $mainColumn.removeClass('col-xl-8 col-lg-7').addClass('col-xl-12 col-lg-12');
        }
    };

    const getClientName = function (id) {
        const key = toStr(id);
        if (key && refClient && refClient[key]) {
            return refClient[key]['clientName'] || 'Client';
        }
        return key ? `Client ${key}` : 'All Clients';
    };

    const getSiteName = function (id) {
        const key = toStr(id);
        if (key && refSite && refSite[key]) {
            return refSite[key]['siteName'] || 'Site';
        }
        return key ? `Site ${key}` : 'All Sites';
    };

    const updateScopeSummary = function () {
        const clientName = getClientName(clientId);
        const hasSite = toStr(siteId) !== '';
        const siteName = hasSite ? getSiteName(siteId) : '';
        $('#lblPgrScope').text(hasSite ? `${clientName} • ${siteName}` : clientName);
        $('#lblPgrSummary').text(hasSite ? `Scoped to ${clientName} / ${siteName}` : `Scoped to ${clientName}`);
    };

    const updateMetricCards = function () {
        const allGroups = [].concat(dataTechnician, dataSupervisor, dataEngineer, dataWoTechnician);
        let totalGroups = 0;
        let activeGroups = 0;
        let inactiveGroups = 0;
        let totalMembers = 0;

        allGroups.forEach(function (group) {
            totalGroups += 1;
            const statusVal = String(group['ppmGroupStatus'] || '');
            if (statusVal === '1') {
                activeGroups += 1;
            } else {
                inactiveGroups += 1;
            }
            const members = Number(group['totalUser']);
            if (Number.isFinite(members)) {
                totalMembers += members;
            }
        });

        $('#metricPgrGroups').text(numberFormat(totalGroups));
        $('#metricPgrActive').text(numberFormat(activeGroups));
        $('#metricPgrInactive').text(numberFormat(inactiveGroups));
        $('#metricPgrMembers').text(numberFormat(totalMembers));
    };

    const updateGroupSummary = function (tableInstance, countSelector, updatedSelector, noun) {
        const nounLabel = noun || 'group';
        const $count = $(countSelector);
        const $updated = $(updatedSelector);
        if (!tableInstance) {
            $count.text(`No ${nounLabel}s loaded`);
            $updated.text('Updated --');
            return;
        }
        const filteredCount = tableInstance.rows({filter: 'applied'}).count();
        const label = filteredCount ? `Showing ${filteredCount} ${nounLabel}${filteredCount === 1 ? '' : 's'}` : `No ${nounLabel}s loaded`;
        $count.text(label);
        $updated.text(`Updated ${getNowStamp()}`);
    };

    const updateUserSummary = function () {
        if (!oTableUser) {
            $('#lblPgrUserCount').text('No users loaded');
            $('#lblPgrUserUpdated').text('Updated --');
            return;
        }
        const filteredCount = oTableUser.rows({filter: 'applied'}).count();
        $('#lblPgrUserCount').text(filteredCount ? `Showing ${filteredCount} user${filteredCount === 1 ? '' : 's'}` : 'No users loaded');
        $('#lblPgrUserUpdated').text(`Updated ${getNowStamp()}`);
    };

    const bindRefreshButton = function (selector, handler) {
        $(selector).off('click').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    handler();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });
    };

    const refreshAllTables = function () {
        self.genTableTechnician();
        self.genTableSupervisor();
        self.genTableEngineer();
        self.genTableWoTechnician();
    };

    this.init = function () {
        const userClientId = toStr(mzGetUserInfoByParam('clientId'));
        const userSiteId = toStr(mzGetUserInfoByParam('siteId'));
        isGlobalAdmin = mzIsRoleExist('1,10');

        clientId = userClientId || findFirstActiveClientId();
        siteId = userSiteId || findFirstActiveSiteId(clientId);

        $mainColumn = $('#divPgrMain');
        $detailsColumn = $('#divPgrDetails');

        setDetailsVisible(false);

        fillClientSelect(clientId);
        fillSiteSelect(clientId, siteId);

        $('#optPgrClientId').on('change', function () {
            const selectedClient = toStr($(this).val());
            clientId = selectedClient;
            fillSiteSelect(selectedClient, '');
            siteId = '';
            if (formValidateSearch) {
                $('#btnPgrSearch').attr('disabled', !formValidateSearch.validateForm());
            }
        });

        $('#optPgrSiteId').on('change', function () {
            siteId = toStr($(this).val());
            if (formValidateSearch) {
                $('#btnPgrSearch').attr('disabled', !formValidateSearch.validateForm());
            }
        });

        const vData = [
            {
                field_id: 'optPgrClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'optPgrSiteId',
                type: 'select',
                name: 'Site',
                validator: {
                    notEmpty: true
                }
            }
        ];

        formValidateSearch = new MzValidate('formPgrSearch');
        formValidateSearch.registerFields(vData);

        $('#formPgrSearch').on('keyup change', function () {
            $('#btnPgrSearch').attr('disabled', !formValidateSearch.validateForm());
        });

        if (!isGlobalAdmin) {
            $('#optPgrClientId, #optPgrSiteId').prop('disabled', true);
        }
        $('#btnPgrSearch').attr('disabled', !formValidateSearch.validateForm());

        oTableTechnician = $('#dtPgrTechnician').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableTechnician.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
                applyRowLabels(nRow, tableLabelsTechnician);
            },
            drawCallback: function () {
                updateGroupSummary(oTableTechnician, '#lblPgrTechnicianCount', '#lblPgrTechnicianUpdated', 'group');
            },
            language: GemsUI.dtEmpty('fa-users', 'No groups loaded.'),
            dom: dtPlainDom,
            aoColumns: [
                {mData: null, bSortable: false},
                {
                    mData: 'ppmGroupName',
                    bSortable: false,
                    mRender: function (data, type) {
                        return type === 'display' ? GemsUI.escape(data || '') : (data || '');
                    }
                },
                {
                    mData: 'reportTo',
                    bSortable: false,
                    mRender: function (data, type) {
                        return type === 'display' ? GemsUI.escape(data || '') : (data || '');
                    }
                },
                {mData: 'totalUser', bSortable: false},
                {
                    mData: null,
                    bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['ppmGroupStatus'], type);
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({id:'lnkPgrTechnicianEdit_' + meta.row, cls:'lnkPgrTechnicianEdit', icon:'fas fa-edit', title:'Edit'})
                            + GemsUI.actionBtn({id:'lnkPgrTechnicianDelete_' + meta.row, cls:'lnkPgrTechnicianDelete', icon:'fas fa-trash-alt', title:'Delete'});
                    }
                }
            ]
        });
        $('#dtPgrTechnician_filter').hide();
        GemsUI.bindDtTooltips('#dtPgrTechnician');

        // Event delegation for Technician table
        $('#dtPgrTechnician').on('click', '.lnkPgrTechnicianEdit', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableTechnician.row(parseInt(rowId, 10)).data();
                self.viewDetails(currentRow['ppmGroupId'], rowId);
                setDetailsVisible(true);
            }
        });
        $('#dtPgrTechnician').on('click', '.lnkPgrTechnicianDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableTechnician.row(parseInt(rowId, 10)).data();
                modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
            }
        });

        $('#btnPgrTechnicianAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '5');
        });

        bindRefreshButton('#btnDtPgrTechnicianRefresh', function () {
            self.genTableTechnician();
        });

        oTableSupervisor = $('#dtPgrSupervisor').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableSupervisor.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
                applyRowLabels(nRow, tableLabelsSupervisor);
            },
            drawCallback: function () {
                updateGroupSummary(oTableSupervisor, '#lblPgrSupervisorCount', '#lblPgrSupervisorUpdated', 'group');
            },
            language: GemsUI.dtEmpty('fa-users', 'No groups loaded.'),
            dom: dtPlainDom,
            aoColumns: [
                {mData: null, bSortable: false},
                {
                    mData: 'ppmGroupName',
                    bSortable: false,
                    mRender: function (data, type) {
                        return type === 'display' ? GemsUI.escape(data || '') : (data || '');
                    }
                },
                {
                    mData: 'reportTo',
                    bSortable: false,
                    mRender: function (data, type) {
                        return type === 'display' ? GemsUI.escape(data || '') : (data || '');
                    }
                },
                {mData: 'totalUser', bSortable: false},
                {
                    mData: null,
                    bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['ppmGroupStatus'], type);
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({id:'lnkPgrSupervisorEdit_' + meta.row, cls:'lnkPgrSupervisorEdit', icon:'fas fa-edit', title:'Edit'})
                            + GemsUI.actionBtn({id:'lnkPgrSupervisorDelete_' + meta.row, cls:'lnkPgrSupervisorDelete', icon:'fas fa-trash-alt', title:'Delete'});
                    }
                }
            ]
        });
        $('#dtPgrSupervisor_filter').hide();
        GemsUI.bindDtTooltips('#dtPgrSupervisor');

        // Event delegation for Supervisor table
        $('#dtPgrSupervisor').on('click', '.lnkPgrSupervisorEdit', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableSupervisor.row(parseInt(rowId, 10)).data();
                self.viewDetails(currentRow['ppmGroupId'], rowId);
                setDetailsVisible(true);
            }
        });
        $('#dtPgrSupervisor').on('click', '.lnkPgrSupervisorDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableSupervisor.row(parseInt(rowId, 10)).data();
                modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
            }
        });

        $('#btnPgrSupervisorAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '3');
        });

        bindRefreshButton('#btnDtPgrSupervisorRefresh', function () {
            self.genTableSupervisor();
        });

        oTableEngineer = $('#dtPgrEngineer').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableEngineer.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
                applyRowLabels(nRow, tableLabelsEngineer);
            },
            drawCallback: function () {
                updateGroupSummary(oTableEngineer, '#lblPgrEngineerCount', '#lblPgrEngineerUpdated', 'group');
            },
            language: GemsUI.dtEmpty('fa-users', 'No groups loaded.'),
            dom: dtPlainDom,
            aoColumns: [
                {mData: null, bSortable: false},
                {
                    mData: 'ppmGroupName',
                    bSortable: false,
                    mRender: function (data, type) {
                        return type === 'display' ? GemsUI.escape(data || '') : (data || '');
                    }
                },
                {mData: 'totalUser', bSortable: false},
                {
                    mData: null,
                    bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['ppmGroupStatus'], type);
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({id:'lnkPgrEngineerEdit_' + meta.row, cls:'lnkPgrEngineerEdit', icon:'fas fa-edit', title:'Edit'})
                            + GemsUI.actionBtn({id:'lnkPgrEngineerDelete_' + meta.row, cls:'lnkPgrEngineerDelete', icon:'fas fa-trash-alt', title:'Delete'});
                    }
                }
            ]
        });
        $('#dtPgrEngineer_filter').hide();
        GemsUI.bindDtTooltips('#dtPgrEngineer');

        $('#dtPgrEngineer').on('click', '.lnkPgrEngineerEdit', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                self.handleEngineerEdit(parseInt(linkId.substr(linkIndex + 1), 10));
            }
        });
        $('#dtPgrEngineer').on('click', '.lnkPgrEngineerDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                self.handleEngineerDelete(parseInt(linkId.substr(linkIndex + 1), 10));
            }
        });

        $('#btnPgrEngineerAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '4');
        });

        bindRefreshButton('#btnDtPgrEngineerRefresh', function () {
            self.genTableEngineer();
        });

        oTableWoTechnician = $('#dtPgrWoTechnician').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableWoTechnician.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
                applyRowLabels(nRow, tableLabelsWoTechnician);
            },
            drawCallback: function () {
                updateGroupSummary(oTableWoTechnician, '#lblPgrWoTechnicianCount', '#lblPgrWoTechnicianUpdated', 'group');
            },
            language: GemsUI.dtEmpty('fa-users', 'No groups loaded.'),
            dom: dtPlainDom,
            aoColumns: [
                {mData: null, bSortable: false},
                {
                    mData: 'ppmGroupName',
                    bSortable: false,
                    mRender: function (data, type) {
                        return type === 'display' ? GemsUI.escape(data || '') : (data || '');
                    }
                },
                {mData: 'totalUser', bSortable: false},
                {
                    mData: null,
                    bSortable: false,
                    mRender: function (data, type, row) {
                        return statusBadge(row['ppmGroupStatus'], type);
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({id:'lnkPgrWoTechnicianEdit_' + meta.row, cls:'lnkPgrWoTechnicianEdit', icon:'fas fa-edit', title:'Edit'})
                            + GemsUI.actionBtn({id:'lnkPgrWoTechnicianDelete_' + meta.row, cls:'lnkPgrWoTechnicianDelete', icon:'fas fa-trash-alt', title:'Delete'});
                    }
                }
            ]
        });
        $('#dtPgrWoTechnician_filter').hide();
        GemsUI.bindDtTooltips('#dtPgrWoTechnician');

        // Event delegation for WoTechnician table
        $('#dtPgrWoTechnician').on('click', '.lnkPgrWoTechnicianEdit', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableWoTechnician.row(parseInt(rowId, 10)).data();
                self.viewDetails(currentRow['ppmGroupId'], rowId);
                setDetailsVisible(true);
            }
        });
        $('#dtPgrWoTechnician').on('click', '.lnkPgrWoTechnicianDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableWoTechnician.row(parseInt(rowId, 10)).data();
                modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
            }
        });

        $('#btnPgrWoTechnicianAdd').on('click', function () {
            modalPpmGroupClass.add(siteId, '8');
        });

        bindRefreshButton('#btnDtPgrWoTechnicianRefresh', function () {
            self.genTableWoTechnician();
        });

        const vDataInfo = [
            {
                field_id: 'txtPgrInfoGroupName',
                type: 'text',
                name: 'Group Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'optPgrInfoReportTo',
                type: 'select',
                name: 'Report To',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'chkPgrInfoStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {}
            }
        ];

        formValidateInfo = new MzValidate('formPgrInfo');
        formValidateInfo.registerFields(vDataInfo);

        $('#formPgrInfo').on('keyup change', function () {
            $('#btnPgrInfoUpdate').attr('disabled', !formValidateInfo.validateForm());
        });

        $('#btnPgrInfoUpdate').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidateInfo.validateNow()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    } else {
                        const txtName = $('#txtPgrInfoGroupName').val();
                        const reportTo = roleId === '5' || roleId === '3' ? $('#optPgrInfoReportTo').val() : '';
                        const statusVal = $("input[name='chkPgrInfoStatus']").is(':checked') ? '1' : '2';
                        const data = {
                            ppmGroupId: ppmGroupId,
                            ppmGroupName: txtName,
                            siteId: siteId,
                            roleId: roleId,
                            reportTo: reportTo,
                            ppmGroupStatus: statusVal,
                            action: 'update'
                        };

                        mzAjaxRequest('ppm_group.php?ppmGroupId=' + ppmGroupId, 'PUT', data);

                        if (roleId === '5') {
                            self.genTableTechnician();
                        } else if (roleId === '3') {
                            self.genTableSupervisor();
                        } else if (roleId === '4') {
                            self.genTableEngineer();
                        } else if (roleId === '8') {
                            self.genTableWoTechnician();
                        }
                        $('#btnPgrInfoUpdate').attr('disabled', true);
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableUser = $('#dtPgrUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            bInfo: false,
            bPaginate: false,
            autoWidth: false,
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = oTableUser.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
                applyRowLabels(nRow, tableLabelsUser);
            },
            drawCallback: function () {
                updateUserSummary();
            },
            language: GemsUI.dtEmpty('fa-user', 'No users loaded.'),
            dom: dtPlainDom,
            aoColumns: [
                {mData: null, bSortable: false},
                {
                    mData: 'userId',
                    bSortable: false,
                    mRender: function (data, type) {
                        const name = refUser[data] ? refUser[data]['userFirstName'] : data;
                        return type === 'display' ? GemsUI.escape(name || '') : (name || '');
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    mRender: function (data, type, row) {
                        const userId = row['userId'];
                        const userStatus = refUser[userId] ? refUser[userId]['userStatus'] : '';
                        if (!refStatus[userStatus]) {
                            return type === 'display' ? '-' : '';
                        }
                        return statusBadge(userStatus, type);
                    }
                },
                {
                    mData: null,
                    bSortable: false,
                    sClass: 'text-center',
                    mRender: function (data, type, row, meta) {
                        return GemsUI.actionBtn({id:'lnkPgrUserDelete_' + meta.row, cls:'lnkPgrUserDelete', icon:'fas fa-trash-alt', title:'Delete'});
                    }
                }
            ]
        });
        $('#dtPgrUser_filter').hide();
        GemsUI.bindDtTooltips('#dtPgrUser');

        // Event delegation for User table
        $('#dtPgrUser').on('click', '.lnkPgrUserDelete', function () {
            const linkId = $(this).attr('id');
            const linkIndex = linkId.indexOf('_');
            if (linkIndex > 0) {
                const rowId = linkId.substr(linkIndex + 1);
                const currentRow = oTableUser.row(parseInt(rowId, 10)).data();
                modalConfirmDeleteClass.delete(currentRow['ppmGroupUserId'], modalPpmUserClass);
            }
        });

        $('#btnPgrUserAdd').on('click', function () {
            modalPpmUserClass.add(ppmGroupId, siteId, roleId);
        });

        bindRefreshButton('#btnDtPgrUserRefresh', function () {
            self.genTableUser();
        });

        $('#btnPgrSearch').on('click', function () {
            if (!formValidateSearch.validateNow()) {
                toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                return;
            }
            ShowLoader();
            setTimeout(function () {
                try {
                    clientId = toStr($('#optPgrClientId').val());
                    siteId = toStr($('#optPgrSiteId').val());
                    refreshAllTables();
                    updateScopeSummary();
                    setDetailsVisible(false);
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        updateScopeSummary();
        refreshAllTables();
    };

    this.viewDetails = function (_ppmGroupId, _rowId) {
        ShowLoader();
        setTimeout(function () {
            try {
                formValidateInfo.clearValidation();
                $('#btnPgrInfoUpdate').attr('disabled', true);

                mzCheckFuncParam([_ppmGroupId, _rowId]);
                ppmGroupId = _ppmGroupId;
                rowRefresh = _rowId;

                const dataPpmGroup = mzAjaxRequest('ppm_group.php?ppmGroupId=' + ppmGroupId, 'GET');
                roleId = dataPpmGroup['roleId'];
                siteId = dataPpmGroup['siteId'];
                clientId = toStr(refSite[siteId]['clientId']);

                if (roleId === '5' || roleId === '3') {
                    const roleCur = roleId === '5' ? '3' : '4';
                    const defaultText = roleId === '5' ? 'Choose Supervisor Group' : 'Choose Engineer Group';
                    fillReportToSelect(roleCur, siteId, defaultText, dataPpmGroup['ppmGroupReportTo']);
                    formValidateInfo.enableField('optPgrInfoReportTo');
                    $('#divPgrInfoReportTo').show();
                } else {
                    formValidateInfo.disableField('optPgrInfoReportTo');
                    $('#divPgrInfoReportTo').hide();
                }

                mzSetFieldValue('PgrInfoReportTo', dataPpmGroup['ppmGroupReportTo'], 'select', 'Report to *');
                mzSetFieldValue('PgrInfoGroupName', dataPpmGroup['ppmGroupName'], 'text');
                mzSetFieldValue('PgrInfoRoleDesc', refRole[roleId]['roleDesc'], 'text');
                mzSetFieldValue('PgrInfoClientName', refClient[clientId]['clientName'], 'text');
                mzSetFieldValue('PgrInfoSiteName', refSite[siteId]['siteName'], 'text');
                mzSetFieldValue('PgrInfoStatus', dataPpmGroup['ppmGroupStatus'], 'checkSingle', '1');

                self.genTableUser();
                setDetailsVisible(true);
                updateScopeSummary();
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.genTableTechnician = function () {
        if (!toStr(siteId)) {
            dataTechnician = [];
            oTableTechnician.clear().rows.add([]).draw();
            updateMetricCards();
            return;
        }
        const dataTechnicianDb = mzAjaxRequest('ppm_group.php?roleId=5&siteId=' + siteId, 'GET');
        dataTechnician = Array.isArray(dataTechnicianDb) ? dataTechnicianDb : [];
        oTableTechnician.clear().rows.add(dataTechnician).draw();
        updateMetricCards();
    };

    this.genTableSupervisor = function () {
        if (!toStr(siteId)) {
            dataSupervisor = [];
            oTableSupervisor.clear().rows.add([]).draw();
            updateMetricCards();
            return;
        }
        const dataSupervisorDb = mzAjaxRequest('ppm_group.php?roleId=3&siteId=' + siteId, 'GET');
        dataSupervisor = Array.isArray(dataSupervisorDb) ? dataSupervisorDb : [];
        oTableSupervisor.clear().rows.add(dataSupervisor).draw();
        updateMetricCards();
    };

    this.genTableEngineer = function () {
        if (!toStr(siteId)) {
            dataEngineer = [];
            oTableEngineer.clear().rows.add([]).draw();
            updateMetricCards();
            return;
        }
        const dataEngineerDb = mzAjaxRequest('ppm_group.php?roleId=4&siteId=' + siteId, 'GET');
        dataEngineer = Array.isArray(dataEngineerDb) ? dataEngineerDb : [];
        oTableEngineer.clear().rows.add(dataEngineer).draw();
        updateMetricCards();
    };

    this.genTableWoTechnician = function () {
        if (!toStr(siteId)) {
            dataWoTechnician = [];
            oTableWoTechnician.clear().rows.add([]).draw();
            updateMetricCards();
            return;
        }
        const dataWoTechnicianDb = mzAjaxRequest('ppm_group.php?roleId=8&siteId=' + siteId, 'GET');
        dataWoTechnician = Array.isArray(dataWoTechnicianDb) ? dataWoTechnicianDb : [];
        oTableWoTechnician.clear().rows.add(dataWoTechnician).draw();
        updateMetricCards();
    };

    this.genTableUser = function () {
        if (!toStr(ppmGroupId)) {
            oTableUser.clear().rows.add([]).draw();
            return;
        }
        const dataUser = mzAjaxRequest('ppm_group.php?type=ppm_group_user&ppmGroupId=' + ppmGroupId, 'GET');
        oTableUser.clear().rows.add(Array.isArray(dataUser) ? dataUser : []).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.getPpmGroupId = function () {
        return ppmGroupId;
    };

    this.hideDetails = function () {
        setDetailsVisible(false);
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefClient = function (_refClient) {
        refClient = _refClient;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalPpmGroupClass = function (_modalPpmGroupClass) {
        modalPpmGroupClass = _modalPpmGroupClass;
    };

    this.setModalPpmUserClass = function (_modalPpmUserClass) {
        modalPpmUserClass = _modalPpmUserClass;
    };

    this.setModalConfirmDeleteClass = function (_modalConfirmDeleteClass) {
        modalConfirmDeleteClass = _modalConfirmDeleteClass;
    };

    // Direct handler methods for Engineer table buttons
    this.handleEngineerEdit = function (rowIndex) {
        const currentRow = oTableEngineer.row(rowIndex).data();
        if (currentRow) {
            self.viewDetails(currentRow['ppmGroupId'], rowIndex);
            setDetailsVisible(true);
        }
    };

    this.handleEngineerDelete = function (rowIndex) {
        const currentRow = oTableEngineer.row(rowIndex).data();
        if (currentRow) {
            modalConfirmDeleteClass.delete(currentRow['ppmGroupId'], modalPpmGroupClass);
        }
    };
}
