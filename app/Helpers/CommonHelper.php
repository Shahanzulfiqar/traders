<?php

/**
 * @developedBy JanJapan IT & Support Center
 * @date Jul 12, 2017
 * @version Version 1.0
 * @author Kashif Umar <Kashif.TLB@gmail.com>
 */
function display_admin_debug($data, $app_user_id = NULL, $die = TRUE) {
    $user_ids = [356];
    if (is_null($app_user_id)) {
        $user_data = session('jjp_user_data');
        $app_user_id = $user_data['id'];
    }
    if (in_array($app_user_id, $user_ids)) {
        $type = gettype($data);
//        echo($type);
        switch ($type) {
            case "string":
                echo("$data\n");
                break;
            case "array": case "object":
                kas_pr($data, FALSE);
                break;
            default:
                var_dump($data);
                break;
        }
        echo("<br>");
        if ($die) {
            die;
        }
    }
}

function display_api_admin_debug($data, $app_user_id, $prefix = NULL) {
    $debug_user_ids = [83, 70];
    if (!is_null($prefix) && $prefix = "api" && in_array($app_user_id, $debug_user_ids)) {
        $type = gettype($data);
//        echo($type);
        $response = [];
        $response['data'] = $data;
        return $response;
    }
}

function kas_pr($data, $die = TRUE) {

//    $user_data = session('jjp_user_data');
//    $user_id = $user_data['id'];
//    if ($user_id == 1) {
    echo("<pre>");
    print_r($data);
    echo("</pre>");
//        if ($die) {
//            die;
//        }
//    }
}

function mToday($time_zone = NULL, $format = "Y_m_d_h_i_s_a") {
    if (!is_null($time_zone)) {
        set_time_zone($time_zone);
    }
    $date = date($format);
    if (!is_null($time_zone)) {
        reset_default_time_zone();
    }
    return $date;
}

function php_to_mysql_date($date) {
    if (!is_null($date)) {
        $date = date("Y-m-d", strtotime($date));
    }
    return $date;
}

function mysql_to_php_date($date) {
    $phpDate = $date;
    if (!is_null($date)) {
        $phpDate = date("d-m-Y", strtotime($date));
    }

    return $phpDate;
}

function timestamp_to_date_time($timestamp, $mysql = false) {
    $date_time = null;
    if (!is_null($timestamp)) {
        if (!$mysql) {
            $date_time = date("d-m-Y h:i:s a", $timestamp);
        } else {
            $date_time = date("Y-m-d H:i:s", $timestamp);
        }
    }
    return $date_time;
}

function mysql_to_php_date_time($date_time) {
    if (!is_null($date_time)) {
        $date_time = date("d-m-Y h:i:s a", strtotime($date_time));
    }
    return $date_time;
}

function current_mysql_date_time() {
    date_default_timezone_set("Asia/Karachi");
    $date = date("Y-m-d H:i:s");
    return $date;
}

/**
 *
 * @param type $time_zone (pk, jpn)
 * @return type 01_01_1970_12_10_20_am/pm
 */
function current_date_time($time_zone = "pk") {
    $time_zone = strtolower($time_zone);
    switch ($time_zone) {
        case "pk":
            date_default_timezone_set("Asia/Karachi");
            break;

        case "jpn":
            date_default_timezone_set("Asia/Tokyo");
            break;

        default:
            date_default_timezone_set("Asia/Karachi");
            break;
    }
    $date = date("Y_m_d_h_i_s_a");
    return $date;
}

/**
 *
 * @param type $time_zone (pk, jpn)
 * @return type timestamp int
 */
function current_timestamp($time_zone = "pk") {
    $time_zone = strtolower($time_zone);
    switch ($time_zone) {
        case "pk":
            date_default_timezone_set("Asia/Karachi");
            break;

        case "jpn":
            date_default_timezone_set("Asia/Tokyo");
            break;

        default:
            date_default_timezone_set("Asia/Karachi");
            break;
    }
    $ts = time();
    return $ts;
}

/**
 * The compare_dates() function first converts the two dates to Unix timestamp format
 * using the strtotime() function. It then compares the two timestamps and returns 0
 * if they are equal, 1 if the first date is greater than the second date, and -1
 * if the first date is smaller than the second date.
 * this function assumes that the input dates are in a format that can be parsed by the
 * strtotime() function, such as "YYYY-MM-DD". If the dates are in a different format,
 *  you may need to modify the function accordingly.
 * returns 0 if they are equal,
 * 1 if the first date is greater than the second date,
 * and -1 if the first date is smaller than the second date.
 */
function compare_dates($date1, $date2) {
    $time1 = strtotime($date1);
    $time2 = strtotime($date2);

    if ($time1 == $time2) {
        return 0;
    } elseif ($time1 > $time2) {
        return 1;
    } else {
        return -1;
    }
}

function remove_data_keys(&$data) {
    $keys = ['target', 'request_path', 'is_ajax_request', 'action', 'debug',
        'prefix', 'path_info', 'app_user_id', 'api_token'];
    foreach ($keys as $key) {
        if (isset($data[$key])) {
            unset($data[$key]);
        }
    }
}

function arrange_valdiation_errors($errors) {
    $keys = $errors->keys();
    $new_errors = [];
    foreach ($keys as $key) {
        $new_errors[$key] = $errors->first($key);
    }
//    display_admin_debug($new_errors);
    return $new_errors;
}

function validate_password($password) {
    if (strlen($password) < 8) {
        throw new Exception("*Password should be at least 8 character", -1);
    }
//    $reg_alpha = "/[a-z]/i";
//    $reg_num = "/[0-9]/";
//    if (!preg_match($reg_alpha, $password) || !preg_match($reg_num, $password)) {
//        throw new Exception("*Password must be alpha-numeric", -1);
//    }
}

function validate_mobile_number($number) {
    $reg_number = "/^923\d{9}$/";
    return preg_match($reg_number, $number);
}

function is_decimal($value) {
    return is_numeric($value) && floor($value) != $value;
}

function is_mobile($request) {
    return \App\Repository\Common\AppRepository::is_mobile($request);
}

/* ================================== ADNAN ============================================================ */

function get_today_total_bid_amount($app_user_id = NULL) {
    $auc_repo = new App\Repository\PreAuction\IAUCAuctionRepository();
    $bids_amount = $auc_repo::get_total_bids_value($app_user_id);
    return $bids_amount;
}

function get_available_auctions() {
    $auc_repo = new App\Repository\PreAuction\IAUCAuctionRepository();
    $auctions = $auc_repo::get_available_auctions();
    return $auctions;
}

function get_customer_bid_amount($pad_id, $app_user_id = NULL) {
    $auc_repo = new App\Repository\PreAuction\IAUCAuctionRepository();
    $bids_amount = $auc_repo::get_customer_bid_amount($pad_id, $app_user_id);
    return $bids_amount;
}

function numberFormat($value, $symbol = null, $default = null) {
    if (!$value) {
        $value = $default;
    } elseif (is_numeric($value)) {
        $value = str_replace(',', '', $value);
        $value = number_format($value);

        $symbol && $value = $symbol . $value;
    }

    return $value;
}

function cleanHtmlString($inputString) {
    // First, strip all HTML tags using strip_tags
    $cleanedString = strip_tags($inputString);

    // Then, use a regular expression to remove any remaining HTML attributes
    // This regex pattern matches any string that starts with a space followed by any character except a space,
    // and continues until it finds a space or the end of the string. This effectively removes attributes.
    $cleanedString = preg_replace('/ \S+/', '', $cleanedString);

    return $cleanedString;
}

/* ================================== ADNAN ============================================================ */

function is_api(Illuminate\Http\Request $request) {
    if ($request->is('api/*')) {
        return true;
    }
    return false;
}

if (!function_exists('str_random')) {

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16) {
        return \Illuminate\Support\Str::random($length);
    }

}

if (!function_exists('getBrowser')) {

    /**
     * Get browser name and version from the User-Agent string.
     */
    function getBrowser($userAgent) {
        $browsers = [
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Opera' => 'Opera|OPR',
            'Edge' => 'Edg',
            'IE' => 'MSIE|Trident',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match("/$pattern\/([\d.]+)/i", $userAgent, $matches)) {
                return ['name' => $name, 'version' => $matches[1]];
            }
        }

        return ['name' => 'Unknown', 'version' => 'Unknown'];
    }

}


if (!function_exists('getPlatform')) {

    /**
     * Get platform name and version from the User-Agent string.
     */
    function getPlatform($userAgent) {
        $platforms = [
            'Windows' => 'Windows NT',
            'Mac OS' => 'Mac OS X',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iOS' => 'iPhone|iPad',
        ];

        foreach ($platforms as $name => $pattern) {
            if (preg_match("/" . preg_quote($pattern, '/') . "[\/\s]?([\d._]*)/i", $userAgent, $matches)) {
                return [
                    'name' => $name,
                    'version' => isset($matches[1]) && $matches[1] !== '' ? str_replace('_', '.', $matches[1]) : 'Unknown'
                ];
            }
        }

        return ['name' => 'Unknown', 'version' => 'Unknown'];
    }

}

if (!function_exists('getUserAgentInfo')) {

    function getUserAgentInfo(Illuminate\Http\Request $request) {
        $ipAddress = $request->ip();
        $detect = new Detection\MobileDetect();

        $userAgent = $request->header('User-Agent');

        $browser = getBrowser($userAgent);
        $platform = getPlatform($userAgent);
        $device = $detect->isMobile() ? 'Mobile' : ($detect->isTablet() ? 'Tablet' : 'Desktop');
        $isMobile = $detect->isMobile();
        $isTablet = $detect->isTablet();
        $isDesktop = !$isMobile && !$isTablet;

        if (is_api($request) && !$isMobile && !$isTablet) {
            $isMobile = true;
            $platform['name'] = 'APP';
            $platform['version'] = 1;
        }

        return [
            'ip_address' => $ipAddress,
            'browser' => $browser['name'],
            'browser_version' => $browser['version'],
            'platform' => $platform['name'],
            'platform_version' => $platform['version'],
            'device' => $device,
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'is_desktop' => $isDesktop,
            'is_robot' => $detect->is('bot'),
            'user_agent' => $userAgent,
        ];
    }

}

function check_rate_mun($rate_mun, $remarks) {
    if (is_numeric($rate_mun) && $rate_mun >= 0 && $rate_mun <= 500 && (preg_match('/^\d+$/', $rate_mun))) {
        return true;
    }
    if (empty($rate_mun) || stristr($rate_mun, 'nashi') || stristr($remarks, 'nashi')) {
        return true;
    }
    return false;
}

function convertToPKT($utcTime) {
    $date = new DateTime($utcTime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Karachi'));
    return $date->format('d-m-Y H:i:s');
}

?>
