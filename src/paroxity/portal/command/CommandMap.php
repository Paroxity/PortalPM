<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use CortexPE\Commando\BaseCommand;
use paroxity\portal\Portal;

class CommandMap
{
	private static Portal $plugin;

	public static function init(Portal $plugin)
	{
		self::$plugin = $plugin;

		if(!$plugin->getConfig()->getNested("command.enable", true)) {
			return;
		}

		self::registerCommand("transfer", new TransferCommand($plugin));
	}

	private static function registerCommand(string $name, BaseCommand $command) {
		if(!self::$plugin->getConfig()->getNested("command.commands." . $name, true)){
			return;
		}

		self::$plugin->getServer()->getCommandMap()->register("portal", $command);
	}
}