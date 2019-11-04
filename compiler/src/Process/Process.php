<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Process;

interface Process
{

	public function getProcess(): \Symfony\Component\Process\Process;

}
