<?php

declare(strict_types=1);

namespace paroxity\portal\packet;

use pocketmine\network\mcpe\NetworkBinaryStream;

abstract class Packet extends NetworkBinaryStream
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

    public function decode(): void
    {
        $this->offset = 0;
        $this->decodeHeader();
        $this->decodePayload();
    }

    protected function decodeHeader(): void
    {
        $pid = $this->getLShort();
        if ($pid !== static::NETWORK_ID) {
            throw new \UnexpectedValueException("Expected " . static::NETWORK_ID . " for packet ID, got $pid");
        }
    }

    protected function decodePayload(): void
    {

    }

    public function encode(): void
    {
        $this->reset();
        $this->encodeHeader();
        $this->encodePayload();
        $this->isEncoded = true;
    }

    protected function encodeHeader(): void
    {
        $this->putLShort(static::NETWORK_ID);
    }

    protected function encodePayload(): void
    {

    }

    abstract public function handlePacket(): void;

    public function clean(): Packet
    {
        $this->buffer = "";
        $this->isEncoded = false;
        $this->offset = 0;
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
     *
     * @return void
     */
    public function __set($name, $value)
    {
        throw new \Error("Undefined property: " . get_class($this) . "::\$" . $name);
    }
}
