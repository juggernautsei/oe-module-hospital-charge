<?php

/**
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 *     All rights reserved
 */

namespace Juggernaut\Module\HospitalCharge\App\Model;

use OpenEMR\Services\FacilityService;
use OpenEMR\Common\Csrf\CsrfUtils;

class HomeModel
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
    public function getHomeData(): array
    {
        $facilities = $this->getFacilities();
        $businessEntity = $this->getBusinessEntity();
        return [
            'title' => 'Rapid Charge',
            'content' => 'This is the hospital charge module',
            'facilities' => $facilities,
            'businesses' => $businessEntity,
            'webroot' => $this->webroot,
            'current_page' => 'home',
            'csrfToken' => $this->csrfToken
        ];
    }
}
