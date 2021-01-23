<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;

abstract class Packet extends DataPacket
{

    public function handle(PacketHandlerInterface $handler): bool
    {
        return true;
    }

    /**
     * The built in handle() method requires a PacketHandlerInterface object, which we do not want as we
     * cannot use it for custom packets.
     */
    abstract public function handlePacket(): void;
}