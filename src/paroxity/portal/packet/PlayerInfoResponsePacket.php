<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\UuidInterface;

class PlayerInfoResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::PLAYER_INFO_RESPONSE_PACKET;

    public UuidInterface $playerUUID;
    public int $status;
    public string $xuid;
    public string $address;

    public static function create(UuidInterface $playerUUID, int $status, string $xuid, string $address): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->status = $status;
        $result->xuid = $xuid;
        $result->address = $address;
        return $result;
    }

    public function getPlayerUUID(): UuidInterface
    {
        return $this->playerUUID;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getXUID(): string
    {
        return $this->xuid;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
        $this->status = $in->getByte();
        $this->xuid = $in->getString();
        $this->address = $in->getString();
    }

    public function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
        $out->putByte($this->status);
        $out->putString($this->xuid);
        $out->putString($this->address);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handlePlayerInfoResponse($this);
    }
}
