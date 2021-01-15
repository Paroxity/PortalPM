<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

class TransferRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::TRANSFER_REQUEST_PACKET;

    /** @var int */
    public $playerEntityRuntimeId;
    /** @var string */
    public $group;
    /** @var string */
    public $server;

    public static function create(int $playerEntityRuntimeId, string $group, string $server): self
    {
        $result = new self;
        $result->playerEntityRuntimeId = $playerEntityRuntimeId;
        $result->group = $group;
        $result->server = $server;
        return $result;
    }

    public function getPlayerEntityRuntimeId(): int
    {
        return $this->playerEntityRuntimeId;
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
        $this->playerEntityRuntimeId = $this->getEntityRuntimeId();
        $this->group = $this->getString();
        $this->server = $this->getString();
    }

    protected function encodePayload(): void
    {
        $this->putEntityRuntimeId($this->playerEntityRuntimeId);
        $this->putString($this->group);
        $this->putString($this->server);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}