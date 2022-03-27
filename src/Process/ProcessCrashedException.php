<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Exception;

class ProcessCrashedException extends Exception
{

	public function __construct(private ?int $exitCode, private string $stdOut, private string $stdErr)
	{
		parent::__construct($this->stdOut . $this->stdErr);
	}

	public function getExitCode(): ?int
	{
		return $this->exitCode;
	}

	public function getStdOut(): string
	{
		return $this->stdOut;
	}

	public function getStdErr(): string
	{
		return $this->stdErr;
	}

}
