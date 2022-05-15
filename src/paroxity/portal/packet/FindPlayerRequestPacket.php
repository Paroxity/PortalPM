<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\UuidInterface;

class FindPlayerRequestPacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::FIND_PLAYER_REQUEST_PACKET;

    public UuidInterface $playerUUID;
    public string $playerName;

    public static function create(UuidInterface $uuid, string $name): self
    {
        $result = new self;
        $result->playerUUID = $uuid;
        $result->playerName = $name;
        return $result;
    }

    public function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
        $this->playerName = $in->getString();
    }

    public function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
        $out->putString($this->playerName);
    }

    public function handlePacket(): void
    {
        // NOOP
    }
}
