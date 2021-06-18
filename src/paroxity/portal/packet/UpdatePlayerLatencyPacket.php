<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\utils\UUID;

class UpdatePlayerLatencyPacket extends Packet
{

    public const NETWORK_ID = ProtocolInfo::UPDATE_PLAYER_LATENCY_PACKET;

    public UUID $playerUUID;
    public int $latency;

    public static function create(UUID $playerUUID, int $latency): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->latency = $latency;
        return $result;
    }

    public function decodePayload(): void
    {
        $this->playerUUID = $this->getUUID();
        $this->latency = $this->getLInt();
    }

    public function encodePayload(): void
    {
        $this->putUUID($this->playerUUID);
        $this->putLInt($this->latency);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleUpdatePlayerLatency($this);
    }
}
