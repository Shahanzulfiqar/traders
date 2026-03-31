<?php

/**
 * @developedBy JanJapan IT & Support Center
 * @date Jul 10, 2017
 * @version Version 1.0
 * @author Kashif Umar <Kashif.TLB@gmail.com>
 */
function load_menus() {
    $obj_user = new App\Repository\Users\UserRepository();
    $menus = $obj_user->get_menus();
    display_menus($menus);
}

function display_menus($menus) {
    foreach ($menus as $menu) {
        if ($menu->menu_type == "expand") {
            echo("\n<li class='treeview'><a href='#'>"
            . "\n<i class='$menu->css_class parent-menu'></i><span>$menu->display_name</span>"
            . "\n<span class='pull-right-container'>"
            . "\n<i class='fa fa-angle-left pull-right'></i></span>"
            . "\n</a>"
            . "\n<ul class='treeview-menu'>");
            display_menus($menu->child_menus);
            echo("\n</ul>"
            . "\n</li>");
        } else {
            echo("\n<li><a href='" . url($menu->uri) . "'>");
            if ($menu->id != 3 && $menu->id != 6) {
                echo("\n<i class='$menu->css_class'></i>$menu->display_name</a></li>");
            } else {
                echo("\n<i class='$menu->css_class'></i><span>$menu->display_name</span></a></li>");
            }
        }
    }
}

function load_child_options($menu, $level = 0) {

    echo("\n<table class='table table-bordered'>");

    foreach ($menu as $m) {
        if ($m->menu_type == "open") {
            echo("\n<tr class='child_row lvl_" . $level . "'>"
            . "\n<td>"
            . "\n"
            . "<div class='row'>"
            . "<div class='col-xs-12'>"
            . "<input type='checkbox' class='child chk' name='menu_ids[$m->id][]' value='1' data-id='$m->id' data-parent-id='$m->parent_menu_id'> "
            . "$m->display_name</div>"
            );

            echo("<div class='col-xs-2'>"
            . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='2' data-parent-id='$m->id'>"
            . " Add</div>"
            . "<div class='col-xs-2'>"
            . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='3' data-parent-id='$m->id'>"
            . " Edit</div>"
            . "<div class='col-xs-2'>"
            . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='4' data-parent-id='$m->id'>"
            . " Delete</div>");
            switch ($m->id) {
                /**
                 * Japan Office - 5
                 * Admin Final Bidding List - 6
                 */
                case 5: case 6:

                    echo("<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='5' data-parent-id='$m->id'>"
                    . " Can Compare</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='8' data-parent-id='$m->id'>"
                    . " Update Is Max Rate</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='9' data-parent-id='$m->id'>"
                    . " View Extra Rate</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='10' data-parent-id='$m->id'>"
                    . " Buying Team</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='13' data-parent-id='$m->id'>"
                    . " Buying Team PDF</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='14' data-parent-id='$m->id'>"
                    . " Buying Team Auctions PDF(s)</div>"
                    . "<div class='col-xs-2' title='Excel sheet of Orix , NPS, Zero, Lum'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='15' data-parent-id='$m->id'>"
                    . " Buying Team JPN XLS</div>"
                    . "");

                    break;

                /**
                 * View Bidding List
                 */
                case 4:
                    echo(""
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='6' data-parent-id='$m->id'>"
                    . " Apply Expense</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='7' data-parent-id='$m->id'>"
                    . " View Auction Info</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='11' data-parent-id='$m->id'>"
                    . " Can Transfer</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='12' data-parent-id='$m->id'>"
                    . " Can View Pre Auc Bids</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='19' data-parent-id='$m->id'>"
                    . " Verify Bid Prices </div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='23' data-parent-id='$m->id'>"
                    . " Reduced Price</div>"
                    . "");

                    break;

                /**
                 * Branch Final Bidding List
                 */
                case 3:
                    echo(""
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='20' data-parent-id='$m->id'>"
                    . " Super Edit</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='22' data-parent-id='$m->id'>"
                    . " Reduced Price</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='24' data-parent-id='$m->id'>"
                    . " Download PDF</div>"
                    . "<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='26' data-parent-id='$m->id'>"
                    . " View Corrected</div>"
                    . "");

                    break;
                /**
                 * Archive View Bidding List -
                 */
                case 43:

                    echo("<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='18' data-parent-id='$m->id'>"
                    . " Fetch API Info</div>"
                    . "");

                    break;

                /**
                 * Not Bid Cars - 61
                 */
                case 61:

                    echo("<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value=16' data-parent-id='$m->id'>"
                    . " Fetch API Info</div>"
                    . "");

                    break;

                /**
                 * Not Purchased Cars - 61
                 */
                case 60:

                    echo("<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value=17' data-parent-id='$m->id'>"
                    . " Fetch API Info</div>"
                    . "");

                    break;

                case 73: case 80:

                    echo("<div class='col-xs-2'>"
                    . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='25' data-parent-id='$m->id'>"
                    . " Enable/Disable</div>"
                    . "");

                    break;
            }
            echo("\n</div>\n</td>\n</tr>");
        } else if ($m->menu_type == "expand") {
            echo("\n<tr  class='parent_inner'>\n<th>"
            . "<input type='checkbox' class='sub_parent' name='menu_ids[$m->id][]' value='1' data-id='$m->id'  data-parent-id='$m->parent_menu_id'> "
            . "$m->display_name<span class='btn glyphicon glyphicon-triangle-bottom'></span></th>\n</tr>\n<tr class='child_inner'>\n<td>");

            load_child_options($m->child_menus, ($level + 1));
            echo("\n</td>\n</tr>");
        }
    }
    echo("\n</table>\n");
}

function load_child_options_with_actions($menu, $level = 0, $actions = []) {

    echo("\n<table class='table table-bordered'>");

    foreach ($menu as $m) {
        if ($m->menu_type == "open") {
            echo("\n<tr class='child_row lvl_" . $level . "' data-menu-id='$m->parent_menu_id' data-id='$m->id'>"
            . "\n<td>"
            . "\n"
            . "<div class='row'>"
            . "<div class='col-xs-12'>"
            . "$m->display_name</div>"
            );

            foreach ($actions as $action) {
                echo("<div class='col-xs-2'>"
                . "<input type='checkbox' class='action' name='menu_ids[$m->id][]' value='$action->action_id' data-parent-id='$m->id'>"
                . " $action->name</div>");
            }

            echo("\n</div>\n</td>\n</tr>");
        } else if ($m->menu_type == "expand") {
            echo("\n<tr  class='parent_inner' data-menu-id='$m->parent_menu_id' data-id='$m->id'>\n<th>"
            . "$m->display_name<span class='sub_parent btn glyphicon glyphicon-triangle-bottom'></span></th>\n</tr>\n<tr class='child_inner'>\n<td>");

            load_child_options_with_actions($m->child_menus, ($level + 1), $actions);
            echo("\n</td>\n</tr>");
        }
    }
    echo("\n</table>\n");
}

/*
function get_menus($parent_menu_id = 0) {

    $app_user_id = session('jjp_user_id');

    $query = "SELECT m.id, m.function_name, m.display_name, "
            . " m.controller_name, m.uri, m.parent_menu_id, m.sort_no, "
            . " m.menu_type, m.menu_level, m.css_class "
            . " FROM menus m ";

    if (!App\Repository\Users\UserAccessRepository::is_super_admin($app_user_id)) {
        $query .= " JOIN roles_menus_permissions rmp "
                . " ON m.id = rmp.menu_id AND rmp.permission_id = 1 "
                . " JOIN users_roles ur "
                . " ON ur.role_id = rmp.role_id AND ur.user_id = $app_user_id "
                . " and ur.deleted_at is null ";
    }

    $query .= " WHERE m.deleted_at IS NULL and m.menu_type <> 'child' "
            . " AND m.is_active AND m.parent_menu_id = $parent_menu_id "
            . " group by m.id "
            . " ORDER BY m.sort_no ASC";

    $menus = \Illuminate\Support\Facades\DB::select($query);

    foreach ($menus as $menu) {
        if ($menu->menu_type == "expand") {
            $menu->child_menus = get_menus($menu->id, $app_user_id);
        }
    }
    return $menus;
}
 *
 */
