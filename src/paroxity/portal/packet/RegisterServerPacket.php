<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class RegisterServerPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::REGISTER_SERVER_PACKET;

	private string $address;

    public static function create(string $address): self
    {
        $result = new self;
        $result->address = $address;
        return $result;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->address = $in->getString();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putString($this->address);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
