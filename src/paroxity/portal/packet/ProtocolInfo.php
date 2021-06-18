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
    public const SERVER_LIST_REQUEST_PACKET = 0x06;
    public const SERVER_LIST_RESPONSE_PACKET = 0x07;
    public const FIND_PLAYER_REQUEST_PACKET = 0x08;
    public const FIND_PLAYER_RESPONSE_PACKET = 0x09;
    public const UPDATE_PLAYER_LATENCY_PACKET = 0x0a;
}
