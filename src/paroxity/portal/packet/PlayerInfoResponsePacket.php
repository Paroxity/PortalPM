<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\utils\UUID;

class PlayerInfoResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::PLAYER_INFO_RESPONSE_PACKET;

    /** @var UUID */
    public $playerUUID;
    /** @var int */
    public $status;
    /** @var string */
    public $xuid;
    /** @var string */
    public $address;

    public static function create(UUID $playerUUID, int $status, string $xuid, string $address): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->status = $status;
        $result->xuid = $xuid;
        $result->address = $address;
        return $result;
    }

    public function getPlayerUUID(): UUID
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

    public function decodePayload(): void
    {
        $this->playerUUID = $this->getUUID();
        $this->status = $this->getByte();
        $this->xuid = $this->getString();
        $this->address = $this->getString();
    }

    public function encodePayload(): void
    {
        $this->putUUID($this->playerUUID);
        $this->putByte($this->status);
        $this->putString($this->xuid);
        $this->putString($this->address);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handlePlayerInfoResponse($this);
    }
}
