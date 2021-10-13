<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\packet\types\ServerListEntry;
use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class ServerListResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::SERVER_LIST_RESPONSE_PACKET;

    /** @var ServerListEntry[] */
    private array $servers = [];

    /**
     * @return ServerListEntry[]
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @param ServerListEntry[] $servers
     */
    public static function create(array $servers): self
    {
        $result = new self;
        $result->servers = $servers;
        return $result;
    }

    public function decodePayload(PacketSerializer $in): void
    {
        for($i = 0, $count = $in->getLInt(); $i < $count; ++$i) {
            $this->servers[$i] = ServerListEntry::create(
                $in->getString(),
                $in->getBool(),
                $in->getVarLong(),
            );
        }
    }

    public function encodePayload(PacketSerializer $out): void
    {
        $out->putLInt(count($this->servers));
        foreach($this->servers as $server){
            $out->putString($server->getName());
            $out->putBool($server->isOnline());
            $out->putVarLong($server->getPlayerCount());
        }
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleServerListResponse($this);
    }
}
