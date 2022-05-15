<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\UuidInterface;

class TransferRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::TRANSFER_REQUEST_PACKET;

    public UuidInterface $playerUUID;
    public string $server;

    public static function create(UuidInterface $playerUUID, string $server): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->server = $server;
        return $result;
    }

    public function getPlayerUUID(): UuidInterface
    {
        return $this->playerUUID;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
        $this->server = $in->getString();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
        $out->putString($this->server);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
