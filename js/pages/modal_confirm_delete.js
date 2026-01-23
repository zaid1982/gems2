function ModalConfirmDelete() {

    let id;
    let returnClass;

    this.init = function () {
        $('#btnMcdSubmit').on('click', function () {
            switch (returnClass.getClassName()) {
                case 'ModalDesignation':
                case 'ModalClient':
                case 'ModalSite':
                case 'ModalContract':
                case 'ModalAssetGroup':
                case 'ModalAssetCategory':
                case 'ModalAssetType':
                case 'ModalAssetBrand':
                case 'ModalAssetModel':
                case 'SectionAssetDetails':
                case 'ModalChecklistQual':
                case 'ModalChecklistQuan':
                case 'SectionChecklist':
                case 'ModalLocationCode':
                case 'ModalContractUser':
                case 'ModalPpmGroup':
                case 'ModalSeverity':
                case 'ModalFailureCode':
                case 'ModalPpmUser':
                    if (typeof returnClass !== 'undefined') {
                        returnClass.delete(id);
                    }
                    break;
                case 'MainHome':
                    if (typeof returnClass !== 'undefined') {
                        returnClass.deleteWo(id);
                    }
                    break;
                default:
                    if (typeof returnClass !== 'undefined') {
                        returnClass.delete(id);
                    }
            }
            $('#modal_confirm_delete').modal('hide');
        });
    };

    this.delete = function (_id, _returnClass) {
        if (typeof _id === 'undefined' || _id === '') {
            toastr['error'](_ALERT_MSG_ERROR_DEFAULT, _ALERT_TITLE_ERROR);
            return false;
        }
        id = _id;
        returnClass = typeof _returnClass !== 'undefined' ? _returnClass : '';
        let message = 'This action will permanently delete the selected record. This cannot be undone.';
        if (returnClass && typeof returnClass.getDeleteMessage === 'function') {
            const customMessage = returnClass.getDeleteMessage(id);
            if (typeof customMessage === 'string' && customMessage.trim() !== '') {
                message = customMessage.trim();
            }
        }
        $('#mcdMessage').text(message);
        $('#modal_confirm_delete').modal({backdrop: 'static', keyboard: false});
    };

    this.init();
}