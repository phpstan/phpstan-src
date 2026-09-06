<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug13876;

use Closure;

/**
 * @template TBait
 * @template TPromised
 */
class Trap
{

	/** @var TBait */
	private $bait;

	/** @var Closure(TBait): TPromised */
	private $switch;

	/**
	 * @param TBait $bait
	 * @param Closure(TBait): TPromised $switch
	 */
	public function __construct($bait, Closure $switch)
	{
		$this->bait = $bait;
		$this->switch = $switch;
	}

	/** @return TPromised */
	public function fall()
	{
		return ($this->switch)($this->bait);
	}

}

class A
{

}

class B
{

	/** @var Trap<int|null, A|null> */
	private Trap $b;

	public function __construct()
	{
		/** @var Trap<int|null, A|null> $nullPerson */
		$nullPerson = new Trap(null, function (): ?A {
			return null;
		});

		$this->b = $nullPerson;
	}

}
