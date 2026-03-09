<?php declare(strict_types = 1);

namespace Bug13876;

/**
 * @template BAIT
 * @template PROMISED
 */
class Trap
{

	/**
	 * @var BAIT
	 */
	private $bait;

	/**
	 * @var \Closure(BAIT):PROMISED
	 */
	private $switch;

	/**
	 * @param BAIT $bait
	 * @param \Closure(BAIT):PROMISED $switch
	 */
	public function __construct($bait, \Closure $switch)
	{
		$this->bait = $bait;
		$this->switch = $switch;
	}

	/**
	 * @return PROMISED
	 */
	public function fall()
	{
		return ($this->switch)($this->bait);
	}
}

class A {}

class B {

	/**
	 * @var Trap<int|null, A|null>
	 */
	private Trap $b;

	public function __construct() {

		/**
		 * @var Trap<int|null, A|null>
		 */
		$nullPerson = new Trap(null, function (): ?A {
			return null;
		});

		$this->b = $nullPerson;
	}

	/**
	 * @return ?A
	 */
	public function getB() {
		return $this->b->fall();
	}
}
