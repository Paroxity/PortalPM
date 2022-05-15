<?php
declare(strict_types=1);

namespace paroxity\portal\thread;

use Exception;
use paroxity\portal\packet\AuthRequestPacket;
use paroxity\portal\packet\Packet;
use paroxity\portal\packet\ProtocolInfo;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use pocketmine\utils\Binary;
use Socket;
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

    private Threaded $sendQueue;
    private Threaded $receiveBuffer;

    private SleeperNotifier $notifier;

    private bool $isRunning;

    public function __construct(string $host, int $port, string $secret, string $name, SleeperNotifier $notifier)
    {
        $this->host = $host;
        $this->port = $port;

        $this->secret = $secret;

        $this->name = $name;

        $this->sendQueue = new Threaded();
        $this->receiveBuffer = new Threaded();

        $this->notifier = $notifier;

        $this->isRunning = false;
        $this->start();
    }

    public function onRun(): void
    {
        $this->registerClassLoaders();

        $socket = $this->connectToSocketServer();

        while ($socket !== null && $this->isRunning) {
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
                $read = @socket_read($socket, 4);
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
     * @return ?Socket
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
            $connected = @socket_connect($socket, $this->host, $this->port);
            if (!$connected) {
                sleep(5);
            }
        } while (!$connected);
        socket_set_nonblock($socket);

        $pk = AuthRequestPacket::create(ProtocolInfo::PROTOCOL_VERSION, $this->secret, $this->name);
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
    	$encoderContext = new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary());
    	$serializer = PacketSerializer::encoder($encoderContext);
    	$packet->encode($serializer);
    	$this->sendQueue[] = $serializer->getBuffer();
    }

    public function getBuffer(): ?string
    {
        return $this->receiveBuffer->shift();
    }
}
