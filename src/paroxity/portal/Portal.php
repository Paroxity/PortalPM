<?php
declare(strict_types=1);

namespace paroxity\portal;

use Closure;
use CortexPE\Commando\PacketHooker;
use paroxity\portal\command\CommandMap;
use paroxity\portal\exception\PortalAuthException;
use paroxity\portal\packet\AuthResponsePacket;
use paroxity\portal\packet\FindPlayerRequestPacket;
use paroxity\portal\packet\FindPlayerResponsePacket;
use paroxity\portal\packet\Packet;
use paroxity\portal\packet\PacketPool;
use paroxity\portal\packet\PlayerInfoRequestPacket;
use paroxity\portal\packet\PlayerInfoResponsePacket;
use paroxity\portal\packet\ProtocolInfo;
use paroxity\portal\packet\RegisterServerPacket;
use paroxity\portal\packet\ServerListRequestPacket;
use paroxity\portal\packet\ServerListResponsePacket;
use paroxity\portal\packet\TransferRequestPacket;
use paroxity\portal\packet\TransferResponsePacket;
use paroxity\portal\packet\UpdatePlayerLatencyPacket;
use paroxity\portal\thread\SocketThread;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Internet;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function strtolower;

class Portal extends PluginBase implements Listener
{
    private static self $instance;

    private SocketThread $thread;
	private string $address;

    /** @var Closure[] */
    private $transferring = [];
    /** @var Closure[] */
    private $playerInfoRequests = [];
    /** @var Closure[] */
    private $serverListRequests = [];
    /** @var Closure[] */
    private $findPlayerRequests = [];

    /** @var int[] */
    private $playerLatencies = [];

    public function onLoad(): void
    {
        self::$instance = $this;
    }

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $config = $this->getConfig();

        $host = $config->get("proxy-address", "127.0.0.1");
        $port = (int)$config->getNested("socket.port", 19131);

        $secret = $config->getNested("socket.secret", "");

        $name = $config->getNested("server.name", "Name");
        $this->address = ($host === "127.0.0.1" ? "127.0.0.1" : Internet::getIP()) . ":" . $this->getServer()->getPort();

        if(!PacketHooker::isRegistered()){
	        PacketHooker::register($this);
        }

	    PacketPool::init();
        CommandMap::init($this);

        $notifier = new SleeperNotifier();
        $this->getServer()->getTickSleeper()->addNotifier($notifier, function () {
        	$context = new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary());
            while (($buffer = $this->thread->getBuffer()) !== null) {
            	$stream = PacketSerializer::decoder($buffer, 0, $context);
                $packet = PacketPool::getPacket($buffer);
                if ($packet instanceof Packet) {
                    $packet->decode($stream);
                    $packet->handlePacket();
                }
            }
        });
	    $this->thread = new SocketThread($host, $port, $secret, $name, $notifier);
    }

    public function onDisable(): void
    {
        $this->thread->quit();
    }

    public static function getInstance(): Portal
    {
        return self::$instance;
    }

    public function getThread(): SocketThread
    {
        return $this->thread;
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $this->playerLatencies[$event->getPlayer()->getUniqueId()->getBytes()] = 0;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        unset($this->playerLatencies[$event->getPlayer()->getUniqueId()->getBytes()]);
    }

    public function transferPlayer(Player $player, string $server, ?Closure $onResponse): void
    {
    	$this->transferPlayerByUUID($player->getUniqueId(), $server, $onResponse);
    }

	public function transferPlayerByUUID(UuidInterface $uuid, string $server, ?Closure $onResponse): void
	{
		if ($onResponse !== null) {
			$this->transferring[$uuid->getBytes()] = $onResponse;
		}
		$this->thread->addPacketToQueue(TransferRequestPacket::create($uuid, $server));
	}

    /**
     * @internal
     */
    public function handleAuthResponse(AuthResponsePacket $packet): void
    {
	    if ($packet->getStatus() !== AuthResponsePacket::RESPONSE_SUCCESS) {
		    $reason = "";
		    switch ($packet->getStatus()) {
			    case AuthResponsePacket::RESPONSE_UNSUPPORTED_PROTOCOL:
				    $reason = "Unsupported protocol version, expected " . $packet->getProtocol() . " got " . ProtocolInfo::PROTOCOL_VERSION;
				    break;
			    case AuthResponsePacket::RESPONSE_INCORRECT_SECRET:
				    $reason = "Incorrect secret provided";
				    break;
			    case AuthResponsePacket::RESPONSE_ALREADY_CONNECTED:
				    $reason = "Client already exists with the provided name";
				    break;
			    case AuthResponsePacket::RESPONSE_UNAUTHENTICATED:
				    $reason = "Attempted to send packets whilst not authenticated";
				    break;
		    }
		    throw new PortalAuthException($reason);
	    }
	    $this->getLogger()->info("Authenticated with socket server");
		$this->thread->addPacketToQueue(RegisterServerPacket::create($this->address));
    }

    /**
     * @internal
     */
    public function handleTransferResponse(TransferResponsePacket $packet): void
    {
        $closure = $this->transferring[$packet->getPlayerUUID()->getBytes()] ?? null;
        if ($closure !== null) {
            unset($this->transferring[$packet->getPlayerUUID()->getBytes()]);
            $player = $this->getServer()->getPlayerByUUID($packet->getPlayerUUID());
            $closure($player, $packet->status, $packet->error);
        }
    }

    public function requestPlayerInfo(Player $player, ?Closure $onResponse): void
    {
        if ($onResponse !== null) {
            $this->playerInfoRequests[$player->getUniqueId()->getBytes()] = $onResponse;
        }

        $this->thread->addPacketToQueue(PlayerInfoRequestPacket::create($player->getUniqueId()));
    }

    /**
     * @internal
     */
    public function handlePlayerInfoResponse(PlayerInfoResponsePacket $packet): void
    {
        $closure = $this->playerInfoRequests[$packet->getPlayerUUID()->getBytes()] ?? null;
        if ($closure !== null) {
            unset($this->playerInfoRequests[$packet->getPlayerUUID()->getBytes()]);
            $player = $this->getServer()->getPlayerByUUID($packet->getPlayerUUID());
            $closure($packet->getPlayerUUID(), $player, $packet->status, $packet->xuid, $packet->address);
        }
    }

    public function requestServerList(?Closure $onResponse): void
    {
        if ($onResponse !== null) {
            $this->serverListRequests[] = $onResponse;
        }

        // There is no point in sending multiple of the packets to the proxy at the same time, so we only
        // send the packet if this is the first request.
        if (count($this->serverListRequests) === 1) {
            $this->thread->addPacketToQueue(ServerListRequestPacket::create());
        }
    }

    /**
     * @internal
     */
    public function handleServerListResponse(ServerListResponsePacket $packet): void
    {
        foreach ($this->serverListRequests as $closure) {
            $closure($packet->getServers());
        }
        $this->serverListRequests = [];
    }

    public function findPlayer(?UuidInterface $uuid, string $name, ?Closure $onResponse): void
    {
        if($onResponse !== null) {
            $this->findPlayerRequests[$uuid === null ? strtolower($name) : $uuid->getBytes()] = $onResponse;
        }

        $this->thread->addPacketToQueue(FindPlayerRequestPacket::create($uuid ?? Uuid::fromString(Uuid::NIL), $name));
    }

    /**
     * @internal
     */
    public function handleFindPlayerResponse(FindPlayerResponsePacket $packet): void
    {
        $closure = $this->findPlayerRequests[$packet->playerUUID->getBytes()] ?? $this->findPlayerRequests[strtolower($packet->playerName)];
        if($closure !== null) {
        	$online = $packet->online;
            $closure($packet->playerUUID, $packet->playerName, $online, $online ? $packet->server : "");
        }
    }

    public function getPlayerLatency(Player $player): int
    {
        /** @var UUID $uuid */
        $uuid = $player->getUniqueId();
        return $this->playerLatencies[$uuid->getBytes()] ?? -1;
    }

    /**
     * @internal
     */
    public function handleUpdatePlayerLatency(UpdatePlayerLatencyPacket $packet): void
    {
        $uuid = $packet->playerUUID->getBytes();
        if(isset($this->playerLatencies[$uuid])){
            $this->playerLatencies[$uuid] = $packet->latency;
        }
    }
}
