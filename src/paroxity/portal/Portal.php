<?php

declare(strict_types=1);

namespace paroxity\portal;

use Closure;
use paroxity\portal\packet\Packet;
use paroxity\portal\packet\PacketPool;
use paroxity\portal\packet\PlayerInfoRequestPacket;
use paroxity\portal\packet\PlayerInfoResponsePacket;
use paroxity\portal\packet\TransferRequestPacket;
use paroxity\portal\packet\TransferResponsePacket;
use paroxity\portal\thread\SocketThread;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Internet;
use pocketmine\utils\UUID;

class Portal extends PluginBase
{
    /** @var self */
    private static $instance;

    /** @var SocketThread */
    private $thread;

    /** @var Closure[] */
    private $transferring = [];

    /** @var Closure[] */
    private $playerInfoRequests = [];

    public function onLoad(): void
    {
        self::$instance = $this;
    }

    public function onEnable(): void
    {
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

    public function transferPlayer(Player $player, string $group, string $server, Closure $onResponse): void
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
                return;
            }
        }
    }

    public function requestPlayerInfo(Player $player, Closure $onResponse): void
    {
        if ($onResponse !== null) {
            $this->playerInfoRequests[$player->getRawUniqueId()] = $onResponse;
        }

        $this->thread->addPacketToQueue(PlayerInfoRequestPacket::create($player->getUniqueId()));
    }

    public function handlePlayerInfoResponse(PlayerInfoResponsePacket $packet)
    {
        $closure = $this->playerInfoRequests[$packet->getPlayerUUID()->toBinary()] ?? null;
        if ($closure !== null) {
            unset($this->playerInfoRequests[$packet->getPlayerUUID()->toBinary()]);
            $player = $this->getServer()->getPlayerByUUID($packet->getPlayerUUID());
            if ($player instanceof Player) {
                $closure($player, $packet->status, $packet->xuid, $packet->address);
                return;
            }
        }
    }
}
