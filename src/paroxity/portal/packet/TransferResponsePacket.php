<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

use paroxity\portal\Portal;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\uuid\UUID;

class TransferResponsePacket extends Packet
{
    public const NETWORK_ID = ProtocolInfo::TRANSFER_RESPONSE_PACKET;

    public const RESPONSE_SUCCESS = 0;
    public const RESPONSE_GROUP_NOT_FOUND = 1;
    public const RESPONSE_SERVER_NOT_FOUND = 2;
    public const RESPONSE_ALREADY_ON_SERVER = 3;
    public const RESPONSE_PLAYER_NOT_FOUND = 4;
    public const RESPONSE_ERROR = 5;

    /** @var UUID */
    public $playerUUID;
    /** @var int */
    public $status;
    /** @var string */
    public $error = "";

    public static function create(UUID $playerUUID, int $status, string $error = ""): self
    {
        $result = new self;
        $result->playerUUID = $playerUUID;
        $result->status = $status;
        $result->error = $error;

        return $result;
    }

    public function getPlayerUUID(): UUID
    {
        return $this->playerUUID;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getError(): string
    {
        return $this->error;
    }

    protected function decodePayload(PacketSerializer $in): void
    {
        $this->playerUUID = $in->getUUID();
        $this->status = $in->getByte();
        if($this->status === self::RESPONSE_ERROR){
            $this->error = $in->getString();
        }
    }

    protected function encodePayload(PacketSerializer $out): void
    {
        $out->putUUID($this->playerUUID);
        $out->putByte($this->status);
        if($this->status === self::RESPONSE_ERROR){
            $out->putString($this->error);
        }
    }

    public function handlePacket(): void
    {
        Portal::getInstance()->handleTransferResponse($this);
    }
}