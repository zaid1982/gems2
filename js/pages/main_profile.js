function MainProfile() {
    const self = this;
    let validator;
    let userId = '';
    let versionLocal = '';
    let refSite = {};
    let refClient = {};
    let refDesignation = {};
    let refGroup = {};
    let refRole = {};
    let profileData = {};
    let initialState = {};
    const DEFAULT_AVATAR_SRC = 'img/background/no-image.png';
    const DEFAULT_AVATAR_NOTE = 'PNG or JPG up to 2 MB';
    const MAX_AVATAR_SIZE = 2 * 1024 * 1024;
    let avatarState = { original: '', pending: null };

    const statusMap = {
        '1': { label: 'Active', tone: 'active', icon: 'fa-check-circle' },
        '2': { label: 'Suspended', tone: 'inactive', icon: 'fa-ban' },
        '0': { label: 'Inactive', tone: 'inactive', icon: 'fa-user-slash' }
    };

    const safeDecryptProfile = function () {
        const raw = sessionStorage.getItem('userInfo');
        if (!raw) { return null; }
        try {
            const decrypted = CryptoJS.AES.decrypt(raw, 'GEMS').toString(CryptoJS.enc.Utf8);
            if (decrypted) {
                return JSON.parse(decrypted);
            }
        } catch (err) {
            /* continue */
        }
        try {
            return JSON.parse(raw);
        } catch (err) {
            return null;
        }
    };

    const persistProfileSession = function (updated) {
        if (!updated) { return; }
        const current = safeDecryptProfile();
        if (!current) { return; }
    if (typeof updated.userEmail !== 'undefined') { current.userEmail = updated.userEmail; }
    if (typeof updated.userFirstName !== 'undefined') { current.userFirstName = updated.userFirstName; }
    if (typeof updated.userLastName !== 'undefined') { current.userLastName = updated.userLastName; }
    if (typeof updated.userMykadNo !== 'undefined') { current.userMykadNo = updated.userMykadNo; }
    if (typeof updated.profileContactNo !== 'undefined') { current.profileContactNo = updated.profileContactNo; }
    if (typeof updated.imgUrl !== 'undefined') { current.imgUrl = updated.imgUrl; }
    if (typeof updated.userFullName !== 'undefined') { current.userFullName = updated.userFullName; }
        try {
            const encrypted = CryptoJS.AES.encrypt(JSON.stringify(current), 'GEMS');
            sessionStorage.setItem('userInfo', encrypted);
        } catch (err) {
            sessionStorage.setItem('userInfo', JSON.stringify(current));
        }
    };

    const setAvatarNote = function (message, tone) {
        const note = $('#lblProfileAvatarNote');
        if (!note.length) { return; }
        const text = message || DEFAULT_AVATAR_NOTE;
        note.text(text);
        note.removeClass('text-danger text-success');
        if (tone === 'error') {
            note.addClass('text-danger');
        } else if (tone === 'success') {
            note.addClass('text-success');
        }
    };

    const renderAvatar = function (src) {
        const avatar = $('#profileAvatar');
        if (!avatar.length) { return; }
        const resolved = src || DEFAULT_AVATAR_SRC;
        avatar.empty();
        const img = $('<img>', {
            src: resolved,
            alt: 'Profile photo'
        });
        avatar.append(img);
    };

    const resetAvatarState = function (imgUrl) {
        avatarState = {
            original: imgUrl || '',
            pending: null
        };
        $('#fileProfileAvatar').val('');
        renderAvatar(avatarState.original);
        setAvatarNote(DEFAULT_AVATAR_NOTE);
    };

    const safeGetLocal = function (name, keyField, api) {
        try {
            const data = mzGetLocalArray(name, versionLocal, keyField, [], api);
            return data || {};
        } catch (err) {
            return {};
        }
    };

    const resolveLabel = function (collection, id, keyField, labelField) {
        const key = (id || '').toString();
        if (!key) { return ''; }
        if (collection && typeof collection === 'object' && !Array.isArray(collection)) {
            if (collection[key]) { return collection[key][labelField] || ''; }
            const numericKey = (parseInt(key, 10)).toString();
            if (collection[numericKey]) { return collection[numericKey][labelField] || ''; }
            if (collection[id]) { return collection[id][labelField] || ''; }
        }
        const list = Array.isArray(collection) ? collection : Object.values(collection || {});
        for (let i = 0; i < list.length; i++) {
            const entry = list[i] || {};
            if ((entry[keyField] || '').toString() === key) {
                return entry[labelField] || '';
            }
        }
        return '';
    };

    const parseRoles = function (roles) {
        if (!roles) { return []; }
        if (Array.isArray(roles)) {
            return roles.map(function (role) {
                return (role.roleId || role).toString().trim();
            }).filter(Boolean);
        }
        return roles.split(',').map(function (part) { return part.trim(); }).filter(Boolean);
    };

    const resolveRoleName = function (roleId) {
        const label = resolveLabel(refRole, roleId, 'roleId', 'roleName')
            || resolveLabel(refRole, roleId, 'roleId', 'roleDesc');
        if (label) {
            return label;
        }
        if ((roleId || '').toString() === '27') {
            return 'MR Reviewer';
        }
        return 'Role ' + roleId;
    };

    const collectFormValues = function () {
        return {
            userEmail: $('#txtPflEmail').val().trim(),
            userFirstName: $('#txtPflFirstName').val().trim(),
            userLastName: $('#txtPflLastName').val().trim(),
            userMykadNo: $('#txtPflIdno').val().trim(),
            profileContactNo: $('#txtPflPhone').val().trim()
        };
    };

    const hasChanges = function () {
        if (avatarState.pending) {
            return true;
        }
        const current = collectFormValues();
        const keys = Object.keys(initialState || {});
        for (let i = 0; i < keys.length; i++) {
            const key = keys[i];
            if ((initialState[key] || '') !== (current[key] || '')) {
                return true;
            }
        }
        return false;
    };

    const toggleSaveState = function () {
        const valid = validator ? validator.validateForm() : false;
        const disabled = !(valid && hasChanges());
        $('#btnProfileSave, #btnProfileSaveTop').prop('disabled', disabled);
    };

    const setField = function (name, value, isInit) {
        try {
            mzSetFieldValue(name, value, 'text', '', isInit);
        } catch (err) {
            const selector = $('#txt' + name);
            selector.val(value || '');
            if (value) {
                $('#lbl' + name).addClass('active');
            } else {
                $('#lbl' + name).removeClass('active');
            }
        }
    };

    const updateStatusPill = function (statusCode) {
        const meta = statusMap[statusCode] || { label: 'Pending', tone: 'pending', icon: 'fa-hourglass-half' };
        const pill = $('#lblProfileStatus');
        pill.removeClass('active inactive pending');
        pill.addClass(meta.tone);
        pill.html('<i class="fas ' + meta.icon + '"></i> ' + meta.label);
        $('#metricProfileStatus').text(meta.label);
        $('#metricProfileStatusDetail').text('Status code ' + (statusCode || '—'));
    };

    const updateAvatar = function (data) {
        const preview = avatarState.pending && avatarState.pending.preview ? avatarState.pending.preview : '';
        const src = preview || (data && data.imgUrl ? data.imgUrl : '');
        renderAvatar(src);
    };

    const updateRoleChips = function (rolesList) {
        const container = $('#profileRoleChips');
        container.empty();
        if (!rolesList.length) {
            container.append('<span class="text-muted small">No roles assigned</span>');
            return;
        }
        rolesList.forEach(function (role) {
            const chip = $('<span class="profile-role-chip"></span>').text(resolveRoleName(role));
            container.append(chip);
        });
    };

    const updateSummary = function (data) {
        const fullName = (data.userFullName || ((data.userFirstName || '') + ' ' + (data.userLastName || ''))).trim();
        $('#lblProfileFullName').text(fullName || '—');
        $('#lblProfileUsername').text(data.userName ? '@' + data.userName : 'Username —');
        $('#lblProfileDesignation').text(resolveLabel(refDesignation, data.designationId, 'designationId', 'designationDesc') || '—');
        const groupName = resolveLabel(refGroup, data.groupId, 'groupId', 'groupName');
        $('#lblProfileGroup').text(groupName ? groupName + ' (#' + data.groupId + ')' : (data.groupId ? 'Group #' + data.groupId : '—'));
        const siteName = resolveLabel(refSite, data.siteId, 'siteId', 'siteName');
        $('#lblProfileSite').text(siteName ? siteName + ' (' + data.siteId + ')' : (data.siteId ? 'Site #' + data.siteId : '—'));
        const clientName = resolveLabel(refClient, data.clientId, 'clientId', 'clientName');
        $('#lblProfileClient').text(clientName ? clientName + ' (' + data.clientId + ')' : (data.clientId ? 'Client #' + data.clientId : '—'));
        $('#lblProfileEmail').text(data.userEmail || '—');
        $('#lblProfileContact').text(data.profileContactNo || '—');
        $('#lblProfileMykad').text(data.userMykadNo || '—');
        updateAvatar(data);
        updateStatusPill(data.userStatus);

        const rolesList = parseRoles(data.roles);
        const roleNames = rolesList.map(resolveRoleName);
        $('#metricProfileRoles').text(rolesList.length);
        if (roleNames.length) {
            const detailText = roleNames.length > 3 ? roleNames.slice(0, 3).join(', ') + ' +' + (roleNames.length - 3) : roleNames.join(', ');
            $('#metricProfileRolesDetail').text(detailText);
        } else {
            $('#metricProfileRolesDetail').text('No roles detected');
        }
        $('#metricProfileSite').text(siteName || '—');
        $('#metricProfileSiteDetail').text(data.siteId ? 'Site ID ' + data.siteId : 'Site ID —');
        $('#metricProfileClient').text(clientName || '—');
        $('#metricProfileClientDetail').text(data.clientId ? 'Client ID ' + data.clientId : 'Client ID —');
        updateRoleChips(rolesList);
        $('#lblProfileUpdated').text('Last updated ' + (moment ? moment().format('DD MMM YYYY, h:mm A') : new Date().toLocaleString()));
        $('#profileHeaderName').text(fullName ? 'Hello, ' + fullName : 'Hello, there');
    };

    const handleAvatarFileInput = function (event) {
        const fileInput = event && event.target ? event.target : null;
        const file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (!file) { return; }

        if (!/^image\/(png|jpe?g)$/i.test(file.type)) {
            setAvatarNote('Please select a PNG or JPG image under 2 MB.', 'error');
            toastr['error']('Please select a PNG or JPG image.', _ALERT_TITLE_ERROR);
            if (fileInput) { fileInput.value = ''; }
            toggleSaveState();
            return;
        }
        if (file.size > MAX_AVATAR_SIZE) {
            setAvatarNote('Image must be 2 MB or smaller.', 'error');
            toastr['error']('Image must be 2 MB or smaller.', _ALERT_TITLE_ERROR);
            if (fileInput) { fileInput.value = ''; }
            toggleSaveState();
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const dataUrl = e.target.result;
            const image = new Image();
            image.onload = function () {
                avatarState.pending = {
                    preview: dataUrl,
                    upload: {
                        name: 'Profile Avatar',
                        filename: file.name,
                        size: file.size,
                        width: image.width,
                        height: image.height,
                        type: file.type,
                        data: dataUrl.split(',')[1]
                    }
                };
                renderAvatar(avatarState.pending.preview);
                setAvatarNote('New photo selected. Save to apply.', 'success');
                toggleSaveState();
            };
            image.onerror = function () {
                setAvatarNote('Unable to read the selected image.', 'error');
                toastr['error']('Unable to read the selected image.', _ALERT_TITLE_ERROR);
                avatarState.pending = null;
                renderAvatar(avatarState.original);
                toggleSaveState();
            };
            image.src = dataUrl;
        };
        reader.onerror = function () {
            setAvatarNote('Unable to read the selected image.', 'error');
            toastr['error']('Unable to read the selected image.', _ALERT_TITLE_ERROR);
            avatarState.pending = null;
            renderAvatar(avatarState.original);
            toggleSaveState();
        };
        reader.onloadend = function () {
            if (fileInput) { fileInput.value = ''; }
        };
        reader.readAsDataURL(file);
    };

    const populateForm = function (data, isInit) {
        setField('PflUsername', data.userName, isInit);
        $('#txtPflUsername').prop('disabled', true);
        setField('PflEmail', data.userEmail, isInit);
        setField('PflFirstName', data.userFirstName, isInit);
        setField('PflLastName', data.userLastName, isInit);
        setField('PflIdno', data.userMykadNo, isInit);
        setField('PflPhone', data.profileContactNo, isInit);
        if (isInit) {
            resetAvatarState(data.imgUrl);
        }
        initialState = collectFormValues();
        toggleSaveState();
    };

    const setupValidation = function () {
        const config = [
            {
                field_id: 'txtPflEmail',
                type: 'text',
                name: 'Email',
                validator: { notEmpty: true, maxLength: 100, email: true }
            },
            {
                field_id: 'txtPflFirstName',
                type: 'text',
                name: 'First Name',
                validator: { notEmpty: true, maxLength: 100 }
            },
            {
                field_id: 'txtPflLastName',
                type: 'text',
                name: 'Last Name',
                validator: { notEmpty: true, maxLength: 100 }
            },
            {
                field_id: 'txtPflIdno',
                type: 'text',
                name: 'MyKad No.',
                validator: { eqLength: 12, digit: true }
            },
            {
                field_id: 'txtPflPhone',
                type: 'text',
                name: 'Contact No.',
                validator: { minLength: 8, maxLength: 11, digit: true }
            }
        ];
        validator = new MzValidate('formPfl');
        validator.registerFields(config);
        $('#formPfl').on('keyup change', 'input', function () {
            toggleSaveState();
        });
    };

    const resolveUserId = function () {
        try {
            userId = mzGetUserInfoByParam('userId');
        } catch (err) {
            const sessionProfile = safeDecryptProfile();
            if (sessionProfile && sessionProfile.userId) {
                userId = sessionProfile.userId;
            }
        }
    };

    const buildProfileData = function (raw) {
        const payload = raw || {};
        const contact = payload.profileContactNo || payload.userContactNo || '';
        return {
            userId: payload.userId || '',
            userName: payload.userName || '',
            userEmail: payload.userEmail || '',
            userFirstName: payload.userFirstName || '',
            userLastName: payload.userLastName || '',
            userFullName: (payload.userFullName || '').trim(),
            userMykadNo: payload.userMykadNo || '',
            profileContactNo: contact,
            designationId: payload.designationId || '',
            groupId: payload.groupId || '',
            siteId: payload.siteId || '',
            clientId: payload.clientId || '',
            roles: payload.roles || '',
            userStatus: payload.userStatus || '',
            imgUrl: payload.imgUrl || ''
        };
    };

    const fetchProfile = function (showLoader) {
        if (!userId) {
            toastr['error']('Unable to determine user profile', _ALERT_TITLE_ERROR);
            return;
        }
        if (showLoader) { ShowLoader(); }
        try {
            const resp = mzAjaxRequest('profile.php?userId=' + userId, 'GET');
            profileData = buildProfileData(resp);
            populateForm(profileData, true);
            updateSummary(profileData);
        } catch (err) {
            toastr['error'](err.message, _ALERT_TITLE_ERROR);
        }
        if (showLoader) { HideLoader(); }
    };

    const submitProfile = function () {
        if (!validator.validateNow()) {
            toastr['error'](_ALERT_MSG_VALIDATION, _ALERT_TITLE_VALIDATION_ERROR);
            return;
        }
        const formValues = collectFormValues();
        const requestPayload = Object.assign({}, formValues);
        if (avatarState.pending && avatarState.pending.upload) {
            requestPayload.profileAvatarUpload = avatarState.pending.upload;
        }
        requestPayload.action = 'profile';
        ShowLoader();
        setTimeout(function () {
            try {
                const resp = mzAjaxRequest('profile.php?userId=' + userId, 'PUT', requestPayload);
                profileData.userEmail = formValues.userEmail;
                profileData.userFirstName = formValues.userFirstName;
                profileData.userLastName = formValues.userLastName;
                profileData.userMykadNo = formValues.userMykadNo;
                profileData.profileContactNo = formValues.profileContactNo;
                profileData.userFullName = (formValues.userFirstName + ' ' + formValues.userLastName).trim();
                if (resp && typeof resp === 'object' && Object.prototype.hasOwnProperty.call(resp, 'imgUrl')) {
                    profileData.imgUrl = resp.imgUrl || '';
                }
                avatarState.pending = null;
                updateSummary(profileData);
                populateForm(profileData, true);
                persistProfileSession(profileData);
            } catch (err) {
                toastr['error'](err.message, _ALERT_TITLE_ERROR);
            }
            HideLoader();
        }, 150);
    };

    const bindEvents = function () {
        $('#btnProfileRefresh').on('click', function () { fetchProfile(true); });
        $('#btnProfileReset').on('click', function () {
            populateForm(profileData, true);
        });
        $('#btnProfileAvatarChange, #profileAvatar').on('click', function () {
            $('#fileProfileAvatar').trigger('click');
        });
        $('#fileProfileAvatar').on('change', function (event) {
            handleAvatarFileInput(event);
        });
        $('#btnProfileSave, #btnProfileSaveTop').on('click', function () { submitProfile(); });
    };

    this.init = function () {
        versionLocal = mzGetDataVersion();
        refSite = safeGetLocal('gems_site', 'siteId', 'site');
        refClient = safeGetLocal('gems_client', 'clientId', 'client');
    refDesignation = safeGetLocal('gems_designation', 'designationId', 'designation');
    refGroup = safeGetLocal('gems_group', 'groupId');
    refRole = safeGetLocal('gems_role', 'roleId', 'role');
        setupValidation();
        bindEvents();
        resolveUserId();
        fetchProfile(false);
    };
}

