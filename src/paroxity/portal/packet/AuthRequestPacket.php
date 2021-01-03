<?php

declare(strict_types=1);

namespace paroxity\portal\packet;

class AuthRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::AUTH_REQUEST_PACKET;

    public const CLIENT_TYPE_SERVER = 0;

    /** @var int */
    public $type;
    /** @var string */
    public $secret;
    /** @var string */
    public $name;
    /** @var string */
    public $extraData;

    public static function create(int $type, string $secret, string $name, string $extraData): self
    {
        $result = new self;
        $result->type = $type;
        $result->secret = $secret;
        $result->name = $name;
        $result->extraData = $extraData;
        return $result;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getServerName(): string
    {
        return $this->name;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getExtraData(): string
    {
        return $this->extraData;
    }

    protected function decodePayload(): void
    {
        $this->type = $this->getByte();
        $this->secret = $this->getString();
        $this->name = $this->getString();
        $this->extraData = $this->getRemaining();
    }

    protected function encodePayload(): void
    {
        $this->putByte($this->type);
        $this->putString($this->secret);
        $this->putString($this->name);
        $this->put($this->extraData);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}