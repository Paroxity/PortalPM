<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\UuidInterface;

class PlayerInfoRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::PLAYER_INFO_REQUEST_PACKET;

    public UuidInterface $playerUUID;

    public static function create(UuidInterface $playerUUID): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        return $result;
    }

    public function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
    }

    public function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
