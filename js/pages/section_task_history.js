function SectionTaskHistory() {

    const className = 'SectionTaskHistory';
    let self = this;
    let transactionId = '';
    let classFrom;
    let refStatus;
    let refRole;
    let refGroup;
    let refUser;
    let refFlow;
    let refCheckpoint;
    let taskDetails;
    let oTableTransaction;

    this.init = function () {
        $('.sectionTaskHistory').hide();

        $('#btnSthBack').on('click', function () {
            $('.sectionTaskHistory').hide();
            if (classFrom.getClassName() === 'MainTrackMonitoring') {
                $('.sectionTnmMain').show();
            }
            $(window).scrollTop(0);
        });

        oTableTransaction = $('#dtSthList').DataTable({
            bLengthChange: false,
            bFilter: true,
            autoWidth: false,
            bPaginate: false,
            bInfo : false,
            aaSorting: [6, 'asc'],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = oTableTransaction.page.info();
                $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            },
            language: _DATATABLE_LANGUAGE,
            aoColumns:
                [
                    {mData: null, bSortable: false},
                    {mData: null, mRender: function (data, type, row){
                            return row['checkpointId'] !== '' ? refCheckpoint[row['checkpointId']]['checkpointDesc'] : '';
                        }},
                    {mData: 'taskClaimedUser', mRender: function (data){
                            return data !== '' ? refUser[data]['userFullName'] : '';
                        }},
                    {mData: 'roleId', mRender: function (data){
                            return data !== '' ? refRole[data]['roleDesc'] : '';
                        }},
                    {mData: 'groupId', mRender: function (data){
                            return data !== '' ? refGroup[data]['groupName'] : '';
                        }},
                    {mData: 'taskDateDue'},
                    {mData: 'taskTimeCreated'},
                    {mData: 'taskTimeSubmit'},
                    {mData: null, bSortable: false,
                        mRender: function (data, type, row) {
                            return '<h6><span class="badge badge-pill '+refStatus[row['taskStatus']]['statusColor']+' z-depth-2">'+refStatus[row['taskStatus']]['statusDesc']+'</span></h6>';
                        }
                    },
                    {mData: null, bSortable: false, sClass: 'text-center',
                        mRender: function (data, type, row, meta) {
                            return '';
                        }
                    }
                ]
        });
        $("#dtSthList_filter").hide();

        let btnChecklistQualOpt = {
            exportOptions: {
                columns: [ 0, 1, 2, 3],
                format: {
                    body: function ( data, row, column ) {
                        if (column === 3) {
                            const n = data.search('">');
                            const k = data.substr(n+2);
                            return k.replace('</span></h6>','');
                        }
                        return data;
                    }
                }
            }
        };

        new $.fn.dataTable.Buttons(oTableTransaction, {
            buttons: [
                $.extend( true, {}, btnChecklistQualOpt, {
                    extend:    'print',
                    text:      '<i class="fas fa-print"></i>',
                    title:     'GEMS 2.0 - Transaction History',
                    titleAttr: 'Print',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistQualOpt, {
                    extend:    'excelHtml5',
                    text:      '<i class="fas fa-file-excel"></i>',
                    title:     'GEMS 2.0 - Transaction History',
                    titleAttr: 'Excel',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                }),
                $.extend( true, {}, btnChecklistQualOpt, {
                    extend:    'pdfHtml5',
                    text:      '<i class="fas fa-file-pdf"></i>',
                    title:     'GEMS 2.0 - Transaction History',
                    titleAttr: 'Pdf',
                    className: 'btn btn-outline-white btn-rounded btn-sm px-2'
                })
            ]
        }).container().appendTo($('#btnDtSthListExport'));

        $('#btnDtSthListRefresh').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    self.genTableSthList();
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });
    };

    this.view = function (_transactionId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_transactionId]);
                transactionId = _transactionId;

                mzSetFieldValue('SthFlowName', taskDetails['flowId'] !== '' ? refFlow[taskDetails['flowId']]['flowDesc'] : '', 'text');
                mzSetFieldValue('SthTransactionNo', taskDetails['transactionNo'], 'text');
                mzSetFieldValue('SthCheckpointDesc', taskDetails['checkpointId'] !== '' ? refCheckpoint[taskDetails['checkpointId']]['checkpointDesc'] : '', 'text');
                mzSetFieldValue('SthTransUser', taskDetails['transUser'] !== '' ? refUser[taskDetails['transUser']]['userFullName'] : '', 'text');
                mzSetFieldValue('SthTransGroup', taskDetails['transGroup'] !== '' ? refGroup[taskDetails['transGroup']]['groupName'] : '', 'text');
                mzSetFieldValue('SthTransactionTimeCreated', taskDetails['transactionTimeCreated'], 'text');
                mzSetFieldValue('SthTaskStatus', refStatus[taskDetails['taskStatus']]['statusDesc'], 'text');
                mzSetFieldValue('SthUserFullName', taskDetails['userId'] !== '' ? refUser[taskDetails['userId']]['userFullName'] : '', 'text');
                mzSetFieldValue('SthTaskTimeCreated', taskDetails['taskTimeCreated'], 'text');
                mzSetFieldValue('SthTransactionStatus', refStatus[taskDetails['transactionStatus']]['statusDesc'], 'text');
                mzSetFieldValue('SthTransactionDateDue', taskDetails['transactionDateDue'], 'text');

                self.genTableSthList();

                $('.sectionTaskHistory').show();
                if (classFrom.getClassName() === 'MainTrackMonitoring') {
                    $('.sectionTnmMain').hide();
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTableSthList = function () {
        const dataTransaction = mzAjaxRequest('track_monitoring.php?type=transaction_history&transactionId='+transactionId, 'GET');
        oTableTransaction.clear().rows.add(dataTransaction).draw();

        let woTask;
        let holdCheckpoint = '';
        if (taskDetails['flowId'] === '2') {
            woTask = mzAjaxRequest('wo.php?type=wo_by_transaction&transactionId='+transactionId, 'GET');
        }

        const dataLength = dataTransaction.length;
        const currentCheckpoint = dataTransaction[dataLength-1]['checkpointId'];
        let isGreen = 'green';
        $('#ulSthStep').html('');
        for (let i = 0; i < refCheckpoint.length; i++) {
            if (typeof refCheckpoint[i] !== 'undefined' && refCheckpoint[i]['flowId'] === taskDetails['flowId']) {
                if (taskDetails['flowId'] === '2') {
                    if (woTask['woTaskType'] === '1' && (refCheckpoint[i]['checkpointId'] === '11' || refCheckpoint[i]['checkpointId'] === '16')) {
                        continue;
                    }
                    else if (woTask['woTaskType'] !== '1' && (refCheckpoint[i]['checkpointId'] === '10' || refCheckpoint[i]['checkpointId'] === '14')) {
                        continue;
                    }
                }
                const isActive = refCheckpoint[i]['checkpointId'] === currentCheckpoint ? 'active' : '';
                if (isActive === 'active') {
                    isGreen = '';
                }
                if (taskDetails['flowId'] === '2' && woTask['woTaskType'] !== '1') {
                    if (refCheckpoint[i]['checkpointId'] === '15') {
                        holdCheckpoint = '<li class="'+isActive+'">\n' +
                            '<a href="#!">\n' +
                            '<span class="circle '+isGreen+'"><i class="'+refCheckpoint[i]['checkpointIcon']+'"></i></span>\n' +
                            '<span class="label">' + refCheckpoint[i]['checkpointDesc'] + '</span>\n' +
                            '</a>\n' +
                            '</li>';
                        continue;
                    } else if (refCheckpoint[i]['checkpointId'] === '16') {
                        $('#ulSthStep').append(holdCheckpoint);
                        continue;
                    }
                }
                $('#ulSthStep').append('<li class="'+isActive+'">\n' +
                    '<a href="#!">\n' +
                    '<span class="circle '+isGreen+'"><i class="'+refCheckpoint[i]['checkpointIcon']+'"></i></span>\n' +
                    '<span class="label">' + refCheckpoint[i]['checkpointDesc'] + '</span>\n' +
                    '</a>\n' +
                    '</li>');
            }
        }
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

    this.setRefRole = function (_refRole) {
        refRole = _refRole;
    };

    this.setRefGroup = function (_refGroup) {
        refGroup = _refGroup;
    };

    this.setRefUser = function (_refUser) {
        refUser = _refUser;
    };

    this.setRefFlow = function (_refFlow) {
        refFlow = _refFlow;
    };

    this.setRefCheckpoint = function (_refCheckpoint) {
        refCheckpoint = _refCheckpoint;
    };

    this.setTaskDetails = function (_taskDetails) {
        taskDetails = _taskDetails;
    };
}