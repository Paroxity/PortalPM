<?php

declare(strict_types=1);

namespace paroxity\portal;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use function preg_match;

class EventListener implements Listener
{

    public function onPlayerPreLogin(PlayerPreLoginEvent $event): void
    {
        if (!preg_match("/^(127|172)/", $event->getIp())) {
            $event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, "You must join via the proxy");
        }
    }
}