<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\Controllers;

class SingleProviderSearch
{

    public static function search($term)
    {
        //Find a single Provider
        $sql = "SELECT fname, lname FROM users WHERE id = ?";
        $providerName = sqlQuery($sql, [$term]);
        return json_encode($providerName);

    }
}
