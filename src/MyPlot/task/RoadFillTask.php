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
	protected $plugin;
	/** @var Plot $plot */
	protected $plot;
	/** @var \pocketmine\level\Level|null $level */
	protected $level;
	/** @var int $height */
	protected $height;
	/** @var Block $plotWallBlock */
	protected $plotWallBlock;
	/** @var Position|Vector3|null $plotBeginPos */
	protected $plotBeginPos;
	/** @var int $xMax */
	protected $xMax;
	/** @var int $zMax */
	protected $zMax;
	/** @var Block $roadBlock */
	protected $roadBlock;
	/** @var Block $groundBlock */
	protected $groundBlock;
	/** @var Block $bottomBlock */
	protected $bottomBlock;
	/** @var int $maxBlocksPerTick */
	protected $maxBlocksPerTick;
	/** @var Vector3 $pos */
	protected $pos;

	//private $roadCounts, $plots, $level, $height, $bottomBlock, $plotFillBlock, $plotFloorBlock, $plotBeginPos, $xMax, $zMax, $maxBlocksPerTick;

	/**
	 * PlotMergeTask constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Plot $start
	 * @param Plot $end
	 * @param int $maxBlocksPerTick
	 */
	public function __construct(MyPlot $plugin, Plot $start, Plot $end, int $maxBlocksPerTick = 256) {
		if($start->isSame($end) or !(abs($start->X - $end->X) < 2 or abs($start->Z - $end->Z) < 2)) {
			throw new \Exception("Invalid Plot arguments");
		}

		$this->plugin = $plugin;
		$this->plot = $start;
		$this->plotBeginPos = $plugin->getPlotPosition($start);
		$this->level = $this->plotBeginPos->getLevel();

		$plotLevel = $plugin->getLevelSettings($start->levelName);
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;

		if(($start->X - $end->X) === 1) {
			$this->plotBeginPos = $this->plotBeginPos->add(0,0,$plotSize);
			$this->xMax = (int)($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int)($this->plotBeginPos->z + $roadWidth);
		}elseif(($start->X - $end->X) === -1) {
			$this->plotBeginPos = $this->plotBeginPos->subtract(0,0,$roadWidth);
			$this->xMax = (int)($this->plotBeginPos->x + $plotSize);
			$this->zMax = (int)($this->plotBeginPos->z + $roadWidth);
		}elseif(($start->Z - $end->Z) === 1) {
			$this->plotBeginPos = $this->plotBeginPos->subtract($roadWidth);
			$this->xMax = (int)($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int)($this->plotBeginPos->z + $plotSize);
		}elseif(($start->Z - $end->Z) === -1) {
			$this->plotBeginPos = $this->plotBeginPos->add($plotSize);
			$this->xMax = (int)($this->plotBeginPos->x + $roadWidth);
			$this->zMax = (int)($this->plotBeginPos->z + $plotSize);
		}

		$this->height = $plotLevel->groundHeight;
		//$this->plotWallBlock = $plotLevel->wallBlock; TODO: delete?
		$this->roadBlock = $plotLevel->plotFloorBlock;
		$this->groundBlock = $plotLevel->plotFillBlock;
		$this->bottomBlock = $plotLevel->bottomBlock;

		$this->maxBlocksPerTick = $maxBlocksPerTick;
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);

		$plugin->getLogger()->debug("Road Clear Task started between plots {$start->X};{$start->Z} and {$end->X};{$end->Z}");
	}

	public function onRun(int $currentTick) {
		$blocks = 0;
		while($this->pos->x < $this->xMax) {
			while($this->pos->z < $this->zMax) {
				while($this->pos->y < $this->level->getWorldHeight()) {
					if($this->pos->y === 0) {
						$block = $this->bottomBlock;
					}elseif($this->pos->y < $this->height) {
						$block = $this->groundBlock;
					}elseif($this->pos->y === $this->height) {
						$block = $this->roadBlock;
					}else{
						$block = Block::get(Block::AIR);
					}
					$this->level->setBlock($this->pos, $block, false, false);
					$blocks++;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
					$this->pos->y++;
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
			$this->pos->z = $this->plotBeginPos->z;
			$this->pos->x++;
		}
		$this->plugin->getLogger()->debug("Plot Road Clear task completed at {$this->plot->X};{$this->plot->Z}");
	}
}