<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\UuidInterface;

class UpdatePlayerLatencyPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::UPDATE_PLAYER_LATENCY_PACKET;

    public UuidInterface $playerUUID;
    public int $latency;

    public static function create(UuidInterface $playerUUID, int $latency): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->latency = $latency;
        return $result;
    }

    public function getPlayerUUID(): UuidInterface
    {
        return $this->playerUUID;
    }

    public function getLatency(): int
    {
        return $this->latency;
    }

    public function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
        $this->latency = $in->getLInt();
    }

    public function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
        $out->putLInt($this->latency);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleUpdatePlayerLatency($this);
    }
}
