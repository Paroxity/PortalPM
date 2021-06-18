<?php

declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\exception\PortalAuthException;
use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class AuthResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::AUTH_RESPONSE_PACKET;

    public const RESPONSE_SUCCESS = 0;
    public const RESPONSE_INCORRECT_SECRET = 1;
    public const RESPONSE_UNKNOWN_TYPE = 2;
    public const RESPONSE_INVALID_DATA = 3;

    public int $status;

    public static function create(int $status): self
    {
        $result = new self;
        $result->status = $status;
        return $result;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->status = $in->getByte();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putByte($this->status);
    }

    public function handlePacket(): void
    {
        if ($this->status !== self::RESPONSE_SUCCESS) {
            $reason = "";
            switch ($this->status) {
                case self::RESPONSE_INCORRECT_SECRET:
                    $reason = "Incorrect secret provided";
                    break;
                case self::RESPONSE_UNKNOWN_TYPE:
                    $reason = "Unknown client type provided";
                    break;
                case self::RESPONSE_INVALID_DATA:
                    $reason = "Invalid/incorrect extra data";
                    break;
            }
            throw new PortalAuthException($reason);
        }
        Portal::getInstance()->getLogger()->info("Authenticated with socket server");
    }
}
