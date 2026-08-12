<?php

/*
 * package   OpenEMR
 * link      http://www.open-emr.org
 * author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * Copyright (c) 2023.
 * All rights reserved
 */

namespace Juggernaut\Module\HospitalCharge;

use Juggernaut\Module\HospitalCharge\App\Menu\MenuItemInjection;
use OpenEMR\Menu\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Bootstrap
{
    /**
     * @var EventDispatcherInterface The object responsible for sending and subscribing to events through the OpenEMR system
     */
    private $eventDispatcher;
    private $twig;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    public function subscribeToEvents(): void
    {
        $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'registerMenuItems']);
    }

    public function registerMenuItems(MenuEvent $event): void
    {
        $item = new MenuItemInjection();
        $item->event = $event;
        $item->HospitalMenuItem();
    }
}
