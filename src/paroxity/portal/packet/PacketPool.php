<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;

class PacketPool
{
    /** @var Packet[] */
    protected static array $pool = [];

    public static function init(): void
    {
        static::registerPacket(new AuthRequestPacket());
        static::registerPacket(new AuthResponsePacket());
        static::registerPacket(new RegisterServerPacket());
        static::registerPacket(new TransferRequestPacket());
        static::registerPacket(new TransferResponsePacket());
        static::registerPacket(new PlayerInfoRequestPacket());
        static::registerPacket(new PlayerInfoResponsePacket());
        static::registerPacket(new ServerListRequestPacket());
        static::registerPacket(new ServerListResponsePacket());
        static::registerPacket(new FindPlayerRequestPacket());
        static::registerPacket(new FindPlayerResponsePacket());
        static::registerPacket(new UpdatePlayerLatencyPacket());
    }

    public static function registerPacket(Packet $packet): void
    {
        static::$pool[$packet->pid()] = clone $packet;
    }

    public static function getPacketById(int $pid): Packet
    {
        return isset(static::$pool[$pid]) ? clone static::$pool[$pid] : new UnknownPacket();
    }

    /**
     * @throws BinaryDataException
     */
    public static function getPacket(string $buffer): Packet
    {
        return static::getPacketById(Binary::readLShort($buffer));
    }
}
