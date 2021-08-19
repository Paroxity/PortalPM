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
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Internet;
use pocketmine\utils\UUID;
use function count;

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
        $this->thread = $thread = new SocketThread($host, $port, $secret, $name, $group, $address, $notifier);

        $this->getServer()->getTickSleeper()->addNotifier($notifier, static function () use ($thread) {
            while (($buffer = $thread->getBuffer()) !== null) {
                $packet = PacketPool::getPacket($buffer);
                if ($packet instanceof Packet) {
                    $packet->decode();
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
        /** @var UUID $uuid */
        $uuid = $event->getPlayer()->getUniqueId();
        $this->playerLatencies[$uuid->toBinary()] = 0;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        /** @var UUID $uuid */
        $uuid = $event->getPlayer()->getUniqueId();
        unset($this->playerLatencies[$uuid->toBinary()]);
    }

    public function transferPlayer(Player $player, string $group, string $server, ?Closure $onResponse): void
    {
        if ($onResponse !== null) {
            $this->transferring[$player->getRawUniqueId()] = $onResponse;
        }
        /** @var UUID $uuid */
        $uuid = $player->getUniqueId();
        $this->thread->addPacketToQueue(TransferRequestPacket::create($uuid, $group, $server));
    }

    /**
     * @internal
     */
    public function handleTransferResponse(TransferResponsePacket $packet): void
    {
        $closure = $this->transferring[$packet->getPlayerUUID()->toBinary()] ?? null;
        if ($closure !== null) {
            unset($this->transferring[$packet->getPlayerUUID()->toBinary()]);
            $player = $this->getServer()->getPlayerByUUID($packet->getPlayerUUID());
            if ($player instanceof Player) {
                $closure($player, $packet->status, $packet->error);
            }
        }
    }

    public function requestPlayerInfo(Player $player, ?Closure $onResponse): void
    {
        if ($onResponse !== null) {
            $this->playerInfoRequests[$player->getRawUniqueId()] = $onResponse;
        }

        /** @var UUID $uuid */
        $uuid = $player->getUniqueId();
        $this->thread->addPacketToQueue(PlayerInfoRequestPacket::create($uuid));
    }

    /**
     * @internal
     */
    public function handlePlayerInfoResponse(PlayerInfoResponsePacket $packet): void
    {
        $closure = $this->playerInfoRequests[$packet->getPlayerUUID()->toBinary()] ?? null;
        if ($closure !== null) {
            unset($this->playerInfoRequests[$packet->getPlayerUUID()->toBinary()]);
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
            $closure($packet->servers);
        }
        $this->serverListRequests = [];
    }

    public function findPlayer(?UUID $uuid, string $name, ?Closure $onResponse): void
    {
        if($onResponse !== null) {
            $this->findPlayerRequests[$uuid === null ? $name : $uuid->toBinary()] = $onResponse;
        }

        $this->thread->addPacketToQueue(FindPlayerRequestPacket::create($uuid ?? new UUID(), $name));
    }

    /**
     * @internal
     */
    public function handleFindPlayerResponse(FindPlayerResponsePacket $packet): void
    {
        $closure = $this->findPlayerRequests[$packet->playerUUID->toBinary()] ?? $this->findPlayerRequests[$packet->name];
        if($closure !== null) {
            $closure($packet->playerUUID, $packet->name, $packet->online, $packet->group, $packet->server);
        }
    }

    public function getPlayerLatency(Player $player): int{
        /** @var UUID $uuid */
        $uuid = $player->getUniqueId();
        return $this->playerLatencies[$uuid->toBinary()] ?? -1;
    }

    /**
     * @internal
     */
    public function handleUpdatePlayerLatency(UpdatePlayerLatencyPacket $packet): void
    {
        $uuid = $packet->playerUUID->toBinary();
        if(isset($this->playerLatencies[$uuid])){
            $this->playerLatencies[$uuid] = $packet->latency;
        }
    }
}
