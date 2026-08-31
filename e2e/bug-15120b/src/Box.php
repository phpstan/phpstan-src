<?php declare(strict_types = 1);

namespace Bug15120b;

/** @template T */
class Box
{

	/** @var T */
	private $item;

	/** @param T $item */
	public function __construct($item)
	{
		$this->item = $item;
	}

	/** @return T */
	public function get()
	{
		return $this->item;
	}

}
