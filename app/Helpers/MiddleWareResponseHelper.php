<?php

/**
 * @developedBy JanJapan IT & Support Center
 * @date May 5, 2018
 * @version Version 1.0
 * @author Kashif Umar <Kashif.TLB@gmail.com>
 */

function app_user_id_error() {
    return response([
        'error' => TRUE,
        'msg' => 'App User Id Missing',
        'code' => 900,
    ]);
}

/**
 * 
 * @description Can Not View Screen
 */
function screen_error() {
    return response([
        'error' => TRUE,
        'msg' => env('UAA'),
        'code' => 900,
    ]);
}

function can_view_error() {
    return response([
        'rows' => [],
        'total' => 0,
        'msg' => env('UAA'),
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_add_error() {
    return response([
        'rows' => [],
        'total' => 0,
        'msg' => 'Can Not Add',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_delete_error() {
    return response([
        'rows' => [],
        'total' => 0,
        'msg' => 'Can Not Delete',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function permission_error() {
    return response([
        'error' => TRUE,
        'msg' => 'Permissions Not Defined',
        'code' => 420
        ]);
}

function can_compre_error() {
    return response([
        'msg' => 'Can Not Compare',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_transfer_error() {
    return response([
        'msg' => 'Can Not Transfer',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_edit_error() {
    return response([
        'msg' => 'Can Not Edit',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_toggle_error() {
    return response([
        'msg' => 'Can Not Change Status',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_apply_expense_error() {
    return response([
        'msg' => 'Can Not Appply Expense',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_update_is_max_rate_error() {
    return response([
        'msg' => 'Can Not Update Is Max Rate',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_fetch_api_data_error() {
    return response([
        'msg' => 'Can Not Fetch API Data',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_fetch_pre_auc_api_data_error() {
    return response([
        'msg' => 'Can Not Fetch API Data',
        'error' => TRUE,
        'code' => 900,
    ]);
}

function can_generic_error($msg) {
    return response([
        'msg' => $msg,
        'error' => TRUE,
        'code' => 900,
    ]);
}
