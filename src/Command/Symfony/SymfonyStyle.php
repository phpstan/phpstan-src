<?php declare(strict_types = 1);

namespace PHPStan\Command\Symfony;

use PHPStan\Command\OutputStyle;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * @internal
 */
final class SymfonyStyle implements OutputStyle
{

	public function __construct(private StyleInterface $symfonyStyle)
	{
	}

	public function getSymfonyStyle(): StyleInterface
	{
		return $this->symfonyStyle;
	}

	public function title(string $message): void
	{
		$this->symfonyStyle->title($message);
	}

	public function section(string $message): void
	{
		$this->symfonyStyle->section($message);
	}

	public function listing(array $elements): void
	{
		$this->symfonyStyle->listing($elements);
	}

	public function success(string $message): void
	{
		$this->symfonyStyle->success($message);
	}

	public function error(string $message): void
	{
		$this->symfonyStyle->error($message);
	}

	public function warning(string $message): void
	{
		$this->symfonyStyle->warning($message);
	}

	public function note(string $message): void
	{
		$this->symfonyStyle->note($message);
	}

	public function caution(string $message): void
	{
		$this->symfonyStyle->caution($message);
	}

	public function table(array $headers, array $rows): void
	{
		$this->symfonyStyle->table($headers, $rows);
	}

	public function newLine(int $count = 1): void
	{
		$this->symfonyStyle->newLine($count);
	}

	public function progressStart(int $max = 0): void
	{
		$this->symfonyStyle->progressStart($max);
	}

	public function progressAdvance(int $step = 1): void
	{
		$this->symfonyStyle->progressAdvance($step);
	}

	public function progressFinish(): void
	{
		$this->symfonyStyle->progressFinish();
	}

}
