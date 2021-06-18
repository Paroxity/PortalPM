<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

class UnknownPacket extends Packet
{
    public const NETWORK_ID = -1; //Invalid, do not try to write this

    public string $payload;

    public function pid(): int{
        if(($this->payload ?? "") !== ""){
            return ord($this->payload[0]);
        }
        return self::NETWORK_ID;
    }

    public function getName() : string{
        return "unknown packet";
    }

    public function decode(): void{
        $this->payload = $this->getSerializer()->getRemaining();
    }

    public function encode(): void{
        //Do not reset the buffer, this class does not have a valid NETWORK_ID constant.
        $this->getSerializer()->put($this->payload);
    }

    public function handlePacket(): void
    {

    }
}
