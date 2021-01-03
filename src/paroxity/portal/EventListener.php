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
        $player = $event->getPlayer();
        if (!preg_match("/^(127|172)/", $player->getAddress())) {
            $event->setKickMessage("You must join via the proxy");
            $event->setCancelled();
        }
    }
}