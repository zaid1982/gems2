function MainUserManagement() {

    const className = 'MainUserManagement';
    const momentAvailable = (typeof moment === 'function');
    let self = this;
    let refStatus;
    let refRole;
    let refDesignation;
    let refGroup;
    let refSite;
    let oTableUser;
    let modalUserClass;
    let refUserType;
    let modalEditPasswordClass;
    let lastUpdated = null;

    function formatTimestamp(value) {
        if (!value) {
            return 'Updated —';
        }
        if (momentAvailable) {
            return 'Updated ' + moment(value).format('DD MMM YYYY, hh:mm A');
        }
        return 'Updated ' + new Date(value).toLocaleString();
    }

    function updateMetrics() {
        if (!oTableUser) {
            return;
        }
        const data = oTableUser.rows({search: 'applied'}).data();
        let total = 0;
        let active = 0;
        let inactive = 0;
        const siteSet = new Set();
        for (let i = 0; i < data.length; i++) {
            const row = data[i];
            if (!row) {
                continue;
            }
            total++;
            if (row['userStatus'] === '1') {
                active++;
            } else {
                inactive++;
            }
            if (row['siteId']) {
                siteSet.add(row['siteId']);
            }
        }
        $('#metricUmnTotal').text(total.toLocaleString());
        $('#metricUmnActive').text(active.toLocaleString());
        $('#metricUmnInactive').text(inactive.toLocaleString());
        $('#metricUmnSites').text(siteSet.size.toLocaleString());
    }

    function updateSummary() {
        if (!oTableUser) {
            return;
        }
        const info = (oTableUser && typeof oTableUser.page === 'function' && typeof oTableUser.page.info === 'function')
            ? oTableUser.page.info()
            : null;
        if (info) {
            $('#lblUmnUserCount').text('Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' records');
        } else {
            $('#lblUmnUserCount').text('Showing 0 of 0 records');
        }
        $('#lblUmnUserUpdated').text(formatTimestamp(lastUpdated));
    }

    function updateFilterSummary() {
        const $select = $('#optUmnGroupId');
        if (!$select.length) {
            return;
        }
        const value = $select.val();
        let label = 'Site: All Sites';
        if (value) {
            const text = ($select.find('option:selected').text() || '').trim();
            if (text) {
                label = 'Site: ' + text;
            }
        }
        $('#lblUmnUserFilter').text(label);
    }

    this.init = function () {
        mzOption('optUmnGroupId', refSite, 'All Sites', 'siteId', 'siteName', {siteStatus: '1'}, '', false);
        updateFilterSummary();

        refUserType = ['', 'GFM Internal', 'Client', 'Public User'];
        oTableUser = $('#dtUmnUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [9, 'desc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = (oTableUser && oTableUser.page && typeof oTableUser.page.info === 'function')
                    ? oTableUser.page.info()
                    : null;
                const rowNumber = info ? (info.page * info.length + (iDisplayIndex + 1)) : (iDisplayIndex + 1);
                $('td', nRow).eq(0).html(rowNumber).attr('data-label', '#');
                $('td', nRow).eq(1).attr('data-label', 'Name');
                $('td', nRow).eq(2).attr('data-label', 'Site');
                $('td', nRow).eq(3).attr('data-label', 'Type');
                $('td', nRow).eq(4).attr('data-label', 'Contact No.');
                $('td', nRow).eq(5).attr('data-label', 'Email');
                $('td', nRow).eq(6).attr('data-label', 'Roles');
                $('td', nRow).eq(7).attr('data-label', 'Status');
                $('td', nRow).eq(8).attr('data-label', 'Actions');
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('.lnkUmnUserEdit').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableUser.row(parseInt(rowId)).data();
                        modalUserClass.edit(currentRow['userId'], rowId);
                    }
                });
                $('.lnkUmnUserPassword').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableUser.row(parseInt(rowId)).data();
                        modalEditPasswordClass.edit(currentRow['userId'], rowId);
                    }
                });
                $('.lnkUmnUserDeactivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableUser.row(parseInt(rowId)).data();
                        modalUserClass.deactivate(currentRow['userId'], rowId);
                    }
                });
                $('.lnkUmnUserActivate').off('click').on('click', function () {
                    const linkId = $(this).attr('id');
                    const linkIndex = linkId.indexOf('_');
                    if (linkIndex > 0) {
                        const rowId = linkId.substr(linkIndex+1);
                        const currentRow = oTableUser.row(parseInt(rowId)).data();
                        modalUserClass.activate(currentRow['userId'], rowId);
                    }
                });
                updateMetrics();
                updateSummary();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: 'userFullName'},
                    {mData: null, mRender: function (data, type, row){
                            return row['siteId'] !== '' ? refSite[row['siteId']]['siteName'] : '';
                        }},
                    {mData: 'userType', mRender: function (data){
                            return refUserType[data];
                        }},
                    {mData: 'userContactNo'},
                    {mData: 'userEmail', mRender: function (data){
                            return mzEmailShort(data, 20);
                        }},
                    {mData: null,
                        mRender: function (data, type, row) {
                            let label = '';
                            let rowData = row['roles'];
                            if (rowData != '') {
                                label = '<ul style="padding-left: 0px; margin-bottom: 0px !important;">';
                                const dataSplit = rowData.split(',');
                                for (let j=0; j<dataSplit.length; j++) {
                                    // Add null checks to prevent errors when refRole is not loaded yet
                                    if (refRole && refRole[dataSplit[j]] && refRole[dataSplit[j]]['roleDesc']) {
                                        label += '<li>' + refRole[dataSplit[j]]['roleDesc'] + '</li>';
                                    } else {
                                        // Fallback to roleId if roleDesc is not available
                                        label += '<li>Role ' + dataSplit[j] + '</li>';
                                    }
                                }
                                label += '</ul>';
                            }
                            return label;
                        }
                    },
                    {mData: null,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['userStatus']]['statusColor']+' z-depth-2">'+refStatus[row['userStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            let label = '<div class="action-btn-group">';
                            label += '<button type="button" class="btn-action btn-edit lnkUmnUserEdit" id="lnkUmnUserEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"><i class="fas fa-edit"></i></button>';
                            label += '<button type="button" class="btn-action btn-edit lnkUmnUserPassword" id="lnkUmnUserPassword_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit Password"><i class="fas fa-unlock-alt"></i></button>';
                            if (row['userStatus'] === '1') {
                                label += '<button type="button" class="btn-action btn-deactivate lnkUmnUserDeactivate" id="lnkUmnUserDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"><i class="fas fa-toggle-off"></i></button>';
                            } else {
                                label += '<button type="button" class="btn-action btn-activate lnkUmnUserActivate" id="lnkUmnUserActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"><i class="fas fa-toggle-on"></i></button>';
                            }
                            label += '</div>';
                            return label;
                        }
                    },
                    {mData: 'userId', visible: false},
                    {mData: 'userStatus', visible: false},
                    {mData: 'roles', visible: false},
                    {mData: 'groupId', visible: false},
                    {mData: 'designationId', visible: false},
                    {mData: 'siteId', visible: false},
                ]
        });
        $("#dtUmnUser_filter").hide();
        $('#txtUmnUserSearch').on('keyup change', function () {
            oTableUser.search($(this).val()).draw();
        });
        $('#optUmnGroupId').on('change', function () {
            const value = $(this).val();
            if (value) {
                oTableUser.column(14).search('^' + value + '$', true, false, true).draw();
            } else {
                oTableUser.column(14).search('', true, false, true).draw();
            }
            updateFilterSummary();
        });
        /*$('#linkUmn0').on('click', function () {
            oTableUser.column(11).search('').draw();
        });
        $('#linkUmn1').on('click', function () {
            oTableUser.column(11).search('1', false, true, false).draw();
        });
        $('#linkUmn2').on('click', function () {
            oTableUser.column(11).search('2', false, true, false).draw();
        });
        $('#linkUmn3').on('click', function () {
            oTableUser.column(11).search('3', false, true, false).draw();
        });
        $('#linkUmn4').on('click', function () {
            oTableUser.column(11).search('4', false, true, false).draw();
        });
        $('#linkUmn5').on('click', function () {
            oTableUser.column(11).search('5', false, true, false).draw();
        });*/

        let cntUser;
        let btnUserOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7],
                format: {
                    body: function ( data, row, column ) {
                        if (row === 0 && column === 0) {
                            cntUser = 1;
                        }
                        if (column === 6) {
                            const m = data.replace('<ul style="padding-left: 0px; margin-bottom: 0px !important;"><li>','');
                            const p = m.replace('</li></ul>', '');
                            const q = p.split('</li><li>').join(', ');
                            return q;
                        } else if (column === 7) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return column === 0 ? cntUser++ : data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableUser, {
            buttons: [
                $.extend( true, {}, btnUserOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - System User List',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnUserOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - System User List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnUserOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - System User List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-primary btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtUmnUserExport'));

        $('#btnDtUmnUserRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableUser();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 200);
        });

        $('#btnUmnUserAdd').on('click', function () {
            modalUserClass.add('umn');
        });

        updateMetrics();
        updateSummary();
        this.genTableUser();
    };

    this.genTableUser = function () {
        console.log('genTableUser called, refRole:', refRole);
        // Ensure refRole is loaded before proceeding
        if (!refRole || Object.keys(refRole).length === 0) {
            console.warn('Role data not loaded yet, retrying in 500ms...');
            setTimeout(() => this.genTableUser(), 500);
            return;
        }
        
        mzAjaxRequest('profile.php', 'GET', {Reportid: '1', 'Cache-Control': 'no-cache, no-transform'}, 'userManagementClass_.displayChart()');
        const dataUser = mzAjaxRequest('profile.php', 'GET');
        lastUpdated = new Date();
        oTableUser.clear().rows.add(dataUser).draw();
    };

    this.displayChart = function (result) {
        if (typeof result === 'string') {
            try {
                result = JSON.parse(result);
            } catch (e) {
                console.error('User Management chart: invalid JSON string result', e, result);
                return;
            }
        }

        if (!Array.isArray(result)) {
            console.warn('User Management chart: unexpected result type', result);
            return;
        }

        // Ensure refRole is loaded before proceeding
        if (!refRole || Object.keys(refRole).length === 0) {
            console.warn('Role data not loaded yet, chart will be updated when data is available');
            console.log('refRole:', refRole);
            return;
        }
        
        console.log('Processing chart data with refRole:', refRole);

        let chartData = [];
        /*let total0 = 0;
        let total1 = 0;
        let total2 = 0;
        let total3 = 0;
        let total4 = 0;
        let total5 = 0;
        let total6 = 0;
        let total7 = 0;
        let total8 = 0;
        let total9 = 0;
        let total10 = 0;
        let total11 = 0;*/

        $.each(result, function (n, u) {
            // Add null checks to prevent errors when refRole is not loaded yet
            if (refRole && refRole[u['roleId']] && refRole[u['roleId']]['roleDesc']) {
                chartData.push({name:refRole[u['roleId']]['roleDesc'], y:parseInt(u['total'])});
            } else {
                // Fallback to roleId if roleDesc is not available
                chartData.push({name:'Role ' + u['roleId'], y:parseInt(u['total'])});
            }
            /*if (u['roleId'] === '1') {
                total1 = parseInt(u['total']);
            } else if (u['roleId'] === '2') {
                total2 = parseInt(u['total']);
            } else if (u['roleId'] === '3') {
                total3 = parseInt(u['total']);
            } else if (u['roleId'] === '4') {
                total4 = parseInt(u['total']);
            } else if (u['roleId'] === '5') {
                total5 = parseInt(u['total']);
            } else if (u['roleId'] === '6') {
                total6 = parseInt(u['total']);
            } else if (u['roleId'] === '7') {
                total7 = parseInt(u['total']);
            } else if (u['roleId'] === '8') {
                total8 = parseInt(u['total']);
            } else if (u['roleId'] === '9') {
                total9 = parseInt(u['total']);
            } else if (u['roleId'] === '10') {
                total10 = parseInt(u['total']);
            } else if (u['roleId'] === '11') {
                total11 = parseInt(u['total']);
            }
            total0 += parseInt(u['total']);*/
        });

        const categories = chartData.map(function (item) {
            return item.name;
        });
        const seriesData = chartData.map(function (item) {
            return item.y;
        });

        if (typeof Highcharts === 'undefined' || typeof Highcharts.chart !== 'function') {
            console.error('Highcharts is not available; cannot render user role chart');
            return;
        }

        Highcharts.chart('chartUmnLeaveByStatus', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Role Distribution'
            },
            xAxis: {
                categories: categories,
                crosshair: true,
                labels: {
                    style: {
                        color: '#0F172A',
                        fontWeight: '600'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'No. of Users',
                    style: {
                        color: '#0F172A',
                        fontWeight: '600'
                    }
                },
                gridLineColor: 'rgba(15, 23, 42, 0.08)',
                labels: {
                    style: {
                        color: '#64748B'
                    }
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                headerFormat: '<span style="font-size: 12px">{point.key}</span><br/>',
                pointFormat: '<span style="color:{point.color}">\u25CF</span> Users: <b>{point.y}</b>'
            },
            credits: {
                enabled: false
            },
            colors: ['#4338CA', '#6366F1', '#8B5CF6', '#312E81', '#C7D2FE', '#A5B4FC', '#60A5FA', '#7C3AED'],
            plotOptions: {
                column: {
                    colorByPoint: true,
                    borderRadius: 6,
                    pointPadding: 0.18,
                    groupPadding: 0.12,
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: '#0F172A',
                            fontWeight: '600'
                        }
                    }
                }
            },
            series: [{
                name: 'Users',
                data: seriesData
            }]
        });
    };

    this.updateTableUmn = function (_dataEdit, _rowEdit) {
        const currentRow = oTableUser.row(_rowEdit).data();
        if (typeof _dataEdit['userStatus'] !== 'undefined') {
            currentRow['userStatus'] = _dataEdit['userStatus'];
        }
        lastUpdated = new Date();
        oTableUser.row(_rowEdit).data(currentRow).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefRole = function (_refRole) {
        console.log('setRefRole called with:', _refRole);
        refRole = _refRole;
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefGroup = function (_refGroup) {
        refGroup = _refGroup;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
        updateFilterSummary();
    };

    this.setModalUserClass = function (_modalUserClass) {
        modalUserClass = _modalUserClass;
    };

    this.setModalEditPasswordClass = function (_modalEditPasswordClass) {
        modalEditPasswordClass = _modalEditPasswordClass;
    };
}