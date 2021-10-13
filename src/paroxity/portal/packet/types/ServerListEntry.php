<?php
declare(strict_types=1);

namespace paroxity\portal\packet\types;

class ServerListEntry
{
    private string $name;
    private bool $online;
    private int $playerCount;

    public static function create(string $name, bool $online, int $playerCount): self {
        $result = new self;
        $result->name = $name;
        $result->online = $online;
        $result->playerCount = $playerCount;
        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }
}
