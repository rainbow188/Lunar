<?php

namespace blackjack200\lunar\listener;

use blackjack200\lunar\LunarPlayer;
use blackjack200\lunar\user\UserManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;

class DefaultListener implements Listener {
	/** @var LoginPacket[] */
	private array $dirtyLoginPacket = [];

	public function onPlayerJoin(PlayerPreLoginEvent $event) : void {
		UserManager::register($event->getPlayer());
		$hash = spl_object_hash($event->getPlayer());
		foreach (UserManager::getUser($event->getPlayer())->getProcessors() as $processor) {
			$processor->processClient($this->dirtyLoginPacket[$hash]);
		}
		unset($this->dirtyLoginPacket[$hash]);
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void {
		UserManager::unregister($event->getPlayer());
		unset($this->dirtyLoginPacket[spl_object_hash($event->getPlayer())]);
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void {
		$user = UserManager::getUser($event->getPlayer());
		if ($user !== null) {
			foreach ($user->getProcessors() as $processor) {
				$processor->processServerBond($event->getPacket());
			}
		}
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void {
		$packet = $event->getPacket();
		if ($packet instanceof LoginPacket) {
			$this->dirtyLoginPacket[spl_object_hash($event->getPlayer())] = $packet;
		}
		$user = UserManager::getUser($event->getPlayer());
		if ($user !== null) {
			foreach ($user->getProcessors() as $processor) {
				$processor->processClient($packet);
			}
		}
	}
}
