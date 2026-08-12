<?php

/*
 *   package   OpenEMR
 *   link      http://www.open-emr.org
 *   author    Sherwin Gaddis <sherwingaddis@gmail.com>
 *       All rights reserved
 *  Copyright (c)
 */

namespace Juggernaut\Module\HospitalCharge\App;

use Juggernaut\Module\HospitalCharge\App\Model\HomeModel;
use OpenEMR\Common\Twig\TwigContainer;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Home
{
    private HomeModel $data;
    private TwigContainer $twig;
    private Environment $template;

    public function __construct()
    {
        $this->data = new HomeModel();
        $this->twig = new TwigContainer(dirname(__FILE__, 3) . '/templates', $GLOBALS["kernel"]);
        $this->template = $this->twig->getTwig();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function contact(): string
    {
        $data = [
            'current_page' => 'contact'
        ];
        return $this->template->render('home/contact.twig', $data);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function about(): string
    {
        $data = [
            'current_page' => 'about'
        ];
        return $this->template->render('home/about.twig', $data);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function home(): string
    {
        return $this->template->render('home/home.twig', $this->data->getHomeData());
    }
}
