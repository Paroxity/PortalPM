<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class ServerListRequestPacket extends Packet
{

    public const NETWORK_ID = ProtocolInfo::SERVER_LIST_REQUEST_PACKET;

    public static function create(): self
    {
        return new self;
    }

    public function decodePayload(PacketSerializer $in): void
    {
    }

    public function encodePayload(PacketSerializer $out): void
    {
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
