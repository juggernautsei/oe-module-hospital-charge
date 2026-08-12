<?php

/*
 *   package   OpenEMR
 *   link      http://www.open-emr.org
 *   author    Sherwin Gaddis <sherwingaddis@gmail.com>
 *   Copyright (c)
 *   All rights reserved
 */
namespace Juggernaut\Module\HospitalCharge\App\Menu;

use OpenEMR\Menu\MenuEvent;
use stdClass;
class MenuItemInjection
{
    public MenuEvent $event;
    public function HospitalMenuItem(): MenuEvent
    {
        $menu = $this->event->getMenu();
        $menuItem = new stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'hos';
        $menuItem->menu_id = 'hos0';
        $menuItem->label = xlt("Rapid Charge");
        $menuItem->url = "/interface/modules/custom_modules/oe-module-hospital-charge/public/index.php/home";
        $menuItem->children = [];
        // Fees workflow; keep in sync with public/index.php ACL gate.
        $menuItem->acl_req = ['patients', 'docs'];
        $menuItem->global_req = [];

        foreach ($menu as $item) {
            if ($item->menu_id == 'feeimg') {
                $item->children[] = $menuItem;
                break;
            }
        }

        $this->event->setMenu($menu);

        return $this->event;

    }
}
