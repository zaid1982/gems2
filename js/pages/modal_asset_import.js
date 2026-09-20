function ModalAssetImport() {

    const className = 'ModalAssetImport';
    let self = this;
    let classFrom;
    let contractId = '';
    let selectedFile = null;
    let previewResult = null;
    let importCompleted = false;

    const escapeHtml = function (value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    const formatFileSize = function (bytes) {
        if (!bytes) {
            return '0 Bytes';
        }
        const units = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + units[i];
    };

    const authHeaders = function () {
        const headers = {};
        if (sessionStorage.getItem('token') !== null) {
            headers.Authorization = 'Bearer ' + sessionStorage.getItem('token');
        }
        return headers;
    };

    const resetState = function () {
        contractId = '';
        selectedFile = null;
        previewResult = null;
        importCompleted = false;
        $('#fileMaiImport').val('');
        $('#lblMaiFileName').text('No file selected');
        $('#lblMaiFileInfo').text('.xlsx or .xls, maximum 10MB');
        $('#divMaiFileRow').removeClass('has-file');
        $('#lblMaiContractName').text('—');
        $('#divMaiSummary, #divMaiPreviewWrap, #divMaiResult').hide();
        $('#tblMaiPreview tbody').empty();
        $('#btnMaiPreview, #btnMaiExecute').prop('disabled', true);
        $('#metricMaiTotal, #metricMaiValid, #metricMaiInvalid').text('0');
    };

    const renderPreview = function (result) {
        previewResult = result;
        $('#metricMaiTotal').text(mzFormatNumber(result.total_rows || 0, 0));
        $('#metricMaiValid').text(mzFormatNumber(result.valid_rows || 0, 0));
        $('#metricMaiInvalid').text(mzFormatNumber(result.invalid_rows || 0, 0));
        $('#divMaiSummary').show();

        const rows = Array.isArray(result.rows) ? result.rows : [];
        let html = '';
        rows.forEach(function (row) {
            const reasons = (row.errors || []).join('; ');
            const statusHtml = row.valid
                ? GemsUI.badge('success', 'Valid')
                : GemsUI.badge('secondary', 'Skipped');
            html += '<tr>' +
                '<td>' + escapeHtml(row.row_number) + '</td>' +
                '<td>' + escapeHtml(row.assetNo) + '</td>' +
                '<td>' + escapeHtml(row.assetName) + '</td>' +
                '<td>' + escapeHtml(row.assetGroup) + '</td>' +
                '<td>' + escapeHtml(row.assetCategory) + '</td>' +
                '<td>' + escapeHtml(row.assetType) + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '<td>' + escapeHtml(reasons) + '</td>' +
                '</tr>';
        });
        $('#tblMaiPreview tbody').html(html);
        $('#divMaiPreviewWrap').show();
        $('#btnMaiExecute').prop('disabled', !result.can_proceed || importCompleted);
    };

    const renderExecuteResult = function (result) {
        importCompleted = true;
        const inserted = result.inserted || 0;
        const skipped = result.skipped || 0;
        $('#metricMaiTotal').text(mzFormatNumber(inserted + skipped, 0));
        $('#metricMaiValid').text(mzFormatNumber(inserted, 0));
        $('#metricMaiInvalid').text(mzFormatNumber(skipped, 0));
        $('#divMaiSummary').show();
        $('#divMaiResult')
            .removeClass('alert-danger')
            .addClass('alert-success')
            .html('<strong>Import complete.</strong> Inserted ' + inserted + ' asset' + (inserted === 1 ? '' : 's') +
                '. Skipped ' + skipped + ' row' + (skipped === 1 ? '' : 's') + '.')
            .show();

        const skippedMap = {};
        (result.skipped_rows || []).forEach(function (row) {
            skippedMap[String(row.row_number)] = (row.errors || []).join('; ');
        });
        const insertedMap = {};
        (result.inserted_assets || []).forEach(function (row) {
            insertedMap[String(row.row_number)] = true;
        });

        $('#tblMaiPreview tbody tr').each(function () {
            const rowNo = String($('td', this).eq(0).text());
            const statusCell = $('td', this).eq(6);
            const reasonCell = $('td', this).eq(7);
            if (insertedMap[rowNo]) {
                statusCell.html(GemsUI.badge('success', 'Imported'));
                reasonCell.text('');
            } else if (typeof skippedMap[rowNo] !== 'undefined') {
                statusCell.html(GemsUI.badge('secondary', 'Skipped'));
                reasonCell.text(skippedMap[rowNo]);
            }
        });
        $('#divMaiPreviewWrap').show();
        $('#btnMaiExecute').prop('disabled', true);
    };

    const postImport = function (action) {
        if (!contractId) {
            throw new Error('Please select a contract first');
        }
        if (!selectedFile) {
            throw new Error('Please choose an Excel file');
        }
        const formData = new FormData();
        formData.append('action', action);
        formData.append('contractId', contractId);
        formData.append('import_file', selectedFile);

        return $.ajax({
            url: 'api/asset_import.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: authHeaders()
        });
    };

    this.init = function () {
        $(document).on('hidden.bs.modal', '#modal_asset_import', function () {
            resetState();
        });

        $(document).on('click', '#btnMaiChooseFile', function () {
            $('#fileMaiImport').trigger('click');
        });

        $(document).on('change', '#fileMaiImport', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            selectedFile = null;
            previewResult = null;
            importCompleted = false;
            $('#divMaiSummary, #divMaiPreviewWrap, #divMaiResult').hide();
            $('#btnMaiExecute').prop('disabled', true);
            if (!file) {
                $('#lblMaiFileName').text('No file selected');
                $('#lblMaiFileInfo').text('.xlsx or .xls, maximum 10MB');
                $('#divMaiFileRow').removeClass('has-file');
                $('#btnMaiPreview').prop('disabled', true);
                return;
            }
            const fileName = file.name.toLowerCase();
            if (!fileName.endsWith('.xlsx') && !fileName.endsWith('.xls')) {
                $('#lblMaiFileName').text(file.name);
                $('#lblMaiFileInfo').text('Please choose an Excel file (.xlsx or .xls)');
                $('#divMaiFileRow').removeClass('has-file');
                $('#btnMaiPreview').prop('disabled', true);
                toastr['error']('Please select an Excel file (.xlsx or .xls)', _ALERT_TITLE_ERROR);
                return;
            }
            selectedFile = file;
            $('#lblMaiFileName').text(file.name);
            $('#lblMaiFileInfo').text(formatFileSize(file.size) + ' • ready to preview');
            $('#divMaiFileRow').addClass('has-file');
            $('#btnMaiPreview').prop('disabled', false);
        });

        $(document).on('click', '#btnMaiDownloadTemplate', function () {
            if (!contractId) {
                toastr['warning']('Please select a contract first', _ALERT_TITLE_WARNING);
                return;
            }
            ShowLoader();
            fetch('api/asset_import.php?action=download_template&contractId=' + encodeURIComponent(contractId), {
                headers: authHeaders()
            }).then(function (response) {
                const contentType = response.headers.get('Content-Type') || '';
                if (contentType.indexOf('application/json') !== -1) {
                    return response.json().then(function (body) {
                        throw new Error(body.errmsg || body.error || 'Failed to download template');
                    });
                }
                if (!response.ok) {
                    throw new Error('Failed to download template');
                }
                const disposition = response.headers.get('Content-Disposition') || '';
                let filename = 'asset_import_template.xlsx';
                const match = disposition.match(/filename="?([^"]+)"?/);
                if (match && match[1]) {
                    filename = match[1];
                }
                return response.blob().then(function (blob) {
                    return { blob: blob, filename: filename };
                });
            }).then(function (payload) {
                const url = window.URL.createObjectURL(payload.blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = payload.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            }).catch(function (e) {
                toastr['error'](e.message || _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            }).finally(function () {
                HideLoader();
            });
        });

        $(document).on('click', '#btnMaiPreview', function () {
            ShowLoader();
            setTimeout(function () {
                postImport('preview_import').done(function (resp) {
                    HideLoader();
                    if (resp && resp.success) {
                        renderPreview(resp.result || {});
                    } else if (resp && (resp.error === 'Expired token' || resp.error === 'Token not valid')) {
                        window.location.href = 'p_login?f=2';
                    } else {
                        toastr['error']((resp && resp.errmsg) ? resp.errmsg : _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                    }
                }).fail(function () {
                    HideLoader();
                    toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                });
            }, 200);
        });

        $(document).on('click', '#btnMaiExecute', function () {
            if (!previewResult || !previewResult.can_proceed) {
                toastr['error']('Preview the file first and ensure there are valid rows', _ALERT_TITLE_ERROR);
                return;
            }
            if (!window.confirm('Import ' + previewResult.valid_rows + ' valid asset' + (previewResult.valid_rows === 1 ? '' : 's') + ' as Active under this contract? Invalid rows will be skipped.')) {
                return;
            }
            ShowLoader();
            setTimeout(function () {
                postImport('execute_import').done(function (resp) {
                    HideLoader();
                    if (resp && resp.success) {
                        if (resp.errmsg) {
                            toastr['success'](resp.errmsg, _ALERT_TITLE_SUCCESS);
                        }
                        renderExecuteResult(resp.result || {});
                        if (classFrom && typeof classFrom.genTableAsz === 'function') {
                            classFrom.genTableAsz();
                        }
                    } else if (resp && (resp.error === 'Expired token' || resp.error === 'Token not valid')) {
                        window.location.href = 'p_login?f=2';
                    } else {
                        toastr['error']((resp && resp.errmsg) ? resp.errmsg : _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                    }
                }).fail(function () {
                    HideLoader();
                    toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
                });
            }, 200);
        });
    };

    this.open = function (_contractId, _contractName) {
        resetState();
        if (!_contractId) {
            toastr['warning']('Please select a contract first', _ALERT_TITLE_WARNING);
            return;
        }
        contractId = _contractId;
        $('#lblMaiContractName').text(_contractName || '—');
        $('#modal_asset_import').modal({backdrop: 'static', keyboard: false});
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}
