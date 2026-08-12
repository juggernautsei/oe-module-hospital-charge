<?php

namespace Juggernaut\Module\HospitalCharge\Controllers;

use Juggernaut\Module\HospitalCharge\Service\ChargeReviewService;
use OpenEMR\Common\Twig\TwigContainer;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChargeReviewController
{
    private $twig;
    private $template;

    public function __construct()
    {
        $this->twig = new TwigContainer(dirname(__DIR__, 2) . '/templates', $GLOBALS["kernel"]);
        $this->template = $this->twig->getTwig();
    }

    /**
     * Display charge review for patient
     * @param string $pid Patient ID
     * @return string
     */
    public function showReview(string $pid): string
    {
        $service = new ChargeReviewService();
        $encounters = $service->getPatientEncounters($pid);
        $encounterId = $encounters[0]['id'] ?? null;

        $data = [
            'patient' => [
                'pid' => $pid,
                'name' => $service->getPatientName($pid)
            ],
            'encounters' => $encounters,
            'procedures' => $encounterId ? $service->getProcedures($pid, $encounterId) : [],
            'issues' => $encounterId ? $service->getPatientIssues($pid, $encounterId) : [],
            'webroot' => $GLOBALS['webroot']
        ];

        return $this->template->render('charge-review/review.twig', $data);
    }

    /**
     * Get encounter data via AJAX
     * @return JsonResponse
     */
    public function getEncounterData(): JsonResponse
    {
        // Check for POST parameters first
        $pid = $_POST['pid'] ?? null;
        $encounterId = $_POST['encounter'] ?? null;
        
        // If not found in POST, try to read from JSON body as fallback
        if (!$pid || !$encounterId) {
            $request = json_decode(file_get_contents('php://input'), true);
            $pid = $request['pid'] ?? $pid;
            $encounterId = $request['encounter'] ?? $encounterId;
        }

        if (!$pid) {
            return new JsonResponse(['error' => 'Patient ID required'], 400);
        }

        if (!$encounterId) {
            // Return only encounters if no specific encounter is requested
            $service = new ChargeReviewService();
            return new JsonResponse([
                'encounters' => $service->getPatientEncounters($pid),
                'procedures' => [],
                'issues' => []
            ]);
        }

        $service = new ChargeReviewService();
        $data = [
            'encounters' => $service->getPatientEncounters($pid),
            'procedures' => $service->getProcedures($pid, $encounterId),
            'issues' => $service->getPatientIssues($pid, $encounterId)
        ];

        return new JsonResponse($data);
    }
}
