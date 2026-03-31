<?php

namespace App\Repository\Users;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Repository\Users\User;
use App\Repository\Users\UserAccessRepository as UAR;
use App\Repository\Common\AppRepository as AppRepo;
use App\Repository\Admin\AdminRepository as AdminRepo;
use App\Repository\Users\MappUser;
use App\Repository\Common\DBRO;
use DateTime;
use DateInterval;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserRepository {

    //put your code here
    public function create_user($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }

        $user = User::where('login_id', $user_data['login_id'])
                ->get(['id']);

        unset($user_data['device_id']);
        /*
          $query = User::query();
          //        $query_count = User::query();
          //            $results['total_count'] = $query_count->count();
          $query = $query->where('login_id', $user_data['login_id']);
          //        $query = $query->orderBy('this');
          //        $query->limit($row_count)->offset($start);
          $user = $query->get(['id']);
         *
         */
        if (count($user)) {
            throw new Exception("*Login Id Already Exist", -1);
        }
        validate_password($user_data['password']);

        $original_password = $user_data['password'];
        $user_data['is_web_user'] = isset($user_data['is_web_user']) ? 1 : 0;
        $user_data['is_buying_team'] = isset($user_data['is_buying_team']) ? 1 : 0;
        $user_data['password'] = Hash::make($user_data['password']);
        $user_data['phone'] = $user_data['whatsapp_no'];
        $user_data['added_by'] = $app_user_id;
        //        if (isset($user_data['force_password_update'])) {
        //            $password_duration = $user_data['password_duration'];
        //            $user_data['password_updated_at'] = date('Y-m-d H:i:s', strtotime("+" . $password_duration . " days"));
        //        }
        $user = User::create($user_data);
        $now = date("Y-m-d H:i:s");

        self::users_for_admin($user->id, $original_password, "user_created_by_admin", $app_user_id);
        return $user;
    }

    public static function delete_user($user_data, $app_user_id = NULL) {
        extract($user_data);

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        try {
            $user = User::find($user_id);
            if ($user) {
                $user->deleted_by = $app_user_id;
                $user->status = 0;
                $user->is_web_user = 0;
                $user->is_app_user = 0;
                $user->is_extension_user = 0;
                $user->password = Hash::make("user_deleted-2");
                $user->api_token = NULL;
                $user->save();
                $user->delete();

                DB::table('users_for_ext')->where('user_id', '=', $user_id)->delete();
                $user_data = ['user_id' => $user_id];
                UserRoleRepository::delete_all_user_roles($user_data, $app_user_id);
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public static function login($user_data, $prefix = NULL) {
        extract($user_data);
        $source = 'Web';
        if (!is_null($prefix) && $prefix == 'api') {
            $source = 'APP';
        }

        $tracking_data['login_id'] = $login_id;
        $tracking_data['password'] = $password;
        $tracking_data['source'] = $source;
        $tracking_data['ip'] = $ip;
        $tracking_data['browser'] = $browser;
        $tracking_data['created_at'] = date('Y-m-d H:i:s');
        $tracking_data['login_status'] = 0;
        $tracking_data['device_id'] = $device_id;

        $user = User::where('login_id', $login_id)->get()->first();
        if (!$user) {
            LoginTracking::create($tracking_data);
            throw new Exception("*Login Id Not Found", -1);
        }

        $tracking_data['user_id'] = $user->id;

        $can_view_offline = UAR::can_view_offline_auction($user->id);
        if (is_null($prefix) && $can_view_offline) {
            $msg = "You cannot use JJPurchase System From Web. Kindly Login on IPAD. Contact Admin";
            $tracking_data['status_message'] = $msg;
            $tracking_data['error_type'] = 3;
            LoginTracking::create($tracking_data);
            throw new Exception($msg, -1);
        }

        if (!is_null($prefix) && $prefix == "api" && $user->mobile_device_binding == 1 && $user->fix_device_id != $device_id) {
            $msg = "Invalid Device - Contact Support";
            $tracking_data['status_message'] = $msg;
            $tracking_data['error_type'] = 3;
            LoginTracking::create($tracking_data);
            throw new Exception($msg, -1);
        }

        if (!Hash::check($password, $user->password)) {
            LoginTracking::create($tracking_data);
            //            throw new Exception("*Incorrect Password", -1);
            throw new Exception("*Incorrect Login Id/Password", -1);
        }

        $tracking_data['login_status'] = 1;
        if (!$user->status) {
            $tracking_data['account_status'] = 0;
            LoginTracking::create($tracking_data);
            throw new Exception("*Your Account is disabled. Contact Admin", -1);
        }

        if ($user->mobile_browser_allowed == 0) {
            if ($user_data['is_mobile']) {
                $msg = "You cannot use JJPurchase System From Mobile. Kindly Login on PC/Laptop. Contact Admin";
                $tracking_data['status_message'] = $msg;
                $tracking_data['error_type'] = 3;
                LoginTracking::create($tracking_data);
                throw new Exception("*$msg", -1);
            }
        }

        $tracking_data['account_status'] = 1;
        $tracking_data['is_latest'] = 1;
        $track_data = LoginTracking::create($tracking_data);
        //        kas_pr($track_data['id']);

        LoginTracking::where('user_id', $tracking_data['user_id'])
                ->where('id', '<>', $track_data['id'])
                ->where('source', $source)
                ->update(['is_latest' => 0]);

        // check users active device for mobile devices - 07-Dec-2018
        if (!is_null($prefix) && $prefix == 'api' && !is_null($device_os) && ($device_os == 'android' || $device_os == 'tablet')) {
            $userDevice = UsersRegisteredDevices::where('user_id', $user->id)
                            ->where('device_id', $device_id)
                            ->whereNull('deleted_at')
                            ->get()->first();
            if ($userDevice) {
                if ($userDevice->enable_device == 0) {
                    throw new Exception("*Your current device is disabled. Contact Admin", -1);
                }
            } else {
                $arr_dev_reg = [
                    'user_id' => $user->id,
                    'device_id' => $device_id,
                    'os' => $device_os,
                    'enable_device' => 0,
                    'created_at' => Date("Y-m-d H:i:s")
                ];
                UsersRegisteredDevices::create($arr_dev_reg);
                throw new Exception("*Your current device is disabled. Contact Admin", -1);
            }
        }
        // check users active device for mobile devices - 07-Dec-2018

        /*
          if ($user->force_password_update && strtotime("now") > strtotime($user->password_updated_at)) {
          throw new Exception("*Your Password is expired. <a href='" . url('fup') . "'>Click to rest </a> ", -1);
          } */
        //put data in session

        $user_data = [];
        $user_data['id'] = $user->id;
        $user_data['login_id'] = $user->login_id;
        $user_data['email'] = is_null($user->email) ? '' : $user->email;
        $user_data['image_url'] = $user->image_url;
        $user_data['full_name'] = $user->full_name;
        $user_data['designation'] = $user->designation;
        //        $user_data['force_password_update'] = $user->force_password_update;
        $user_data['password_updated_at'] = $user->password_updated_at;
        //        $user_data['password_duration'] = $user->password_duration;
        if (is_null($prefix)) {
            Session::put('jjp_user_id', $user->id);
            Session::put('jjp_logged_in', TRUE);
            Session::put('jjp_user_data', $user_data);
            $cookie_domain = env('SESSION_DOMAIN');
            $str_user_data = json_encode($user_data);
            $cookie = Cookie::make('jjpud', $str_user_data, 240, "/", $cookie_domain);

            return $cookie;
            //            $expire = time() + (60 * 60 * 8);
            //            $minutes = (60 * 8);
            //            $cookie_path = env('COOKIE_URL');
            //            $cookie_domain = env('SESSION_DOMAIN');
            //            $str_user_data = json_encode($user_data);
            //            setcookie("jjpud", $str_user_data, $expire, $cookie_path, $cookie_domain);
            //            Cookie::queue("jjpud", $str_user_data, $minutes);
        } else if ($prefix == "api") {
            $mapp_user_data = [
                'user_id' => $user->id,
                'device_id' => $device_id,
                'device' => '',
                'browser' => $browser,
                'ip' => $ip,
                'device_token' => $device_token
            ];
            //                $user->generateToken($mapp_user_data);
            $user_data['api_token'] = $user->generateToken($mapp_user_data);

            return $user_data;
        }
    }

    public static function get_user($id) {
        $user = User::find($id);
        if (!$user) {
            throw new Exception("*User Not Found", -1);
        }
        return $user;
    }

    public function update_user($user_data, $app_user_id = NULL) {
        $user_id = $user_data['user_id'];
        unset($user_data['user_id']);
        unset($user_data['app_user_id']);
        unset($user_data['api_token']);
        unset($user_data['device_id']);

        if (isset($user_data['login_id'])) {
            $new_login_id = $user_data['login_id'];

            $user_other = User::where('login_id', $user_data['login_id'])
                    ->where('id', '<>', $user_id)
                    ->get(['id']);
            if (count($user_other)) {
                throw new Exception("*Login Id Already Exist for Another User", -1);
            }
        }

        $user = User::find($user_id);
        $old_login_id = $user->login_id;

        foreach ($user_data as $name => $value) {
            $user->$name = $value;
        }
        $user->phone = $user->whatsapp_no;
        //        if (isset($user_data['force_password_update'])) {
        //            if ($user_data['force_password_update'] == 1) {
        //                $password_duration = $user_data['password_duration'];
        //                $user->password_updated_at = date('Y-m-d H:i:s', strtotime("+" . $password_duration . " days"));
        //            } else {
        //                $password_duration = 0;
        //            }
        //        }
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $user->last_edit_by = $app_user_id;
        $user->save();
        if ($old_login_id != $new_login_id && $user->is_extension_user) {
            $query_update = "UPDATE users_for_ext SET "
                    . " login_id = '$user_data[login_id]' "
                    . " WHERE user_id = $user_id";
            DB::update($query_update);
        }
    }

    public static function update_bulk_users($bulk_users_data, $app_user_id = null) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        //        display_admin_debug($bulk_users_data);
        extract($bulk_users_data);
        foreach ($bulk_users_data['full_names'] as $user_id => $full_name) {
            $user = User::find($user_id);
            if (!empty(trim($full_names[$user_id]))) {
                $user->full_name = $full_names[$user_id];
            }
            if (!empty(trim($emails[$user_id]))) {
                $user->email = $emails[$user_id];
            }
            if (!empty(trim($whatsapp_nos[$user_id]))) {
                $user->whatsapp_no = $whatsapp_nos[$user_id];
                $user->phone = $whatsapp_nos[$user_id];
            }
            if (!empty(trim($designations[$user_id]))) {
                $user->designation = $designations[$user_id];
            }
            if (!empty(trim($country_ids[$user_id])) && is_numeric($country_ids[$user_id]) && $country_ids[$user_id] > 0) {
                $user->country_id = $country_ids[$user_id];
            }
            $user->last_edit_by = $app_user_id;
            $user->save();
        }
    }

    public static function is_user_logged_in($app_user_id = NULL, $action = NULL) {

        if (is_null($app_user_id)) {
            //web
            //Check Global System Auth First
            if (!self::validateToken()) {
                return false;
            }

            if (isset($_COOKIE['jjpud']) && (!Session::has('jjp_user_id') || !Session::has('jjp_logged_in') || !Session::has('jjp_user_data'))) {
                $obj_user_data = json_decode($_COOKIE['jjpud']);
                Session::put('jjp_user_id', $obj_user_data->id);
                Session::put('jjp_logged_in', TRUE);
                $user_data = [];
                foreach ($obj_user_data as $field => $value) {
                    $user_data[$field] = $value;
                }
                Session::put('jjp_user_data', $user_data);
            }
            if (Session::has('jjp_user_id') && Session::has('jjp_logged_in')) {
                //            dd("TRUE");
                $app_user_id = self::get_user_id();
                $user = User::find($app_user_id);
                //            dd($user_status);
                if (!$user->status) {
                    self::logout([
                        'prefix' => null
                    ]);
                    return false;
                }
                if (!$user->is_web_user) {
                    self::logout([
                        'prefix' => null
                    ]);
                    return false;
                }
                return true;
            }
            return false;
        }
    }

    public static function global_verify_login($request, $source = null, $is_app = 0) {
        try {
            // Prepare user agent information
            $userAgent = getUserAgentInfo($request);

            // Make request to third-party authentication service
            $response = Http::post(env('GLOBAL_API_URL') . 'user/login', [
                'username' => $request->login_id,
                'password' => $request->password,
                'trusted_code' => $request->trusted_code ?? 0,
                'system_id' => env('SYSTEM_ID'),
                'ip' => $userAgent['ip_address'],
                'browser' => json_encode($userAgent),
                'device_id' => $request->device_id ?? '',
                'source' => $source ?? '',
                'is_app' => $is_app ?? 0
            ]);

            if ($response->failed()) {
                throw new Exception($response->json()['message'] ?? 'Invalid credentials', -1);
            }

            // Retrieve user data from response
            $data = $response->json();
            $userData = $data['user'] ?? null;
            $token = $data['token'] ?? null;

            if (!$token && !$userData['twoFA']) {
                throw new Exception('Login failed. Token not received.', -1);
            }

            JWT::$leeway = 300;
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            //            if ($userData['status'] == 0) {
            //                throw new Exception('Login failed. Account Disabled.', -1);
            //            }
            if ($userData['twoFA'] == 1) {
                $systemRights = $userData['system_rights'] ?? [];
            } else {

                // Decode JWT Token
                //                JWT::$leeway = 300;
                //                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
                $decodedArray = (array) $decoded;
                $systemRights = $decodedArray['system_rights'] ?? [];
            }
            $systemUserId = null;
            $masterUserId = null;
            $user_status = 0;
            foreach ((array) $systemRights as $systemRight) {
                if (is_object($systemRight)) {
                    $systemRight = (array) $systemRight; // Convert object to array
                }

                if ($systemRight['system_id'] == env('SYSTEM_ID')) {
                    $systemUserId = $systemRight['external_user_id'];
                    $masterUserId = $systemRight['user_id'];
                    $user_status = $systemRight['status'];
                    break;
                }
            }
            if ($user_status == 0) {
                throw new Exception('Account Disabled. Contact Support.', -1);
            }

            if (!$systemUserId) {
                throw new Exception('You do not have system access.', -1);
            }

            // Return user and token for further processing in verify_login
            //            JWT::$leeway = 300;
            //            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            $result_data = [
                'user' => $userData,
                'token' => $token,
                'token_expiry_time' => 0,
                'systemUserId' => $systemUserId,
                'masterUserId' => $masterUserId,
                'twoFA' => $userData['twoFA'],
            ];

            //            if ($request->login_id == "adil_admin") {
            //                kas_pr($decoded);
            //                die;
            //            }

            if (isset($decoded->exp)) {
                $result_data['token_expiry_timestamp'] = $decoded->exp;
                $result_data['token_expiry_time'] = timestamp_to_date_time($decoded->exp, true);
                //                if ($request->login_id == "adil_admin") {
                //                    kas_pr($decoded->exp);
                //                    kas_pr($result_data['token_expiry_time']);
                //                    die;
                //                }
            }

            return $result_data;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function validateToken($token = null): bool {
        if (is_null($token)) {
            $token = session('auth_token');
        }

        if (!$token) {
            return false;
        }

        try {
            JWT::$leeway = 300;
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            // Check if token is expired
            if (isset($decoded->exp) && Carbon::now()->timestamp >= $decoded->exp) {
                session()->forget('auth_token');
                session()->forget('masterUserId');

                self::logout(
                        [
                            'prefix' => null,
                        ]
                );

                return false;
            }

            return true;
        } catch (\Exception $e) {

            session()->forget('auth_token');
            session()->forget('masterUserId');
            self::logout();

            return false;
        }
    }

    public static function is_user_active($app_user_id = NULL) {
        if (is_null($app_user_id)) {
            if (Session::has('jjp_user_status')) {
                $status = Session::get('jjp_user_status');
            } else {
                $app_user_id = self::get_user_id();
                $user = User::find($app_user_id);
                $status = $user->status;
                Session::put('jjp_user_status', $status);
            }
        } else {
            $user = User::find($app_user_id);
            $status = $user->status;
        }
        return $status;
    }

    public static function get_full_name($app_user_id = NULL) {
        $full_name = "Guest";

        if (is_null($app_user_id)) {
            if (self::is_user_logged_in()) {
                $user_data = Session::get('jjp_user_data');
            } else {
                $user_data = [];
            }
        } else {
            $user_data = User::select('full_name')
                            ->where('id', $app_user_id)
                            ->get()->first();
        }
        if ($user_data) {
            $full_name = $user_data['full_name'];
        }
        return $full_name;
    }

    public function get_user_data($app_user_id = NULL) {

        $user_data['id'] = NULL;
        $user_data['login_id'] = NULL;
        $user_data['email'] = NULL;
        $user_data['image_url'] = "warehouse/uploads/users/no_image.jpg";
        $user_data['full_name'] = "Guest";
        $user_data['designation'] = "Guest";

        if (is_null($app_user_id)) {
            if (self::is_user_logged_in()) {
                $user_data = Session::get('jjp_user_data');
            }
        } else {
            $user_data = User::select('id', 'login_id', 'email', 'image_url', 'full_name', 'designation')
                            ->where('id', $app_user_id)
                            ->get()->first();
        }
        return $user_data;
    }

    public static function global_auth_logout($request_data) {
        /*         * ********** Send Logout Request to Global Auth ********** */
        if (is_null($request_data['prefix'])) {
            $token = session('auth_token');
            //            $global_auth_master_user_id = $user->global_auth_master_user_id;
        } else {
            $query = "SELECT us.id user_id, us.status, us.mobile_device_binding, us.fix_device_id, "
                    . " mapp_users.api_token, mapp_users.global_auth_token, mapp_users.global_auth_master_user_id "
                    . " FROM users us "
                    . " LEFT JOIN mapp_users ON us.id = mapp_users.user_id "
                    . " AND mapp_users.device_id = '$request_data[device_id]'"
                    . " AND mapp_users.api_token = '$request_data[api_token]' "
                    . " AND mapp_users.deleted_at IS NULL "
                    . " WHERE us.id = $request_data[app_user_id] ";
            $users = DB::select($query);
            $user = $users[0];
            $token = $user->global_auth_token;
            //            $global_auth_master_user_id = $user->global_auth_master_user_id;
        }

        $userAgent = getUserAgentInfo(request());
        //        $system_id = env('SYSTEM_ID');
        //         'user' => [
        //                'id' => $global_auth_master_user_id,
        //                'system_id' => $system_id
        //            ]

        $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $token,
                ])->post(
                env('GLOBAL_API_URL') . 'user/logout',
                [
                    'ip' => $userAgent['ip_address'],
                    'browser' => json_encode($userAgent),
                ]
        );

        if ($response->failed()) {
            //return response()->json( $response->json(), 401);
        }

        /*         * ********** Send Logout Request to Global Auth ********** */
    }

    public static function logout($request_data) {

        self::global_auth_logout($request_data);
        if (is_null($request_data['prefix'])) {
            Session::forget('jjp_user_id');
            Session::forget('jjp_logged_in');
            Session::forget('jjp_user_data');
            Session::forget('jjp_user_status');
            Session::forget('auth_token');
            Session::forget('masterUserId');
            Session::flush();
            $cookie_path = env('COOKIE_URL');
            $cookie_domain = env('SESSION_DOMAIN');
            setcookie("jjpud", "aaa", 1, $cookie_path, $cookie_domain);
        } else {
            $now = current_mysql_date_time();
            $query_update = "UPDATE mapp_users SET "
                    . " deleted_at = '$now' "
                    . " WHERE api_token = '$request_data[api_token]' "
                    . " AND device_id = '$request_data[device_id]' "
                    . " AND user_id = $request_data[app_user_id] ";
            DB::update($query_update);
        }
    }

    public static function logout_socket($request_data) {

        $now = current_mysql_date_time();
        $query_update = "UPDATE mapp_users SET "
                . " deleted_at = '$now' "
                . " WHERE global_auth_master_user_id = $request_data[masterUserId] "
                //                . " AND api_token = '$request_data[api_token]' "
                //                . " AND device_id = '$request_data[device_id]' "
                . " ";

        DB::update($query_update);
    }

    public function get_menus($parent_menu_id = 0, $app_user_id = NULL, $prefix = NULL, $is_menu_display = TRUE, $is_debug = FALSE) {

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $can_view_offline = UAR::can_view_offline_auction($app_user_id);
        if ($can_view_offline) {
            $menus = self::get_menus_buying_team($parent_menu_id, $app_user_id, $prefix, $is_menu_display);
        } else {
            $menus = self::get_menus_private($parent_menu_id, $app_user_id, $prefix, $is_menu_display);
        }

        /**
         * 3    -   branch_auctions_data	Branch Final Bidding List
         * 6    -   all_auctions_data	Admin Final Bidding List
         *
         */
        //        display_admin_debug($is_menu_display);
        //        if ($is_debug) {
        //            display_admin_debug($menus);
        //        }
        if ($is_menu_display) {
            foreach ($menus as $menu) {
                if ($menu->menu_type == "expand") {
                    foreach ($menu->child_menus as $index => $cmenu) {
                        if (in_array($cmenu->id, [3, 6])) {
                            $menus[] = $cmenu;
                            unset($menu->child_menus[$index]);
                        }
                    }

                    /**
                     * 1    -   Markets
                     * 5    -   Japan Office
                     *
                     */
                    if (in_array($menu->id, [1, 5])) {
                        $child_menus_temp = [];
                        foreach ($menu->child_menus as $index => $cmenu) {
                            $child_menus_temp[] = $cmenu;
                        }
                        $menu->child_menus = $child_menus_temp;
                    }
                }
            }
        }
        //        display_admin_debug($menus);
        return $menus;
    }

    private static function get_menus_private($parent_menu_id = 0, $app_user_id = NULL, $prefix = NULL, $is_menu_display = TRUE) {

        $query = "";
        if (is_null($prefix)) {
            $app_user_id = self::get_user_id();
            $query = "SELECT m.id, m.function_name, m.display_name, "
                    . " m.controller_name, m.uri, "
                    //                    . " CASE "
                    //                    . " WHEN m.id = 99 AND $app_user_id = 1 THEN 'countries_plus_live' "
                    //                    . " ELSE m.uri "
                    //                    . " END uri, "
                    . " m.parent_menu_id, m.sort_no, "
                    . " m.menu_type, m.menu_level, m.css_class ";
        } else {
            $query = "SELECT m.id, m.display_name, "
                    . " m.uri, m.app_uri, m.parent_menu_id, m.sort_no, m.css_class,  "
                    . " m.menu_type, ifnull(m.image_uri, '') image_uri ";
        }

        $query .= " FROM menus m ";

        if (!UAR::is_super_admin($app_user_id)) {
            $query .= " JOIN roles_menus_permissions rmp "
                    . " ON m.id = rmp.menu_id AND rmp.permission_id = 1 "
                    . " JOIN users_roles ur "
                    . " ON ur.role_id = rmp.role_id AND ur.user_id = $app_user_id "
                    . " and ur.deleted_at is null ";
        }

        $query .= " WHERE m.deleted_at IS NULL and m.menu_type <> 'child' "
                . " AND m.is_active AND m.parent_menu_id = $parent_menu_id ";
        if ($prefix == "api") {
            $query .= " and is_app_menu = 1 ";
        }
        $query .= " group by m.id "
                . " ORDER BY m.sort_no ASC";

        $menus = DBRO::select($query);
        //        display_admin_debug($query, $app_user_id, FALSE);
        foreach ($menus as $menu) {
            if ($menu->menu_type == "expand") {
                $menu->child_menus = self::get_menus_private($menu->id, $app_user_id, $prefix, $is_menu_display);
            }
        }
        return $menus;
    }

    private static function get_menus_buying_team($parent_menu_id = 0, $app_user_id = NULL, $prefix = NULL, $is_menu_display = TRUE) {

        $query = "SELECT m.id, m.display_name, "
                . " m.uri, m.app_uri, m.parent_menu_id, m.sort_no, m.css_class,  "
                . " m.menu_type, ifnull(m.image_uri, '') image_uri ";
        $query .= " FROM menus m ";

        $query .= " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 1 "
                . " JOIN users_roles ur "
                . " ON ur.role_id = rmp.role_id AND ur.user_id = $app_user_id "
                . " and ur.deleted_at is null ";

        $query .= " WHERE m.is_active AND m.is_buying_team = 1 "
                . " AND m.deleted_at IS NULL ";

        $query .= " AND is_app_menu = 1 ";

        $query .= " group by m.id "
                . " ORDER BY m.sort_no ASC";

        $menus = DBRO::select($query);

        return $menus;
    }

    public static function get_user_id() {

        return Auth::id() ?? 0;
    }

    public static function get_user_country_ids($app_user_id = NULL) {
        if (is_null($app_user_id)) {
            if (Session::has('jjp_user_country_ids')) {
                $country_ids = Session::get('jjp_user_country_ids');
                return $country_ids;
            }
            $app_user_id = self::get_user_id();
        }

        $query_check = "SELECT rc.country_id "
                . " FROM roles_countries rc "
                . " JOIN hr_level_detail hld "
                . " ON rc.country_id = hld.id AND hld.is_active = 1 "
                . " JOIN users_roles ur "
                . " ON rc.role_id = ur.role_id AND ur.user_id = $app_user_id "
                . " and ur.deleted_at is null "
                . " JOIN roles ON ur.role_id = roles.id "
                . " AND roles.deleted_at is NULL";

        $countries = DBRO::select($query_check);
        if (!$countries) {
            $query_countries = "SELECT hld.id country_id "
                    . " FROM hr_level_detail hld "
                    . " where hld.hr_level_id = 2 "
                    . " and hld.is_active = 1 "
                    . " and hld.deleted_at is null";
            $countries = DBRO::select($query_countries);
        }
        $country_ids = [];
        foreach ($countries as $c) {
            $country_ids[] = $c->country_id;
        }
        return $country_ids;
    }

    public static function get_user_country_ids_new($app_user_id = NULL) {
        if (is_null($app_user_id)) {
            if (Session::has('jjp_user_country_ids')) {
                $country_ids = Session::get('jjp_user_country_ids');
                return $country_ids;
            }
            $app_user_id = self::get_user_id();
        }

        if (UAR::is_super_admin($app_user_id)) {
            $query_countries = "SELECT hld.id country_id "
                    . " FROM hr_level_detail hld "
                    . " where hld.hr_level_id = 2 "
                    . " and hld.is_active = 1 "
                    . " and hld.deleted_at is null";
            $countries = DBRO::select($query_countries);
        } else {
            $query = "SELECT rc.country_id "
                    . " FROM roles_countries rc "
                    . " JOIN users_roles ur "
                    . " ON rc.role_id = ur.role_id AND ur.user_id = $app_user_id "
                    . " and ur.deleted_at is null ";

            $countries = DBRO::select($query);
        }
        $country_ids = [];
        if ($countries) {
            foreach ($countries as $c) {
                $country_ids[] = $c->country_id;
            }
        }
        return $country_ids;
    }

    public static function get_user_country_name_new($app_user_id = NULL) {
        if (is_null($app_user_id)) {
            if (Session::has('jjp_user_country_ids')) {
                $country_ids = Session::get('jjp_user_country_ids');
                return $country_ids;
            }
            $app_user_id = self::get_user_id();
        }

        if (UAR::is_super_admin($app_user_id)) {
            $query_countries = "SELECT hld.hr_name country_name "
                    . " FROM hr_level_detail hld "
                    . " where hld.hr_level_id = 2 "
                    . " and hld.is_active = 1 "
                    . " and hld.deleted_at is null";
            $countries = DBRO::select($query_countries);
        } else {
            $query = "SELECT hld.hr_name country_name "
                    . " FROM roles_countries rc "
                    . " JOIN users_roles ur "
                    . " ON rc.role_id = ur.role_id AND ur.user_id = $app_user_id "
                    . " JOIN hr_level_detail hld "
                    . " ON rc.country_id = hld.id AND hld.hr_level_id = 2 "
                    . " and ur.deleted_at is null ";

            $countries = DBRO::select($query);
        }
        $country_names = [];
        if ($countries) {
            foreach ($countries as $c) {
                $country_names[] = $c->country_name;
            }
        }
        return $country_names;
    }

    public static function get_all_users($current, $row_count, $search_options) {

        extract($search_options);
        //        display_admin_debug($search_options);
        $select = "SELECT users.id user_id, users.login_id, users.email, users.phone, "
                . " users.whatsapp_no, "
                . " users.full_name, "
                . " g_user.password, g_user.password lt_password, ufa.action password_updated_by, "
                . " DATE_FORMAT(users.password_updated_at, '%d-%m-%Y') password_updated_at, "
                //                . " lt.password lt_password, "
                . " users.deleted_at, "
                . " users.designation, users.country_id, hld.hr_name country_name, "
                . " IFNULL(GROUP_CONCAT(cr.country_name), 'All markets') market_access, "
                . " users.status, users.image_url image, users.is_web_user, users.is_app_user, "
                . " users.is_extension_user, users.is_japan_manager, users.is_bidder, "
                . " users.is_buying_team, "
                . " CASE "
                . "     WHEN jpn_office_roles.id IS NOT NULL THEN 'Japan Office' "
                . "     WHEN pk_user_roles.id IS NOT NULL THEN 'PK OFFICE' "
                . "     ELSE 'Branch User' "
                . " END user_type, "
                . " CASE "
                . "     WHEN ubct.total_bids_count >= 50 THEN 'Active Bidder' "
                . "     ELSE 'Non Bidder' "
                . " END bidder_status, "
                . " @total_bids_count:= IFNULL(ubct.total_bids_count, 0) total_bids_count, "
                . " users.show_to_all, "
                . " users.force_password_update , users.password_duration, "
                . " DATE_FORMAT(users.password_updated_at, '%d-%m-%Y') password_updated_at, "
                . " IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) days_logged_in_web, "
                . " IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) days_logged_in_app, "
                . " IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) days_logged_in_ext, "
                . " DATE_FORMAT(lt_web.created_at, '%d-%m-%Y %r') last_login_web, "
                . " DATE_FORMAT(lt_app.created_at, '%d-%m-%Y %r') last_login_app, "
                . " DATE_FORMAT(users.last_bid_at, '%d-%m-%Y %r') last_login_ext ";
        //        }

        $query_users_bids_counts_total = "WITH users_bids_counts_total AS ("
                . " SELECT ubc.user_id, SUM(ubc.bids_count) total_bids_count FROM users_bids_counts ubc "
                . " WHERE ubc.deleted_at IS NULL ";

        $query_last_date = "SELECT MAX(ubc.auction_date) auction_date "
                . " FROM users_bids_counts ubc LIMIT 1 ";
        $r_last_date = DB::select($query_last_date);
        $end_date = $r_last_date[0]->auction_date;
        $results['last_bid_date'] = mysql_to_php_date($end_date);
        $condition_bid_count = "";
        $query_users_bids_counts_total .= " AND ubc.auction_date <= '$end_date' ";
        if (isset($bidder_status) && is_numeric($bidder_status) && $bidder_status >= 0) {
            if ($bidder_status == 0) {
                $condition_bid_count .= " AND (ubct.total_bids_count < 50 OR ubct.total_bids_count IS NULL) ";
            } else {
                $condition_bid_count .= " AND ubct.total_bids_count >= 50 ";
            }
        }
        if (isset($bidding_period) && is_numeric($bidding_period) && $bidding_period >= 0) {
            $date = new DateTime($end_date);

            switch ($bidding_period) {

                case 1: //30 days
                    $date->sub(new DateInterval('P30D'));
                    break;

                case 2: //60 days
                    $date->sub(new DateInterval('P60D'));
                    break;

                case 3: //90 days
                    $date->sub(new DateInterval('P90D'));
                    break;

                case 4: //120 days
                    $date->sub(new DateInterval('P120D'));
                    break;

                case 5: //150 days
                    $date->sub(new DateInterval('P150D'));
                    break;

                case 6: //180 days
                    $date->sub(new DateInterval('P180D'));
                    break;
            }

            $start_date = $date->format('Y-m-d');
            $query_users_bids_counts_total .= " AND ubc.auction_date >='$start_date' ";
        }

        $query_users_bids_counts_total .= " GROUP BY ubc.user_id), ";

        $query_country_roles = " country_roles AS ("
                . " SELECT rm.id role_id, ur.user_id, rm.role_name, rm.role_display_name, rm.country_restrict, "
                . " roc.country_id, hld.hr_name country_name "
                . " FROM purchase_prod.roles rm "
                . " JOIN purchase_prod.users_roles ur ON rm.id = ur.role_id AND ur.deleted_at IS NULL "
                . " JOIN purchase_prod.roles_countries roc ON rm.id = roc.role_id "
                . " JOIN purchase_prod.hr_level_detail hld ON roc.country_id = hld.id "
                . " WHERE rm.country_restrict = 1 AND rm.deleted_at is NULL)";

        $from = " FROM users "
                . " LEFT JOIN global_user_management.system_user_mapping sum "
                . " ON users.id = sum.external_user_id AND sum.system_id =  " . env('SYSTEM_ID')
                . " LEFT JOIN global_user_management.users g_user "
                . " ON sum.user_id = g_user.user_id"
                . " LEFT JOIN hr_level_detail hld ON users.country_id = hld.id "
                . " LEFT JOIN login_tracking lt_web "
                . " ON users.id = lt_web.user_id AND lt_web.login_status = 1 "
                . " AND lt_web.is_latest = 1 AND lt_web.source = 'web' "
                . " LEFT JOIN login_tracking lt_app "
                . " ON users.id = lt_app.user_id AND lt_app.login_status = 1 "
                . " AND lt_app.is_latest = 1 AND lt_app.source = 'app' "
                . " LEFT JOIN users_for_admin ufa "
                . " ON users.id = ufa.user_id and  ufa.active = 1 "
                . " LEFT JOIN users_bids_counts_total ubct "
                . " ON users.id = ubct.user_id "
                . " LEFT JOIN country_roles cr ON users.id = cr.user_id "
                . " LEFT JOIN purchase_prod.users_roles jpn_user_roles ON users.id = jpn_user_roles.user_id "
                . " AND jpn_user_roles.role_id = 25 AND jpn_user_roles.deleted_at IS NULL "
                . " LEFT join purchase_prod.roles jpn_office_roles "
                . " ON jpn_office_roles.id = jpn_user_roles.role_id AND jpn_office_roles.deleted_at is NULL "
                . " LEFT JOIN purchase_prod.users_roles pk_user_roles ON users.id = pk_user_roles.user_id "
                . " AND pk_user_roles.role_id = 167 AND pk_user_roles.deleted_at IS NULL ";
        $where = " WHERE users.deleted_at IS NULL"
                . " $condition_bid_count ";

        if (isset($status)) {
            if ($status == 1 || $status == 0) {
                $where .= " AND users.status = $status";
            }
        }

        if (isset($is_web_user)) {
            if ($is_web_user == 1 || $is_web_user == 0) {
                $where .= " AND users.is_web_user = $is_web_user";
            }
        }

        if (isset($is_app_user)) {
            if ($is_app_user == 1 || $is_app_user == 0) {
                $where .= " AND users.is_app_user = $is_app_user";
            }
        }

        if (isset($is_extension_user)) {
            if ($is_extension_user == 1 || $is_extension_user == 0) {
                $where .= " AND users.is_extension_user = $is_extension_user";
            }
        }

        if (isset($is_japan_manager)) {
            if ($is_japan_manager == 1 || $is_japan_manager == 0) {
                $where .= " AND users.is_japan_manager = $is_japan_manager";
            }
        }

        if (isset($is_buying_team)) {
            if ($is_buying_team == 1 || $is_buying_team == 0) {
                $where .= " AND users.is_buying_team = $is_buying_team";
            }
        }

        if (isset($country_id) && !empty($country_id)) {
            $where .= " AND users.country_id = $country_id";
        }

        if (isset($user_type) && is_numeric($user_type) && $user_type >= 1) {
            switch ($user_type) {
                case 1: //Japan Users
                    $where .= " AND jpn_user_roles.id IS NOT NULL";
                    break;

                case 2: //Branch Users
                    $where .= " AND pk_user_roles.id IS NULL "
                            . " AND jpn_user_roles.id IS NULL";
                    break;

                case 3: //PK Office
                    $where .= " AND pk_user_roles.id IS NOT NULL";
                    break;

                default:
                    break;
            }
        }


        if (isset($days) && is_numeric($days)) {
            if ($days == -1) {
                $where .= " AND lt_web.id IS NULL AND lt_app.id IS NULL "
                        . " AND users.last_bid_at IS NULL ";
            } else {
                if (isset($not_logged_in_system)) {
                    switch ($not_logged_in_system) {
                        case 0: //ALL
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days "
                                    . " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days "
                                    . " AND IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;

                        case 1: //WEB
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days ";
                            break;

                        case 2: //App
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days ";
                            break;

                        case 3: //Extension
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;
                    }
                }
            }
        }

        if (isset($user_id) && !empty($user_id)) {
            $where .= " and users.id = $user_id";
        }
        $table_prefixes = [];
        $table_prefixes['full_name'] = "users.full_name";
        $table_prefixes['login_id'] = "users.login_id";
        $table_prefixes['country_name'] = "hld.hr_name";
        $table_prefixes['user_id'] = "users.id";
        $table_prefixes['designation'] = "users.designation";
        $table_prefixes['email'] = "users.email";
        $table_prefixes['status'] = "users.status";
        $table_prefixes['is_web_user'] = "users.is_web_user";
        $table_prefixes['is_app_user'] = "users.is_app_user";
        $table_prefixes['is_extension_user'] = "users.is_extension_user";

        if (isset($where_or_like)) {
            $where .= AppRepo::make_where_or_like_conditon($table_prefixes, $where_or_like);
        }

        $order_by = "";
        if (isset($sort) && !is_null($sort) && !empty($sort)) {
            $keys = array_keys($sort);
            $key = $keys[0];
            $col = $table_prefixes[$key];
            $order_by .= " ORDER BY $col $sort[$key]";
        } else {
            $order_by .= " ORDER BY users.full_name ASC ";
        }

        $limit = "";

        $start = 0;
        if ($current > 0 && $row_count > 0) {
            //echo("Current - $current<br>Row COunt - $rowCount");
            $start = (intval($current) - 1) * $row_count;
            $limit .= " limit $start, $row_count";
        }
        $query = $select . $from . $where . " GROUP BY users.id " . $order_by . $limit;
        $query_count = "SELECT COUNT(*) count FROM(SELECT users.id " . $from . $where
                . " GROUP BY users.id) AS users_count";

        //        display_admin_debug("$query_users_bids_counts_total;$query_country_roles;$query;$query_count;");
        //        display_admin_debug($query);
        //echo $query;exit;
        $final_query = $query_users_bids_counts_total . $query_country_roles . $query;
        $final_query_count = $query_users_bids_counts_total . $query_country_roles . $query_count;
        //                display_admin_debug($final_query);
        $users = DB::select($final_query);

        if (!$users) {
            throw new Exception("*No Data Found", -1);
        }

        $total_count = DBRO::select($final_query_count);
        $results['data'] = $users;
        $results['total_count'] = $total_count[0]->count;
        return $results;
    }

    public static function get_original_password($user_id) {

        $user = DB::table('users_for_admin')
                        ->select('password')
                        ->where('user_id', '=', $user_id)
                        ->where('active', '=', 1)
                        ->get()->first();

        return $user->password;
    }

    public static function change_user_status($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $user = User::withTrashed()->find($user_id);
        if (isset($status) && $status == 1) {
            $user->status = 0;
            $user->is_web_user = 0;
            $user->is_extension_user = 0;
            $user->is_app_user = 0;
            //            $user->is_japan_manager = 0;
            //            $user->is_buying_team = 0;
            $password = "blocked";
        } else { // if ($status == 0) {
            $user->status = 1;
            //            $user->force_password_update = 0;
            //            $user->password_duration = 0;
            $password = self::get_original_password($user_id);
        }

        $user->stats_block_remarks = isset($stats_block_remarks) ? $stats_block_remarks : null;
        $user->last_edit_by = $app_user_id;
        $user->save();

        if ($user->status == 0) {
            self::users_for_extension($user_id, $password, $app_user_id);
        }
        //        DB::table('users_for_ext')
        //                ->where('user_id', $user_id)
        //                ->update(['password' => $password]);
    }

    public static function change_is_bidder($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $user = User::find($user_id);
        if (isset($is_bidder) && $is_bidder == 1) {
            $user->is_bidder = 0;
        } else {
            $user->is_bidder = 1;
        }
        $user->last_edit_by = $app_user_id;
        $user->save();
    }

    public static function change_show_to_all($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $user = User::find($user_id);
        if (isset($show_to_all) && $show_to_all == 1) {
            $user->show_to_all = 0;
        } else {
            $user->show_to_all = 1;
        }
        $user->last_edit_by = $app_user_id;
        $user->save();
    }

    public static function change_user_web_status($user_data, $app_user_id = NULL) {
        extract($user_data);
        $user = User::find($user_id);
        if ($user->status && $user->is_app_user == 0 && $user->is_extension_user == 0 && $web_user == 1) {
            throw new Exception("*You cannot Set All Aceess (Web/Extension/App) Off", -1);
        }
        if ($web_user == 1) {
            $user->is_web_user = 0;
        } else if ($web_user == 0) {
            if (!$user->status) {
                throw new Exception("*Enable User First", -1);
            }
            $user->is_web_user = 1;
        }
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $user->last_edit_by = $app_user_id;
        $user->save();

        $user2 = User::find($user_id);
        if ($user2->is_web_user == 0) {
            //            self::logout([
            //                'prefix' => null
            //            ]);
        }
    }

    public static function change_user_app_status($user_data, $app_user_id = NULL) {
        extract($user_data);
        $user = User::find($user_id);
        if ($user->status && $user->is_web_user == 0 && $user->is_extension_user == 0 && $app_user == 0) {
            throw new Exception("*You cannot Set All Aceess (Web/Extension/App) Off", -1);
        }

        if ($app_user == 1) {
            $user->is_app_user = 0;
        } else if ($app_user == 0) {
            if (!$user->status) {
                throw new Exception("*Enable User First", -1);
            }
            $user->is_app_user = 1;
        }
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $user->last_edit_by = $app_user_id;
        $user->save();
    }

    public static function change_user_extension_status($user_data, $app_user_id = NULL) {
        extract($user_data);
        $user = User::find($user_id);

        if ($user->status && $user->is_web_user == 0 && $user->is_app_user == 0 && $extension_user == 0) {
            throw new Exception("*You cannot Set All Aceess (Web/Extension/App) Off", -1);
        }

        if ($extension_user == 1) {
            $user->is_extension_user = 0;
        } else if ($extension_user == 0) {
            if (!$user->status) {
                throw new Exception("*Enable User First", -1);
            }
            $user->is_extension_user = 1;
        }
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $user->last_edit_by = $app_user_id;
        $user->save();

        $password = self::get_original_password($user_id);
        self::users_for_extension($user_id, $password, $app_user_id);
    }

    public static function change_japan_manager_status($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $user = User::find($user_id);
        if (isset($is_japan_manager) && $is_japan_manager == 1) {
            $user->is_japan_manager = 0;
        } else { // if ($status == 0) {
            $user->is_japan_manager = 1;
        }
        $user->last_edit_by = $app_user_id;
        $user->save();
        return $user->is_japan_manager;
    }

    public static function change_buying_team_status($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $user = User::find($user_id);
        if (isset($is_buying_team) && $is_buying_team == 1) {
            $user->is_buying_team = 0;
        } else { // if ($status == 0) {
            $user->is_buying_team = 1;
        }
        $user->last_edit_by = $app_user_id;
        $user->save();
        return $user->is_buying_team;
    }

    public function update_password_by_admin($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $now = current_mysql_date_time();

        DB::beginTransaction();
        try {
            $enc_password = self::encrypt($password);
            $query_update_password = "UPDATE global_user_management.users g_user "
                    . " JOIN global_user_management.system_user_mapping sum "
                    . " ON sum.user_id = g_user.user_id AND sum.system_id = " . env('SYSTEM_ID') . " SET "
                    . " g_user.password = '$enc_password' "
                    . " WHERE sum.external_user_id = $user_id";
            DB::update($query_update_password);

            $user = User::find($user_id);
            $user->password = $enc_password;
            $user->last_edit_by = $app_user_id;
            $user->password_updated_at = $now;
            $user->save();
            self::users_for_admin($user_id, $password, "password_changed_by_admin");
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            AdminRepo::create_error_log("UserRepository", __FUNCTION__, $ex, $app_user_id);
            throw $ex;
        }
    }

    public function update_password_by_user($user_data, $app_user_id = NULL) {
        extract($user_data);
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $user = User::find($app_user_id);

        if (Hash::check($password, $user->password)) {
            throw new Exception("*you can not set same password again.", -1);
        }

        $user->password = Hash::make($password);
        //        if ($user->force_password_update) {
        //            if ($user->password_duration == -1) {
        //                $user->force_password_update = 0;
        //                $user->password_duration = 0;
        //                $user->password_updated_at = NULL;
        //            } else {
        //                $user->password_updated_at = date('Y-m-d H:i:s', strtotime("+" . $user->password_duration . " days"));
        //            }
        //        }
        $user->last_edit_by = $app_user_id;
        $now = current_mysql_date_time();
        $user->password_updated_at = $now;
        $user->save();
        self::users_for_admin($app_user_id, $password, "password_changed_by_user");

        // update current session
        $user_data = Session::get('jjp_user_data');
        $user_data['password_updated_at'] = $user->password_updated_at;
        //        $user_data['password_duration'] = $user->password_duration;
        //        $user_data['force_password_update'] = $user->force_password_update;
        Session::put('jjp_user_data', $user_data);
        return true;
    }

    public static function users_for_admin($user_id, $password, $action, $app_user_id = NULL) {
        $now = current_mysql_date_time();
        $user = User::withTrashed()->find($user_id);
        if (is_null($app_user_id)) {
            $added_by = self::get_user_id();
        } else {
            $added_by = $app_user_id;
        }

        $query_update = "update users_for_admin set "
                . " active = 0"
                . " where user_id = $user_id ";
        $users_for_admin_update = DB::update($query_update);

        $query_insert = "insert into users_for_admin "
                . " (id, user_id, login_id, password, action, added_by, created_at, active) "
                . " values "
                . " (NULL, $user_id, '$user->login_id', '$password', "
                . " '$action', $added_by, '$now', 1) ";
        $users_for_admin = DB::insert($query_insert);

        self::users_for_extension($user_id, $password, $app_user_id);
    }

    public static function users_for_extension($user_id, $password, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $added_by = $app_user_id;
        $user = User::find($user_id);
        $now = date("Y-m-d H:i:s");
        $query_select = "select user_id from users_for_ext where user_id = $user_id";
        $ext_user = DBRO::select($query_select);
        if ($ext_user) {
            if ($password == "blocked" || !$user->is_extension_user) {
                DB::table('users_for_ext')->where('user_id', $user_id)->delete();
            } else {
                $query_update = "update users_for_ext set "
                        . " password = '$password', "
                        . " action = '', "
                        . " last_edit_by = $added_by, "
                        . " updated_at = '$now' "
                        . " where user_id = $user_id";
                DB::update($query_update);
            }
        } else {
            if ($user->is_extension_user) {
                $query_insert = "insert into users_for_ext "
                        . " (id, user_id, login_id, password, action, added_by, created_at) "
                        . " values "
                        . " (NULL, $user_id, '$user->login_id', '$password', "
                        . " '', $added_by, '$now') ";
                $users_for_ext = DB::insert($query_insert);
            }
        }
    }

    public static function get_dubai_users() {
        $users = User::where('country_id', 17)->get();
        return $users;
    }

    public static function get_branch_users($app_user_id = NULL) {

        if (UAR::is_super_admin($app_user_id)) {
            $users = User::select('id', 'login_id', 'email', 'full_name', 'designation', 'country_id', 'status', 'image_url')
                            ->where('status', 1)
                            ->orderBy('login_id')->get();
        } else {
            if (is_null($app_user_id)) {
                $app_user_id = self::get_user_id();
            }
            $user = User::where('id', $app_user_id)->get()->first();
            /*
              $query_countries = "select country_id from users_branches where user_id = $app_user_id";
              $countries = DBRO::select($query_countries);
              $country_ids = [];
              if (count($countries)) {
              foreach ($countries as $c) {
              $country_ids[] = $c->country_id;
              }
              } else {
              $query_users = "select user_id from users_branches where country_id = $user->country_id";
              $branch_users = DBRO::select($query_users);
              if (count($branch_users)) {

              }
              }

              $country_ids[] = $user->country_id;
              $users = User::select('id', 'login_id', 'email', 'full_name', 'designation', 'country_id', 'status', 'image_url')
              ->whereIn('country_id', $country_ids)
              ->orderBy('login_id')->get();
             */
//            $users = User::select('id', 'login_id', 'email', 'full_name', 'designation', 'country_id', 'status', 'image_url')
//                            ->where('country_id', $user->country_id)
//                            ->orderBy('login_id')->get();
            /**
             *
             * 84 - Adnan Malik
             * 60 - Data Entry
             * 49 - Developer
             * 2 - Kashif MD
             * 68 - Khurram
             * 23 - Yahya Khan
             * 69 - Zahid Ejaz
             */
            $query = "SELECT u.id, u.login_id, u.email, u.full_name, "
                    . " u.designation, u.country_id, u.status, u.image_url "
                    . " FROM users_branches ub "
                    . " JOIN users_branches ub1 "
                    . " on ub.country_id = ub1.country_id AND ub1.user_id = $app_user_id "
                    . " AND ub1.deleted_at is NULL AND ub.deleted_at is NULL "
                    . " join users u "
                    . " on u.id = ub.user_id AND u.no_show_in_added_by = 0 "
                    . " AND u.status = 1 "
                    . " AND u.deleted_at is NULL "
                    . " WHERE u.id NOT IN (84, 60, 49, 2, 68, 23, 69,356) "
                    . " GROUP BY ub.user_id "
                    . " order by u.login_id asc ";
            //display_admin_debug($query);
            //echo $query;exit;
            $users = DBRO::select($query);
        }
        return $users;
    }

    public static function get_japan_user_ids() {
        $query = "SELECT u.id FROM  users u "
                . " JOIN users_roles ur ON u.id = ur.user_id AND ur.role_id = 25 "
                . " WHERE u.deleted_at IS NULL";
        $data = DBRO::select($query);
        //        kas_pr($users_ids);
        $user_ids = [];
        foreach ($data as $d) {
            $user_ids[] = $d->id;
        }
        //        kas_pr($user_ids);

        return $user_ids;
    }

    public static function get_all_users_roles($current, $row_count, $search_options) {
        extract($search_options);
        $query = "SELECT u.id, u.login_id, r.id, r.role_name, r.role_display_name, "
                . " r.country_restrict, rc.country_id country_role_id, "
                . " role_country.hr_name country_role_name, u.email, u.full_name, "
                . " u.designation, u.country_id, user_country.hr_name user_country, "
                . " u.status, u.image_url ";
        $query_count = "select count(*) count ";
        $from = " FROM users u "
                . " JOIN users_roles ur ON u.id = ur.user_id AND ur.deleted_at is NULL "
                . " join roles r ON r.id = ur.role_id AND r.deleted_at is NULL "
                . " LEFT JOIN roles_countries rc ON rc.role_id = ur.role_id "
                . " LEFT join hr_level_detail user_country "
                . " ON u.country_id = user_country.id "
                . " AND user_country.deleted_at is NULL "
                . " LEFT join hr_level_detail role_country "
                . " ON rc.country_id = role_country.id "
                . " AND role_country.deleted_at is NULL "
                . " WHERE u.deleted_at is NULL";
        if (isset($condition)) {
            $from .= $condition;
        }

        $query .= $from;
        $query_count .= $from;

        $table_prefixes = [];
        $table_prefixes['role_display_name'] = "r.role_display_name";

        if (isset($sort) && !is_null($sort) && !empty($sort)) {
            $keys = array_keys($sort);
            $key = $keys[0];
            $col = $table_prefixes[$key];
            $query .= " order by $col $sort[$key]";
        } else {
            $query .= " order by r.id asc ";
        }

        $start = 0;
        if ($current > 0 && $row_count > 0) {
            //echo("Current - $current<br>Row COunt - $rowCount");
            $start = (intval($current) - 1) * $row_count;
            $query .= " limit $start, $row_count";
        }
        //        display_admin_debug($query);
        $roles = DBRO::select($query);

        if (!$roles) {
            throw new Exception("*No Data Found", -1);
        }

        $total_count = DBRO::select($query_count);
        $results['data'] = $roles;
        $results['total_count'] = $total_count[0]->count;
        return $results;
    }

    public function delete_user_role($user_role_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
            ;
        }
        extract($role_data);
        $role = Role::find($role_id);
        if ($role) {
            $role->deleted_by = $app_user_id;
            $role->save();
            $role->delete();
        }
    }

    public function update_user_role($user_role_data) {
        die;
        $role_id = $role_data['role_id'];
        unset($role_data['role_id']);
        $role = Role::find($role_id);
        foreach ($role_data as $name => $value) {
            $role->$name = $value;
        }
        $role->role_name = str_replace(" ", "_", strtolower($role_data['role_display_name']));
        $role->last_edit_by = self::get_user_id();
        $role->save();
    }

    public function get_countries_permissions() {

    }

    public static function verify_fup($user_data) {
        extract($user_data);

        if ($newpassword != $cpassword) {
            throw new Exception("*New password and Confirm password do not match.", -1);
        }

        if (strlen($newpassword) < 6) {
            throw new Exception("*New Password is too short.", -1);
        }
        /*
          if (!preg_match("#[0-9]+#", $newpassword)) {
          throw new Exception("*Password must include at least one number.", -1);
          }

          if (!preg_match("#[a-zA-Z]+#", $newpassword)) {
          throw new Exception("*Password must include at least one letter.", -1);
          }
         */

        $user = User::where('login_id', $login_id)->get()->first();
        if (!$user) {
            throw new Exception("*Login Id Not Found", -1);
        }

        if (!Hash::check($password, $user->password)) {
            throw new Exception("*Incorrect Password", -1);
        }

        if ($newpassword == $password) {
            throw new Exception("*you can not set same password again.", -1);
        }

        if (!$user->status) {
            throw new Exception("*Your Account is disabled. Contact Admin", -1);
        }

        //        $user->password_updated_at = date('Y-m-d H:i:s', strtotime("+" . $user->password_duration . " days"));

        $user->password = Hash::make($newpassword);
        $user->save();
    }

    public static function force_update_password_by_admin($app_user_id = NULL, $search = []) {

        $search['status'] = 1;
        extract($search);
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }


        $users = self::get_all_users(0, 0, $search);
        $users = $users['data'];

        $list = array();
        foreach ($users as $user) {

            $password = str_random(6);
            /* $user->password = Hash::make($password);
              $user->last_edit_by = $app_user_id;
              $user->password_updated_at = date('Y-m-d H:i:s', strtotime("+" . $user->password_duration . " days"));
              $user->save(); */
            $dbdata['password'] = Hash::make($password);
            $dbdata['last_edit_by'] = $app_user_id;
            $dbdata['password_updated_at'] = date('Y-m-d H:i:s', strtotime("+" . $user->password_duration . " days"));
            User::where('id', $user->user_id)->update($dbdata);
            $list[] = $user->login_id . ',' . $user->full_name . ',' . $password;

            if (!empty($user->email)) {
                $email_data = [
                    'user' => $user->full_name,
                    'content' => 'Your password has been updated.you new password is ' . $password . '.'
                ];
                $result = AppRepo::send_mail(
                        $user->email,
                        'Password Updated',
                        'common.common_password_email',
                        $email_data
                );
            }

            /* if (!empty($user->phone)) {
              $message = "Hello " . $user->full_name . ' ';
              $message .= 'Your password has been updated.you new password is ' . $password . '.';
              AppRepo::send_message($user->phone, $message);
              } */
        }

        /** start creating password file for admin * */
        $download_folder = 'warehouse/downloads/passwords/';
        $template_dir = $_SERVER['DOCUMENT_ROOT'] . env('BASE_FOLDER') . $download_folder;
        //$template_dir = $_SERVER['DOCUMENT_ROOT'] . "/" . 'jj/JJPurchase/' . $download_folder;
        $file_name = 'passwords.csv';
        $file = fopen($template_dir . $file_name, "w");
        foreach ($list as $line) {
            fputcsv($file, explode(',', $line));
        }
        fclose($file);
        $download_link = url($download_folder . $file_name);

        /** End creating password file for admin * */
        $admin_email = "usman.janjapan@gmail.com";
        $email_data = [
            'title' => 'Password Update',
            'user' => 'Admin',
            'content' => 'Password updated for all users.'
        ];
        $result = AppRepo::send_mail($admin_email, 'User Password Updated', 'common.common_password_email', $email_data, $download_link);

        // clear file after sending mail
        $file = fopen($template_dir . $file_name, "w");
        fputcsv($file, []);
        fclose($file);
    }

    public static function is_force_password_updated() {

        if (Session::has('jjp_user_data') && Session::has('jjp_logged_in')) {
            $user_data = Session::get('jjp_user_data');
            if (isset($user_data['password_duration']) && $user_data['password_duration'] == -1) {
                return true;
            } else {
                if ($user_data['force_password_update'] && strtotime("now") > strtotime($user_data['password_updated_at'])) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function check_user_app_version($user_data) {

        extract($user_data);

        $user = MappUser::where('user_id', $app_user_id)
                        ->where('device_id', $device_id)
                        ->where('device', $device)
                        ->get(['app_version'])->first();

        return $user['app_version'];
    }

    public static function update_user_app_version($user_data) {

        extract($user_data);

        MappUser::where('user_id', $app_user_id)
                ->where('device_id', $device_id)
                ->where('device', $device)
                ->update([
                    'app_version' => $app_version
        ]);
    }

    public static function is_web_user($login_id = NULL, $app_user_id = NULL) {
        if (!is_null($app_user_id)) {
            $user = User::where('id', $app_user_id)
                    ->where('is_web_user', 1)
                    ->get();
        } else if (!is_null($login_id)) {
            $user = User::where('login_id', $login_id)
                    ->where('is_web_user', 1)
                    ->get();
        } else {
            return false;
        }
        if (!count($user)) {
            return false;
        }
        return true;
    }

    public static function is_app_user($login_id = NULL, $app_user_id = NULL) {
        if (!is_null($app_user_id)) {
            $user = User::where('id', $app_user_id)
                    ->where('is_app_user', 1)
                    ->get();
        } else if (!is_null($login_id)) {
            $user = User::where('login_id', $login_id)
                    ->where('is_app_user', 1)
                    ->get();
        } else {
            return false;
        }
        //        display_admin_debug($user, 1);
        if (!count($user)) {
            return false;
        }
        return true;
    }

    public static function is_extension_user($login_id = NULL, $app_user_id = NULL) {
        if (!is_null($app_user_id)) {
            $user = User::where('id', $app_user_id)
                    ->where('is_extension_user', 1)
                    ->get();
        } else if (!is_null($login_id)) {
            $user = User::where('login_id', $login_id)
                    ->where('is_extension_user', 1)
                    ->get();
        } else {
            return false;
        }
        //        display_admin_debug($user, 1);
        if (!count($user)) {
            return false;
        }
        return true;
    }

    public static function get_user_branches($user_id, $app_user_id = NULL) {

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $base_url = env('APP_URL');
        $query = "SELECT ub.user_id, ub.country_id, hld.hr_name country_name,"
                . " concat('$base_url', 'images/flags/flag_', ub.country_id, '.jpg') flag_url "
                . " from users_branches ub "
                . " join hr_level_detail hld "
                . " ON hld.id = ub.country_id AND hld.deleted_at is NULL "
                . " WHERE ub.user_id = $user_id AND ub.deleted_at IS NULL "
                . " order by country_id";
        $branches = DBRO::select($query);
        $branch_ids = [];
        foreach ($branches as $b) {
            $branch_ids[] = $b->country_id;
        }
        $results = [];
        $results['branches'] = $branches;
        $results['branch_ids'] = $branch_ids;
        return $results;
    }

    public static function update_user_branches($user_data, $app_user_id = NULL) {

        $user_id = $user_data['user_id'];

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }

        DB::table('users_branches')
                ->where('user_id', $user_id)
                ->delete();

        $country_rights = [];
        if (count($user_data['branch_ids'])) {
            $now = date('Y-m-d H:i:s');
            foreach ($user_data['branch_ids'] as $bid) {
                $country_rights[] = [
                    'user_id' => $user_id,
                    'created_at' => $now,
                    'added_by' => $app_user_id,
                    'country_id' => $bid
                ];
            }
            DB::table('users_branches')->insert($country_rights);
        }
    }

    public static function get_user_countries($user_id, $app_user_id = NULL) {

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $base_url = env('APP_URL');
        $query = "SELECT uc.user_id, uc.country_id, hld.hr_name country_name,"
                . " concat('$base_url', 'images/flags/flag_', uc.country_id, '.jpg') flag_url "
                . " from users_countries uc "
                . " join hr_level_detail hld "
                . " ON hld.id = uc.country_id AND hld.deleted_at is NULL "
                . " WHERE uc.user_id = $user_id AND uc.deleted_at IS NULL "
                . " order by country_id";
        $countries = DBRO::select($query);
        $country_ids = [];
        foreach ($countries as $c) {
            $country_ids[] = $c->country_id;
        }
        $results = [];
        $results['countries'] = $countries;
        $results['country_ids'] = $country_ids;
        return $results;
    }

    public static function update_user_countries($user_data, $app_user_id = NULL) {

        $user_id = $user_data['user_id'];

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }

        DB::table('users_countries')
                ->where('user_id', $user_id)
                ->delete();

        $country_rights = [];
        if (count($user_data['country_ids'])) {
            $now = date('Y-m-d H:i:s');
            foreach ($user_data['country_ids'] as $cid) {
                $country_rights[] = [
                    'user_id' => $user_id,
                    'created_at' => $now,
                    'added_by' => $app_user_id,
                    'country_id' => $cid
                ];
            }
            DB::table('users_countries')->insert($country_rights);
        }
    }

    public static function update_user_device_token($user_data) {
        extract($user_data);

        MappUser::where('user_id', $app_user_id)
                ->where('device_id', $device_id)
                ->where('device', $device)
                ->update([
                    'device_token' => $device_token,
                    'os' => $os
        ]);
    }

    public static function get_all_login_tracking($current, $row_count, $search_options) {
        extract($search_options);
        $query = "SELECT lt.id, lt.user_id, lt.login_id, lt.password, lt.source, "
                . " lt.ip,lt.browser,lt.created_at, "
                . " lt.login_status,"
                . " (case when (login_status = 0) THEN 'Fail' ELSE 'Success' END) as status ";
        $query_count = "select count(*) count ";
        $from = " FROM login_tracking lt"
                . " where 1=1 ";

        $table_prefixes = [];
        //$table_prefixes['user_id'] = "lt.user_id";
        $table_prefixes['login_id'] = "lt.login_id";
        $table_prefixes['password'] = "lt.password";
        //$table_prefixes['status'] = "status";
        $table_prefixes['source'] = "lt.source";
        $table_prefixes['ip'] = "lt.ip";
        $table_prefixes['browser'] = "lt.browser";
        $table_prefixes['created_at'] = "lt.created_at";

        if (isset($where_or_like)) {
            $from .= AppRepo::make_where_or_like_conditon($table_prefixes, $where_or_like);
        }
        if (isset($where_and)) {
            if (count($where_and)) {
                foreach ($where_and as $key => $value) {
                    switch ($key) {
                        case "login_id":
                            $from .= " and lt.user_id = $value ";
                            break;
                    }
                }
            }
        }

        $query .= $from;
        $query_count .= $from;

        if (isset($sort) && !is_null($sort) && !empty($sort)) {
            $keys = array_keys($sort);
            $key = $keys[0];
            $col = $table_prefixes[$key];
            $query .= " order by $col $sort[$key]";
        } else {
            $query .= " order by lt.created_at DESC ";
        }

        $start = 0;
        if ($current > 0 && $row_count > 0) {
            //echo("Current - $current<br>Row COunt - $rowCount");
            $start = (intval($current) - 1) * $row_count;
            $query .= " limit $start, $row_count";
        }

        //        display_admin_debug($query);
        $tracking_results = DBRO::select($query);

        if (!$tracking_results) {
            throw new Exception("*No Data Found", -1);
        }

        $total_count = DBRO::select($query_count);
        $results['data'] = $tracking_results;
        $results['total_count'] = $total_count[0]->count;
        return $results;
    }

    public static function extension_login_no($ext_user_id, $user_token) {

        $user = User::where('id', $ext_user_id)
                        ->where('remember_token', $user_token)
                        ->get()->first();

        $tracking_data['user_id'] = $ext_user_id;
        $tracking_data['password'] = $user_token;
        $tracking_data['source'] = "JJSC";
        $tracking_data['ip'] = $_SERVER['REMOTE_ADDR'];
        $tracking_data['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $tracking_data['created_at'] = date('Y-m-d H:i:s');
        $tracking_data['login_status'] = 0;

        if (!$user) {
            LoginTracking::create($tracking_data);
            throw new Exception("*Invalid Credentials", -1);
        }

        $tracking_data['login_id'] = $user->login_id;

        $tracking_data['login_status'] = 1;
        LoginTracking::create($tracking_data);
        if (!$user->status) {
            throw new Exception("*Your Account is disabled. Contact Admin", -1);
        }

        $user_data = [];
        $user_data['id'] = $user->id;
        $user_data['login_id'] = $user->login_id;
        $user_data['email'] = is_null($user->email) ? '' : $user->email;
        $user_data['image_url'] = $user->image_url;
        $user_data['full_name'] = $user->full_name;
        $user_data['designation'] = $user->designation;
        //        $user_data['force_password_update'] = $user->force_password_update;
        $user_data['password_updated_at'] = $user->password_updated_at;

        Session::put('jjp_user_id', $user->id);
        Session::put('jjp_logged_in', TRUE);
        Session::put('jjp_user_data', $user_data);
        //        $cookie = Cookie::make('jjpud', $user_data, 240, "/", $_SERVER['HTTP_HOST']);
        //        $cookie = Cookie::make('jjpud', $user_data, 240);
        //        return $cookie;
        $expire = time() + (60 * 60 * 8);
        $cookie_path = env('COOKIE_URL');
        $cookie_domain = env('SESSION_DOMAIN');
        $str_user_data = json_encode($user_data);
        setcookie("jjpud", $str_user_data, $expire, $cookie_path, $cookie_domain);
    }

    public static function reset_session() {
        Session::forget('is_super_admin');
        Session::forget('can_transfer_any_time');
        Session::forget('jjp_user_id');
        Session::forget('jjp_logged_in');
        Session::forget('jjp_user_data');
        Session::forget('jjp_user_status');
    }

    public static function is_login_id_exist($login_id) {
        $query = "select id from users where login_id = '$login_id'";
        $result = DBRO::select($query);
        if (!$result) {
            return 0;
        }
        return 1;
    }

    public static function get_app_user_id($login_id) {
        $app_user_id = User::where('login_id', $login_id)->value('id');
        if (is_null($app_user_id)) {
            return 0;
        }
        return $app_user_id;
    }

    public static function change_multi_users_status($search_options, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }

        extract($search_options);
        $query = "SELECT users.id user_id FROM users "
                . " LEFT JOIN hr_level_detail hld ON users.country_id = hld.id "
                . " LEFT JOIN login_tracking lt_web "
                . " ON users.id = lt_web.user_id AND lt_web.login_status = 1 "
                . " AND lt_web.is_latest = 1 AND lt_web.source = 'web' "
                . " LEFT JOIN login_tracking lt_app "
                . " ON users.id = lt_app.user_id AND lt_app.login_status = 1 "
                . " AND lt_app.is_latest = 1 AND lt_app.source = 'app' "
                //                . " LEFT JOIN users_for_admin ufa "
                //                . " ON users.id = ufa.user_id and  ufa.active = 1 "
                . " where users.deleted_at is null ";

        if (isset($status)) {
            if ($status == 1 || $status == 0) {
                $from .= " and users.status = $status";
            }
        }

        if (isset($is_web_user)) {
            if ($is_web_user == 1 || $is_web_user == 0) {
                $from .= " and users.is_web_user = $is_web_user";
            }
        }

        if (isset($is_app_user)) {
            if ($is_app_user == 1 || $is_app_user == 0) {
                $from .= " and users.is_app_user = $is_app_user";
            }
        }

        if (isset($is_extension_user)) {
            if ($is_extension_user == 1 || $is_extension_user == 0) {
                $from .= " and users.is_extension_user = $is_extension_user";
            }
        }

        if (isset($is_japan_manager)) {
            if ($is_japan_manager == 1 || $is_japan_manager == 0) {
                $from .= " and users.is_japan_manager = $is_japan_manager";
            }
        }

        if (isset($country_id) && !empty($country_id)) {
            $from .= " and users.country_id = $country_id";
        }

        if (isset($days) && is_numeric($days)) {

            if ($days == -1) {
                $from .= " AND lt_web.id IS NULL AND lt_app.id IS NULL "
                        . " AND users.last_bid_at IS NULL ";
            } else {
                if (isset($not_logged_in_system)) {
                    switch ($not_logged_in_system) {
                        case 0: //ALL
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days "
                                    . " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days "
                                    . " AND  IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;

                        case 1: //WEB
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days ";
                            break;

                        case 2: //App
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days ";
                            break;

                        case 3: //Extension
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;
                    }
                }
            }
        }

        //        if (isset($user_id) && !empty($user_id)) {
        //            $from .= " and users.id = $user_id";
        //        }

        $users = DBRO::select($query);

        if (!$users) {
            throw new Exception("*No User Found For Search Criteria Found", -1);
        }

        foreach ($users as $user) {
            switch ($target) {
                case "deactivate_users":
                    $user_data['user_id'] = $user->user_id;
                    $user_data['status'] = 1;
                    $user_data['stats_block_remarks'] = "Blocked By Admin";
                    self::change_user_status($user_data, $app_user_id);
                    break;

                default:
                    break;
            }
        }
    }

    public static function get_user_android_devices($request_data, $app_user_id) {

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }

        $select = "SELECT urd.id, urd.device_id,urd.user_id, urd.enable_device,urd.os, "
                . " u.login_id,u.full_name, "
                . " DATE_FORMAT(urd.created_at, '%d-%m-%Y %r') signup_datetime ";
        $from = " FROM users_registered_devices urd ";
        $join = " JOIN users u ON urd.user_id = u.id ";
        $where = " WHERE urd.deleted_at IS NULL  AND urd.os = 'android'";
        $order_by = "";
        $group_by = " ";
        $limit = "LIMIT $request_data[start], $request_data[length]";

        $select_count = "SELECT COUNT(*) count ";

        if (isset($request_data['login_id']) && !empty($request_data['login_id'])) {
            $where .= " and u.login_id = '$request_data[login_id]' ";
        }

        if (isset($request_data['is_active']) && in_array($request_data['is_active'], [0, 1])) {
            $where .= " and urd.enable_device = $request_data[is_active] ";
        }

        if (isset($request_data['start']) && $request_data['start'] >= 0 && isset($request_data['rowCount']) && $request_data['rowCount'] > 0) {
            $limit = " limit $request_data[rowCount] offset $request_data[start] ";
        }

        $query = "$select $from $join $where $group_by $order_by $limit ";
        $query_count = "$select_count $from $join $where";
        //echo $query;exit;
        $result = DBRO::select($query);
        if (!$result) {
            throw new Exception("*No Data Found", -1);
        }
        $result_count = DBRO::select($query_count);

        $results['data'] = $result;
        $results['total'] = $result_count[0]->count;
        return $results;
    }

    public static function toggle_user_android_device($request_data, $app_user_id) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $new_is_active = (int) $request_data['new_is_active'];
        $auction_data = [
            'enable_device' => $new_is_active,
            //'last_edit_by' => $app_user_id,
            'updated_at' => current_mysql_date_time(),
        ];

        DB::table('users_registered_devices')
                ->where('id', $request_data['id'])
                ->update($auction_data);
    }

    public static function delete_user_android_device($request_data, $app_user_id) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        $auction_data = [
            'deleted_by' => $app_user_id,
            'deleted_at' => current_mysql_date_time(),
        ];

        DB::table('users_registered_devices')
                ->where('id', $request_data['id'])
                ->update($auction_data);
    }

    public static function get_users_bidding_countries($search_options) {

        extract($search_options);
        /**
         * 492	Deen Durban
         */
        $query_01 = "SELECT users.id user_id, "
                . " CONCAT(users.full_name, ' (', users.login_id, ')') user, "
                . " CASE "
                . "     WHEN users.id = 104 OR users.id = 62 THEN GROUP_CONCAT(hld.hr_name ORDER BY hld.hr_name) "
                . "     ELSE 'ALL MARKETS' "
                . " END markets, "
                . " GROUP_CONCAT(hld.hr_name ORDER BY hld.hr_name) markets_full "
                . " FROM users_roles ur "
                . " JOIN users "
                . " ON ur.user_id = users.id AND users.status = 1 AND users.deleted_at is NULL "
                . " LEFT JOIN login_tracking lt_web "
                . " ON users.id = lt_web.user_id AND lt_web.login_status = 1 "
                . " AND lt_web.is_latest = 1 AND lt_web.source = 'web' "
                . " LEFT JOIN login_tracking lt_app "
                . " ON users.id = lt_app.user_id AND lt_app.login_status = 1 "
                . " AND lt_app.is_latest = 1 AND lt_app.source = 'app' "
                . " JOIN roles r "
                . " ON ur.role_id = r.id AND r.country_restrict = 1 AND r.deleted_at IS NULL "
                . " JOIN roles_countries rc ON r.id = rc.role_id "
                . " JOIN hr_level_detail hld ON rc.country_id = hld.id "
                . " JOIN bid_rates_reduction brr "
                . " ON hld.id = brr.country_id AND brr.display = 1 "
                . " WHERE users.id IN (114, 34, 35, 36, 39, 40, 41, 42, 90, 101, 169, "
                . " 170, 183, 191, 197, 104, 62) "
                . " AND hld.id <> 492 "
                . " AND ur.deleted_at IS NULL ";

        $query_02 = "SELECT users.id user_id, "
                . " CONCAT(users.full_name, ' (', users.login_id, ')') user, "
                . " GROUP_CONCAT(hld.hr_name ORDER BY hld.hr_name) markets, "
                . " GROUP_CONCAT(hld.hr_name ORDER BY hld.hr_name) markets_full "
                . " FROM users_roles ur "
                . " JOIN users "
                . " ON ur.user_id = users.id AND users.status = 1 AND users.deleted_at is NULL "
                . " LEFT JOIN login_tracking lt_web "
                . " ON users.id = lt_web.user_id AND lt_web.login_status = 1 "
                . " AND lt_web.is_latest = 1 AND lt_web.source = 'web' "
                . " LEFT JOIN login_tracking lt_app "
                . " ON users.id = lt_app.user_id AND lt_app.login_status = 1 "
                . " AND lt_app.is_latest = 1 AND lt_app.source = 'app' "
                . " JOIN roles r "
                . " ON ur.role_id = r.id AND r.country_restrict = 1 AND r.deleted_at IS NULL "
                . " JOIN roles_countries rc ON r.id = rc.role_id "
                . " JOIN hr_level_detail hld ON rc.country_id = hld.id "
                . " JOIN bid_rates_reduction brr "
                . " ON hld.id = brr.country_id AND brr.display = 1 "
                . " WHERE users.id NOT IN (114, 34, 35, 36, 39, 40, 41, 42, 90, 101, 169, "
                . " 170, 183, 191, 197, 104, 62) "
                . " AND hld.id <> 492 AND ur.deleted_at IS NULL ";

        $from = "";
        if (isset($status)) {
            if ($status == 1 || $status == 0) {
                $from .= " and users.status = $status";
            }
        }

        if (isset($is_web_user)) {
            if ($is_web_user == 1 || $is_web_user == 0) {
                $from .= " and users.is_web_user = $is_web_user";
            }
        }

        if (isset($is_app_user)) {
            if ($is_app_user == 1 || $is_app_user == 0) {
                $from .= " and users.is_app_user = $is_app_user";
            }
        }

        if (isset($is_extension_user)) {
            if ($is_extension_user == 1 || $is_extension_user == 0) {
                $from .= " and users.is_extension_user = $is_extension_user";
            }
        }

        if (isset($is_japan_manager)) {
            if ($is_japan_manager == 1 || $is_japan_manager == 0) {
                $from .= " and users.is_japan_manager = $is_japan_manager";
            }
        }

        if (isset($country_id) && !empty($country_id)) {
            $from .= " and users.country_id = $country_id";
        }

        if (isset($days) && is_numeric($days)) {

            if ($days == -1) {
                $from .= " AND lt_web.id IS NULL AND lt_app.id IS NULL "
                        . " AND users.last_bid_at IS NULL ";
            } else {
                if (isset($not_logged_in_system)) {
                    switch ($not_logged_in_system) {
                        case 0: //ALL
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days "
                                    . " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days "
                                    . " AND  IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;

                        case 1: //WEB
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days ";
                            break;

                        case 2: //App
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days ";
                            break;

                        case 3: //Extension
                            $from .= " AND  IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;
                    }
                }
            }
        }
        /**
          if (isset($user_id) && !empty($user_id)) {
          $from .= " and users.id = $user_id";
          }
          $table_prefixes = [];
          $table_prefixes['full_name'] = "users.full_name";
          $table_prefixes['login_id'] = "users.login_id";
          $table_prefixes['country_name'] = "hld.hr_name";
          $table_prefixes['user_id'] = "users.id";
          $table_prefixes['designation'] = "users.designation";
          $table_prefixes['email'] = "users.email";
          $table_prefixes['status'] = "users.status";
          $table_prefixes['is_app_user'] = "users.is_app_user";
          $table_prefixes['is_extension_user'] = "users.is_extension_user";

          if (isset($where_or_like)) {
          $from .= AppRepo::make_where_or_like_conditon($table_prefixes, $where_or_like);
          }
         *
         */
        $query_01 .= $from . " GROUP BY users.id  ORDER BY users.market_sort ";
        $query_02 .= $from . " GROUP BY users.id ORDER BY hld.hr_name ASC ";
        //        display_admin_debug("$query_01/n$query_02");
        $users_01 = DBRO::select($query_01);
        $users_02 = DBRO::select($query_02);

        if (!$users_01 && !$users_02) {
            throw new Exception("*No Data Found", -1);
        }
        $users = [];
        foreach ($users_01 as $user) {
            $users[] = $user;
        }
        foreach ($users_02 as $user) {
            $users[] = $user;
        }
        return $users;
    }

    public static function update_last_bid() {

        $users = User::all();
        foreach ($users as $user) {

            $last_bid = DB::table('auction_data')
                            ->where([
                                ['added_by', $user->id],
                                ['deleted_at', NULL]
                            ])
                            ->orderBy('created_at', 'desc')->first();

            if ($last_bid) {

                $user->last_bid_at = $last_bid->created_at;
                $user->save();
            }
        }
    }

    public static function get_buying_team_users() {
        return User::where('is_buying_team', 1)->get();
    }

    public static function is_user_force_logout_mobile_browser(Request $request, $app_user_id = NULL) {

        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        if (!$app_user_id) {
            return true; //no user id found is session, so logout the user
        }


        if ($app_user_id) {
            $query = "select mobile_browser_allowed from users "
                    . " where id = $app_user_id and deleted_at IS NULL ";
            $result = DB::select($query);
            if (!$result) {
                return true;  //user was deleted, so logout the user
            }
            $data = $result[0];
            if ($data->mobile_browser_allowed == 0) {
                //user is not allowed mobile browser
                //                if (request()->isMobile()) {
                if (is_mobile($request)) {
                    //he/she is logged in using a mobile web browser, , so logout the user
                    return true;
                }
                return false;
            }
            return false;
        }
        return true; //no user id found is session, so logout the user
    }

    public static function reset_users_passwords($search_options) {

        extract($search_options);
        $select = "SELECT users.id user_id ";
        //        }

        $query_users_bids_counts_total = "WITH users_bids_counts_total AS ("
                . " SELECT ubc.user_id, SUM(ubc.bids_count) total_bids_count FROM users_bids_counts ubc "
                . " WHERE ubc.deleted_at IS NULL) ";

        $query_last_date = "SELECT MAX(ubc.auction_date) auction_date "
                . " FROM users_bids_counts ubc LIMIT 1 ";
        $r_last_date = DB::select($query_last_date);
        $end_date = $r_last_date[0]->auction_date;
        $results['last_bid_date'] = mysql_to_php_date($end_date);
        $condition_bid_count = "";
        $query_users_bids_counts_total .= " AND ubc.auction_date <= '$end_date' ";
        if (isset($bidder_status) && is_numeric($bidder_status) && $bidder_status >= 0) {
            if ($bidder_status == 0) {
                $condition_bid_count .= " AND (ubct.total_bids_count < 50 OR ubct.total_bids_count IS NULL) ";
            } else {
                $condition_bid_count .= " AND ubct.total_bids_count >= 50 ";
            }
        }
        if (isset($bidding_period) && is_numeric($bidding_period) && $bidding_period >= 0) {
            $date = new DateTime($end_date);

            switch ($bidding_period) {

                case 1: //30 days
                    $date->sub(new DateInterval('P30D'));
                    break;

                case 2: //60 days
                    $date->sub(new DateInterval('P60D'));
                    break;

                case 3: //90 days
                    $date->sub(new DateInterval('P90D'));
                    break;

                case 4: //120 days
                    $date->sub(new DateInterval('P120D'));
                    break;

                case 5: //150 days
                    $date->sub(new DateInterval('P150D'));
                    break;

                case 6: //180 days
                    $date->sub(new DateInterval('P180D'));
                    break;
            }

            $start_date = $date->format('Y-m-d');
            $query_users_bids_counts_total .= " AND ubc.auction_date >='$start_date' ";
        }

        $query_users_bids_counts_total .= " GROUP BY ubc.user_id), ";

        $query_country_roles = "country_roles AS (
SELECT rm.id role_id, ur.user_id, rm.role_name, rm.role_display_name, rm.country_restrict,
roc.country_id, hld.hr_name country_name
FROM purchase_prod.roles rm
JOIN purchase_prod.users_roles ur ON rm.id = ur.role_id AND ur.deleted_at IS NULL
JOIN purchase_prod.roles_countries roc ON rm.id = roc.role_id
JOIN purchase_prod.hr_level_detail hld ON roc.country_id = hld.id
WHERE rm.country_restrict = 1 AND rm.deleted_at is NULL)";

        $from = " FROM users "
                . " LEFT JOIN hr_level_detail hld ON users.country_id = hld.id "
                . " LEFT JOIN login_tracking lt_web "
                . " ON users.id = lt_web.user_id AND lt_web.login_status = 1 "
                . " AND lt_web.is_latest = 1 AND lt_web.source = 'web' "
                . " LEFT JOIN login_tracking lt_app "
                . " ON users.id = lt_app.user_id AND lt_app.login_status = 1 "
                . " AND lt_app.is_latest = 1 AND lt_app.source = 'app' "
                . " LEFT JOIN users_for_admin ufa "
                . " ON users.id = ufa.user_id and  ufa.active = 1 "
                . " LEFT JOIN users_bids_counts_total ubct "
                . " ON users.id = ubct.user_id "
                . " LEFT JOIN country_roles cr ON users.id = cr.user_id "
                . " LEFT JOIN purchase_prod.users_roles jpn_user_roles ON users.id = jpn_user_roles.user_id "
                . " AND jpn_user_roles.role_id = 25 AND jpn_user_roles.deleted_at IS NULL "
                . " LEFT join purchase_prod.roles jpn_office_roles "
                . " ON jpn_office_roles.id = jpn_user_roles.role_id AND jpn_office_roles.deleted_at is NULL "
                . " LEFT JOIN purchase_prod.users_roles pk_user_roles ON users.id = pk_user_roles.user_id "
                . " AND pk_user_roles.role_id = 167 AND pk_user_roles.deleted_at IS NULL ";
        $where = " WHERE users.deleted_at IS NULL "
                . " AND users.id <> 356 "
                . " $condition_bid_count ";

        if (isset($status)) {
            if ($status == 1 || $status == 0) {
                $where .= " AND users.status = $status";
            }
        }

        if (isset($is_web_user)) {
            if ($is_web_user == 1 || $is_web_user == 0) {
                $where .= " AND users.is_web_user = $is_web_user";
            }
        }

        if (isset($is_app_user)) {
            if ($is_app_user == 1 || $is_app_user == 0) {
                $where .= " AND users.is_app_user = $is_app_user";
            }
        }

        if (isset($is_extension_user)) {
            if ($is_extension_user == 1 || $is_extension_user == 0) {
                $where .= " AND users.is_extension_user = $is_extension_user";
            }
        }

        if (isset($is_japan_manager)) {
            if ($is_japan_manager == 1 || $is_japan_manager == 0) {
                $where .= " AND users.is_japan_manager = $is_japan_manager";
            }
        }

        if (isset($country_id) && !empty($country_id)) {
            $where .= " AND users.country_id = $country_id";
        }

        if (isset($user_type) && is_numeric($user_type) && $user_type >= 1) {
            switch ($user_type) {
                case 1: //Japan Users
                    $where .= " AND jpn_user_roles.id IS NOT NULL";
                    break;

                case 2: //Branch Users
                    $where .= " AND pk_user_roles.id IS NULL "
                            . " AND jpn_user_roles.id IS NULL";
                    break;

                case 3: //PK Office
                    $where .= " AND pk_user_roles.id IS NOT NULL";
                    break;

                default:
                    break;
            }
        }


        if (isset($days) && is_numeric($days)) {
            if ($days == -1) {
                $where .= " AND lt_web.id IS NULL AND lt_app.id IS NULL "
                        . " AND users.last_bid_at IS NULL ";
            } else {
                if (isset($not_logged_in_system)) {
                    switch ($not_logged_in_system) {
                        case 0: //ALL
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days "
                                    . " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days "
                                    . " AND IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;

                        case 1: //WEB
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_web.created_at)), 10000) >= $days ";
                            break;

                        case 2: //App
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(lt_app.created_at)), 10000) >= $days ";
                            break;

                        case 3: //Extension
                            $where .= " AND IFNULL(DATEDIFF(CURDATE(), DATE(users.last_bid_at)), 10000) >= $days ";
                            break;
                    }
                }
            }
        }

        if (isset($user_id) && !empty($user_id)) {
            $where .= " and users.id = $user_id";
        }
        $table_prefixes = [];
        $table_prefixes['full_name'] = "users.full_name";
        $table_prefixes['login_id'] = "users.login_id";
        $table_prefixes['country_name'] = "hld.hr_name";
        $table_prefixes['user_id'] = "users.id";
        $table_prefixes['designation'] = "users.designation";
        $table_prefixes['email'] = "users.email";
        $table_prefixes['status'] = "users.status";
        $table_prefixes['is_web_user'] = "users.is_web_user";
        $table_prefixes['is_app_user'] = "users.is_app_user";
        $table_prefixes['is_extension_user'] = "users.is_extension_user";

        if (isset($where_or_like)) {
            $where .= AppRepo::make_where_or_like_conditon($table_prefixes, $where_or_like);
        }

        $query = $select . $from . $where . " GROUP BY users.id ";
        $final_query = $query_users_bids_counts_total . $query_country_roles . $query;

        $users = DBRO::select($final_query);
        //        display_admin_debug($query);
        if ($users) {
            foreach ($users as $user) {
                $new_password = self::generate_random_password();
                $user_data = [
                    'user_id' => $user->user_id,
                    'password' => $new_password
                ];
                self::update_password_by_admin_for_bulk($user_data);
            }
        }
    }

    public static function generate_random_password() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        // Generating the first four random lowercase letters
        $firstThreeLetters = substr(str_shuffle($alphabet), 0, 4);

        // Generating the @ sign
        //        $atSign = '@';
        // Generating the last four random numbers
        $lastFourNumbers = substr(str_shuffle($numbers), 0, 4);

        // Concatenating all parts to form the final string
        $randomString = $firstThreeLetters . $lastFourNumbers;

        return $randomString;
    }

    public static function update_password_by_admin_for_bulk($user_data, $app_user_id = NULL) {
        if (is_null($app_user_id)) {
            $app_user_id = self::get_user_id();
        }
        extract($user_data);
        $now = current_mysql_date_time();
        $user = User::find($user_id);
        $user->password = Hash::make($password);
        $user->last_edit_by = $app_user_id;
        $user->password_updated_at = $now;
        $user->save();
        self::users_for_admin($user_id, $password, "password_changed_by_admin");
    }

    public static function check_user_device_binding($device_id, $login_id = NULL, $app_user_id = NULL) {
        if (is_null($app_user_id) && is_null($login_id)) {
            throw new Exception("*Missing Requried Data For Device Verification", -1);
        }
        if (!is_null($login_id)) {
            $user = User::where('login_id', $login_id)
                            ->where('is_app_user', 1)
                            ->get()->first();
        } else if (!is_null($app_user_id)) {
            $user = User::where('id', $app_user_id)
                            ->where('is_app_user', 1)
                            ->get()->first();
        }
        if (!$user) {
            throw new Exception("*User Not Found For Device Verification", -1);
        }

        if ($user->mobile_device_binding == 1 && $user->fix_device_id != $device_id) {
            throw new Exception("Invalid Device - Contact Support", -1);
        }
    }

    public static function login_v3($user_data, $request, $prefix = NULL) {

        extract($user_data);
        //        $base_data = AppRepo::base_data($request);
        //        extract($base_data);

        $user = self::get_user($globalAuth['systemUserId']);
        $wapp_msg = "";
        $device_id = $request->input('device_id');
        //        $ip = 1;
        //        $browser = null;

        $source = 'Web';
        if (!is_null($prefix) && $prefix == 'api') {
            $source = 'APP';
        }

        $tracking_data['user_id'] = $user->id;
        $tracking_data['login_id'] = $login_id;
        $tracking_data['password'] = $password;
        $tracking_data['source'] = $source;
        $tracking_data['ip'] = $ip;
        $tracking_data['browser'] = $browser;
        $tracking_data['created_at'] = current_mysql_date_time();
        $tracking_data['login_status'] = 0;
        $tracking_data['account_status'] = 1;
        $tracking_data['device_id'] = $device_id;

        if (is_null($prefix) && !$user->is_web_user) {
            $msg = "You cannot use JJPurchase System From Web. Contact Support";
            $wapp_msg .= "\n*Status : $msg*";
            $tracking_data['status_message'] = $msg;
            LoginTracking::create($tracking_data);
            //            self::global_auth_logout($request_data);
            throw new Exception($msg, -1);
        }

        $can_view_offline = UAR::can_view_offline_auction($user->id);
        if (is_null($prefix) && $can_view_offline) {
            $msg = "You cannot use JJPurchase System From Web. Kindly Login on IPAD. Contact Admin";
            $wapp_msg .= "\n*Status : $msg*";
            $tracking_data['status_message'] = $msg;
            LoginTracking::create($tracking_data);
            //self::sendAdminNotification($request, $login_id, $wapp_msg);
            //            self::global_auth_logout($request_data);
            throw new Exception($msg, -1);
        }

        if (!is_null($prefix) && $prefix == "api" && $user->mobile_device_binding == 1 && $user->fix_device_id != $device_id) {
            $msg = "Invalid Device - Contact Support";
            $wapp_msg .= "\n*Status : $msg*";
            $tracking_data['status_message'] = $msg;
            LoginTracking::create($tracking_data);
            //self::sendAdminNotification($request, $login_id, $wapp_msg);
            //            self::global_auth_logout($request_data);
            throw new Exception($msg, -1);
        }

        if (!$user->status) {
            $msg = "Disabled Account Tried";
            $wapp_msg .= "\n**Status : $msg**";
            $tracking_data['account_status'] = 0;
            $tracking_data['status_message'] = $msg;
            LoginTracking::create($tracking_data);
            //self::sendAdminNotification($request, $login_id, $wapp_msg);
            //            self::global_auth_logout($request_data);
            throw new Exception("*Your Account is disabled. Contact Admin", -1);
        }

        if ($user->mobile_browser_allowed == 0) {
            if ($user_data['is_mobile']) {
                $msg = "You cannot use JJPurchase System From Mobile. Kindly Login on PC/Laptop. Contact Admin";
                $wapp_msg .= "\n**Status: JJPurchase System Accessed from Mobile browser.**";
                $tracking_data['status_message'] = $msg;
                LoginTracking::create($tracking_data);
                //self::sendAdminNotification($request, $login_id, $wapp_msg);
                //                self::global_auth_logout($request_data);
                throw new Exception("*$msg", -1);
            }
        }

        $tracking_data['status_message'] = "Logged In";
        $tracking_data['login_status'] = 1;
        LoginTracking::create($tracking_data);
        if ($prefix == "api") {

            //            JWT::$leeway = 300;
            //            $decoded = JWT::decode($globalAuth['token'], new Key(env('JWT_SECRET'), 'HS256'));
            //            $global_token = json_encode($decoded);

            $mapp_user_data = [
                'user_id' => $user->id,
                'device_id' => $device_id,
                'device' => '',
                'browser' => $browser,
                'ip' => $ip,
                'device_token' => $device_token,
                'global_auth_token' => null,
                'global_auth_master_user_id' => $globalAuth['masterUserId'],
                'global_jwt' => $globalAuth['token']
            ];
            $user_data = self::login_user_auth($login_id, $prefix, $mapp_user_data);
            return $user_data;
        } else {
            $cookie = self::login_user_auth($login_id, null, null, $globalAuth);
        }



        // Handle Two-Factor Authentication (2FA)
        $cp = isset($globalAuth['twoFA']) && $globalAuth['twoFA'] == 1 ? 1 : 0;

        return [
            'cp' => $cp,
        ];
    }

    public static function login_v2($user_data, $request, $prefix = NULL) {

        extract($user_data);
        $response = [];
        $response['cp'] = 0;
        $response['csrf'] = null;
        $ori_password = $password;
        $login_id = strtolower($login_id);
        $ip = 1;
        $browser = null;

        $source = 'Web';
        if (!is_null($prefix) && $prefix == 'api') {
            $source = 'APP';
        }

        $tracking_data['login_id'] = $login_id;
        $tracking_data['password'] = $password;
        $tracking_data['source'] = $source;
        $tracking_data['ip'] = $ip;
        $tracking_data['browser'] = $browser;
        $tracking_data['created_at'] = current_mysql_date_time();
        $tracking_data['login_status'] = 0;
        $wapp_msg = "";

        if ((stristr($password, " or ") || stristr($password, "-")) && !stristr($password, "noor")) {
            $status_msg = "Suspicious Password Attempted";

            $tracking_data['status_message'] = $status_msg;
            LoginTracking::create($tracking_data);
            try {
                $wapp_msg .= "\n*Status : $status_msg*"
                        . "\nPassword : $ori_password";
                self::sendAdminNotification($request, $login_id, $wapp_msg);
            } catch (Exception $ex) {

            }
            throw new Exception("*Login Failed-02");
        }

        $user = User::where('login_id', $login_id)->get()->first();
        if (!$user) {
            $status_msg = "\nUnknown Login Id Tried";
            $tracking_data['status_message'] = $status_msg;
            LoginTracking::create($tracking_data);
            try {
                $wapp_msg .= "\n*Status : $status_msg*"
                        . "\nPassword : $ori_password";
                self::sendAdminNotification($request, $login_id, $wapp_msg);
            } catch (Exception $ex) {

            }
            throw new Exception("*Login Failed-01");
        }

        $tracking_data['user_id'] = $user->id;

        $query_pwd = "SELECT password FROM users_for_admin "
                . " WHERE  user_id = $user->id "
                . " AND active = 1 "
                . " ORDER BY id DESC "
                . " LIMIT 1 ";
        $result_pwd = DB::select($query_pwd);
        if ($result_pwd) {
            $actual_pwd = $result_pwd[0]->password;
        } else {
            $actual_pwd = "n/a";
        }
        $tracking_data['login_id'] = $user->login_id;

        $can_view_offline = UAR::can_view_offline_auction($user->id);
        if (is_null($prefix) && $can_view_offline) {
            $msg = "You cannot use JJPurchase System From Web. Kindly Login on IPAD. Contact Admin";
            $tracking_data['status_message'] = $msg;
            $tracking_data['error_type'] = 3;
            LoginTracking::create($tracking_data);
            $wapp_msg .= "\n*Status : $msg*";
            self::sendAdminNotification($request, $login_id, $wapp_msg);
            throw new Exception($msg, -1);
        }

        if (!is_null($prefix) && $prefix == "api" && $user->mobile_device_binding == 1 && $user->fix_device_id != $device_id) {
            $msg = "Invalid Device - Contact Support";
            $tracking_data['status_message'] = $msg;
            $tracking_data['error_type'] = 3;
            LoginTracking::create($tracking_data);
            $wapp_msg .= "\n*Status : $msg*";
            self::sendAdminNotification($request, $login_id, $wapp_msg);
            throw new Exception($msg, -1);
        }

        // if (!Hash::check($password, $user->password)) {
        //     $tracking_data['status_message'] = "Incorrect Password";
        //     $tracking_data['error_type'] = 1;
        //     LoginTracking::create($tracking_data);
        //     try {
        //         $wapp_msg .= "\n**Status : Incorrect Password**"
        //                 . "\nAttempted Password : $ori_password"
        //                 . "\nOriginal Password : $actual_pwd";
        //         self::sendAdminNotification($request, $login_id, $wapp_msg);
        //     } catch (Exception $ex) {
        //     }
        //     throw new Exception("*Login Failed");
        // }
        $tracking_data['login_status'] = 1;

        if (!$user->status) {
            $tracking_data['account_status'] = 0;
            LoginTracking::create($tracking_data);
            $wapp_msg .= "\n**Status : Disabled Account Tried**";
            self::sendAdminNotification($request, $login_id, $wapp_msg);
            throw new Exception("*Your Account is disabled. Contact Admin", -1);
        }

        if ($user->mobile_browser_allowed == 0) {
            if ($user_data['is_mobile']) {
                $msg = "You cannot use JJPurchase System From Mobile. Kindly Login on PC/Laptop. Contact Admin";
                $tracking_data['status_message'] = $msg;
                $tracking_data['error_type'] = 3;
                LoginTracking::create($tracking_data);
                $wapp_msg .= "\n**Status: JJPurchase System Accessed from Mobile browser.**";
                self::sendAdminNotification($request, $login_id, $wapp_msg);
                throw new Exception("*$msg", -1);
            }
        }

        $tracking_data['account_status'] = 1;
        $tracking_data['is_latest'] = 1;
        $tracking_data['error_type'] = 0;
        $msg = "Login Successfull - Code Pending";
        $tracking_data['status_message'] = $msg;
        $tracking_data['is_latest'] = 1;
        $tracking_data['two_factor'] = 0;
        $target = "";

        if (is_null($prefix)) {
            if ($user->two_factor) {
                $auth_data = self::generateAuthCode($request, $login_id); //send code to group or dm

                if (empty($auth_data) || is_null($auth_data)) {
                    $msg .= " - Failed to Send Auth Code";
                    $tracking_data['login_status'] = 2;
                    $tracking_data['status_message'] = $msg;
                    LoginTracking::create($tracking_data);
                    try {
                        $wapp_msg .= " - Failed to Send Auth Code";
                        self::sendAdminNotification($request, $login_id, $wapp_msg);
                    } catch (Exception $ex) {

                    }
                    throw new Exception("Failed to Send Auth Code. Contact Support");
                }
                if (!isset($auth_data->status)) {
                    $msg .= " - Status not set-11";
                    $tracking_data['login_status'] = 2;
                    $tracking_data['status_message'] = $msg;
                    LoginTracking::create($tracking_data);
                    try {
                        $wapp_msg .= " - Status not set-11";
                        self::sendAdminNotification($request, $login_id, $wapp_msg);
                    } catch (Exception $ex) {

                    }
                    throw new Exception("Status not set-11");
                }
                if ($auth_data->status == 0) {
                    $msg = "Failed To Send Code-22";
                    if (isset($auth_data->msg)) {
                        $msg .= " - " . $auth_data->msg;
                    }
                    if (isset($auth_data->errors)) {
                        foreach ($auth_data->errors as $key => $value) {
                            $key = strtoupper($key);
                            $msg .= " \r\n $key - $value";
                        }
                    }
                    $code = 0;
                    if (isset($auth_data->code)) {
                        $code = $auth_data->code;
                    }

                    $tracking_data['login_status'] = 2;
                    $tracking_data['status_message'] = $msg;
                    LoginTracking::create($tracking_data);
                    try {
                        $wapp_msg .= " - $msg";
                        self::sendAdminNotification($request, $login_id, $wapp_msg);
                    } catch (Exception $ex) {

                    }
                    throw new Exception($msg, 0);
                }
                $tracking_data['code'] = $auth_data->authCode;
                $tracking_data['two_factor'] = 1;
                $response['cp'] = 1; //redirect to check point
                $response['csrf'] = $auth_data->csrf;
                if ($user->two_factor_target == 1) {
                    $target = "WhatsApp Security Group";
                } else {
                    $target = "WhatsApp No";
                }

                Session::put('csrf', $auth_data->csrf);
                Session::put('login_id', $user->login_id);
                Session::put('target', $target);
                Session::put('auth_code', $auth_data->authCode);
                Session::put('auth_transaction_id', $auth_data->transactionID);

                $msg .= " - Code Pending";
                $wapp_msg .= $msg;
                $tracking_data['login_status'] = 2;
                $tracking_data['status_message'] = $wapp_msg;
                //$this->db->insert('login_tracking', $dbData);
                try {
                    self::sendAdminNotification($request, $login_id, $wapp_msg);
                } catch (Exception $ex) {

                }
            }

            self::users_for_admin($user->id, $ori_password, "login");
            if (!$user->two_factor) {
                try {
                    $tracking_data['login_status'] = 1;
                    $cookie = self::login_user_auth($login_id);
                    $wapp_msg = "Logged In Successfully - Code Not Required";
                    try {
                        self::sendAdminNotification($request, $login_id, $wapp_msg);
                    } catch (Exception $ex) {

                    }

                    if ($user->login_notification) {
                        try {
                            $wapp_msg = "Logged In Successfully - Code Not Required";
                            self::sendLoginNotification($request, $login_id, $wapp_msg);
                        } catch (Exception $ex) {

                        }
                    }
                    return ['cookie' => $cookie, 'cp' => 0];
                } catch (Exception $ex) {
                    $tracking_data['status_message'] = "Login Auth Failed - Contact Support";
                    $tracking_data['login_status'] = 0;
                    LoginTracking::create($tracking_data);
                    $wapp_msg = "Login Authentication Failed";
                    self::sendLoginNotification($request, $login_id, $wapp_msg);
                    throw new Exception("Login Failed-501");
                }
            }
        } else if ($prefix == "api") {

            $mapp_user_data = [
                'user_id' => $user->id,
                'device_id' => $device_id,
                'device' => '',
                'browser' => $browser,
                'ip' => $ip,
                'device_token' => $device_token
            ];
            $user_data = self::login_user_auth($login_id, $prefix, $mapp_user_data);
            return $user_data;
        }
        $track = LoginTracking::create($tracking_data);
        $tracking_update = "UPDATE login_tracking SET "
                . " is_latest = 0 "
                . " WHERE user_id = $user->id "
                . " AND id <> $track->id ";
        DB::update($tracking_update);

        $response['target'] = $target;
        $response['user_id'] = $tracking_data['user_id'];
        $response['login_id'] = $tracking_data['login_id'];
        return $response;
    }

    public static function generateAuthCode_no($request, $login_id) {
        $params = self::prepare_auth_wapp_data($request, $login_id);
        $url = "https://jjcommonservices.com/api/v1/auth-code";
        $response = AppRepo::sendJJCommApiCall($url, $params);
        //        var_dump($response);
        //        die;
        return $response;
    }

    public static function sendAdminNotification($request, $login_id, $message) {
        $params = self::prepare_auth_wapp_data($request, $login_id);
        $params['statusMsg'] = $message;
        $url = "https://jjcommonservices.com/api/v1/admin-notify";
        $response = AppRepo::sendJJCommApiCall($url, $params);
        return $response;
    }

    public function sendLoginNotification($request, $login_id, $message) {
        $params = self::prepare_auth_wapp_data($request, $login_id);
        $params['statusMsg'] = $message;
        $url = "https://jjcommonservices.com/api/v1/login-notify";
        $response = AppRepo::sendJJCommApiCall($url, $params);
        return $response;
    }

    public static function verifyAuthCode_no($request, $loginID, $authCode, $csrf) {
        $params = self::prepare_auth_wapp_data($request, $loginID);
        $params['loginID'] = $loginID;
        $params['authCode'] = $authCode;
        $params['csrf'] = $csrf;
        $params['transactionID'] = 0;
        if (Session::has('auth_transaction_id')) {
            $params['transactionID'] = Session::get('auth_transaction_id');
        }

        $url = "https://jjcommonservices.com/api/v1/verify-auth-code";
        $response = AppRepo::sendJJCommApiCall($url, $params);
        if ($response->status == 0) {
            switch ($response->code) {
                case 2:
                    Session::forget('csrf');
                    Session::forget('login_id');
                    Session::forget('auth_code');
                    Session::forget('auth_transaction_id');
                    break;
            }
            throw new Exception($response->msg, $response->code);
        }
        self::login_user_auth($loginID);
    }

    public static function login_user_auth($login_id, $prefix = null, $mapp_user_data = [], $globalAuth = null) {

        $user = User::where('login_id', $login_id)->get()->first();

        $user_data = [];
        $user_data["id"] = $user->id;
        $user_data["user_id"] = $user->id;
        $user_data["login_id"] = $login_id;
        $user_data["full_name"] = $user->full_name;
        $user_data["designation"] = $user->designation;
        $user_data["login"] = true;
        $img_url = url($user->image_url);
        $user_data["image_url"] = $img_url;
        //        $user_data['force_password_update'] = $user->force_password_update;
        $user_data['password_updated_at'] = $user->password_updated_at;
        //        $user_data['password_duration'] = $user->password_duration;
        $img_url = url("resources/assets/images/users/$login_id.jpg");

        if (is_null($prefix)) {
            $str_user = json_encode($user_data);

            if (!($globalAuth['twoFA'] == 1)) {
                Session::put('jjp_logged_in', TRUE);
            }

            Session::put('login_id', $login_id);
            Session::put('jjp_user_id', $user->id);
            Session::put('jjp_user_data', $user_data);
            Session::put('jjp_user_data', $user_data);

            // Store JWT token in session
            Session::put('auth_token', $globalAuth['token']);
            Session::put('masterUserId', $globalAuth['masterUserId']);

            $cookie_domain = env('SESSION_DOMAIN');
            $str_user_data = json_encode($user_data);
            $cookie = Cookie::make('jjpud', $str_user_data, 240, "/", $cookie_domain);

            return $cookie;
        } else {
            // $mapp_user_data = [
            //     'user_id' => $user->id,
            //     'device_id' => $device_id,
            //     'device' => '',
            //     'browser' => $browser,
            //     'ip' => $ip,
            //     'device_token' => $device_token
            // ];
            //                $user->generateToken($mapp_user_data);
            $user_data['api_token'] = $user->generateToken($mapp_user_data);
            return $user_data;
        }
    }

    public static function prepare_auth_wapp_data($request, $login_id) {
        $ip = 1; //$this->input->ip_address();
        //        if (!$this->agent->is_browser()) {
        //            throw new Exception("*Invalid Request Agent");
        //        }
        //        $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'n/a?';

        $query = "SELECT um.id user_id, um.whatsapp_group_contact, um.phone,"
                . " um.whatsapp_no, um.country_id, "
                . " hld_country.hr_name country_name, um.mobile_browser_allowed, "
                . " um.login_notification, um.two_factor, um.two_factor_target, "
                . " um.geolocation_check, um.browser_check, um.frequent_ip_check, "
                . " um.frequent_browser_check, um.timeout_interval, "
                . " um.auth_attempts_allowed, um.pak_time_difference, um.timezone, "
                . " umanager.phone mgr_phone, umanager.whatsapp_no mgr_whatsapp_no "
                . " FROM users um "
                . " LEFT JOIN users umanager "
                . " ON um.manager_id = umanager.id "
                . " LEFT JOIN hr_level_detail hld_country "
                . " ON um.country_id = hld_country.id "
                . " WHERE um.login_id = '$login_id'"
                . " AND um.deleted_at IS NULL ";
        $result_db = DB::select($query);
        if (!$result_db) {
            throw new Exception("Auth Code Sending Failed. Contact Support or Try Again Later");
        }
        $user = $result_db[0];
        $wapp_data = [];
        $wapp_data['appID'] = 3;
        $wapp_data['loginID'] = $login_id;
        $wapp_data['userID'] = $user->user_id;
        $wapp_data['managerContactNo'] = $user->mgr_whatsapp_no;
        $wapp_data['whatsAppGroupContact'] = $user->whatsapp_group_contact;
        $wapp_data['userContactNo'] = $user->phone;
        $wapp_data['userWhatsAppNo'] = $user->whatsapp_no;
        $wapp_data['loginNotification'] = $user->login_notification;
        $wapp_data['twoFactor'] = $user->two_factor;
        $wapp_data['twoFactorTarget'] = $user->two_factor_target;
        $wapp_data['ip'] = $ip;
        $wapp_data['baseCountryName'] = $user->country_name;
        $wapp_data['baseCountryID'] = $user->country_id;
        $wapp_data['browserName'] = "XYZ"; //$this->agent->browser();
        $wapp_data['userAgent'] = $request->header('User-Agent');
        $wapp_data['requestURI'] = "XYZ"; //$this->agent->referrer();
        $wapp_data['timestamp'] = current_mysql_date_time();
        $wapp_data['localTimestamp'] = current_mysql_date_time($user->timezone);
        $wapp_data['timezone'] = $user->timezone;
        $wapp_data['pakTimeDifference'] = $user->pak_time_difference;
        $wapp_data['authAttemptsAllowed'] = $user->auth_attempts_allowed;
        $wapp_data['mobileBrowserAllowed'] = $user->mobile_browser_allowed;
        $wapp_data['geolocationCheck'] = $user->geolocation_check;
        $wapp_data['browserCheck'] = $user->browser_check;
        $wapp_data['frequentIPCheck'] = $user->frequent_ip_check;
        $wapp_data['frequentBrowserCheck'] = $user->frequent_browser_check;
        $wapp_data['timeoutInterval'] = $user->timeout_interval;
        $wapp_data['isMobile'] = is_mobile($request) ? 1 : 0;
        $wapp_data['isReferral'] = 0; //$this->agent->is_referral() ? 1 : 0;
        $wapp_data['robot'] = 0; //$this->agent->robot() ? 1 : 0;

        return $wapp_data;
    }

    public static function get_all_branch_users($app_user_id = NULL) {

        /**
         *
         * 84 - Adnan Malik
         * 60 - Data Entry
         * 49 - Developer
         * 2 - Kashif MD
         * 68 - Khurram
         * 23 - Yahya Khan
         * 69 - Zahid Ejaz
         */
        $users = User::select('id', 'login_id', 'email', 'full_name', 'designation', 'country_id', 'status', 'image_url')
                        ->where('status', 1)
                        ->whereNotIn('id', [84, 60, 49, 2, 68, 23, 69, 356])
                        ->orderBy('login_id')->get();

        return $users;
    }

    private static function getKey() {
        $key = env('ENCRYPTION_KEY');
        if (!$key) {
            throw new Exception("Encryption key is not set in .env");
        }
        return hex2bin($key); // Convert hex key to binary
    }

    public static function encrypt($plainText) {
        $key = self::getKey();
        $iv = random_bytes(16); // Generate a 16-byte random IV

        $encrypted = openssl_encrypt($plainText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new Exception("Encryption failed.");
        }

        return bin2hex($iv) . ":" . bin2hex($encrypted);
    }

    public static function decrypt($encryptedText) {
        $key = self::getKey();

        [$ivHex, $encryptedHex] = explode(':', $encryptedText, 2);

        $iv = hex2bin($ivHex);
        $encrypted = hex2bin($encryptedHex);

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new Exception("Decryption failed.");
        }

        return $decrypted;
    }
}
