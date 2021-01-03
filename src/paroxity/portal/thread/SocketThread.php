<?php

declare(strict_types=1);

namespace paroxity\portal\thread;

use Exception;
use paroxity\portal\packet\AuthRequestPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\Thread;
use pocketmine\utils\Binary;
use Threaded;
use function sleep;
use function socket_close;
use function socket_connect;
use function socket_create;
use function socket_read;
use function socket_write;
use function strlen;
use function usleep;
use const AF_INET;
use const PTHREADS_INHERIT_NONE;
use const SOCK_STREAM;
use const SOL_TCP;

class SocketThread extends Thread
{
    /** @var string */
    private $host;
    /** @var int */
    private $port;

    /** @var string */
    private $secret;
    /** @var string */
    private $name;
    /** @var string */
    private $group;
    /** @var string */
    private $address;

    /** @var Threaded */
    private $sendQueue;
    /** @var Threaded */
    private $receiveBuffer;

    /** @var SleeperNotifier */
    private $notifier;

    /** @var bool */
    private $isRunning;

    public function __construct(string $host, int $port, string $secret, string $name, string $group, string $address, SleeperNotifier $notifier)
    {
        $this->host = $host;
        $this->port = $port;

        $this->secret = $secret;

        $this->name = $name;
        $this->group = $group;
        $this->address = $address;

        $this->sendQueue = new Threaded();
        $this->receiveBuffer = new Threaded();

        $this->notifier = $notifier;

        $this->isRunning = false;
        $this->start();
    }

    public function run(): void
    {
        $this->registerClassLoader();

        $socket = $this->connectToSocketServer();

        while ($this->isRunning) {
            while (($send = $this->sendQueue->shift()) !== null) {
                $length = strlen($send);
                $wrote = socket_write($socket, Binary::writeLInt($length) . $send, 4 + $length);
                if ($wrote === 0) {
                    socket_close($socket);
                    $socket = $this->connectToSocketServer();
                }
            }

            do {
                $read = socket_read($socket, 4);
                if (strlen($read) === 4) {
                    $length = Binary::readLInt($read);
                    $read = socket_read($socket, $length);
                    if ($read !== false) {
                        $this->receiveBuffer[] = $read;
                        $this->notifier->wakeupSleeper();
                    }
                } elseif ($read === 0) {
                    socket_close($socket);
                    $socket = $this->connectToSocketServer();
                }
            } while ($read !== false);
            usleep(25000);
        }

        socket_close($socket);
    }

    /**
     * @return resource
     * @throws Exception
     */
    public function connectToSocketServer()
    {
        do {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } while (!$socket);

        do {
            $connected = socket_connect($socket, $this->host, $this->port);
            if (!$connected) {
                sleep(10);
            }
        } while (!$connected);

        $extraData = Binary::writeUnsignedVarInt(strlen($this->group)) . $this->group . Binary::writeUnsignedVarInt(strlen($this->address)) . $this->address;
        $pk = AuthRequestPacket::create(AuthRequestPacket::CLIENT_TYPE_SERVER, $this->secret, $this->name, $extraData);
        $this->addPacketToQueue($pk);

        return $socket;
    }

    public function start($options = PTHREADS_INHERIT_NONE): bool
    {
        $this->isRunning = true;
        return parent::start($options);
    }

    public function quit(): void
    {
        $this->isRunning = false;
        parent::quit();
    }

    public function addPacketToQueue(DataPacket $packet): void
    {
        $packet->encode();
        $this->sendQueue[] = $packet->getBuffer();
    }

    public function getBuffer(): ?string
    {
        return $this->receiveBuffer->shift();
    }
}
