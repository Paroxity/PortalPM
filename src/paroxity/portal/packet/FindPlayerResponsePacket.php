<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\utils\UUID;

class FindPlayerResponsePacket extends Packet
{

    public const NETWORK_ID = ProtocolInfo::FIND_PLAYER_RESPONSE_PACKET;

    public UUID $playerUUID;
    public string $name;
    public bool $online;
    public string $server;

    public static function create(UUID $playerUUID, string $name, bool $online, string $server): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->name = $name;
        $result->online = $online;
        $result->server = $server;
        return $result;
    }

    public function decodePayload(): void
    {
        $this->playerUUID = $this->getUUID();
        $this->name = $this->getString();
        $this->online = $this->getBool();
        $this->server = $this->getString();
    }

    public function encodePayload(): void
    {
        $this->putUUID($this->playerUUID);
        $this->putString($this->name);
        $this->putBool($this->online);
        $this->putString($this->server);
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleFindPlayerResponse($this);
    }
}
