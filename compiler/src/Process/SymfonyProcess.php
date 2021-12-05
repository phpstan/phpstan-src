<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Process;

use Symfony\Component\Console\Output\OutputInterface;

final class SymfonyProcess implements Process
{

	/** @var \Symfony\Component\Process\Process<string, string> */
	private $process;

	/**
	 * @param string[] $command
	 */
	public function __construct(array $command, string $cwd, OutputInterface $output)
	{
		$this->process = (new \Symfony\Component\Process\Process($command, $cwd, null, null, null))
			->mustRun(static function (string $type, string $buffer) use ($output): void {
				$output->write($buffer);
			});
	}

	/**
	 * @return \Symfony\Component\Process\Process<string, string>
	 */
	public function getProcess(): \Symfony\Component\Process\Process
	{
		return $this->process;
	}

}
