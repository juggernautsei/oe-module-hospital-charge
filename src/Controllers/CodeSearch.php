<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\Controllers;

class CodeSearch
{
    public static function search($type, $term)
    {
        $searchTerm = '%' . $term . '%';

        // Search for codes table for cpt4 codes
        if ($type === 'CPT4') {
            $sql = "SELECT c.id, c.code, c.code_text, p.pr_price, p.pr_level "
                . "FROM codes c "
                . "LEFT JOIN prices p ON c.id = p.pr_id "
            . "WHERE (c.code LIKE ? "
                . "OR c.code_text LIKE ?) "
                ."AND code_type = ?";
            $stmt = sqlStatement($sql, [$searchTerm, $searchTerm, 1]);
        } else if ($type === 'ICD10') {
            // Search for codes table for icd10 codes
            $sql = "SELECT dx_id, formatted_dx_code, short_desc FROM icd10_dx_order_code WHERE (formatted_dx_code LIKE ? OR short_desc LIKE ?) AND active = 1";
            $stmt = sqlStatement($sql, [$searchTerm, $searchTerm]);
        } else {
            // search hcpcs codes
            $sql = "SELECT code, code_text FROM codes WHERE code LIKE ? OR codes.code_text LIKE ? AND code_type = ?";
            $stmt = sqlStatement($sql, [$searchTerm, $searchTerm, 3]);
        }

        $codes = [];
        if ($type === 'ICD10') {
            while ($row = sqlFetchArray($stmt)) {
                $codes[] = [
                    'id' => $row['dx_id'],
                    'code' => $row['formatted_dx_code'],
                    'description' => $row['short_desc'],
                    'price' => 0,
                    'pr_level' => 0,
                    'type' => $type
                ];
            }

            return json_encode($codes);
        }
        while ($row = sqlFetchArray($stmt)) {
            $codes[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'description' => $row['code_text'],
                'price' => $row['pr_price'],
                'pr_level' => $row['pr_level'],
                'type' => $type
            ];
        }

        return json_encode($codes);
    }
}
