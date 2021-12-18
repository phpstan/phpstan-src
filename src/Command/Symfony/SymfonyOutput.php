<?php declare(strict_types = 1);

namespace PHPStan\Command\Symfony;

use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class SymfonyOutput implements Output
{

	private OutputInterface $symfonyOutput;

	private OutputStyle $style;

	public function __construct(
		OutputInterface $symfonyOutput,
		OutputStyle $style,
	)
	{
		$this->symfonyOutput = $symfonyOutput;
		$this->style = $style;
	}

	public function writeFormatted(string $message): void
	{
		$this->symfonyOutput->write($message, false, OutputInterface::OUTPUT_NORMAL);
	}

	public function writeLineFormatted(string $message): void
	{
		$this->symfonyOutput->writeln($message, OutputInterface::OUTPUT_NORMAL);
	}

	public function writeRaw(string $message): void
	{
		$this->symfonyOutput->write($message, false, OutputInterface::OUTPUT_RAW);
	}

	public function getStyle(): OutputStyle
	{
		return $this->style;
	}

	public function isVerbose(): bool
	{
		return $this->symfonyOutput->isVerbose();
	}

	public function isDebug(): bool
	{
		return $this->symfonyOutput->isDebug();
	}

}
