<?php

namespace Juggernaut\Module\HospitalCharge\Service;

class ChargeReviewService
{
    public function getPatientEncounters($pid): array
    {
        $encounters = [];
        $result = sqlStatement(
            "SELECT DISTINCT fe.encounter, DATE(fe.date) as date
             FROM form_encounter fe
             JOIN billing b ON b.encounter = fe.encounter
             WHERE fe.pid = ?
             AND b.activity = 1
             AND b.code_type = 'CPT4'
             ORDER BY fe.date DESC LIMIT 10",
            [$pid]
        );

        while ($row = sqlFetchArray($result)) {
            $encounters[] = [
                'id' => $row['encounter'],
                'date' => $row['date']
            ];
        }
        return $encounters;
    }

    public function getProcedures($pid, $encounter): array
    {
        $procedures = [];
        $result = sqlStatement(
            "SELECT code, code_text as description, fee, units, justify, modifier
             FROM billing
             WHERE pid = ? AND encounter = ?
             AND activity = 1 AND code_type = 'CPT4'
             ORDER BY id",
            [$pid, $encounter]
        );

        while ($row = sqlFetchArray($result)) {
            $procedures[] = [
                'code' => $row['code'],
                'description' => htmlspecialchars($row['description'], ENT_QUOTES), // Escape special chars including commas
                'fee' => $row['fee'],
                'units' => $row['units'],
                'modifiers' => $row['modifier'],
                'justify' => $row['justify']
            ];
        }
        return $procedures;
    }
    public function getPatientIssues($pid, $encounter = null): array
    {
        $issues = [];
        $sql = "SELECT code, code_text as description
                FROM billing
                WHERE pid = ? AND activity = 1
                AND code_type = 'ICD10'";

        $params = [$pid];

        if ($encounter) {
            $sql .= " AND encounter = ?";
            $params[] = $encounter;
        }

        $sql .= " ORDER BY id";

        $result = sqlStatement($sql, $params);

        while ($row = sqlFetchArray($result)) {
            $issues[] = [
                'code' => $row['code'],
                'description' => htmlspecialchars($row['description'], ENT_QUOTES) // Escape special chars including commas
            ];
        }
        return $issues;
    }

    public function getPatientName($pid)
    {
        $result = sqlQuery(
            "SELECT CONCAT(fname, ' ', lname) as name
             FROM patient_data WHERE pid = ?",
            [$pid]
        );
        return $result['name'] ?? '';
    }
}
