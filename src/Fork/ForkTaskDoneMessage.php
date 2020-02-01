<?php declare(strict_types = 1);

namespace PHPStan\Fork;

class ForkTaskDoneMessage implements ForkMessage
{

	/** @var string */
	public $file;

	/** @var mixed */
	public $data;

	/**
	 * @param string $file
	 * @param mixed $data
	 */
	public function __construct(string $file, $data)
	{
		$this->file = $file;
		$this->data = $data;
	}

}
