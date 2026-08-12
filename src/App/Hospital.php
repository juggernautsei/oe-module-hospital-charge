<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\App;

use Juggernaut\Module\HospitalCharge\App\Model\HospitalModel;
use OpenEMR\Common\Twig\TwigContainer;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Hospital
{
    private HospitalModel $data;
    private TwigContainer $twig;
    private Environment $template;

    public function __construct()
    {
        $this->data = new HospitalModel();
        $this->twig = new TwigContainer(dirname(__FILE__, 3) . '/templates', $GLOBALS["kernel"]);
        $this->template = $this->twig->getTwig();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function hospital(): string
    {
        return $this->template->render('hospital/hospital.twig', $this->data->getHospitalData());
    }
}
