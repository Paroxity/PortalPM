<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use paroxity\portal\packet\TransferResponsePacket;
use paroxity\portal\Portal;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;

class TransferCommand extends BaseCommand
{
	public function __construct(Portal $plugin)
	{
		parent::__construct(
			$plugin,
			"transfer",
			"Fast transfer player to another server",
		);
		$this->setPermission("portal.command.transfer");
	}

	protected function prepare(): void
	{
		$this->registerArgument(0, new TargetPlayerArgument());
		$this->registerArgument(1, new RawStringArgument("group"));
		$this->registerArgument(2, new RawStringArgument("server"));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		/** @var Portal $plugin */
		$plugin = $this->plugin;
		$player = $plugin->getServer()->getPlayerByPrefix($args["player"]);

		if(!$player instanceof Player){
			$plugin->findPlayer(null, $args["player"], function(UuidInterface $uuid, string $playerName, bool $online, string $group, string $server) use ($sender, $args): void {
				if(!$online) {
					$sender->sendMessage(TextFormat::RED . "Player could not be found");
					return;
				}

				$this->transfer($sender, $uuid, $args["group"], $args["server"]);
			});
			return;
		}

		$this->transfer($sender, $player->getUniqueId(), $args["group"], $args["server"]);
	}

	private function transfer(CommandSender $sender, UuidInterface $uuid, string $group, string $server): void
	{
		/** @var Portal $plugin */
		$plugin = $this->plugin;
		$plugin->transferPlayerByUUID($uuid, $group, $server, function(?Player $player, int $status, string $error) use ($sender, $group, $server): void {
			switch($status) {
				case TransferResponsePacket::RESPONSE_SUCCESS:
					if($sender !== $player && !$sender instanceof ConsoleCommandSender) {
						$sender->sendMessage(TextFormat::GREEN . "Player: " . $player->getName() . " was transferred to " . $group . ":" . $server . " successfully");
					}

					$player->sendMessage(TextFormat::GREEN . "You were transferred to " . $group . ":" . $server);
					$this->plugin->getLogger()->info("Player: " . $player->getName() . " was transferred to " . $group . ":" . $server . " by " . $sender->getName());
				break;

				case TransferResponsePacket::RESPONSE_SERVER_NOT_FOUND:
					$sender->sendMessage(TextFormat::RED . "Server: " . $group . ":" . $server . " not found");
				break;

				case TransferResponsePacket::RESPONSE_ALREADY_ON_SERVER:
					$sender->sendMessage(TextFormat::RED . "Player is already on that server");
				break;

				case TransferResponsePacket::RESPONSE_PLAYER_NOT_FOUND:
					$sender->sendMessage(TextFormat::RED . "Player could not be found");
				break;

				case TransferResponsePacket::RESPONSE_ERROR:
					$sender->sendMessage(TextFormat::RED . "An error occurred while trying to transfer the player");
					$sender->sendMessage(TextFormat::RED . "Error: " . $error);
				break;
			}
		});
	}
}