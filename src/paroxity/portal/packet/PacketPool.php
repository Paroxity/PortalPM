<?php

declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;

class PacketPool
{
    protected static $pool = [];

    public static function init()
    {
        static::registerPacket(new AuthRequestPacket());
        static::registerPacket(new AuthResponsePacket());
        static::registerPacket(new TransferRequestPacket());
        static::registerPacket(new TransferResponsePacket());
        static::registerPacket(new PlayerInfoRequestPacket());
        static::registerPacket(new PlayerInfoResponsePacket());
    }

    public static function registerPacket(Packet $packet)
    {
        static::$pool[$packet->pid()] = clone $packet;
    }

    public static function getPacketById(int $pid): ?Packet
    {
        return isset(static::$pool[$pid]) ? clone static::$pool[$pid] : null;
    }

    /**
     * @throws BinaryDataException
     */
    public static function getPacket(string $buffer): Packet
    {
        $offset = 0;
        $pk = static::getPacketById(Binary::readLShort($buffer, $offset));
        $pk->setBuffer($buffer, $offset);

        return $pk;
    }
}