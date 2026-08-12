<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\Controllers;

class PatientSearch
{
    public static function search($term)
    {
        // Check if the search term is a date entry
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $term)) {
            // Convert MM/DD/YYYY to YYYY-MM-DD
            $dateParts = explode('-', $term);
            $formattedDob = "{$dateParts[2]}-{$dateParts[0]}-{$dateParts[1]}";  // YYYY-MM-DD format
            // Check if the date is valid
            if (!strtotime($formattedDob)) {
                return json_encode([]);
            }
            $stmt = sqlStatement("SELECT * FROM patient_data WHERE DOB = ?", [$formattedDob]);
        } else {
            // Search for patient
            $searchTerm = '%' . $term . '%';
            $sql = "SELECT * FROM patient_data WHERE fname LIKE ? OR lname LIKE ? OR pid LIKE ?";
            $stmt = sqlStatement($sql, [$searchTerm, $searchTerm, $searchTerm]);
        }
        $patients = [];
        while ($row = sqlFetchArray($stmt)) {
            $patients[] = [
                'id' => $row['pid'],
                'name' => $row['fname'] . ' ' . $row['lname'],
                'dob' => $row['DOB']
            ];
        }
        return json_encode($patients);
    }
}
