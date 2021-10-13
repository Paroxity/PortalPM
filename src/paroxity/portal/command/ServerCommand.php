<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use paroxity\portal\Portal;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;
use function strtolower;

class ServerCommand extends BaseCommand
{
	public function __construct(Portal $plugin)
	{
		parent::__construct(
			$plugin,
			"server",
			"Check which server you are on currently",
		);
		$this->setPermission("portal.command.server;portal.command.server.self;portal.command.server.other");
	}

	protected function prepare(): void
	{
		$this->registerArgument(0, new TargetPlayerArgument(true));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		/** @var Portal $plugin */
		$plugin = $this->plugin;
		$target = $sender->getName();

		if($sender instanceof ConsoleCommandSender && !isset($args["player"])) {
			$this->sendUsage();
			return;
		}

		if(isset($args["player"]) && !$sender->hasPermission("portal.command.server.other")) {
			$sender->sendMessage(TextFormat::RED . "You don't have the permission to check server of other player");
			return;
		}

		if(isset($args["player"])) {
			$target = $args["player"];
		}

		$plugin->findPlayer(null, $target, function(UuidInterface $uuid, string $playerName, bool $online, string $server) use ($sender): void {
			if(!$online) {
				$sender->sendMessage(TextFormat::RED . "Player: $playerName could not be found");
				return;
			}

			if(strtolower($sender->getName()) === strtolower($playerName)) {
				$sender->sendMessage(TextFormat::GREEN . "You are currently on $server");
			}else{
				$sender->sendMessage(TextFormat::GREEN . "Player: $playerName is currently on $server");
			}
		});
	}
}