<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Process;

use Symfony\Component\Console\Output\OutputInterface;

interface ProcessFactory
{

	public function create(string $command, string $cwd): Process;

	public function setOutput(OutputInterface $output): void;

}
