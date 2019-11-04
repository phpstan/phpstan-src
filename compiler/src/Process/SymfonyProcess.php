<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Process;

use Symfony\Component\Console\Output\OutputInterface;

final class SymfonyProcess implements Process
{

	/** @var \Symfony\Component\Process\Process */
	private $process;

	/**
	 * @param string[] $command
	 * @param string $cwd
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	public function __construct(array $command, string $cwd, OutputInterface $output)
	{
		$this->process = (new \Symfony\Component\Process\Process($command, $cwd, null, null, null))
			->mustRun(static function (string $type, string $buffer) use ($output): void {
				$output->write($buffer);
			});
	}

	public function getProcess(): \Symfony\Component\Process\Process
	{
		return $this->process;
	}

}
