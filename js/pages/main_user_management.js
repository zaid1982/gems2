function MainUserManagement() {

    const className = 'MainUserManagement';
    let self = this;
    let refStatus;
    let refRole;
    let refDesignation;
    let refGroup;
    let oTableUser;
    let modalUserClass;

    this.init = function () {
        mzOption('optUmnDesignationId', refDesignation, 'All Designation', 'designationId', 'designationDesc', {designationStatus: '1'}, '', false);
        mzOption('optUmnGroupId', refGroup, 'All Sites', 'groupId', 'groupName', {groupStatus: '1'}, '', false);

        oTableUser = $('#dtUmnUser').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [8, 'desc'],
            autoWidth: false,
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
                            return row['groupId'] !== '' ? refGroup[row['groupId']]['groupName'] : '';
                        }},
                    {mData: null, mRender: function (data, type, row){
                            return row['designationId'] !== '' ? refDesignation[row['designationId']]['designationDesc'] : '';
                        }},
                    {mData: 'userContactNo'},
                    {mData: 'userEmail', mRender: function (data){
                            return mzEmailShort(data, 15);
                        }},
                    {mData: null,
                        mRender: function (data, type, row) {
                            let label = '';
                            let rowData = row['roles'];
                            if (rowData != '') {
                                label = '<ul style="padding-left: 0px; margin-bottom: 0px !important;">';
                                const dataSplit = rowData.split(',');
                                for (let j=0; j<dataSplit.length; j++) {
                                    label += '<li>' + refRole[dataSplit[j]]['roleDesc'] + '</li>';
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
                            let label = '<a><i class="fas fa-edit lnkUmnUserEdit" id="lnkUmnUserEdit_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Kemaskini"></i></a>&nbsp;&nbsp;';
                            if (row['userStatus'] === '1') {
                                label += '<a><i class="fas fa-toggle-off lnkUmnUserDeactivate" id="lnkUmnUserDeactivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Nyahaktifkan"></i></a>&nbsp;&nbsp;';
                            } else {
                                label += '<a><i class="fas fa-toggle-on lnkUmnUserActivate" id="lnkUmnUserActivate_' + meta.row + '" data-toggle="tooltip" data-placement="top" title="Aktifkan"></i></a>&nbsp;&nbsp;';
                            }
                            return label;
                        }
                    },
                    {mData: 'userId', visible: false},
                    {mData: 'userStatus', visible: false},
                    {mData: 'roles', visible: false},
                    {mData: 'groupId', visible: false},
                    {mData: 'designationId', visible: false}
                ]
        });
        $("#dtUmnUser_filter").hide();
        $('#txtUmnUserSearch').on('keyup change', function () {
            oTableUser.search($(this).val()).draw();
        });
        $('#optUmnDesignationId').on('change', function () {
            oTableUser.column(13).search($(this).val(), false, true, false).draw();
        });
        $('#optUmnGroupId').on('change', function () {
            oTableUser.column(12).search($(this).val(), false, true, false).draw();
        });
        $('#linkUmn0').on('click', function () {
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
        });

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
        mzAjaxRequest('profile.php', 'GET', {Reportid: '1', 'Cache-Control': 'no-cache, no-transform'}, 'userManagementClass_.displayChart()');
        const dataUser = mzAjaxRequest('profile.php', 'GET');
        oTableUser.clear().rows.add(dataUser).draw();
    };

    this.displayChart = function (result) {
        result = JSON.parse(result);

        let chartData = [];
        let total0 = 0;
        let total1 = 0;
        let total2 = 0;
        let total3 = 0;
        let total4 = 0;
        let total5 = 0;

        $.each(result, function (n, u) {
            chartData.push({name:refRole[u['roleId']]['roleDesc'], y:parseInt(u['total'])});
            if (u['roleId'] === '1') {
                total1 = parseInt(u['total']);
            } else if (u['roleId'] === '2') {
                total2 = parseInt(u['total']);
            } else if (u['roleId'] === '3') {
                total3 = parseInt(u['total']);
            } else if (u['roleId'] === '4') {
                total4 = parseInt(u['total']);
            } else if (u['roleId'] === '5') {
                total5 = parseInt(u['total']);
            }
            total0 += parseInt(u['total']);
        });

        $('#linkUmn0').html('<span class="bullet blue"></span> All Roles <span class="badge blue float-right">'+mzFormatNumber(total0)+'</span>');
        $('#linkUmn1').html('<span class="bullet yellow"></span> '+refRole[1]['roleDesc']+' <span class="badge yellow float-right">'+mzFormatNumber(total1)+'</span>');
        $('#linkUmn2').html('<span class="bullet light-green"></span> '+refRole[2]['roleDesc']+' <span class="badge light-green float-right">'+mzFormatNumber(total2)+'</span>');
        $('#linkUmn3').html('<span class="bullet red accent-2"></span> '+refRole[3]['roleDesc']+' <span class="badge red accent-2 float-right">'+mzFormatNumber(total3)+'</span>');
        $('#linkUmn4').html('<span class="bullet purple"></span> '+refRole[4]['roleDesc']+' <span class="badge purple float-right">'+mzFormatNumber(total4)+'</span>');
        $('#linkUmn5').html('<span class="bullet blue-grey accent-2"></span> '+refRole[5]['roleDesc']+' <span class="badge blue-grey accent-2 float-right">'+mzFormatNumber(total5)+'</span>');

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
        refRole = _refRole;
    };

    this.setRefDesignation = function (_refDesignation) {
        refDesignation = _refDesignation;
    };

    this.setRefGroup = function (_refGroup) {
        refGroup = _refGroup;
    };

    this.setModalUserClass = function (_modalUserClass) {
        modalUserClass = _modalUserClass;
    };
}