<?php

declare(strict_types=1);

namespace paroxity\portal;

use Closure;
use paroxity\portal\packet\AuthRequestPacket;
use paroxity\portal\packet\AuthResponsePacket;
use paroxity\portal\packet\Packet;
use paroxity\portal\packet\TransferRequestPacket;
use paroxity\portal\packet\TransferResponsePacket;
use paroxity\portal\thread\SocketThread;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Internet;

class Portal extends PluginBase
{
    /** @var self */
    private static $instance;

    /** @var SocketThread */
    private $thread;

    /** @var Closure[] */
    private $transferring = [];

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

        PacketPool::registerPacket(new AuthRequestPacket());
        PacketPool::registerPacket(new AuthResponsePacket());

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
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
        $this->transferring[$player->getId()] = $onResponse;
        $this->thread->addPacketToQueue(TransferRequestPacket::create($player->getUniqueId(), $group, $server));
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
}