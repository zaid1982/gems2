
(function () {
  'use strict';
  let dt;
  let editingId = null;
  let formValidate;

  function headers() {
    const t = sessionStorage.getItem('token');
    return t ? { Authorization: 'Bearer ' + t } : {};
  }

  function statusBadge(v, type) {
    const isActive = !!parseInt(v || 0, 10);
    const txt = isActive ? 'Active' : 'Inactive';
    if (type && type !== 'display') {
      return txt;
    }
    return GemsUI.badge(isActive ? 'success' : 'secondary', txt);
  }

  function initValidate() {
    formValidate = new MzValidate('frmLoc');
    formValidate.registerFields([
      { field_id: 'txtLocName', type: 'text', name: 'Location Name', validator: { notEmpty: true, maxLength: 150 } },
      { field_id: 'txtLocDesc', type: 'text', name: 'Description', validator: { maxLength: 255 } },
      { field_id: 'optLocStatusForm', type: 'select', name: 'Status', validator: { notEmpty: true } }
    ]);
  }

  function initDataTable() {
    dt = $('#dtLoc').DataTable({
      language: GemsUI.dtEmpty('fa-map-marker-alt', 'No locations recorded yet.', 'No locations match the current search.'),
      searching: true,
      ordering: true,
      autoWidth: false,
      paging: true,
      dom: GemsUI.dtDomButtons,
      buttons: [
        { extend: 'csv', title: 'space_locations', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-csv"></i>', titleAttr: 'CSV' },
        { extend: 'excel', title: 'space_locations', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-excel"></i>', titleAttr: 'Excel' },
        { extend: 'print', title: 'space_locations', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-print"></i>', titleAttr: 'Print' }
      ],
      columnDefs: [
        { targets: 0, orderable: false, searchable: false },
        { targets: -1, orderable: false, searchable: false }
      ],
      order: [[1, 'asc']],
      data: [],
      columns: [
        { data: null, render: function () { return ''; } },
        { data: 'spaceLocationName', defaultContent: '' },
        { data: 'spaceLocationDesc', defaultContent: '' },
        {
          data: 'spaceLocationStatus',
          render: function (d, type) {
            return statusBadge(parseInt(d || 0, 10), type);
          }
        },
        {
          data: null,
          className: 'text-center text-nowrap noVis',
          render: function (row) {
            let html = GemsUI.actionBtn({
              tint: 'gems-btn-action-edit',
              cls: 'btnLocEdit',
              title: 'Edit',
              icon: 'fas fa-edit'
            });
            if (parseInt(row.spaceLocationStatus, 10)) {
              html += GemsUI.actionBtn({
                tint: 'gems-btn-action-delete',
                cls: 'btnLocDeactivate',
                title: 'Deactivate',
                icon: 'fas fa-toggle-off'
              });
            } else {
              html += GemsUI.actionBtn({
                tint: 'gems-btn-action-view',
                cls: 'btnLocActivate',
                title: 'Activate',
                icon: 'fas fa-toggle-on'
              });
            }
            return html;
          }
        }
      ]
    });
    dt.on('order.dt search.dt', function () {
      dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
        cell.innerHTML = i + 1;
      });
    }).draw();
    dt.buttons().container().appendTo($('#dtLocButtons'));
    GemsUI.bindDtTooltips('#dtLoc');
  }

  function loadList() {
    ShowLoader();
    let qs = '';
    const $statusEl = $('#optLocStatus');
    if ($statusEl.length) {
      const status = $statusEl.val();
      if (status === '0' || status === '1') {
        qs = '?status=' + encodeURIComponent(status);
      }
    }
    $.ajax({
      url: 'api/space_location.php' + qs,
      method: 'GET',
      dataType: 'json',
      headers: headers()
    })
      .done(function (resp) {
        if (resp && resp.success) {
          dt.clear().rows.add(resp.result || []).draw();
        } else {
          toastr['error']((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
        }
      })
      .fail(function () {
        toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
      })
      .always(function () {
        HideLoader();
      });
  }

  function openModal(row) {
    if (formValidate) {
      formValidate.clearValidation();
    }
    editingId = (row && row.spaceLocationId) ? parseInt(row.spaceLocationId, 10) : null;
    $('#frmLoc')[0].reset();
    $('#hidLocId').val(editingId || '');
    $('#txtLocName').val(row ? row.spaceLocationName : '');
    $('#txtLocDesc').val(row ? row.spaceLocationDesc : '');
    $('#optLocStatusForm').val(row ? String(parseInt(row.spaceLocationStatus || 0, 10)) : '1');
    $('#modalLocFormLabel').text(editingId ? 'Edit Location' : 'Add Location');
    $('#modalLocForm').modal('show');
  }

  function save() {
    if (formValidate && !formValidate.validateNow()) {
      toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
      return;
    }
    const name = $('#txtLocName').val().trim();
    if (!name) {
      toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_ERROR);
      return;
    }
    const payload = {
      name: name,
      description: ($('#txtLocDesc').val() || null),
      status: parseInt($('#optLocStatusForm').val() || '1', 10)
    };
    ShowLoader();
    if (editingId) {
      $.ajax({
        url: 'api/space_location.php/' + editingId,
        method: 'PUT',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        dataType: 'json',
        headers: headers()
      })
        .done(function (resp) {
          if (resp && resp.success) {
            $('#modalLocForm').modal('hide');
            toastr['success'](resp.errmsg || 'Updated', _ALERT_TITLE_SUCCESS);
            loadList();
          } else {
            toastr['error']((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
          }
        })
        .fail(function () {
          toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
        })
        .always(function () {
          HideLoader();
        });
    } else {
      $.ajax({
        url: 'api/space_location.php',
        method: 'POST',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        dataType: 'json',
        headers: headers()
      })
        .done(function (resp) {
          if (resp && resp.success) {
            $('#modalLocForm').modal('hide');
            toastr['success'](resp.errmsg || 'Created', _ALERT_TITLE_SUCCESS);
            loadList();
          } else {
            toastr['error']((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
          }
        })
        .fail(function () {
          toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
        })
        .always(function () {
          HideLoader();
        });
    }
  }

  function setStatus(row, status) {
    if (!row || !row.spaceLocationId) {
      return;
    }
    const payload = { status: status ? 1 : 0 };
    ShowLoader();
    $.ajax({
      url: 'api/space_location.php/' + row.spaceLocationId,
      method: 'PUT',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      dataType: 'json',
      headers: headers()
    })
      .done(function (resp) {
        if (resp && resp.success) {
          toastr['success'](resp.errmsg || 'Updated', _ALERT_TITLE_SUCCESS);
          loadList();
        } else {
          toastr['error']((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
        }
      })
      .fail(function () {
        toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
      })
      .always(function () {
        HideLoader();
      });
  }

  function initFilters() {
    const $status = $('#optLocStatus');
    if ($status.length) {
      $status.on('change', loadList);
    }
    $('#txtLocSearch').on('keyup', function () {
      dt.search(this.value).draw();
    });
  }

  function initButtons() {
    $('#btnDtLocRefresh, #btnLocRefreshTable').on('click', loadList);
    $('#btnLocAdd').on('click', function () { openModal(null); });
    $('#btnLocSave').on('click', save);
    $('#dtLoc').on('click', '.btnLocEdit', function () {
      const row = dt.row($(this).closest('tr')).data();
      openModal(row);
    });
    $('#dtLoc').on('click', '.btnLocActivate', function () {
      const row = dt.row($(this).closest('tr')).data();
      setStatus(row, true);
    });
    $('#dtLoc').on('click', '.btnLocDeactivate', function () {
      const row = dt.row($(this).closest('tr')).data();
      setStatus(row, false);
    });
  }

  $(document).ready(function () {
    try {
      if (typeof initiatePages === 'function') {
        initiatePages();
      }
    } catch (e) {}
    initValidate();
    initDataTable();
    initFilters();
    initButtons();
    loadList();
  });
})();
