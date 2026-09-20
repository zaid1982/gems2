function MainSpaceType() {
  const className = 'MainSpaceType';
  let self = this;
  let oTable;
  let modalSpaceTypeClass;
  let modalConfirmDeleteClass;
  let categoryLookup = {};

  function rowIdFromLink(el) {
    const linkId = $(el).attr('id') || '';
    const linkIndex = linkId.indexOf('_');
    return linkIndex > 0 ? linkId.substr(linkIndex + 1) : '';
  }

  function rowDataFromLink(el) {
    const rowId = rowIdFromLink(el);
    if (!rowId || !oTable) {
      return null;
    }
    return { rowId: rowId, data: oTable.row(parseInt(rowId, 10)).data() };
  }

  function statusBadge(row, type) {
    const isActive = parseInt(row['spaceTypeStatus'], 10) === 1;
    const text = isActive ? 'Active' : 'Inactive';
    if (type !== 'display') {
      return text;
    }
    return GemsUI.badge(isActive ? 'success' : 'secondary', text);
  }

  this.init = function () {
    oTable = $('#dtSpcTyp').DataTable({
      bLengthChange: false,
      bFilter: true,
      autoWidth: false,
      aaSorting: [[1, 'asc']],
      language: GemsUI.dtEmpty('fa-tag', 'No space types recorded yet.', 'No types match the current search.'),
      dom: GemsUI.dtDomButtons,
      buttons: [
        { extend: 'csv', title: 'space_types', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-csv"></i>', titleAttr: 'CSV' },
        { extend: 'excel', title: 'space_types', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-excel"></i>', titleAttr: 'Excel' },
        { extend: 'print', title: 'space_types', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-print"></i>', titleAttr: 'Print' }
      ],
      fnRowCallback: function (nRow, aData, iDisplayIndex) {
        const info = oTable.page.info();
        $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
      },
      aoColumns: [
        { mData: null, bSortable: false },
        { mData: 'spaceTypeName' },
        {
          mData: null,
          mRender: function (data, type, row) {
            const id = parseInt(row['spaceCategoryId'], 10);
            const name = categoryLookup[id] || '-';
            return type === 'display' ? GemsUI.escape(name) : name;
          }
        },
        { mData: 'spaceTypeDesc' },
        {
          mData: null,
          mRender: function (data, type, row) {
            return statusBadge(row, type);
          }
        },
        {
          mData: null,
          bSortable: false,
          sClass: 'text-center text-nowrap noVis',
          mRender: function (data, type, row, meta) {
            let html = GemsUI.actionBtn({
              tint: 'gems-btn-action-edit',
              cls: 'lnkSpcTypEdit',
              id: 'lnkSpcTypEdit_' + meta.row,
              title: 'Edit',
              icon: 'fas fa-edit'
            });
            if (parseInt(row['spaceTypeStatus'], 10) === 1) {
              html += GemsUI.actionBtn({
                tint: 'gems-btn-action-delete',
                cls: 'lnkSpcTypDeactivate',
                id: 'lnkSpcTypDeactivate_' + meta.row,
                title: 'Deactivate',
                icon: 'fas fa-toggle-off'
              });
            } else {
              html += GemsUI.actionBtn({
                tint: 'gems-btn-action-view',
                cls: 'lnkSpcTypActivate',
                id: 'lnkSpcTypActivate_' + meta.row,
                title: 'Activate',
                icon: 'fas fa-toggle-on'
              });
            }
            html += GemsUI.actionBtn({
              tint: 'gems-btn-action-delete',
              cls: 'lnkSpcTypDelete',
              id: 'lnkSpcTypDelete_' + meta.row,
              title: 'Delete',
              icon: 'fas fa-trash-alt'
            });
            return html;
          }
        },
        { mData: 'spaceTypeId', visible: false, sClass: 'noVis' }
      ]
    });

    oTable.buttons().container().appendTo($('#dtSpcTypButtons'));
    GemsUI.bindDtTooltips('#dtSpcTyp');

    const tbody = $('#dtSpcTyp tbody');
    tbody.on('click', '.lnkSpcTypEdit', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalSpaceTypeClass.edit(current.data['spaceTypeId'], current.rowId);
      }
    });
    tbody.on('click', '.lnkSpcTypDeactivate', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalSpaceTypeClass.deactivate(current.data['spaceTypeId'], current.rowId);
      }
    });
    tbody.on('click', '.lnkSpcTypActivate', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalSpaceTypeClass.activate(current.data['spaceTypeId'], current.rowId);
      }
    });
    tbody.on('click', '.lnkSpcTypDelete', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalConfirmDeleteClass.delete(current.data['spaceTypeId'], modalSpaceTypeClass);
      }
    });

    $('#txtSpcTypSearch').on('keyup change', function () {
      oTable.search($(this).val()).draw();
    });
    $('#btnSpcTypAdd').on('click', function () {
      modalSpaceTypeClass.add();
    });
    $('#btnSpcTypRefreshTable').on('click', function () {
      ShowLoader();
      setTimeout(function () {
        try {
          self.genTable(1);
        } catch (e) {
          toastr['error'](e.message, _ALERT_TITLE_ERROR);
        }
        HideLoader();
      }, 200);
    });
    self.loadCategories().then(function () { self.genTable(0); }).catch(function () { self.genTable(0); });
  };

  this.genTable = function () {
    const token = sessionStorage.getItem('token');
    const headers = token ? { Authorization: 'Bearer ' + token } : {};
    $.ajax({
      url: 'api/ref_space_type.php',
      method: 'GET',
      dataType: 'json',
      headers: headers
    }).done(function (resp) {
      if (resp && resp.success) {
        oTable.clear().rows.add(resp.result || []).draw();
      } else {
        toastr['error']((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
      }
    }).fail(function () {
      toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
    });
  };

  this.loadCategories = function () {
    return new Promise(function (resolve, reject) {
      const token = sessionStorage.getItem('token');
      const headers = token ? { Authorization: 'Bearer ' + token } : {};
      $.ajax({
        url: 'api/ref_space_category.php',
        method: 'GET',
        dataType: 'json',
        headers: headers
      }).done(function (resp) {
        if (resp && resp.success) {
          categoryLookup = {};
          (resp.result || []).forEach(function (c) {
            categoryLookup[parseInt(c['spaceCategoryId'], 10)] = c['spaceCategoryName'];
          });
          resolve(categoryLookup);
        } else {
          reject(new Error((resp && resp.errmsg) || _ALERT_MSG_ERROR_DEFAULT));
        }
      }).fail(function () {
        reject(new Error(_ALERT_MSG_ERROR_DEFAULT));
      });
    });
  };

  this.addTableAct = function (row) { oTable.row.add(row).draw(); };
  this.updateTableAct = function (row, idx) {
    const current = oTable.row(idx).data();
    if (typeof row['spaceTypeName'] !== 'undefined') current['spaceTypeName'] = row['spaceTypeName'];
    if (typeof row['spaceTypeDesc'] !== 'undefined') current['spaceTypeDesc'] = row['spaceTypeDesc'];
    if (typeof row['spaceTypeStatus'] !== 'undefined') current['spaceTypeStatus'] = row['spaceTypeStatus'];
    oTable.row(idx).data(current).draw();
  };

  this.getClassName = function () { return className; };
  this.setModalSpaceTypeClass = function (modal) { modalSpaceTypeClass = modal; };
  this.setModalConfirmDeleteClass = function (modal) { modalConfirmDeleteClass = modal; };
}

document.addEventListener('DOMContentLoaded', function () {
  ShowLoader();
  setTimeout(function () {
    try {
      initiatePages();
      const mcd = new ModalConfirmDelete();
      const mst = new ModalSpaceType();
      const main = new MainSpaceType();
      mst.setClassFrom(main);
      main.setModalSpaceTypeClass(mst);
      main.setModalConfirmDeleteClass(mcd);
      main.init();
      mst.init();
    } catch (e) {
      toastr['error'](e.message, _ALERT_TITLE_ERROR);
    }
    HideLoader();
  }, 200);
});
