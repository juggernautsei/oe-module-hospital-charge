<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\Controllers;

use OpenEMR\Billing\BillingUtilities;
use Juggernaut\Module\HospitalCharge\App\Model\HospitalModel;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Common\Csrf\CsrfUtils;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HospitalBillingController
{
    private array $claim;
    private BillingUtilities $claimProcessor;
    private HospitalModel $data;
    private Environment $template;
    private TwigContainer $twig;

    public function __construct()
    {
        $this->claim = [];
        $this->claimProcessor = new BillingUtilities();
        $this->data = new HospitalModel();
        $this->twig = new TwigContainer(dirname(__FILE__, 3) . '/templates', $GLOBALS["kernel"]);
        $this->template = $this->twig->getTwig();
    }

    /**
     * Process hospital claim submission
     * Checks for existing encounter and creates one if needed
     *
     * @return string
     */
    public function processHospitalClaim(): string
    {
        if (!CsrfUtils::verifyCsrfToken($_POST['csrf_token_form'] ?? '')) {
            return json_encode(['error' => xl('Authentication Error')]);
        }

        //Validate and process the submitted form data
        $this->claim = $_POST;
        if (empty($this->claim['facility'])) {
            return json_encode(['error' => 'No facility data provided']);
        }

        try {
            // Check for existing encounter or create a new one
            $encounterId = $this->checkOrCreateEncounter(
                $this->claim['selected_patient_id'],
                $this->claim['selected_provider_id'],
                $this->claim['servicedate']
            );

            if (!$encounterId) {
                throw new \Exception('Encounter creation failed');
            }

            // Store billing data for diagnosis codes
            if (!empty($this->claim['diagnosis_list'])) {
                foreach ($this->claim['diagnosis_list'] as $index => $diagnosisCode) {
                    if (empty($diagnosisCode)) {
                        continue;
                    }

                    $diagnosisText = $this->claim['diagnosis_text'][$index] ?? '';

                    // Step 1: Add billing entry using core method
                    $billingId = $this->claimProcessor::addBilling(
                        $encounterId,
                        'ICD10',
                        $diagnosisCode,
                        $diagnosisText,
                        $this->claim['selected_patient_id'],
                        1,
                        $this->claim['selected_provider_id'],
                        '',
                        '',
                        0,
                        '',
                        ''
                    );

                    // Step 2: Update the billing entry with hospital_date
                    if ($billingId) {
                        // For diagnosis codes, use the first procedure's date of service if available
                        // or fall back to the global service date
                        $diagnosisDate = !empty($this->claim['dos'][0]) ?
                            date('Y-m-d', strtotime($this->claim['dos'][0])) :
                            date('Y-m-d', strtotime($this->claim['servicedate']));

                        sqlQuery(
                            "UPDATE billing SET hospital_date = ? WHERE id = ?",
                            [$diagnosisDate, $billingId]
                        );
                    }
                }
            }

            // Store billing data for procedure codes
            if (!empty($this->claim['procedure_list'])) {
                foreach ($this->claim['procedure_list'] as $index => $procedureCode) {
                    if (empty($procedureCode)) {
                        continue;
                    }

                    $procedureText = $this->claim['procedure_text'][$index] ?? '';
                    $procedureFee = $this->claim['procedure_fee'][$index] ?? 0;
                    $procedureModifier = $this->claim['procedure_modifier'][$index] ?? '';
                    $procedureUnits = $this->claim['procedure_units'][$index] ?? 1;

                    // If fee is 0 or empty, lookup the price from the database
                    if (empty($procedureFee) || $procedureFee == 0) {
                        $procedureFee = $this->lookupPriceForCode($procedureCode);
                    }

                    // Get justification codes (diagnosis pointers)
                    $justify = '';
                    if (!empty($this->claim['procedure_justify'][$index])) {
                        $justify = $this->claim['procedure_justify'][$index];
                    }

                    // Step 1: Add billing entry using core method
                    $billingId = $this->claimProcessor::addBilling(
                        $encounterId,
                        'CPT4',
                        $procedureCode,
                        $procedureText,
                        $this->claim['selected_patient_id'],
                        1,
                        $this->claim['selected_provider_id'],
                        $procedureModifier,
                        $procedureUnits,
                        "$procedureFee",
                        '',
                        $justify
                    );

                    // Step 2: Update the billing entry with hospital_date
                    if ($billingId) {
                        // Use the individual date of service for each procedure row
                        // instead of the global service date
                        $individualDOS = !empty($this->claim['dos'][$index]) ?
                            date('Y-m-d', strtotime($this->claim['dos'][$index])) :
                            date('Y-m-d', strtotime($this->claim['servicedate']));

                        sqlQuery(
                            "UPDATE billing SET hospital_date = ? WHERE id = ?",
                            [$individualDOS, $billingId]
                        );
                    }
                }
            }

            // Return success response
            $populate = $this->data->getHospitalData();
            $populate['hospital_data'] = [
                'status' => 'success',
                'message' => 'Hospital claim created successfully',
                'encounter' => $encounterId,
                'facility' => $this->claim['facility'],
                'date' => $this->claim['servicedate'],
                'provider' => $this->claim['selected_provider_id'],
                'billing_facility' => $this->claim['billing_facility'],
            ];
            return $this->template->render('hospital/hospital.twig', $populate);
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if an encounter exists for the patient on the given date
     * If not, create a new encounter
     *
     * @param int $pid Patient ID
     * @param int $provider_id Provider ID
     * @param string $service_date Service date
     * @return int The encounter ID
     */
    private function checkOrCreateEncounter($pid, $provider_id, $service_date): int
    {
        // First check if an encounter already exists for this patient on this date
        $existingEncounter = $this->getExistingEncounter($pid, $service_date);

        if ($existingEncounter) {
            return $existingEncounter;
        }

        // No existing encounter found, create a new one
        return $this->createEncounter($pid, $provider_id, $service_date);
    }

    /**
     * Get existing encounter for patient on the given date
     *
     * @param int $pid Patient ID
     * @param string $service_date Service date
     * @return int|null Encounter ID if found, null otherwise
     */
    private function getExistingEncounter($pid, $service_date): ?int
    {
        $date = date('Y-m-d', strtotime($service_date));
        $sql = "SELECT encounter FROM form_encounter WHERE pid = ? AND DATE(date) = ? ORDER BY encounter DESC LIMIT 1";
        $result = sqlQuery($sql, [$pid, $date]);

        return $result['encounter'] ?? null;
    }

    /**
     * Create a new encounter for the patient
     *
     * @param int $pid Patient ID
     * @param int $provider_id Provider ID
     * @param string $service_date Service date
     * @return int The new encounter ID
     */
    private function createEncounter($pid, $provider_id, $service_date): int
    {
        require_once dirname(__DIR__, 6) . "/library/forms.inc.php";
        $conn = $GLOBALS['adodb']['db'];
        $encounter = $conn->GenID("sequences");
        $facilityName = $this->getFacilityName($this->claim['facility']);
        $facility_id = $this->claim['facility'];
        
        // Validate that billing facility has taxonomy for X12 generation
        $billingFacilityTaxonomy = $this->getBillingFacilityTaxonomy($this->claim['billing_facility']);
        if (empty($billingFacilityTaxonomy)) {
            error_log("Warning: Billing facility ID {$this->claim['billing_facility']} has no taxonomy code set. X12 claims may fail validation.");
        }

        addForm(
            $encounter,
            "Auto Generated Hospital Encounter",
            sqlInsert(
                "INSERT INTO form_encounter SET " .
                "date = ?, " .
                "onset_date = '', " .
                "reason = ?, " .
                "sensitivity = 'normal', " .
                "referral_source = '', " .
                "pid = ?, " .
                "encounter = ?, " .
                "provider_id = ?," .
                "facility = ?," .
                "facility_id = ?," .
                "billing_facility = ?," .
                "pc_catid = 9," .
                "pos_code = 21", // 21 is the POS code for Inpatient Hospital (different from 11 used in rapid billing)

                array(
                    date('Y-m-d H:i:s', strtotime($service_date)),
                    "Generated encounter for hospital visit",
                    $pid,
                    $encounter,
                    ($provider_id ?? ''),
                    $facilityName,
                    ($facility_id ?? ''),
                    $this->claim['billing_facility']
                )
            ),
            "newpatient",
            $pid,
            0,
            date('Y-m-d'),
            'SYSTEM'
        );
        return $encounter ?: 0;
    }

    /**
     * Get facility name by ID
     *
     * @param int $facility_id Facility ID
     * @return string Facility name
     */
    private function getFacilityName($facility_id): string
    {
        $sql = "SELECT `name` FROM `facility` WHERE `id` = ?";
        $result = sqlQuery($sql, [$facility_id]);
        return $result['name'] ?? '';
    }

    /**
     * Lookup price for a CPT code from the codes/prices tables
     *
     * @param string $code CPT code
     * @return float Price for the code, 0.00 if not found
     */
    private function lookupPriceForCode($code): float
    {
        $sql = "SELECT c.id, p.pr_price "
            . "FROM codes c "
            . "LEFT JOIN prices p ON c.id = p.pr_id "
            . "WHERE c.code = ? AND c.code_type = 1 "
            . "LIMIT 1";
        $result = sqlQuery($sql, [$code]);
        
        return (float)($result['pr_price'] ?? 0.00);
    }

    /**
     * Get billing facility taxonomy for X12 PRV segment validation
     *
     * @param int $facility_id Facility ID
     * @return string Facility taxonomy code
     */
    private function getBillingFacilityTaxonomy($facility_id): string
    {
        $sql = "SELECT `facility_taxonomy` FROM `facility` WHERE `id` = ?";
        $result = sqlQuery($sql, [$facility_id]);
        return $result['facility_taxonomy'] ?? '';
    }
}
