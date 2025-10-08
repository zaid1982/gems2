function MainZone () {

    const className = 'MainZone';
    let self = this;
    let oTableZne;
    let refStatus;
    let refSite;
    let modalZoneClass;
    let urlLinkBase;
    let baseAppPath;

    this.init = function () {
    const urlParams = window.location.href.split('/p_');
    baseAppPath = urlParams[0]; // base including protocol, host and application path before /p_ segment
    urlLinkBase = baseAppPath + '/p_complaint?z=';
        
        oTableZne = $('#dtZneData').DataTable({
            bLengthChange: false,
            bFilter: true,
            aaSorting: [[1, 'asc'], [2, 'asc'], [3, 'asc']],
            ordering: true,
            language: _DATATABLE_LANGUAGE,
            pageLength: 10,
            autoWidth: false,
            dom: "<'row'<'col-5 col-sm-7 px-0 pb-2'B><'col-7 col-sm-5 pb-0'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6 col-md-5 d-none d-sm-block'i><'col-sm-6 col-md-7'p>>",
            columnDefs: [
                { bSortable: false, targets: [0] },
                { className: 'text-center', targets: [0, 3, 6] },
                { className: 'noVis', targets: [0] }
            ],
            buttons: [
                { extend: 'colvis', columns: ':not(.noVis)', fade: 400, collectionLayout: 'one-column', text:'<i class="fas fa-columns"></i>', className: 'btn btn-outline-grey btn-sm px-2 ml-0', titleAttr: 'Column Visibility'},
                { extend: 'print', className: 'btn btn-outline-blue-grey btn-sm px-2 btnZneHide', text:'<i class="fas fa-print"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Print', exportOptions: mzExportOpt},
                { extend: 'copy', className: 'btn btn-outline-blue btn-sm px-2 ml-0 btnZneHide', text:'<i class="fas fa-copy"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Copy', exportOptions: mzExportOpt},
                { extend: 'excelHtml5', className: 'btn btn-outline-green btn-sm px-2 ml-0 btnZneHide', text:'<i class="fas fa-file-excel"></i>', title:'GEMS - FCA Zone List', titleAttr: 'Excel', exportOptions: mzExportExcelOpt},
                { extend: 'pdfHtml5', className: 'btn btn-outline-red btn-sm px-2 ml-0 mr-3 btnZneHide', text:'<i class="fas fa-file-pdf"></i>', title:'GEMS - FCA Zone List', titleAttr: 'PDF', exportOptions: mzExportOpt},
                { text: '<i class="fas fa-download mr-2"></i>Download Template', className: 'btn btn-outline-blue-grey btn-sm px-2 ml-0', attr: { id: 'btnZneDownloadTemplate' }, titleAttr: 'Download Zone Template'},
                { text: '<i class="fas fa-upload mr-2"></i>Import Zones', className: 'btn btn-outline-purple btn-sm px-2 ml-0', attr: { id: 'btnZneImport' }, titleAttr: 'Import Zones from Excel'},
                { text: '<i class="fas fa-plus mr-2"></i>Add New Zone', className: 'btn btn-outline-red btn-sm px-2 ml-0', attr: { id: 'btnZneAdd' }, titleAttr: 'Add New FCA Zone'}
            ],
            fnRowCallback : function(nRow, aData, iDisplayIndex){
                const info = $(this).DataTable().page.info();
                $('td', nRow).eq(0).html(info.start + (iDisplayIndex + 1));
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                $('#btnZneDownloadTemplate').off('click').on('click', function () {
                    self.downloadTemplate();
                });
                $('#btnZneImport').off('click').on('click', function () {
                    self.showImportDialog();
                });
                $('#btnZneAdd').off('click').on('click', function () {
                    modalZoneClass.add();
                });
            },
            aoColumns: [
                {mData: null},
                {mData: 'siteId', mRender: function(data) {
                        return refSite[data]['siteName'];
                    }},
                {mData: 'zoneType'},
                {mData: 'zoneCode'},
                {mData: 'zoneName'},
        {mData: null, mRender: function(row) {
            const zoneId = row.zoneId;
            const complaintLink = urlLinkBase + zoneId; // already full path
            return '<div class="small" style="font-family:monospace; line-height:1.2; word-break:break-all;">'
                + complaintLink + '</div>';
            }},
                {mData: 'zoneStatus', mRender: function(data) {
                        return refStatus[data]['statusDesc'];
                    }}
            ]
        });
        let oTableZneTbody = $('#dtZneData tbody');
        oTableZneTbody.delegate('tr', 'click', function () {
            const data = $('#dtZneData').DataTable().row(this).data();
            modalZoneClass.edit(data['zoneId']);
        });
        oTableZneTbody.delegate('tr', 'mouseenter', function (evt) {
            const data = $('#dtZneData').DataTable().row(this).data();
            const zoneName = typeof data['zoneName'] !== 'undefined' ? data['zoneName'] : '';
            const cell = $(evt.target).closest('td');
            cell.css('cursor', 'pointer');
            cell.attr('data-toggle', 'tooltip');
            cell.attr('title', 'Click to edit '+zoneName+' details');
            $('[data-toggle="tooltip"]').tooltip();
        });

        self.genTable();
    };

    this.genTable = function () {
        const dataDb = mzAjaxRequest2('zone', 'GET');
        console.log(dataDb);
        oTableZne.clear().rows.add(dataDb).draw();
    };

    this.getClassName = function () {
        return className;
    };

    this.setRefStatus = function (_refStatus) {
        refStatus = _refStatus;
    };

    this.setRefSite = function (_refSite) {
        refSite = _refSite;
    };

    this.setModalZoneClass = function (_modalZoneClass) {
        modalZoneClass = _modalZoneClass;
    };
    
    this.getUrlLinkBase = function () {
        return urlLinkBase;
    };

    this.getBaseAppPath = function () {
        return baseAppPath;
    };

    this.downloadTemplate = function () {
        ShowLoader();
        setTimeout(function () {
            try {
                let header = {
                    'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                };
                if (sessionStorage.getItem('token') !== null) {
                    header['Authorization'] = 'Bearer ' + sessionStorage.getItem('token');
                }
                
                fetch('api/zone_template.php', {
                    method: 'GET',
                    headers: header
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to download template');
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    const now = new Date();
                    const pad = n => n.toString().padStart(2, '0');
                    const formattedDate = now.getFullYear().toString() +
                        pad(now.getMonth() + 1) +
                        pad(now.getDate()) + '_' +
                        pad(now.getHours()) +
                        pad(now.getMinutes()) +
                        pad(now.getSeconds());
                    link.download = 'zone_template_' + formattedDate + '.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    HideLoader();
                    toastr['success']('Template downloaded successfully', _ALERT_TITLE_SUCCESS);
                })
                .catch(error => {
                    HideLoader();
                    toastr['error'](error.message, _ALERT_TITLE_ERROR);
                });
            } catch (e) {
                HideLoader();
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        }, 200);
    };

    this.showImportDialog = function () {
        // Create hidden file input
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.xlsx,.xls';
        fileInput.style.display = 'none';
        
        fileInput.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                self.uploadZoneFile(file);
            }
            document.body.removeChild(fileInput);
        };
        
        document.body.appendChild(fileInput);
        fileInput.click();
    };

    this.uploadZoneFile = function (file) {
        ShowLoader();
        setTimeout(function () {
            try {
                const formData = new FormData();
                formData.append('file', file);

                let header = {};
                if (sessionStorage.getItem('token') !== null) {
                    header['Authorization'] = 'Bearer ' + sessionStorage.getItem('token');
                }

                fetch('zone/import', {
                    method: 'POST',
                    headers: header,
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    HideLoader();
                    if (data.success) {
                        const stats = data.result;
                        let message = `Import completed:\n`;
                        message += `✓ ${stats.success} zones added\n`;
                        if (stats.failed > 0) {
                            message += `✗ ${stats.failed} failed\n`;
                        }
                        if (stats.skipped > 0) {
                            message += `⊘ ${stats.skipped} skipped\n`;
                        }
                        
                        if (stats.errors.length > 0) {
                            message += `\nErrors:\n${stats.errors.slice(0, 5).join('\n')}`;
                            if (stats.errors.length > 5) {
                                message += `\n... and ${stats.errors.length - 5} more`;
                            }
                        }
                        
                        toastr['success'](message, _ALERT_TITLE_SUCCESS);
                        self.genTable(); // Refresh the table
                    } else {
                        throw new Error(data.errmsg || 'Import failed');
                    }
                })
                .catch(error => {
                    HideLoader();
                    toastr['error'](error.message, _ALERT_TITLE_ERROR);
                });
            } catch (e) {
                HideLoader();
                toastr['error'](e.message, _ALERT_TITLE_ERROR);
            }
        }, 200);
    };
}