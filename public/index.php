<?php

/**
 * Hospital Charge module front controller / router.
 *
 * @package   OpenEMR
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

use Juggernaut\Module\HospitalCharge\App\Exceptions\RouteNotFoundException;
use Juggernaut\Module\HospitalCharge\App\Home;
use Juggernaut\Module\HospitalCharge\App\Hospital;
use Juggernaut\Module\HospitalCharge\Controllers\ChargeReviewController;
use Juggernaut\Module\HospitalCharge\Controllers\HospitalBillingController;
use Juggernaut\Module\HospitalCharge\Controllers\RapidBillingController;
use Juggernaut\Module\HospitalCharge\Controllers\SearchController;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Twig\TwigContainer;

require_once dirname(__DIR__, 4) . '/globals.php';

// Align with Fees menu ACL used by the module menu item.
if (!AclMain::aclCheckCore('patients', 'docs') && !AclMain::aclCheckCore('acct', 'bill')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render(
        'core/unauthorized.html.twig',
        ['pageTitle' => xl('Hospital Charge')]
    );
    exit;
}

const MODULE_NAME = 'oe-module-hospital-charge';

$router = new Juggernaut\Module\HospitalCharge\App\Router();
$webroot = $GLOBALS['webroot'];
$routePath = $webroot . '/interface/modules/custom_modules/oe-module-hospital-charge/public/index.php';

//you can create a router in your module to handle requests
//These are just ideas to make your module more dynamic
$router
    ->get($routePath . "/home", [Home::class, 'home'])
    ->get($routePath . "/hospital", [Hospital::class, 'hospital'])
    ->get($routePath . "/about", [Home::class, 'about'])
    ->get($routePath . "/contact", [Home::class, 'contact'])
    ->get($routePath . "/searchPatient/{term}", [SearchController::class, 'findPatient'])
    ->get($routePath . "/searchProvider/{term}", [SearchController::class, 'findProvider'])
    ->get($routePath . "/searchSingleProvider/{term}", [SearchController::class, 'findSingleProvider'])
    ->get($routePath . "/searchCpt4/{type}/{term}", [SearchController::class, 'findCode'])
    ->get($routePath . "/searchPatientDiagnoses/{pid}", [SearchController::class, 'searchPatientDiagnoses'])
    ->post($routePath . "/startClaim", [RapidBillingController::class, 'rapidClaim'])
    ->post($routePath . "/processHospitalClaim", [HospitalBillingController::class, 'processHospitalClaim'])
    ->get($routePath . "/charge-review/{pid}", [ChargeReviewController::class, 'showReview'])
    ->post($routePath . "/charge-review/encounter-data", [ChargeReviewController::class, 'getEncounterData'])
;

// Run the router
try {
    echo $router->resolve($_SERVER['REQUEST_URI'], strtolower($_SERVER['REQUEST_METHOD']));
} catch (RouteNotFoundException $e) {
    echo $e->getMessage();
}
