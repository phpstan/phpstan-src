<?php

namespace Bug8781;

class ExecSync
{
	/**
	 * @var array
	 */
	private $stdOut;

	/**
	 * @var string
	 */
	private $command;

	/**
	 * @param string $command
	 */
	public function __construct($command)
	{
		$this->command = $command;
	}

	public function run(): void
	{
		exec($this->command, $this->stdOut);
	}

	public function wait(): array
	{
		return $this->stdOut;
	}
}

