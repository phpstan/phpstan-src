<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Process;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class DefaultProcessFactory implements ProcessFactory
{

	/** @var OutputInterface */
	private $output;

	public function __construct()
	{
		$this->output = new NullOutput();
	}

	public function create(string $command, string $cwd): Process
	{
		return new SymfonyProcess($command, $cwd, $this->output);
	}

	public function setOutput(OutputInterface $output): void
	{
		$this->output = $output;
	}

}
