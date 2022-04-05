<?php
declare(strict_types=1);

namespace paroxity\portal\packet\types;

class ServerListEntry
{
    private string $name;
    private int $playerCount;

    public static function create(string $name,  int $playerCount): self {
        $result = new self;
        $result->name = $name;
        $result->playerCount = $playerCount;
        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }
}
