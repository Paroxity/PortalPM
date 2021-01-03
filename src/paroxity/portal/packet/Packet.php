<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class Packet extends DataPacket
{

    public function handle(NetworkSession $session): bool
    {
        return true;
    }

    /**
     * The built in handle() method requires a NetworkSession object, which we do not have as the packet is
     * not coming from a player.
     */
    abstract public function handlePacket(): void;
}