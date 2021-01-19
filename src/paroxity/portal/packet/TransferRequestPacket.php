<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\utils\UUID;

class TransferRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::TRANSFER_REQUEST_PACKET;

    /** @var UUID */
    public $playerUUID;
    /** @var string */
    public $group;
    /** @var string */
    public $server;

    public static function create(UUID $playerUUID, string $group, string $server): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->group = $group;
        $result->server = $server;
        return $result;
    }

    public function getPlayerUUID(): UUID
    {
        return $this->playerUUID;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    protected function decodePayload(): void
    {
        $this->playerUUID = $this->getUUID();
        $this->group = $this->getString();
        $this->server = $this->getString();
    }

    protected function encodePayload(): void
    {
        $this->putUUID($this->playerUUID);
        $this->putString($this->group);
        $this->putString($this->server);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}