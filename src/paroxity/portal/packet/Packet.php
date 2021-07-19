<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use function get_class;

abstract class Packet
{
    public const NETWORK_ID = 0;

    public bool $isEncoded = false;

    public function pid(): int
    {
        return $this::NETWORK_ID;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function decode(PacketSerializer $in): void
    {
	    $in->rewind();
        $this->decodeHeader($in);
        $this->decodePayload($in);
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

    public function encode(PacketSerializer $out): void
    {
        $this->encodeHeader($out);
        $this->encodePayload($out);
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
