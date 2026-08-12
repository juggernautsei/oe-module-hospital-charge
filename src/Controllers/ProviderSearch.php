<?php
/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\Controllers;

class ProviderSearch
{
    public static function search($term)
    {
        // Search for authorized providers only
        $searchTerm = '%' . $term . '%';
        $sql = "SELECT * FROM users WHERE (fname LIKE ? OR lname LIKE ?) AND authorized = 1 AND active = 1";
        $stmt = sqlStatement($sql, [$searchTerm, $searchTerm]);
        $providers = [];
        while ($row = sqlFetchArray($stmt)) {
            $providers[] = [
                'id' => $row['id'],
                'name' => $row['fname'] . ' ' . $row['lname']
            ];
        }
        return json_encode($providers);
    }
}
