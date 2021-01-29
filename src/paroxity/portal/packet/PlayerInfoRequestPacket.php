<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\uuid\UUID;

class PlayerInfoRequestPacket extends Packet
{

    public const NETWORK_ID = ProtocolInfo::PLAYER_INFO_REQUEST_PACKET;

    /** @var UUID */
    public $playerUUID;

    public static function create(UUID $playerUUID): self
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