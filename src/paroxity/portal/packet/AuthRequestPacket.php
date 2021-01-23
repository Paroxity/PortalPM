<?php

declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

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

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->type = $in->getByte();
        $this->secret = $in->getString();
        $this->name = $in->getString();
        $this->extraData = $in->getRemaining();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putByte($this->type);
        $out->putString($this->secret);
        $out->putString($this->name);
        $out->put($this->extraData);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}