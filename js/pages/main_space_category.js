function MainSpaceCategory() {
  const className = 'MainSpaceCategory';
  let self = this;
  let oTable;
  let modalSpaceCategoryClass;
  let modalConfirmDeleteClass;

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
    const isActive = parseInt(row['spaceCategoryStatus'], 10) === 1;
    const text = isActive ? 'Active' : 'Inactive';
    if (type !== 'display') {
      return text;
    }
    return GemsUI.badge(isActive ? 'success' : 'secondary', text);
  }

  this.init = function () {
    oTable = $('#dtSpcCat').DataTable({
      bLengthChange: false,
      bFilter: true,
      autoWidth: false,
      aaSorting: [[1, 'asc']],
      language: GemsUI.dtEmpty('fa-cubes', 'No space categories recorded yet.', 'No categories match the current search.'),
      dom: GemsUI.dtDomButtons,
      buttons: [
        { extend: 'csv', title: 'space_categories', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-csv"></i>', titleAttr: 'CSV' },
        { extend: 'excel', title: 'space_categories', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-file-excel"></i>', titleAttr: 'Excel' },
        { extend: 'print', title: 'space_categories', className: 'btn btn-outline-secondary btn-sm', text: '<i class="fas fa-print"></i>', titleAttr: 'Print' }
      ],
      fnRowCallback: function (nRow, aData, iDisplayIndex) {
        const info = oTable.page.info();
        $('td', nRow).eq(0).html(info.page * info.length + (iDisplayIndex + 1));
      },
      aoColumns: [
        { mData: null, bSortable: false },
        { mData: 'spaceCategoryName' },
        { mData: 'spaceCategoryDesc' },
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
              cls: 'lnkSpcCatEdit',
              id: 'lnkSpcCatEdit_' + meta.row,
              title: 'Edit',
              icon: 'fas fa-edit'
            });
            if (parseInt(row['spaceCategoryStatus'], 10) === 1) {
              html += GemsUI.actionBtn({
                tint: 'gems-btn-action-delete',
                cls: 'lnkSpcCatDeactivate',
                id: 'lnkSpcCatDeactivate_' + meta.row,
                title: 'Deactivate',
                icon: 'fas fa-toggle-off'
              });
            } else {
              html += GemsUI.actionBtn({
                tint: 'gems-btn-action-view',
                cls: 'lnkSpcCatActivate',
                id: 'lnkSpcCatActivate_' + meta.row,
                title: 'Activate',
                icon: 'fas fa-toggle-on'
              });
            }
            html += GemsUI.actionBtn({
              tint: 'gems-btn-action-delete',
              cls: 'lnkSpcCatDelete',
              id: 'lnkSpcCatDelete_' + meta.row,
              title: 'Delete',
              icon: 'fas fa-trash-alt'
            });
            return html;
          }
        },
        { mData: 'spaceCategoryId', visible: false, sClass: 'noVis' }
      ]
    });

    oTable.buttons().container().appendTo($('#dtSpcCatButtons'));
    GemsUI.bindDtTooltips('#dtSpcCat');

    const tbody = $('#dtSpcCat tbody');
    tbody.on('click', '.lnkSpcCatEdit', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalSpaceCategoryClass.edit(current.data['spaceCategoryId'], current.rowId);
      }
    });
    tbody.on('click', '.lnkSpcCatDeactivate', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalSpaceCategoryClass.deactivate(current.data['spaceCategoryId'], current.rowId);
      }
    });
    tbody.on('click', '.lnkSpcCatActivate', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalSpaceCategoryClass.activate(current.data['spaceCategoryId'], current.rowId);
      }
    });
    tbody.on('click', '.lnkSpcCatDelete', function () {
      const current = rowDataFromLink(this);
      if (current && current.data) {
        modalConfirmDeleteClass.delete(current.data['spaceCategoryId'], modalSpaceCategoryClass);
      }
    });

    $('#txtSpcCatSearch').on('keyup change', function () {
      oTable.search($(this).val()).draw();
    });
    $('#btnSpcCatAdd').on('click', function () {
      modalSpaceCategoryClass.add();
    });
    $('#btnSpcCatRefreshTable').on('click', function () {
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
    self.genTable(0);
  };

  this.genTable = function () {
    const token = sessionStorage.getItem('token');
    const headers = token ? { Authorization: 'Bearer ' + token } : {};
    $.ajax({
      url: 'api/ref_space_category.php',
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

  this.addTableAct = function (row) { oTable.row.add(row).draw(); };

  this.updateTableAct = function (row, idx) {
    const current = oTable.row(idx).data();
    if (typeof row['spaceCategoryName'] !== 'undefined') current['spaceCategoryName'] = row['spaceCategoryName'];
    if (typeof row['spaceCategoryDesc'] !== 'undefined') current['spaceCategoryDesc'] = row['spaceCategoryDesc'];
    if (typeof row['spaceCategoryStatus'] !== 'undefined') current['spaceCategoryStatus'] = row['spaceCategoryStatus'];
    oTable.row(idx).data(current).draw();
  };

  this.getClassName = function () { return className; };
  this.setModalSpaceCategoryClass = function (modal) { modalSpaceCategoryClass = modal; };
  this.setModalConfirmDeleteClass = function (modal) { modalConfirmDeleteClass = modal; };
}

document.addEventListener('DOMContentLoaded', function () {
  ShowLoader();
  setTimeout(function () {
    try {
      initiatePages();
      const mcd = new ModalConfirmDelete();
      const msc = new ModalSpaceCategory();
      const main = new MainSpaceCategory();
      msc.setClassFrom(main);
      main.setModalSpaceCategoryClass(msc);
      main.setModalConfirmDeleteClass(mcd);
      main.init();
      msc.init();
    } catch (e) {
      toastr['error'](e.message, _ALERT_TITLE_ERROR);
    }
    HideLoader();
  }, 200);
});
