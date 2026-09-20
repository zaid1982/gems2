function ModalSite() {

    const className = 'ModalSite';
    let self = this;
    let siteId = '';
    let rowRefresh = '';
    let classFrom;
    let refStatus;
    let refClient;
    let oTableProblemType;
    let oTableSiteLocation;

    function clientRows(activeOnly) {
        const rows = [];
        $.each(refClient, function (key, client) {
            if (!client || typeof client !== 'object') {
                return true;
            }
            if (activeOnly && String(client['clientStatus']) !== '1') {
                return true;
            }
            rows.push(client);
            return true;
        });
        rows.sort(function (a, b) {
            return (a['clientName'] || '').localeCompare(b['clientName'] || '');
        });
        return rows;
    }

    function fillClientSelect(activeOnly, selected) {
        GemsUI.fillSelect(
            'optMstClientId',
            clientRows(activeOnly),
            'clientId',
            function (row) {
                return row['clientName'] || '';
            },
            'Choose Client',
            selected
        );
    }

    function setClientSelectDisabled(disabled) {
        $('#optMstClientId').prop('disabled', !!disabled);
    }

    function statusBadge(statusId) {
        const status = refStatus && refStatus[statusId] ? refStatus[statusId] : null;
        const label = status && status['statusDesc'] ? status['statusDesc'] : 'Unknown';
        let kind = 'secondary';
        if (String(statusId) === '1') {
            kind = 'success';
        } else if (String(statusId) === '5') {
            kind = 'warning';
        }
        return GemsUI.badge(kind, GemsUI.escape(label));
    }

    this.init = function () {
        const vData = [
            {
                field_id: 'optMstClientId',
                type: 'select',
                name: 'Client',
                validator: {
                    notEmpty: true
                }
            },
            {
                field_id: 'txtMstName',
                type: 'text',
                name: 'Site Name',
                validator: {
                    notEmpty: true,
                    maxLength: 150
                }
            },
            {
                field_id: 'txtMstCode',
                type: 'text',
                name: 'Site Code',
                validator: {
                    notEmpty: true,
                    maxLength: 5
                }
            },
            {
                field_id: 'txaMstDesc',
                type: 'text',
                name: 'Description',
                validator: {
                    maxLength: 255
                }
            },
            {
                field_id: 'chkMstWorkRequest',
                type: 'checkSingle',
                name: 'Work Request',
                validator: {
                }
            },
            {
                field_id: 'chkMstPublic',
                type: 'checkSingle',
                name: 'Public QR Scan',
                validator: {
                }
            },
            {
                field_id: 'chkMstStatus',
                type: 'checkSingle',
                name: 'Status',
                validator: {
                }
            }
        ];

        let formValidate = new MzValidate('formMst');
        formValidate.registerFields(vData);

        $('#formMst').on('keyup change', function () {
            $('#btnMstSubmit').attr('disabled', !formValidate.validateForm());
        });

        $('#modal_site').on('hidden.bs.modal', function () {
            formValidate.clearValidation();
            $('#btnMstSubmit').attr('disabled', true);
            setClientSelectDisabled(false);
        });

        $('#btnMstSubmit').on('click', function () {
            ShowLoader();
            setTimeout(function () {
                try {
                    if (!formValidate.validateForm()) {
                        toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
                    }
                    else {
                        const clientId = $('#optMstClientId').val();
                        const txtName = $('#txtMstName').val();
                        const txtCode = $('#txtMstCode').val();
                        const txtDesc = $('#txaMstDesc').val();
                        const siteIsWr = $("input[name='chkMstWorkRequest']").is(':checked') ? '1' : '0';
                        const siteIsPublic = $("input[name='chkMstPublic']").is(':checked') ? '1' : '0';
                        const statusVal = $("input[name='chkMstStatus']").is(':checked') ? '1' : '2';
                        const data = {
                            clientId: clientId,
                            siteName: txtName,
                            siteCode: txtCode,
                            siteDesc: txtDesc,
                            siteIsWr: siteIsWr,
                            siteIsPublic: siteIsPublic,
                            siteStatus: statusVal
                        };

                        let tempRow = {};
                        if (siteId === '') {
                            siteId = mzAjaxRequest('site.php', 'POST', data);
                            if (classFrom.getClassName() === 'MainSite') {
                                tempRow['siteId'] = siteId;
                                tempRow['clientId'] = clientId;
                                tempRow['siteName'] = txtName;
                                tempRow['siteCode'] = txtCode;
                                tempRow['siteDesc'] = txtDesc;
                                tempRow['siteIsWr'] = siteIsWr;
                                tempRow['siteIsPublic'] = siteIsPublic;
                                tempRow['siteStatus'] = statusVal;
                                classFrom.addTableSte(tempRow);
                            }
                        } else {
                            data['action'] = 'update';
                            mzAjaxRequest('site.php?siteId=' + siteId, 'PUT', data);
                            if (classFrom.getClassName() === 'MainSite') {
                                tempRow['siteId'] = siteId;
                                tempRow['siteName'] = txtName;
                                tempRow['siteCode'] = txtCode;
                                tempRow['siteDesc'] = txtDesc;
                                tempRow['siteIsWr'] = siteIsWr;
                                tempRow['siteIsPublic'] = siteIsPublic;
                                tempRow['siteStatus'] = statusVal;
                                classFrom.updateTableSte(tempRow, rowRefresh);
                            }
                        }
                        $('#modal_site').modal('hide');
                    }
                } catch (e) {
                    toastr['error'](e.message, _ALERT_TITLE_ERROR);
                }
                HideLoader();
            }, 300);
        });

        oTableProblemType = $('#dtMstProblemType').DataTable({
            bLengthChange: false,
            autoWidth: false,
            bFilter: false,
            aaSorting: [[1, 'asc']],
            language: GemsUI.dtEmpty('fa-list', 'No problem types recorded yet.', 'No problem types recorded yet.'),
            dom: GemsUI.dtDom,
            pagingType: 'simple_numbers',
            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            aoColumns: [
                {mData: null, bSortable: false, sClass: 'text-center'},
                {mData: 'siteProblemTypeName'},
                {mData: null, sClass: 'text-center',
                    mRender: function (data, type, row) {
                        if (type !== 'display') {
                            return refStatus && refStatus[row['siteProblemTypeStatus']]
                                ? refStatus[row['siteProblemTypeStatus']]['statusDesc']
                                : '';
                        }
                        return statusBadge(row['siteProblemTypeStatus']);
                    }
                },
                {mData: 'siteProblemTypeStatus', visible: false,
                    mRender: function (data, type, row) {
                        return refStatus && refStatus[row['siteProblemTypeStatus']]
                            ? refStatus[row['siteProblemTypeStatus']]['statusDesc']
                            : '';
                    }
                }
            ]
        });

        if ($('#dtMstSiteLocation').length) {
            oTableSiteLocation = $('#dtMstSiteLocation').DataTable({
                bLengthChange: false,
                autoWidth: false,
                bFilter: false,
                aaSorting: [[1, 'asc']],
                language: GemsUI.dtEmpty('fa-map-marker-alt', 'No site locations recorded yet.', 'No site locations recorded yet.'),
                dom: GemsUI.dtDom,
                pagingType: 'simple_numbers',
                fnRowCallback: function (nRow, aData, iDisplayIndex) {
                    const info = oTableSiteLocation.page.info();
                    $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
                },
                aoColumns: [
                    {mData: null, bSortable: false},
                    {mData: 'siteLocationName'},
                    {mData: null,
                        mRender: function (data, type, row) {
                            if (type !== 'display') {
                                return refStatus && refStatus[row['siteLocationStatus']]
                                    ? refStatus[row['siteLocationStatus']]['statusDesc']
                                    : '';
                            }
                            return statusBadge(row['siteLocationStatus']);
                        }
                    },
                    {mData: 'siteLocationStatus', visible: false,
                        mRender: function (data, type, row) {
                            return refStatus && refStatus[row['siteLocationStatus']]
                                ? refStatus[row['siteLocationStatus']]['statusDesc']
                                : '';
                        }
                    }
                ]
            });
        }
    };

    this.add = function () {
        siteId = '';
        rowRefresh = '';

        ShowLoader();
        setTimeout(function () {
            try {
                $('.isMstWorkRequest').hide();
                fillClientSelect(true);
                setClientSelectDisabled(false);

                mzSetFieldValue('MstStatus', '1', 'checkSingle', '1');
                $('#lblMstTitle').html('<i class="fas fa-plus me-2"></i>Add Site');
                $('#modal_site').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.edit = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                $('.isMstWorkRequest').hide();
                mzCheckFuncParam([_siteId, _rowRefresh]);
                siteId = _siteId;
                rowRefresh = _rowRefresh;

                const dataMst = mzAjaxRequest('site.php?siteId=' + siteId, 'GET');
                fillClientSelect(false, dataMst['clientId']);
                mzSetFieldValue('MstName', dataMst['siteName'], 'text');
                mzSetFieldValue('MstCode', dataMst['siteCode'], 'text');
                mzSetFieldValue('MstDesc', dataMst['siteDesc'], 'textarea');
                mzSetFieldValue('MstWorkRequest', dataMst['siteIsWr'], 'checkSingle', '1');
                mzSetFieldValue('MstPublic', dataMst['siteIsPublic'], 'checkSingle', '1');
                mzSetFieldValue('MstStatus', dataMst['siteStatus'], 'checkSingle', '1');

                setClientSelectDisabled(true);
                if (dataMst['siteIsWr'] === '1') {
                    $('.isMstWorkRequest').show();
                    self.genTableProblemType();
                }

                $('#lblMstTitle').html('<i class="far fa-edit me-2"></i>Edit Site');
                $('#modal_site').modal({backdrop: 'static', keyboard: false});
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 200);
    };

    this.deactivate = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh]);
                mzAjaxRequest('site.php?siteId=' + _siteId, 'PUT', {action: 'deactivate'});
                const tempRow = {siteStatus: '2'};
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.updateTableSte(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.activate = function (_siteId, _rowRefresh) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId, _rowRefresh]);
                mzAjaxRequest('site.php?siteId=' + _siteId, 'PUT', {action: 'activate'});
                const tempRow = {siteStatus: '1'};
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.updateTableSte(tempRow, _rowRefresh);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.delete = function (_siteId) {
        ShowLoader();
        setTimeout(function () {
            try {
                mzCheckFuncParam([_siteId]);
                mzAjaxRequest('site.php?siteId=' + _siteId, 'DELETE');
                if (classFrom.getClassName() === 'MainSite') {
                    classFrom.genTableSte(1);
                }
            } catch (e) {
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 300);
    };

    this.genTableProblemType = function () {
        const dataDb = mzAjaxRequest('site_problem_type.php?siteId=' + siteId, 'GET');
        oTableProblemType.clear().rows.add(dataDb).draw();
    };

    this.genTableLocation = function () {
        if (!oTableSiteLocation) {
            return;
        }
        const dataDb = mzAjaxRequest('site.php?type=problemType&siteId=' + siteId, 'GET');
        oTableSiteLocation.clear().rows.add(dataDb).draw();
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
}
