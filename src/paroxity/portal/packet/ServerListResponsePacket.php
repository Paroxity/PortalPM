<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\packet\types\ServerListEntry;
use paroxity\portal\Portal;
use pocketmine\utils\UUID;

class ServerListResponsePacket extends Packet
{

    public const NETWORK_ID = ProtocolInfo::SERVER_LIST_RESPONSE_PACKET;

    /** @var ServerListEntry[] */
    public $servers = [];

    /**
     * @param ServerListEntry[] $servers
     */
    public static function create(array $servers): self
    {
        $result = new self;
        $result->servers = $servers;
        return $result;
    }

    public function decodePayload(): void
    {
        for($i = 0, $count = $this->getLInt(); $i < $count; ++$i) {
            $this->servers[$i] = ServerListEntry::create(
                $this->getString(),
                $this->getString(),
                $this->getBool(),
                $this->getVarLong(),
            );
        }
    }

    public function encodePayload(): void
    {
        $this->putLInt(count($this->servers));
        foreach($this->servers as $server){
            $this->putString($server->getName());
            $this->putString($server->getGroup());
            $this->putBool($server->isOnline());
            $this->putVarLong($server->getPlayerCount());
        }
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleServerListResponse($this);
    }
}
