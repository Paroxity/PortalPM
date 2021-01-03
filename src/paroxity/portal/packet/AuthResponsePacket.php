<?php

declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\exception\PortalAuthException;
use paroxity\portal\Portal;

class AuthResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::AUTH_RESPONSE_PACKET;

    public const RESPONSE_SUCCESS = 0;
    public const RESPONSE_INCORRECT_SECRET = 1;
    public const RESPONSE_UNKNOWN_TYPE = 2;
    public const RESPONSE_INVALID_DATA = 3;

    /** @var int */
    public $status;
    /** @var string */
    public $reason;

    public static function create(int $status, string $reason): self
    {
        $result = new self;
        $result->status = $status;
        $result->reason = $reason;
        return $result;
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
        $this->status = $this->getByte();
        $this->reason = $this->getString();
    }

    protected function encodePayload(): void
    {
        $this->putByte($this->status);
        $this->putString($this->reason);
    }

    public function handlePacket(): void
    {
        if ($this->status !== self::RESPONSE_SUCCESS) {
            throw new PortalAuthException($this->reason);
        }
        Portal::getInstance()->getLogger()->info($this->reason);
    }
}