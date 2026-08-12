<?php

namespace Juggernaut\Module\HospitalCharge\App\Exceptions;

class ViewNotFoundException extends \Exception
{
    public function __construct()
    {
        ViewNotFoundException::__construct();
    }

    protected $message = 'View Not found';
}
