<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\utils\UUID;

class FindPlayerRequestPacket extends Packet
{

    public const NETWORK_ID = ProtocolInfo::FIND_PLAYER_REQUEST_PACKET;

    public UUID $playerUUID;
    public string $name;

    public static function create(UUID $playerUUID, string $name): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->name = $name;
        return $result;
    }

    public function decodePayload(): void
    {
        $this->playerUUID = $this->getUUID();
        $this->name = $this->getString();
    }

    public function encodePayload(): void
    {
        $this->putUUID($this->playerUUID);
        $this->putString($this->name);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
