<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use CortexPE\Commando\BaseCommand;
use paroxity\portal\Portal;

class CommandMap
{
	private static Portal $plugin;

	public static function init(Portal $plugin): void
	{
		self::$plugin = $plugin;

		if(!$plugin->getConfig()->getNested("command.enable", true)) {
			return;
		}

		self::registerCommand("transfer", new TransferCommand($plugin));
		self::registerCommand("server", new ServerCommand($plugin));
		self::registerCommand("servers", new ServersCommand($plugin));
	}

	private static function registerCommand(string $name, BaseCommand $command): void
	{
		if(!self::$plugin->getConfig()->getNested("command.commands." . $name, true)){
			return;
		}

		self::$plugin->getServer()->getCommandMap()->register("portal", $command);
	}
}