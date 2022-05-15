<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class AuthRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::AUTH_REQUEST_PACKET;

    private int $protocol;
	private string $secret;
	private string $name;

    public static function create(int $protocol, string $secret, string $name): self
    {
        $result = new self;
        $result->protocol = $protocol;
        $result->secret = $secret;
        $result->name = $name;
        return $result;
    }

	public function getProtocol(): int
	{
		return $this->protocol;
	}

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getServerName(): string
    {
        return $this->name;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->protocol = $in->getLInt();
        $this->secret = $in->getString();
        $this->name = $in->getString();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putLInt($this->protocol);
        $out->putString($this->secret);
        $out->putString($this->name);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
