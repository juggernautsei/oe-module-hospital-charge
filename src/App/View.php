<?php

/*
 *   package   OpenEMR
 *   link      http://www.open-emr.org
 *   author    Sherwin Gaddis <sherwingaddis@gmail.com>
 *       All rights reserved
 * Copyright (c) 2025.
 */

namespace Juggernaut\Module\HospitalCharge\App;

use Juggernaut\Module\HospitalCharge\App\Exceptions\ViewNotFoundException;

class View
{
    protected string $view;
    protected array $params;

    public function __construct(
        $view,
        $params = []
    )
    {
        $this->view = $view;
        $this->params = $params;
    }

    /**
     * @return string
     * @throws ViewNotFoundException
     */
    public function render(): string
    {
        $viewFile = VIEW_PATH . '/' . $this->view . '.php';
        if (! file_exists($viewFile)) {
            throw new ViewNotFoundException();
        }
        ob_start();
        include_once $viewFile;
        return (string) ob_get_clean();
    }
}
