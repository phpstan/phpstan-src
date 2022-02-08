<?php

namespace Bug4910;

class Facing{
	public const NORTH = 2;
	public const SOUTH = 3;
	public const WEST = 4;
	public const EAST = 5;
}

/**
 * @phpstan-type VineAcceptedFaces Facing::NORTH|Facing::EAST|Facing::SOUTH|Facing::WEST
 */
class Vine{
	/**
	 * @var int[]
	 * @phpstan-var array<VineAcceptedFaces, VineAcceptedFaces>
	 */
	protected $faces = [];

	/**
	 * @param int[] $faces
	 * @phpstan-param list<VineAcceptedFaces> $faces
	 * @return $this
	 */
	public function setFaces(array $faces) : self{
		$uniqueFaces = [];
		foreach($faces as $face){
			if($face !== Facing::NORTH && $face !== Facing::SOUTH && $face !== Facing::WEST && $face !== Facing::EAST){
				throw new \InvalidArgumentException("Facing can only be north, east, south or west");
			}
			$uniqueFaces[$face] = $face;
		}

		$this->faces = $uniqueFaces;
		return $this;
	}
}
