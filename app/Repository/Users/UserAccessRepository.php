<?php

/**
 * @developedBy JanJapan IT & Support Center
 * @date Jul 7, 2017
 * @version Version 1.0
 * @author Kashif Umar <Kashif.TLB@gmail.com>
 */

namespace App\Repository\Users;

use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Hash;
use Exception;
//use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Repository\Users\UserRepository as UserRepo;
use App\Repository\Admin\AdminRepository as AdminRepo;
use App\Repository\PreAuction\PreAuction;
//use App\Repository\Common\DBRO;
use Carbon\Carbon;

class UserAccessRepository {

    public static function permissions($controller_name, $function_name, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        if (self::is_super_admin($app_user_id)) {

            $permissions = [
                'can_view' => TRUE,
                'can_add' => TRUE,
                'can_edit' => TRUE,
                'can_delete' => TRUE,
                'can_compare_bids' => TRUE,
                'can_apply_expense' => TRUE,
                'can_view_admin_auction_info' => TRUE,
                'can_view_extra_price' => TRUE,
                'buying_team' => TRUE,
                'buying_team_pdf' => TRUE,
                'buying_team_auctions_pdf' => TRUE,
                'buying_team_jpn_xls' => TRUE,
                'can_transfer' => TRUE,
                'can_view_pre_auc_bids' => TRUE,
                'fetch_nbc_info' => TRUE,
                'fetch_np_info' => TRUE,
                'fetch_pa_info' => TRUE,
                'verify_bid_prices' => TRUE,
                'super_edit_branch_bid_list' => TRUE,
                'reduced_price_branch_list' => TRUE,
                'reduced_price_view_list' => TRUE,
                'fetch_pa_log_info' => TRUE,
                'can_toggle' => TRUE,
                'can_view_corrected' => TRUE,
            ];

            return $permissions;
        }

        $query = "SELECT rmp.permission_id "
                . " FROM roles_menus_permissions rmp "
                . " JOIN menus m ON m.id = rmp.menu_id "
                . " AND m.controller_name = '$controller_name' "
                . " AND m.function_name = '$function_name' "
                . " JOIN roles r ON r.id = rmp.role_id AND r.deleted_at IS NULL "
                . " JOIN users_roles ur ON r.id = ur.role_id "
                . " AND ur.deleted_at is NULL "
                . " AND ur.user_id = $app_user_id;";

//        if ($app_user_id == 15) {
//            echo($query);
//            die;
//        }
        $result = DB::select($query);
        $permissions = [
            'can_view' => FALSE,
            'can_add' => FALSE,
            'can_edit' => FALSE,
            'can_delete' => FALSE,
            'can_compare_bids' => FALSE,
            'can_apply_expense' => FALSE,
            'can_view_admin_auction_info' => FALSE,
            'can_view_extra_price' => FALSE,
            'can_transfer' => FALSE,
            'buying_team' => FALSE,
            'buying_team_pdf' => FALSE,
            'buying_team_auctions_pdf' => FALSE,
            'buying_team_jpn_xls' => FALSE,
            'can_view_pre_auc_bids' => FALSE,
            'fetch_nbc_info' => FALSE,
            'fetch_np_info' => FALSE,
            'fetch_pa_info' => FALSE,
            'verify_bid_prices' => FALSE,
            'super_edit_branch_bid_list' => FALSE,
            'reduced_price_branch_list' => FALSE,
            'reduced_price_view_list' => FALSE,
            'fetch_pa_log_info' => FALSE,
            'can_toggle' => FALSE,
            'can_view_corrected' => FALSE,
        ];

        if ($result) {
            foreach ($result as $r) {
                switch ($r->permission_id) {
                    case 1:
                        $permissions ['can_view'] = TRUE;
                        break;
                    case 2:
                        $permissions ['can_add'] = TRUE;
                        break;
                    case 3:
                        $permissions ['can_edit'] = TRUE;
                        break;
                    case 4:
                        $permissions ['can_delete'] = TRUE;
                        break;
                    case 5:
                        $permissions ['can_compare_bids'] = TRUE;
                        break;
                    case 6:
                        $permissions ['can_apply_expense'] = TRUE;
                        break;
                    case 7:
                        $permissions ['can_view_admin_auction_info'] = TRUE;
                        break;
                    case 9:
                        $permissions ['can_view_extra_price'] = TRUE;
                        break;
                    case 10:
                        $permissions ['buying_team'] = TRUE;
                        break;
                    case 11:
                        $permissions ['can_transfer'] = TRUE;
                        break;

                    case 12:
                        $permissions ['can_view_pre_auc_bids'] = TRUE;
                        break;

                    case 13:
                        $permissions ['buying_team_pdf'] = TRUE;
                        break;

                    case 14:
                        $permissions ['buying_team_auctions_pdf'] = TRUE;
                        break;

                    case 15:
                        $permissions ['buying_team_jpn_xls'] = TRUE;
                        break;

                    case 16:
                        $permissions ['fetch_nbc_info'] = TRUE;
                        break;

                    case 17:
                        $permissions ['fetch_np_info'] = TRUE;
                        break;

                    case 18:
                        $permissions ['fetch_pa_info'] = TRUE;
                        break;

                    case 19:
                        $permissions ['verify_bid_prices'] = TRUE;
                        break;

                    case 20:
                        $permissions ['super_edit_branch_bid_list'] = TRUE;
                        break;
                    case 22:
                        $permissions ['reduced_price_branch_list'] = TRUE;
                        break;
                    case 23:
                        $permissions ['reduced_price_view_list'] = TRUE;
                        break;

                    case 21:
                        $permissions ['fetch_pa_log_info'] = TRUE;
                        break;
                    case 24:
                        $permissions ['branch_pdf_download'] = TRUE;
                        break;
                    case 25:
                        $permissions ['can_toggle'] = TRUE;
                        break;
                    case 26:
                        $permissions ['can_view_corrected'] = TRUE;
                        break;
                }
            }
        }

        return $permissions;
    }

    public static function is_super_admin($app_user_id = NULL, $prefix = null) {

//        display_admin_debug($app_user_id, FALSE);
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        if (self::is_dev_admin($app_user_id)) {
            return true;
        }

        if (Session::has('is_super_admin')) {
            $is_super_admin = Session::get('is_super_admin');
            return $is_super_admin;
        }
//        display_admin_debug($app_user_id, FALSE);
        $query = "SELECT id "
                . "FROM users_roles ur "
                . "WHERE ur.role_id = 1 AND ur.user_id = $app_user_id "
                . " and ur.deleted_at is null ";
//        display_admin_debug($query);
        $user_role = DB::select($query);
        if ($user_role) {
            if (is_null($prefix)) {
                Session::put('is_super_admin', 1);
            }
            return true;
        }
        if (is_null($prefix)) {
            Session::put('is_super_admin', 0);
        }
        return false;
    }

    public static function is_dev_admin($app_user_id): bool {
        $query = "SELECT u.id "
                . " FROM users u "
                . " WHERE u.is_dev_admin = 1 AND u.id = $app_user_id "
                . " AND u.deleted_at IS NULL ";
        $dev_admin = DB::select($query);
        if (count($dev_admin)) {
            return true;
        }
        return false;
    }

    public static function is_branch_user($app_user_id = null, $prefix = null) {

        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        if (self::is_super_admin($app_user_id, $prefix)) {
            return true;
        }

        $user = DB::table('roles')
                        ->join('users_roles', function ($join) {
                            $join->on('roles.id', '=', 'users_roles.role_id')
                                    ->whereNull('users_roles.deleted_at')
                                    ->where('roles.id', '=', 3);
                        })
                        ->where(function ($query) use ($app_user_id) {
                            $query->where('users_roles.user_id', '=', $app_user_id);
                        })->whereNull('roles.deleted_at')->get()->first();
        if ($user) {
            return true;
        }
        return false;
    }

    public static function is_japan_user($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
//        role_id = 2 is japan_user
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT r.id  FROM roles r  "
                . " JOIN users_roles ur "
                . " ON r.id = ur.role_id  AND r.id = 2 AND ur.user_id = $app_user_id "
                . " WHERE r.deleted_at IS NULL";
        $result = DB::select($query);
        if ($result) {
            return TRUE;
        }
        return FALSE;
    }

    public static function is_dubai_user($app_user_id = NULL, $source = "unknown") {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }

        $app = "APP";
        if (is_null($app_user_id)) {
            $app = "WEB";
            $app_user_id = UserRepo::get_user_id();
        }

        $user = User::find($app_user_id);

//        if($app_user){
//            kas_pr($user);
//            die;
//        }
        try {
            if ($user->country_id == 17) {
                return TRUE;
            }
        } catch (Exception $ex) {
            $function_name = "is_dubai_user_" . $app . "_" . $source;
            AdminRepo::create_error_log("UserAccessRepository", $function_name, $ex, $app_user_id);
        }
        return FALSE;
    }

    public static function is_buying_team($app_user_id = null) {

        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        if (Session::has('is_buying_team')) {
            $is_buying_team = Session::get('is_buying_team');
            return $is_buying_team;
        }
        $query = " SELECT users.id, users.is_buying_team "
                . " FROM users "
                . " WHERE users.id = $app_user_id "
                . " AND users.is_buying_team = 1 "
                . " AND users.status = 1 ";
        $is_bt = DB::select($query);
        if ($is_bt) {
            Session::put('is_buying_team', 1);
            return true;
        }
        Session::put('is_buying_team', 0);
        return false;
    }

    public static function can_compare_bids($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        $query = "SELECT p.*, rmp.role_id, m.function_name, rmp.menu_id "
                . " FROM permissions p "
                . " JOIN roles_menus_permissions rmp ON p.id = rmp.permission_id "
                . " JOIN menus m ON m.id = rmp.menu_id AND m.deleted_at IS NULL "
                . " JOIN users_roles ur ON ur.role_id = rmp.role_id "
                . " AND ur.deleted_at IS NULL "
                . " WHERE p.id = 5 AND m.function_name = 'all_auctions_data' "
                . " AND m.controller_name = 'auctions' "
                . " AND ur.user_id = $app_user_id";
        $result = DB::select($query);
        if ($result) {
            return TRUE;
        }
        return FALSE;
    }

    public static function can_apply_expense($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        $query = "SELECT rmp.role_id, m.function_name, rmp.menu_id "
                . " FROM roles_menus_permissions rmp "
                . " JOIN menus m ON m.id = rmp.menu_id AND m.deleted_at IS NULL "
                . " JOIN users_roles ur ON ur.role_id = rmp.role_id "
                . " AND ur.deleted_at IS NULL "
                . " WHERE rmp.permission_id = 6 "
                . " AND m.function_name = 'auctions_data' "
                . " AND m.controller_name = 'preauctions' "
                . " AND ur.user_id = $app_user_id";

        $result = DB::select($query);
        if ($result) {
            return TRUE;
        }
        return FALSE;
    }

    public static function can_update_is_max_rate($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        $query = "SELECT rmp.role_id, m.function_name, rmp.menu_id "
                . " FROM roles_menus_permissions rmp "
                . " JOIN menus m ON m.id = rmp.menu_id AND m.deleted_at IS NULL "
                . " JOIN users_roles ur ON ur.role_id = rmp.role_id "
                . " AND ur.deleted_at IS NULL "
                . " WHERE rmp.permission_id = 8 "
                . " AND m.function_name = 'all_auctions_data' "
                . " AND m.controller_name = 'auctions' "
                . " AND ur.user_id = $app_user_id";

        $result = DB::select($query);
        if ($result) {
            return TRUE;
        }
        return FALSE;
    }

    public static function can_view_admin_auction_info($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        $query = "SELECT p.*, rmp.role_id, m.function_name, rmp.menu_id "
                . " FROM permissions p "
                . " JOIN roles_menus_permissions rmp ON p.id = rmp.permission_id "
                . " JOIN menus m ON m.id = rmp.menu_id AND m.deleted_at IS NULL "
                . " JOIN users_roles ur ON ur.role_id = rmp.role_id "
                . " AND ur.deleted_at IS NULL "
                . " WHERE p.id = 7 AND m.function_name = 'auctions_data' "
                . " AND m.controller_name = 'preauctions' "
                . " AND ur.user_id = $app_user_id";
        $result = DB::select($query);
        if ($result) {
            return TRUE;
        }
        return FALSE;
    }

    public static function can_view($controller_name, $function_name, $app_user_id = NULL) {

        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT m.id menu_id, m.is_active, m.is_app_menu FROM menus m "
                . " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 1 "
                . " JOIN roles r ON r.id = rmp.role_id AND r.deleted_at is NULL "
                . " JOIN users_roles ur ON ur.role_id = r.id AND ur.deleted_at is NULL "
                . " JOIN users u ON u.id = ur.user_id AND ur.user_id = $app_user_id "
                . " AND u.deleted_at is NULL "
                . " WHERE m.controller_name = '$controller_name' "
                . " AND m.function_name = '$function_name' AND m.deleted_at is NULL ";
        
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        if (!$result[0]->is_active) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_add($controller_name, $function_name, $app_user_id = NULL) {

//        display_admin_debug($app_user_id);
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT m.id menu_id FROM menus m "
                . " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 2 "
                . " JOIN roles r ON r.id = rmp.role_id AND r.deleted_at is NULL "
                . " JOIN users_roles ur ON ur.role_id = r.id AND ur.deleted_at is NULL "
                . " JOIN users u ON u.id = ur.user_id AND ur.user_id = $app_user_id "
                . " AND u.deleted_at is NULL "
                . " WHERE m.controller_name = '$controller_name' "
                . " AND m.function_name = '$function_name' AND m.deleted_at is NULL ";
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_delete($controller_name, $function_name, $app_user_id = NULL) {

        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT m.id menu_id FROM menus m "
                . " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 4 "
                . " JOIN roles r ON r.id = rmp.role_id AND r.deleted_at is NULL "
                . " JOIN users_roles ur ON ur.role_id = r.id AND ur.deleted_at is NULL "
                . " JOIN users u ON u.id = ur.user_id AND ur.user_id = $app_user_id "
                . " AND u.deleted_at is NULL "
                . " WHERE m.controller_name = '$controller_name' "
                . " AND m.function_name = '$function_name' AND m.deleted_at is NULL ";
//        if($app_user_id == 96){
//            echo($query);
//            die;
//        }
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_edit($controller_name, $function_name, $app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT m.id menu_id FROM menus m "
                . " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 3 "
                . " JOIN roles r ON r.id = rmp.role_id AND r.deleted_at is NULL "
                . " JOIN users_roles ur ON ur.role_id = r.id AND ur.deleted_at is NULL "
                . " JOIN users u ON u.id = ur.user_id AND ur.user_id = $app_user_id "
                . " AND u.deleted_at is NULL "
                . " WHERE m.controller_name = '$controller_name' "
                . " AND m.function_name = '$function_name' AND m.deleted_at is NULL ";
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_super_edit_branch_list($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }

        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT rmp.id, rmp.role_id, rmp.menu_id, rmp.permission_id, "
                . " roles.role_name, users.login_id "
                . " FROM roles_menus_permissions rmp "
                . " JOIN roles ON rmp.role_id = roles.id AND roles.deleted_at IS NULL "
                . " JOIN users_roles ur ON roles.id = ur.role_id AND ur.deleted_at IS NULL "
                . " JOIN users ON ur.user_id = users.id AND users.deleted_at IS NULL "
                . " WHERE rmp.permission_id = 20 "
                . " and users.id = $app_user_id";
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function transfer_lock_12am_jpn_time($app_user_id = NULL) {
        if (self::is_super_admin($app_user_id) || self::is_japan_user($app_user_id) || self::is_dubai_user($app_user_id, "UAC_transfer_lock_12am_jpn_time")) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        //role id 38
        //jpn_12_am_transfer
        $query = "SELECT ur.id FROM users_roles ur  "
                . " WHERE ur.role_id = 38 AND ur.user_id = $app_user_id";
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_view_all_countries($app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        //role id 40
        //view all countries
        $query = "SELECT ur.id FROM users_roles ur  "
                . " WHERE ur.role_id = 40 AND ur.user_id = $app_user_id";
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_transfer($app_user_id = NULL) {

        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT u.id, u.status "
                . " FROM users u "
                . " JOIN users_roles ur ON u.id =  ur.user_id AND ur.deleted_at IS NULL "
                . " join roles r ON ur.role_id = r.id AND r.deleted_at IS NULL "
                . " JOIN roles_menus_permissions rmp ON ur.role_id = rmp.role_id  "
                . " AND rmp.menu_id = 4 AND rmp.permission_id = 11 "
                . " WHERE u.id = $app_user_id "
                . " AND u.status = 1 AND u.deleted_at IS NULL ";

        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_transfer_any_time($app_user_id = NULL) {
        return TRUE;
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
//        if(SESSION::has('can_transfer_any_time')){
//            $can_transfer_any_time = SESSION::get('can_transfer_any_time');
//            return $can_transfer_any_time;
//        }
        //role id 41
        //transfer any time
        $query = "SELECT ur.id FROM users_roles ur  "
                . " WHERE ur.role_id = 41 AND ur.user_id = $app_user_id";
        $result = DB::select($query);
        if (!$result) {
            SESSION::put('can_transfer_any_time', 0);
            return FALSE;
        }
        SESSION::put('can_transfer_any_time', 1);
        return TRUE;
    }

    public static function can_view_pre_auc_bids($app_user_id = NULL) {
        /**
         * 107 - dxbol
         * 185 - inayat_uk
         */
        $restricted_users = [107, 185];
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }

        if (!in_array($app_user_id, $restricted_users)) {
            return TRUE;
        }

        return FALSE;
    }

    public static function online_auction_bids_mobile_add_restrict($controller_name, $function_name, $app_user_id = NULL) {
//        display_admin_debug($app_user_id);
        if (self::is_super_admin($app_user_id)) {
            return FALSE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        $query = "
                    SELECT r.role_name
                    FROM menus m
                    JOIN roles_menus_permissions rmp
                    ON m.id = rmp.menu_id AND rmp.permission_id = 2
                    JOIN roles r ON r.id = rmp.role_id AND r.deleted_at is NULL
                    JOIN users_roles ur ON ur.role_id = r.id AND ur.deleted_at is NULL
                    JOIN users u ON u.id = ur.user_id AND ur.user_id = $app_user_id
                    AND u.deleted_at is NULL
                    WHERE m.controller_name = '$controller_name'
                    AND m.function_name = '$function_name' AND m.deleted_at is NULL
                    AND r.role_name = 'online_auction_bids_mobile_add_restrict'
                 ";
        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_fetch_pa_api_data($app_user_id = NULL) {

        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT m.id menu_id, m.is_active, m.is_app_menu "
                . " FROM menus m "
                . " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 18 "
                . " and m.id = 43 "
                . " JOIN roles r ON r.id = rmp.role_id AND r.deleted_at is NULL "
                . " JOIN users_roles ur ON ur.role_id = r.id AND ur.deleted_at is NULL "
                . " JOIN users u ON u.id = ur.user_id AND ur.user_id = $app_user_id "
                . " AND u.deleted_at is NULL "
                . " where m.deleted_at is NULL ";
//        if($app_user_id == 96 ){
//            echo($query);
//            die;
//        }

        $result = DB::select($query);
        if (!$result) {
            return FALSE;
        }
        if (!$result[0]->is_active) {
            return FALSE;
        }
        return TRUE;
    }

    public static function can_verify_reduction_checks($app_user_id = NULL) {

        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }
        return FALSE;
    }

    public static function can_view_offline_auction($app_user_id = NULL) {

//        if (self::is_super_admin($app_user_id)) {
//            return TRUE;
//        }
        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }
        $query = "SELECT ur.id, ur.user_id, ur.role_id "
                . " FROM users_roles ur "
                . " JOIN roles rol ON ur.role_id = rol.id AND rol.deleted_at IS NULL "
                . " JOIN users us ON ur.user_id = us.id AND us.status = 1 AND us.deleted_at IS NULL "
                . " WHERE us.id = $app_user_id AND (ur.role_id IN (168,169) OR us.is_buying_team = 1) "
                . " AND ur.deleted_at is NULL ";
        $result = DB::select($query);
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param type $auction_date YYYY-mm-dd
     * @param type $app_user_id
     * @return bool, if auction date 5:15 AM is less than current date_time then return true, else false based on JPN Time
     */
    public static function can_download_offline_auction($auction_date, $app_user_id = NULL) {
        if (self::is_super_admin($app_user_id)) {
            return TRUE;
        }

        if (is_null($app_user_id)) {
            $app_user_id = UserRepo::get_user_id();
        }

        date_default_timezone_set("Asia/Tokyo");
        // Convert $auction_date to Carbon instance and add 5 hours and 15 minutes
        $auction_date_time = Carbon::parse($auction_date)->addHours(5)->addMinutes(15);
        date_default_timezone_set("Asia/Karachi");
        // Get current date and time based on Japan timezone
        $current_date_time = Carbon::now('Asia/Tokyo');

        // Compare both date_times
        return $current_date_time->gte($auction_date_time);
    }

    public static function is_pdf_generated($auction_date) {
        $db_auction_date = php_to_mysql_date($auction_date);

        $query = "SELECT btpfm.pdf_file_master_id "
                . " FROM buying_teams_pdf_files_master btpfm "
                . " WHERE btpfm.auction_date = '$db_auction_date' "
                . " AND DATE(btpfm.created_at) = btpfm.auction_date";
        $result = DB::select($query);
        if (!$result) {
            return false;
        }
        return true;
    }

    public static function can_delete_view_bidding_entry($auction_data_id, $app_user_id) {

        $pac = PreAuction::find($auction_data_id);
        if ($pac->shifted == 1) {
            return false;
        }

        if (is_null($app_user_id)) {
            $app_user_id = get_user_id();
        }

        if (is_super_admin($app_user_id) || self::is_dev_admin($app_user_id)) {
            return true;
        }
        //check user market rights
        return true;
    }
}
