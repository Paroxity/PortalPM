<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;

class TransferResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::TRANSFER_RESPONSE_PACKET;

    public const RESPONSE_SUCCESS = 0;
    public const RESPONSE_GROUP_NOT_FOUND = 1;
    public const RESPONSE_SERVER_NOT_FOUND = 2;
    public const RESPONSE_ALREADY_ON_SERVER = 3;
    public const RESPONSE_PLAYER_NOT_FOUND = 4;
    public const RESPONSE_ERROR = 5;

    /** @var int */
    public $playerEntityRuntimeId;
    /** @var int */
    public $status;
    /** @var string */
    public $reason;

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

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    protected function decodePayload(): void
    {
        $this->playerEntityRuntimeId = $this->getEntityRuntimeId();
        $this->status = $this->getByte();
        $this->reason = $this->getString();
    }

    protected function encodePayload(): void
    {
        $this->putEntityRuntimeId($this->playerEntityRuntimeId);
        $this->putByte($this->status);
        $this->putString($this->reason);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleTransferResponse($this);
    }
}