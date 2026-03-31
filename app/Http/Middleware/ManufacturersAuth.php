<?php

namespace App\Http\Middleware;

use Closure;
use App\Repository\Users\UserAccessRepository as UAR;
use App\Repository\Auctions\AuctionRepository as AucRepo;

class ManufacturersAuth {

    use BaseAuth;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $action_data = self::prepare_request($request);
        extract($action_data);

        switch ($uri) {

            //'Auctions@branch_auctions_data');
            case 'manufacturers':
            case 'api/manufacturers':
                $can_view = UAR::can_view("ManufacturerController", "index", $app_user_id);
                
                if (!$can_view) {
                    if (is_null($prefix)) {
                        return redirect('/');
                    } else {
                        if ($prefix == "api") {
                            return screen_error();
                        }
                    }
                }
                break;

            //'Auctions@get_branch_auctions_data');
            case 'manufacturers/data':
            case 'api/manufacturers/data':
                $can_view = UAR::can_view("ManufacturerController", "index", $app_user_id);
                if (!$can_view) {
                    return can_view_error();
                }
               
                break;

            //'Auctions@delete_auctions_data_super');
            case 'delete_bids_super':
            case 'api/delete_bids_super':
                $can_delete = UAR::can_delete("Auctions", "branch_auctions_data", $app_user_id);
                if (!$can_delete) {
                    return can_delete_error();
                }

                $rules = [
                    'auction_date' => 'required|date',
                ];
                $messages = [
                    'auction_date.required' => '*Required',
                    'auction_date.date' => '*Not A Valid Date',
                ];

                $response = self::process_validation($request->all(), $rules, $messages);

                if (count($response)) {
                    return response($response);
                }

                break;

            //'Auctions@all_auctions_data'
            case 'auction_report':
            case 'api/admin_auctions_master':
                $can_view = UAR::can_view("Auctions", "all_auctions_data", $app_user_id);
                if (!$can_view) {
                    if (is_null($prefix)) {
                        return redirect('/');
                    } else {
                        if ($prefix == "api") {
                            return screen_error();
                        }
                    }
                }
                break;

            case 'api/admin_auctions_data_app_storage':
                $can_view = UAR::can_view("Auctions", "all_auctions_data", $app_user_id);
                $can_view2 = UAR::can_view_offline_auction($app_user_id);
                if (!$can_view && !$can_view2) {
                    return can_view_error();
                }
                break;
            //'Auctions@get_all_auctions_data');
            case 'get_auction_report':
            case 'api/admin_auctions_data':
                $can_view = UAR::can_view("Auctions", "all_auctions_data", $app_user_id);
                if (!$can_view) {
                    return can_view_error();
                }
                break;
            case 'generate_pdf_auction_report':
            case 'api/generate_pdf_auction_report':
                $can_view = UAR::can_view("Auctions", "all_auctions_data", $app_user_id);
                if (!$can_view) {
                    return can_view_error();
                }
                break;
            //'Auctions@get_other_bids');
            case 'gob':
            case 'api/get_other_bids':
                $can_compare_bids = UAR::can_compare_bids($app_user_id);
                if (!$can_compare_bids) {
                    return can_compre_error();
                }
                break;

            //'Auctions@get_other_bids_service');
            case 'api/gabs':

                $can_view = UAR::can_view("Auctions", "all_auctions_data", $app_user_id);
                if (!$can_view) {
                    return screen_error();
                }

                $can_compare_bids = UAR::can_compare_bids($app_user_id);
                if (!$can_compare_bids) {
                    return can_compre_error();
                }

                $rules = [
                    'auction_date' => 'required|date',
                    'lot_no' => 'required',
                    'auction_company_id' => 'required',
//                    'country_id' => 'required',
                ];
                $messages = [
                    'auction_date.required' => '*Required',
                    'auction_date.date' => '*Not A Valid Date',
                    'lot_no.required' => '*Required',
                    'auction_company_id.required' => '*Required',
//                    'country_id.required' => '*Required',
                ];

                $response = self::process_validation($request->all(), $rules, $messages);

                if (count($response)) {
                    return response($response);
                }

                break;

            // Auctions@delete_auction_bid');
            case 'dab':
            case 'api/remove_auction_bid':
                if (!UAR::can_delete("Auctions", "auctions_data", $app_user_id) && !UAR::can_delete("Auctions", "branch_auctions_data", $app_user_id)) {
                    return can_delete_error();
                }
                break;

            //'Auctions@update_mun_rates');
            case 'umr':
            case 'api/test_save_mun_rate':
            case 'api/save_mun_rate':
            case 'api/update_branch_rate':
            case 'api/update-branch-rate-v2':
                if (!UAR::can_edit("Auctions", "auctions_data", $app_user_id) && !UAR::can_edit("Auctions", "branch_auctions_data", $app_user_id)) {
                    return can_edit_error();
                }

                $auction_data_id = $request->input('auction_data_id');
                if ($uri == 'api/update_branch_rate' || $uri == 'api/update-branch-rate-v2') {

                    if (is_null($auction_data_id) || !is_numeric($auction_data_id) || $auction_data_id <= 0) {
                        return response([
                            'msg' => '*Auction Data Id Missing/Invalid',
                            'error' => TRUE,
                        ]);
                    }
                }

                break;

            //'update_is_max_rate');
            case 'update_max_rate':
            case 'api/update_max_rate':

                if (!UAR::can_update_is_max_rate($app_user_id)) {
                    return can_update_is_max_rate_error();
                }

                $rules = [
                    'auction_date' => 'required|date',
                ];
                $messages = [
                    'auction_date.required' => '*Required',
                    'auction_date.date' => '*Not A Valid Date',
                ];

                $response = self::process_validation($request->all(), $rules, $messages);

                if (count($response)) {
                    return response($response);
                }
                break;

            //'Auctions@get_auction_companies');
            case 'get_auction_companies':
            case 'api/branch_auc_companies': //Auctions@get_auction_companies
            case 'api/admin_auc_companies': //Auctions@get_auction_companies
            case 'branch_auction_models':
            case 'api/branch_auction_models':
            case 'admin_auction_models':
            case 'api/admin_auction_models':

                //no authentication
                break;

            /**
              //Auctions@lock_unlock_auction');
              case 'lua':
              break;

              //'Auctions@add_auction_data'
              case 'add_auction':
              break;

              //', 'Auctions@store');

              case 'uad':
              break;
             *
             */
            default:
                return permission_error();
                break;
        }
        return $next($request);
    }
}
