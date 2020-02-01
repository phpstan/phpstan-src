<?php declare(strict_types = 1);

namespace PHPStan\Fork;

class ForkProcessDoneMessage implements ForkMessage
{

	/** @var mixed */
	public $data;

	/**
	 * @param mixed $data
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

}
