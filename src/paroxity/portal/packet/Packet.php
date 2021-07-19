<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use function get_class;

abstract class Packet
{
    public const NETWORK_ID = 0;

    public bool $isEncoded = false;
    private PacketSerializer $serializer;

    public function __construct()
    {
        $this->serializer = new PacketSerializer();
    }

    public function getSerializer(): PacketSerializer
    {
        return $this->serializer;
    }

    public function setSerializer(PacketSerializer $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function pid(): int
    {
        return $this::NETWORK_ID;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function decode(): void
    {
        $this->serializer->rewind();
        $this->decodeHeader($this->serializer);
        $this->decodePayload($this->serializer);
    }

    protected function decodeHeader(PacketSerializer $in): void
    {
        $pid = $in->getLShort();
        if ($pid !== static::NETWORK_ID) {
            throw new \UnexpectedValueException("Expected " . static::NETWORK_ID . " for packet ID, got $pid");
        }
    }

    protected function decodePayload(PacketSerializer $in): void
    {
    }

    public function encode(): void
    {
        $this->serializer = new PacketSerializer();
        $this->encodeHeader($this->serializer);
        $this->encodePayload($this->serializer);
        $this->isEncoded = true;
    }

    protected function encodeHeader(PacketSerializer $out): void
    {
        $out->putLShort(static::NETWORK_ID);
    }

    protected function encodePayload(PacketSerializer $out): void
    {
    }

    abstract public function handlePacket(): void;

    public function clean(): Packet
    {
        $this->serializer = new PacketSerializer();
        $this->isEncoded = false;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        throw new \Error("Undefined property: " . get_class($this) . "::\$" . $name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value): void
    {
        throw new \Error("Undefined property: " . get_class($this) . "::\$" . $name);
    }
}
