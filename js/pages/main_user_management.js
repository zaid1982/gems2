function MainUserManagement() {

    const className = 'MainUserManagement';
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

    this.init = function () {
        mzOption('optUmnGroupId', refSite, 'All Sites', 'siteId', 'siteName', {siteStatus: '1'}, '', false);

        refUserType = ['', 'GFM Internal', 'Client', 'Public User'];
        oTableUser = $('#dtUmnUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [9, 'desc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableUser.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
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
                            let label = '<a><i class="fas fa-edit lnkUmnUserEdit" id="lnkUmnUserEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>&nbsp;&nbsp;';
                            label += '<a><i class="fas fa-unlock-alt lnkUmnUserPassword" id="lnkUmnUserPassword_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Edit Password"></i></a>&nbsp;&nbsp;';
                            if (row['userStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkUmnUserDeactivate" id="lnkUmnUserDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Deactivate"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkUmnUserActivate" id="lnkUmnUserActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Activate"></i></a>&nbsp;&nbsp;';
                            }
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
            oTableUser.column(14).search("^" + $(this).val() + "$", true, false, true).draw();
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
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnUserOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - System User List',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnUserOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - System User List',
                    titleAttr: 'Pdf',
                    orientation: 'landscape',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
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
        oTableUser.clear().rows.add(dataUser).draw();
    };

    this.displayChart = function (result) {
        result = JSON.parse(result);

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

        /*$('#linkUmn0').html('<span class="bullet blue z-depth-2"></span> All Roles <span class="badge blue float-right">'+mzFormatNumber(total0)+'</span>');
        $('#linkUmn1').html('<span class="bullet yellow z-depth-2"></span> '+refRole[1]['roleDesc']+' <span class="badge yellow float-right">'+mzFormatNumber(total1)+'</span>');
        $('#linkUmn2').html('<span class="bullet light-green z-depth-2"></span> '+refRole[2]['roleDesc']+' <span class="badge light-green float-right">'+mzFormatNumber(total2)+'</span>');
        $('#linkUmn3').html('<span class="bullet red accent-2 z-depth-2"></span> '+refRole[3]['roleDesc']+' <span class="badge red accent-2 float-right">'+mzFormatNumber(total3)+'</span>');
        $('#linkUmn4').html('<span class="bullet mdb-color lighten-2 z-depth-2"></span> '+refRole[4]['roleDesc']+' <span class="badge mdb-color lighten-2 float-right">'+mzFormatNumber(total4)+'</span>');
        $('#linkUmn5').html('<span class="bullet teal accent-2 z-depth-2"></span> '+refRole[5]['roleDesc']+' <span class="badge teal accent-2 float-right">'+mzFormatNumber(total5)+'</span>');
        $('#linkUmn6').html('<span class="bullet pink z-depth-2"></span> '+refRole[6]['roleDesc']+' <span class="badge pink float-right">'+mzFormatNumber(total6)+'</span>');
        $('#linkUmn7').html('<span class="bullet purple lighten-4 z-depth-2"></span> '+refRole[7]['roleDesc']+' <span class="badge purple lighten-4 float-right">'+mzFormatNumber(total7)+'</span>');
        $('#linkUmn8').html('<span class="bullet light-green z-depth-2"></span> '+refRole[8]['roleDesc']+' <span class="badge light-green float-right">'+mzFormatNumber(total8)+'</span>');
        $('#linkUmn9').html('<span class="bullet red accent-2 z-depth-2"></span> '+refRole[9]['roleDesc']+' <span class="badge red accent-2 float-right">'+mzFormatNumber(total9)+'</span>');
        $('#linkUmn10').html('<span class="bullet purple z-depth-2"></span> '+refRole[10]['roleDesc']+' <span class="badge purple float-right">'+mzFormatNumber(total10)+'</span>');
        $('#linkUmn11').html('<span class="bullet mdb-color lighten-2 z-depth-2"></span> '+refRole[11]['roleDesc']+' <span class="badge mdb-color lighten-2 float-right">'+mzFormatNumber(total11)+'</span>');*/

        Highcharts.chart('chartUmnLeaveByStatus', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'Total Users by Role'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.y} ({point.percentage:.1f}%)</b>'
            },
            credits:{
                enabled:false
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.y}',
                        color: 'white'
                    }
                }
            },
            series: [{
                name: 'Status',
                data: chartData
            }]
        });
    };

    this.updateTableUmn = function (_dataEdit, _rowEdit) {
        const currentRow = oTableUser.row(_rowEdit).data();
        if (typeof _dataEdit['userStatus'] !== 'undefined') {
            currentRow['userStatus'] = _dataEdit['userStatus'];
        }
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
    };

    this.setModalUserClass = function (_modalUserClass) {
        modalUserClass = _modalUserClass;
    };

    this.setModalEditPasswordClass = function (_modalEditPasswordClass) {
        modalEditPasswordClass = _modalEditPasswordClass;
    };
}