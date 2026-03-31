<?php

/**
 * @developedBy HiFuzTech
 * @date December 11, 2024
 * @version Version 2.0
 * @author Kashif Umar <Kashif.TLB@gmail.com>
 */
use Illuminate\Support\Facades\Session;
use App\Repository\Users\UserAccessRepository as UAR;
use App\Repository\Users\UserRepository as UserRepo;
use App\Repository\Users\User;

function is_super_admin($app_user_id, $prefix = null) {
        
    if (is_null($prefix) && Session::has('is_super_admin')) {    
        $is_super_admin = session('is_super_admin');
    } else {        
        $is_super_admin = UAR::is_super_admin($app_user_id, $prefix);
    }
    return $is_super_admin;
}

function is_dev_admin($app_user_id, $prefix = null) {

//    if (!$is_api && Session::has('is_dev_admin')) {
//        $is_dev_admin = session('is_dev_admin');
//    } else {
    $is_dev_admin = UAR::is_dev_admin($app_user_id);
//    }
    return $is_dev_admin;
//    return false;
}

function get_user_data($app_user_id = null) {
    $obj_user = new UserRepo();
    $user_data = $obj_user->get_user_data($app_user_id);
//    display_admin_debug($user_data);
    return $user_data;
}

function get_user_id() {
//    $app_user_id = env("APP_USER_ID");
    return UserRepo::get_user_id();
//    return $app_user_id;
}

function full_name($app_user_id = NULL, $prefix = null) {
//    if (!is_null($prefix)) {
//        $app_user_id = get_user_id();
//        $full_name = session('full_name');
//    } else {
    $user_data = User::where('id', $app_user_id)
                    ->select(['first_name', 'last_name'])->get();
    $user = $user_data[0];
    $full_name = $user->first_name . " " . $user->last_name;
//    }
//    $full_name = "Developer";
    return $full_name;
}

function get_logo($type) {
    $logo = url('public/img/logo.png');
    /**
      if ($type == 1) {
      $logo = session('logo');
      } else {
      $logo = session('logo_small');
      }
     *
     */
    return url("$logo");
}

function get_user_email($app_user_id) {
    $user_data = User::where('id', $app_user_id)
                    ->select(['email'])->get();
    $user = $user_data[0];
    return $user->email;
}

function get_user_image_url() {
//    $image_url = session('image_url');
//    if (is_null($image_url)) {
    $image_url = "public/img/default_pic.png";
//    }
    return url($image_url);
}

function timezone($app_user_id = NULL) {
    $timezone = env("TIME_ZONE");
    /**
      if (is_null($app_user_id)) {
      $timezone = session('timezone');
      } else {
      $office = App\Repository\Office\Office::where('user_id', $app_user_id)
      ->select(['country_id'])->get();
      $country_id = $office[0]->country_id;
      $country = \App\Repository\App\Country::where('id', $country_id)
      ->select(['timezone'])->get();
      $timezone = $country[0]->timezone;
      }
     *
     */
    return $timezone;
}

function dialing_code($app_user_id = NULL) {
    $dialing_code = "0092";
    /**
      if (is_null($app_user_id)) {
      $dialing_code = session('dialing_code');
      } else {
      $office = App\Repository\Office\Office::where('user_id', $app_user_id)
      ->select(['country_id'])->get();
      $country_id = $office[0]->country_id;
      $country = \App\Repository\App\Country::where('id', $country_id)
      ->select(['dialing_code'])->get();
      $dialing_code = $country[0]->dialing_code;
      }
     *
     */
    return $dialing_code;
}

?>