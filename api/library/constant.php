<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2/18/2019
 * Time: 10:39 PM
 */

class Class_constant {

    const URL = '//localhost:8081/icon/';

    const ERR_DEFAULT = 'Error on system. Please contact Administrator!';
    const ERR_LOGIN_NOT_EXIST = 'User ID not exist';
    const ERR_LOGIN_WRONG_PASSWORD = 'Password is incorrect';
    const ERR_LOGIN_NOT_ACTIVE = 'User ID is not active. Please contact Administrator to activate.';
    const ERR_FORGOT_PASSWORD_NOT_EXIST = 'User ID not exist';
    const ERR_CHANGE_PASSWORD_WRONG_CURRENT = 'Current Password is incorrect';
    const ERR_ROLE_DELETE_HAVE_TASK = 'This user cannot be removed from this roles since there are still task assigned. Please delegate the task first.';
    const ERR_ROLE_DELETE_ALONE = 'There is no other user are assigned to this role. Please assign this role to new user before remove this user form this role.';
    const ERR_USER_ADD_SIMILAR_BADAN = 'No. Badan telah sedia didaftar. Sila pastikan No. Badan adalah betul.';
    const ERR_USER_ADD_SIMILAR_USERNAME = 'Login ID telah sedia didaftar. Sila pastikan Login ID baru yang belum didaftar.';

    const SUC_FORGOT_PASSWORD = 'Your password successfully reset. Please login with temporary password sent to your email.';
    const SUC_CHANGE_PASSWORD = 'Your password successfully changed';

    const ERR_USER_DEACTIVATE = 'User already inactive';
    const ERR_USER_ACTIVATE = 'User already active';
    const ERR_DESIGNATION_SIMILAR = 'Designation already exist';
    const ERR_DESIGNATION_DEACTIVATE = 'Designation already inactive';
    const ERR_DESIGNATION_ACTIVATE = 'Designation already active';
    const ERR_DESIGNATION_DELETE = 'Designation cannot be deleted because currently registered under user profile';
    const ERR_CLIENT_SIMILAR = 'Client already exist';
    const ERR_CLIENT_DEACTIVATE = 'Client already inactive';
    const ERR_CLIENT_ACTIVATE = 'Client already active';
    const ERR_CLIENT_DELETE_SITE = 'Client cannot be deleted because currently registered under Site';
    const ERR_SITE_SIMILAR = 'Site already exist under similar Client';
    const ERR_SITE_DEACTIVATE = 'Site already inactive';
    const ERR_SITE_ACTIVATE = 'Site already active';
    const ERR_SITE_DELETE_CONTRACT = 'Site cannot be deleted because currently registered under Contract';
    const ERR_CONTRACT_SIMILAR = 'Contract already exist under similar Client';
    const ERR_CONTRACT_DEACTIVATE = 'Contract already inactive';
    const ERR_CONTRACT_ACTIVATE = 'Contract already active';
    const ERR_CONTRACT_DELETE_ASSET = 'Contract cannot be deleted because currently registered under Asset';

    const SUC_USER_ADD = 'User successfully added';
    const SUC_USER_UPDATE = 'User successfully updated';
    const SUC_USER_DEACTIVATE = 'User successfully deactivated';
    const SUC_USER_ACTIVATE = 'User successfully activated';
    const SUC_DESIGNATION_ADD = 'Designation successfully added';
    const SUC_DESIGNATION_EDIT = 'Designation successfully updated';
    const SUC_DESIGNATION_DEACTIVATE = 'Designation successfully deactivated';
    const SUC_DESIGNATION_ACTIVATE = 'Designation successfully activated';
    const SUC_DESIGNATION_DELETE = 'Designation successfully deleted';
    const SUC_CLIENT_ADD = 'Client successfully added';
    const SUC_CLIENT_EDIT = 'Client successfully updated';
    const SUC_CLIENT_DEACTIVATE = 'Client successfully deactivated';
    const SUC_CLIENT_ACTIVATE = 'Client successfully activated';
    const SUC_CLIENT_DELETE = 'Client successfully deleted';
    const SUC_SITE_ADD = 'Site successfully added';
    const SUC_SITE_EDIT = 'Site successfully updated';
    const SUC_SITE_DEACTIVATE = 'Site successfully deactivated';
    const SUC_SITE_ACTIVATE = 'Site successfully activated';
    const SUC_SITE_DELETE = 'Site successfully deleted';
    const SUC_CONTRACT_ADD = 'Contract successfully added';
    const SUC_CONTRACT_EDIT = 'Contract successfully updated';
    const SUC_CONTRACT_DEACTIVATE = 'Contract successfully deactivated';
    const SUC_CONTRACT_ACTIVATE = 'Contract successfully activated';
    const SUC_CONTRACT_DELETE = 'Contract successfully deleted';
}