<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\UuidInterface;

class FindPlayerResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::FIND_PLAYER_RESPONSE_PACKET;

    public UuidInterface $playerUUID;
    public string $playerName;
    public bool $online;
    public string $server;

    public static function create(UuidInterface $uuid, string $name, bool $online, string $server): self
    {
        $result = new self;
        $result->playerUUID = $uuid;
        $result->playerName = $name;
        $result->online = $online;
        $result->server = $server;
        return $result;
    }

    public function getPlayerUUID(): UuidInterface
    {
        return $this->playerUUID;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
        $this->playerName = $in->getString();
        $this->online = $in->getBool();
        if($this->online) {
	        $this->server = $in->getString();
        }
    }

    public function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
        $out->putString($this->playerName);
        $out->putBool($this->online);
	    if($this->online) {
		    $out->putString($this->server);
	    }
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleFindPlayerResponse($this);
    }
}
