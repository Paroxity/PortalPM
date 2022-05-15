<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use CortexPE\Commando\BaseCommand;
use paroxity\portal\packet\types\ServerListEntry;
use paroxity\portal\Portal;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ServersCommand extends BaseCommand
{
	/** @var Portal */
	protected $plugin;

	public function __construct(Portal $plugin)
	{
		parent::__construct(
			$plugin,
			"servers",
			"Get a list of servers on the proxy",
		);
		$this->setPermission("portal.command.servers");
	}

	protected function prepare(): void
	{
		// NOOP
	}

	/**
	 * @param mixed[] $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		$this->plugin->requestServerList(function(array $servers) use($sender) {
			$serverList = array_map(fn(ServerListEntry $server) => $server->getName() . TextFormat::GREEN . " (" . $server->getPlayerCount() . " players)" . TextFormat::RESET, $servers);
			$sender->sendMessage("There are " . TextFormat::GREEN . count($servers) . TextFormat::RESET . " servers connected to the proxy: " . implode(", ", $serverList));
		});
	}
}