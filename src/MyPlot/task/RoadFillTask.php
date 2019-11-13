<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

class RoadFillTask extends Task {
	/** @var MyPlot $plugin */
	private $plugin;
	private $roadCounts, $plots, $level, $height, $bottomBlock, $plotFillBlock, $plotFloorBlock, $plotBeginPos, $xMax, $zMax, $maxBlocksPerTick;

	/**
	 * PlotMergeTask constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Plot[] $plots
	 * @param int $maxBlocksPerTick
	 */
	public function __construct(MyPlot $plugin, array $plots, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		foreach($plots as $plot) {
			$key = $plot->__toString().$plot->levelName;
			$this->roadCounts[$key] = count($plugin->getProvider()->getMergedPlots($plot, true));
			$this->plots[$key] = $plot;
		}
		$this->maxBlocksPerTick = $maxBlocksPerTick;
	}

	public function onRun(int $currentTick) {
		foreach($this->roadCounts as $key => $count) {
			$plot = $this->plots[$key];
			$plotBeginPos = $this->plugin->getPlotPosition($plot);
			$level = $plotBeginPos->getLevel();
			$plotLevel = $this->plugin->getLevelSettings($plot->levelName);
			$plotSize = $plotLevel->plotSize;
			$xMax = $plotBeginPos->x + $plotSize;
			$zMax = $plotBeginPos->z + $plotSize;
			$height = $plotLevel->groundHeight;
			$bottomBlock = $plotLevel->bottomBlock;
			$plotFillBlock = $plotLevel->plotFillBlock;
			$plotFloorBlock = $plotLevel->plotFloorBlock;
			$blocks = 0;
			while($pos->x < $xMax) {
				while($pos->z < $zMax) {
					if($this->plugin->getPlotByPosition(Position::fromObject($pos, $level)) === null) {
						while($pos->y < $level->getWorldHeight()) {
							if($pos->y === 0) {
								$block = $bottomBlock;
							}elseif($pos->y < $height) {
								$block = $plotFillBlock;
							}elseif($pos->y === $height) {
								$block = $plotFloorBlock;
							}else{
								$block = Block::get(Block::AIR);
							}
							$level->setBlock($pos, $block, false, false);
							$blocks++;
							if($blocks >= $this->maxBlocksPerTick) {
								$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
								return;
							}
							$pos->y++;
						}
						$pos->y = 0;
					}
					$pos->z++;
				}
				$pos->z = $plotBeginPos->z;
				$pos->x++;
			}

			unset($this->roadCounts[$key]);
		}
	}
}