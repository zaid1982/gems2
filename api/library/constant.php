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

    const ERR_ACTIVITY_SUBMIT_NOT_DRAFT = 'Peralatan telah dihantar sebelum ini. Sila refresh paparan.';
    const ERR_ACTIVITY_SUBMIT_DATE = 'Tarikh Mula atau Tarikh Tamat yang dimasukkan telah berlalu. Sila semak semula Tarikh Mula dan Tarikh Tamat.';
    const ERR_ACTIVITY_ASSET_SIMILAR = 'Peralatan telah sedia ada';
    const ERR_ACTIVITY_ASSET_CHECKED = 'Peralatan telah sedia dipulangkan. Sila refresh Senarai Peralatan.';
    const ERR_USER_DEACTIVATE = 'Pengguna Sistem telah sedia tidak aktif';
    const ERR_USER_ACTIVATE = 'Pengguna Sistem telah sedia aktif';
    const ERR_ACTIVITY_SUBMIT_NOASSET = 'Sila pastikan peralatan yang dipinjam didaftarkan';
    const ERR_LEAVE_TYPE_SIMILAR = 'Jenis Cuti telah sedia ada';
    const ERR_LEAVE_TYPE_DEACTIVATE = 'Jenis Cuti telah sedia tidak aktif';
    const ERR_LEAVE_TYPE_ACTIVATE = 'Jenis Cuti telah sedia aktif';
    const ERR_LEAVE_TYPE_DELETE = 'Jenis Cuti tidak boleh dihapus kerana telah didaftarkan dalam rekod cuti';
    const ERR_JABATAN_SIMILAR = 'Jabatan telah sedia ada';
    const ERR_JABATAN_DEACTIVATE = 'Jabatan telah sedia tidak aktif';
    const ERR_JABATAN_ACTIVATE = 'Jabatan telah sedia aktif';
    const ERR_JABATAN_DELETE = 'Jabatan tidak boleh dihapus kerana telah didaftarkan dalam rekod pegawai';
    const ERR_JAWATAN_SIMILAR = 'Jawatan telah sedia ada';
    const ERR_JAWATAN_DEACTIVATE = 'Jawatan telah sedia tidak aktif';
    const ERR_JAWATAN_ACTIVATE = 'Jawatan telah sedia aktif';
    const ERR_JAWATAN_DELETE = 'Jawatan tidak boleh dihapus kerana telah didaftarkan dalam rekod pegawai';
    const ERR_ASSET_CATEGORY_SIMILAR = 'Jenis Peralatan telah sedia ada';
    const ERR_ASSET_CATEGORY_DEACTIVATE = 'Jenis Peralatan telah sedia tidak aktif';
    const ERR_ASSET_CATEGORY_ACTIVATE = 'Jenis Peralatan telah sedia aktif';
    const ERR_ASSET_CATEGORY_DELETE = 'Jenis Peralatan tidak boleh dihapus kerana telah didaftarkan dalam rekod pegawai';
    const ERR_ASSET_SIMILAR_SERIAL = 'No Siri Peralatan telah sedia ada';
    const ERR_ASSET_SIMILAR_REGNO = 'No Pendaftaran Peralatan telah sedia ada';
    const ERR_ASSET_DEACTIVATE = 'Peralatan telah sedia tidak aktif';
    const ERR_ASSET_ACTIVATE = 'Peralatan telah sedia aktif';
    const ERR_ASSET_DELETE = 'Peralatan tidak boleh dihapus kerana telah didaftarkan dalam rekod aktiviti';

    const SUC_ACTIVITY_SAVE = 'Aktiviti berjaya disimpan';
    const SUC_ACTIVITY_DELETE = 'Aktiviti berjaya dihapus';
    const SUC_ACTIVITY_ASSET_DELETE = 'Peralatan berjaya dihapus';
    const SUC_ACTIVITY_ASSET_CHECKED = 'Peralatan yang dipulangkan berjaya direkodkan';
    const SUC_ACTIVITY_SUBMIT = 'Aktiviti berjaya dihantar. Petugas telah disusun jadual seperti ditunjukkan di Senarai Petugas.';
    const SUC_USER_ADD = 'Pengguna Sistem berjaya ditambah';
    const SUC_USER_UPDATE = 'Pengguna Sistem berjaya dikemaskini';
    const SUC_USER_DEACTIVATE = 'Pengguna Sistem berjaya dinyahaktifkan';
    const SUC_USER_ACTIVATE = 'Pengguna Sistem berjaya diaktifkan';
    const SUC_LEAVE_TYPE_ADD = 'Jenis Cuti berjaya ditambah';
    const SUC_LEAVE_TYPE_EDIT = 'Jenis Cuti berjaya dikemaskini';
    const SUC_LEAVE_TYPE_DEACTIVATE = 'Jenis Cuti berjaya dinyahaktifkan';
    const SUC_LEAVE_TYPE_ACTIVATE = 'Jenis Cuti berjaya diaktifkan';
    const SUC_LEAVE_TYPE_DELETE = 'Jenis Cuti berjaya dihapus';
    const SUC_JABATAN_ADD = 'Jabatan berjaya ditambah';
    const SUC_JABATAN_EDIT = 'Jabatan berjaya dikemaskini';
    const SUC_JABATAN_DEACTIVATE = 'Jabatan berjaya dinyahaktifkan';
    const SUC_JABATAN_ACTIVATE = 'Jabatan berjaya diaktifkan';
    const SUC_JABATAN_DELETE = 'Jabatan berjaya dihapus';
    const SUC_JAWATAN_ADD = 'Jawatan berjaya ditambah';
    const SUC_JAWATAN_EDIT = 'Jawatan berjaya dikemaskini';
    const SUC_JAWATAN_DEACTIVATE = 'Jawatan berjaya dinyahaktifkan';
    const SUC_JAWATAN_ACTIVATE = 'Jawatan berjaya diaktifkan';
    const SUC_JAWATAN_DELETE = 'Jawatan berjaya dihapus';
    const SUC_ASSET_CATEGORY_ADD = 'Jenis Peralatan berjaya ditambah';
    const SUC_ASSET_CATEGORY_EDIT = 'Jenis Peralatan berjaya dikemaskini';
    const SUC_ASSET_CATEGORY_DEACTIVATE = 'Jenis Peralatan berjaya dinyahaktifkan';
    const SUC_ASSET_CATEGORY_ACTIVATE = 'Jenis Peralatan berjaya diaktifkan';
    const SUC_ASSET_CATEGORY_DELETE = 'Jenis Peralatan berjaya dihapus';
    const SUC_ASSET_ADD = 'Peralatan berjaya ditambah';
    const SUC_ASSET_EDIT = 'Peralatan berjaya dikemaskini';
    const SUC_ASSET_DEACTIVATE = 'Peralatan berjaya dinyahaktifkan';
    const SUC_ASSET_ACTIVATE = 'Peralatan berjaya diaktifkan';
    const SUC_ASSET_DELETE = 'Peralatan berjaya dihapus';
}