<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\utils\UUID;

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

    public function decodePayload(): void
    {
        $this->playerUUID = $this->getUUID();
    }

    public function encodePayload(): void
    {
        $this->putUUID($this->playerUUID);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}