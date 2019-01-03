<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MergeSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and ($sender->hasPermission("myplot.command.middle"));
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			return false;
		}
		$levelName = $args[2] ?? $sender->getLevel()->getFolderName();
		if(!$this->getPlugin()->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("merge.notinplotworld"));
			return true;
		}
		/** @var string[] $plotIdArray */
		$plotIdArray = explode(";", $args[0]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("merge.wrongid"));
			return true;
		}
		$plot = $this->getPlugin()->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		if($plot->owner == "" and !$sender->hasPermission("myplot.admin.merge")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("merge.unclaimed"));
			return true;
		}
		/** @var string[] $plotIdArray */
		$plotIdArray = explode(";", $args[1]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("merge.wrongid"));
			return true;
		}
		$plot2 = $this->getPlugin()->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		if($plot2->owner == "" and !$sender->hasPermission("myplot.admin.merge")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("merge.unclaimed"));
			return true;
		}
		if($this->getPlugin()->mergePlots($plot, $plot2)) {
			$plot = TextFormat::GREEN . $plot . TextFormat::WHITE;
			$plot2 = TextFormat::GREEN . $plot2 . TextFormat::WHITE;
			$sender->sendMessage($this->translateString("merge.success", [$plot2, $plot]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return false;
	}
}