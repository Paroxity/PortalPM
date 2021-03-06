<?php

declare(strict_types=1);

namespace paroxity\portal\thread;

use Exception;
use paroxity\portal\packet\AuthRequestPacket;
use paroxity\portal\packet\Packet;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\Thread;
use pocketmine\utils\Binary;
use Threaded;
use function sleep;
use function socket_close;
use function socket_connect;
use function socket_create;
use function socket_last_error;
use function socket_read;
use function socket_set_nonblock;
use function socket_write;
use function strlen;
use function usleep;
use const AF_INET;
use const PTHREADS_INHERIT_NONE;
use const SOCK_STREAM;
use const SOL_TCP;

class SocketThread extends Thread
{
    private string $host;
    private int $port;

    private string $secret;
    private string $name;
    private string $group;
    private string $address;

    private Threaded $sendQueue;
    private Threaded $receiveBuffer;

    private SleeperNotifier $notifier;

    private bool $isRunning;

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
                $wrote = @socket_write($socket, Binary::writeLInt($length) . $send, 4 + $length);
                if ($wrote !== 4 + $length) {
                    socket_close($socket);
                    $socket = $this->connectToSocketServer();
                    if($socket === null) {
                        break;
                    }
                }
            }

            do {
                $read = socket_read($socket, 4);
                if(!$read && socket_last_error($socket) === 10054) {
                    socket_close($socket);
                    $socket = $this->connectToSocketServer();
                    if($socket === null) {
                        break;
                    }
                }
                if($read !== false) {
                    if (strlen($read) === 4) {
                        $length = Binary::readLInt($read);
                        $read = @socket_read($socket, $length);
                        if ($read !== false) {
                            $this->receiveBuffer[] = $read;
                            $this->notifier->wakeupSleeper();
                        }
                    } elseif ($read === "") {
                        socket_close($socket);
                        $socket = $this->connectToSocketServer();
                        if($socket === null) {
                            break;
                        }
                    }
                }
            } while ($read !== false);
            usleep(25000);
        }

        if($socket !== null) {
            socket_close($socket);
        }
    }

    /**
     * @return resource
     * @throws Exception
     */
    public function connectToSocketServer()
    {
        do {
            if(!$this->isRunning) {
                return null;
            }
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } while (!$socket);

        do {
            if(!$this->isRunning) {
                return null;
            }
            $connected = @socket_connect($socket, $this->host, $this->port);
            if (!$connected) {
                sleep(5);
            }
        } while (!$connected);
        socket_set_nonblock($socket);

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

    public function addPacketToQueue(Packet $packet): void
    {
        $packet->encode();
        $this->sendQueue[] = $packet->getBuffer();
    }

    public function getBuffer(): ?string
    {
        return $this->receiveBuffer->shift();
    }
}
