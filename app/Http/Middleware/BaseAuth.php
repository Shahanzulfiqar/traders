<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Validator;
use App\Repository\Users\UserRepository as UserRepo;
trait BaseAuth {

    protected static function prepare_request($request) {
        $route = $request->route();
        $uri = $route->uri;
        $app_user_id = $request->input('app_user_id');
        $app_id = $request->input('app_id');
        $prefix = "api";
        $is_api = true;
        if (!is_api($request)) {
            $app_user_id = UserRepo::get_user_id();
            $is_api = false;
            $prefix = null;
        }
        $path_info = $request->getPathInfo();

        $request_data['path_info'] = str_replace("/api", "", $path_info);
        $request_data['prefix'] = $prefix;
        $request_data['app_user_id'] = $app_user_id;
        $request_data['app_id'] = $app_id;
        $request_data['uri'] = $uri;
        return $request_data;

        //return $next($request);
    }

    public static function process_validation($request_data, $rules, $messages) {
        $response = [];
        $validator = Validator::make($request_data, $rules, $messages);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $new_errors = arrange_valdiation_errors($errors);
            $response['errors'] = $new_errors;
            $response['rows'] = [];
            $response['data'] = [];
            $response['total'] = 0;
            $response['error'] = TRUE;
            $response['success'] = FALSE;
            $response['recordsTotal'] = 0;
            $response['recordsFiltered'] = 0;
            $response['msg'] = "*Check Your Errors";
            $response['draw'] = isset($request_data['draw']) ? $request_data['draw'] : 1;
        }
        return $response;
    }

    protected static function collate_error_messages($errors) {
        $msg = "<ul class='list-group errors-msgs-group'>";
        foreach ($errors as $key => $error) {
            $msg .= "<li class='list-group-item'>";
            if (!stristr($error, "_error")) {
                $error = str_replace("*", "", $error);
                switch ($key) {
                    case "project_id":
                        $key = "project";
                        break;
                }
                $key = str_replace("_", " ", $key);
                $key = ucwords($key);
                $msg .= $key . " " . $error;
            } else if (stristr($error, "_error")) {
                $msg .= $error;
            }
        }
        $msg .= "</li>";

        $msg .= "</ul>";
        return $msg;
    }
}
