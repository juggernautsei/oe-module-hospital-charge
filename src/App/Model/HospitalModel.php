<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\App\Model;

use OpenEMR\Services\FacilityService;
use OpenEMR\Common\Csrf\CsrfUtils;

/**
 *
 */
class HospitalModel
{
    private string $webroot;
    private string|false $csrfToken;

    public function __construct()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $this->webroot = $protocol . $_SERVER["HTTP_HOST"] . $GLOBALS["webroot"];
        $this->csrfToken = CsrfUtils::collectCsrfToken();
    }

    private function getFacilities(): array
    {
        $facilityService = new FacilityService();
        return $facilityService->getAllFacility();
    }

    private function getBusinessEntity(): array
    {
        $facilityService = new FacilityService();
        return $facilityService->getAllBillingLocations();
    }
    public function getHospitalData(): array
    {
        $facilities = $this->getFacilities();
        $businessEntity = $this->getBusinessEntity();
        return [
            'title' => 'Hospital Charges',
            'content' => 'This is the hospital charge module',
            'webroot' => $this->webroot,
            'current_page' => 'hospital',
            'csrfToken' => $this->csrfToken,
            'facilities' => $facilities,
            'businesses' => $businessEntity
        ];
    }
}
