<?php
declare(strict_types=1);

namespace paroxity\portal\packet;

class ProtocolInfo
{

    public const AUTH_REQUEST_PACKET = 0x00;
    public const AUTH_RESPONSE_PACKET = 0x01;
    public const TRANSFER_REQUEST_PACKET = 0x02;
    public const TRANSFER_RESPONSE_PACKET = 0x03;
    public const PLAYER_INFO_REQUEST_PACKET = 0x04;
    public const PLAYER_INFO_RESPONSE_PACKET = 0x05;
}