<?php
declare(strict_types=1);

namespace paroxity\portal;

use Closure;
use paroxity\portal\packet\FindPlayerRequestPacket;
use paroxity\portal\packet\FindPlayerResponsePacket;
use paroxity\portal\packet\Packet;
use paroxity\portal\packet\PacketPool;
use paroxity\portal\packet\PlayerInfoRequestPacket;
use paroxity\portal\packet\PlayerInfoResponsePacket;
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

class Portal extends PluginBase implements Listener
{
    private static self $instance;

    private SocketThread $thread;

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
        $group = $config->getNested("server.group", "Hub");
        $address = ($host === "127.0.0.1" ? "127.0.0.1" : Internet::getIP()) . ":" . $this->getServer()->getPort();

        PacketPool::init();

        $notifier = new SleeperNotifier();
        $this->thread = new SocketThread($host, $port, $secret, $name, $group, $address, $notifier);

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

    public function transferPlayer(Player $player, string $group, string $server, ?Closure $onResponse): void
    {
        if ($onResponse !== null) {
            $this->transferring[$player->getId()] = $onResponse;
        }
        $this->thread->addPacketToQueue(TransferRequestPacket::create($player->getUniqueId(), $group, $server));
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
            if ($player instanceof Player) {
                $closure($player, $packet->status, $packet->xuid, $packet->address);
            }
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
            $this->findPlayerRequests[$uuid === null ? $name : $uuid->getBytes()] = $onResponse;
        }

        $this->thread->addPacketToQueue(FindPlayerRequestPacket::create($uuid ?? Uuid::fromString(Uuid::NIL), $name));
    }

    /**
     * @internal
     */
    public function handleFindPlayerResponse(FindPlayerResponsePacket $packet): void
    {
        $closure = $this->findPlayerRequests[$packet->playerUUID->getBytes()] ?? $this->findPlayerRequests[$packet->playerName];
        if($closure !== null) {
            $closure($packet->playerUUID, $packet->playerName, $packet->online, $packet->group, $packet->server);
        }
    }

    public function getPlayerLatency(Player $player): int{
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
