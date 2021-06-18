<?php
declare(strict_types=1);

namespace paroxity\portal\packet\types;

class ServerListEntry
{
    private string $name;
    private string $group;
    private bool $online;
    private int $playerCount;

    public static function create(string $name, string $group, bool $online, int $playerCount): self {
        $result = new self;
        $result->name = $name;
        $result->group = $group;
        $result->online = $online;
        $result->playerCount = $playerCount;
        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroup(): string
    {
        return $this->group;
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
