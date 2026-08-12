<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\Controllers;

use OpenEMR\Common\Database\QueryUtils;

class SearchController
{
    public function findPatient($term)
    {
        // Search for patient
        // Return JSON response
        return PatientSearch::search($term);
    }
    public function findProvider($term)
    {
        // Search for provider
        // Return JSON response
        return ProviderSearch::search($term);
    }
    public function findSingleProvider($term)
    {
        return SingleProviderSearch::search($term);
    }
    public function findCode($type, $term)
    {
        // Search for code
        // Return JSON response
        return CodeSearch::search($type, $term);
    }

    /**
     * Search for previous diagnosis codes used for a patient in the last 6 months
     *
     * @param int $pid Patient ID
     * @return string JSON response containing array of diagnosis codes with descriptions
     */
    public function searchPatientDiagnoses($pid)
    {
        try {
            // Validate pid is a positive integer
            $pid = filter_var($pid, FILTER_VALIDATE_INT);
            if ($pid === false || $pid <= 0) {
                http_response_code(400);
                return json_encode(['error' => 'Invalid patient ID']);
            }

            // Query billing table for distinct justify values from last 6 months
            $sql = "SELECT DISTINCT justify
                    FROM billing
                    WHERE pid = ?
                      AND justify IS NOT NULL
                      AND justify != ''
                      AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";

            $results = QueryUtils::fetchRecords($sql, [$pid]);

            // Extract and deduplicate diagnosis codes from justify strings
            // Format: ICD10|E11.9:ICD10|I10:ICD10|E78.5:
            $diagnosisCodes = [];
            foreach ($results as $row) {
                $justifyString = $row['justify'] ?? '';
                // Split by colon delimiter
                $tokens = explode(':', $justifyString);

                foreach ($tokens as $token) {
                    $token = trim($token);
                    if (empty($token)) {
                        continue;
                    }

                    // Extract code from ICD10|CODE format
                    if (preg_match('/ICD10\|([A-Z0-9\.]+)/i', $token, $matches)) {
                        $code = strtoupper($matches[1]);
                        // Deduplicate (case-insensitive)
                        if (!in_array($code, $diagnosisCodes)) {
                            $diagnosisCodes[] = $code;
                        }
                    }
                }
            }

            // Cap at 200 codes to prevent performance issues
            if (count($diagnosisCodes) > 200) {
                $diagnosisCodes = array_slice($diagnosisCodes, 0, 200);
            }

            // If no codes found, return empty array
            if (empty($diagnosisCodes)) {
                return json_encode([]);
            }

            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($diagnosisCodes), '?'));

            // Lookup descriptions from icd10_dx_order_code table in a single batched query
            $descSql = "SELECT formatted_dx_code AS code,
                               IFNULL(short_desc, long_desc) AS description
                        FROM icd10_dx_order_code
                        WHERE formatted_dx_code IN ($placeholders)";

            $stmt = sqlStatement($descSql, $diagnosisCodes);

            // Build associative array for quick lookup (case-insensitive)
            $descriptions = [];
            while ($row = sqlFetchArray($stmt)) {
                // Trim whitespace from both code and description
                $code = trim($row['code'] ?? '');
                $desc = trim($row['description'] ?? '');
                $descriptions[strtoupper($code)] = $desc;
            }

            // Build final response array
            $response = [];
            foreach ($diagnosisCodes as $code) {
                $response[] = [
                    'code' => $code,
                    'description' => $descriptions[$code] ?? ''
                ];
            }

            return json_encode($response);

        } catch (\Exception $e) {
            error_log("Error in searchPatientDiagnoses: " . $e->getMessage());
            http_response_code(500);
            return json_encode(['error' => 'Failed to retrieve diagnosis codes']);
        }
    }
}
