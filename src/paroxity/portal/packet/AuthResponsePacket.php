<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class AuthResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::AUTH_RESPONSE_PACKET;

    public const RESPONSE_SUCCESS = 0;
    public const RESPONSE_UNSUPPORTED_PROTOCOL = 1;
    public const RESPONSE_INCORRECT_SECRET = 2;
    public const RESPONSE_ALREADY_CONNECTED = 3;
    public const RESPONSE_UNAUTHENTICATED = 4;

	private int $protocol;
    private int $status;

    public static function create(int $protocol, int $status): self
    {
        $result = new self;
        $result->protocol = $protocol;
        $result->status = $status;
        return $result;
    }

	public function getProtocol(): int
	{
		return $this->protocol;
	}

	public function getStatus(): int
	{
		return $this->status;
	}

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->protocol = $in->getLInt();
        $this->status = $in->getByte();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putLInt($this->protocol);
        $out->putByte($this->status);
    }

    public function handlePacket(): void
    {
	    Portal::getInstance()->handleAuthResponse($this);
    }
}
