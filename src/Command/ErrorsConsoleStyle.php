<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
use Override;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use function array_unshift;
use function explode;
use function implode;
use function sprintf;
use function strlen;
use const DIRECTORY_SEPARATOR;

final class ErrorsConsoleStyle extends SymfonyStyle
{

	public const OPTION_NO_PROGRESS = 'no-progress';

	private bool $showProgress;

	private ProgressBar $progressBar;

	private ?bool $isCiDetected = null;

	public function __construct(InputInterface $input, OutputInterface $output)
	{
		parent::__construct($input, $output);
		$this->showProgress = $input->hasOption(self::OPTION_NO_PROGRESS) && !(bool) $input->getOption(self::OPTION_NO_PROGRESS);
	}

	private function isCiDetected(): bool
	{
		if ($this->isCiDetected === null) {
			$ciDetector = new CiDetector();
			$this->isCiDetected = $ciDetector->isCiDetected();
		}

		return $this->isCiDetected;
	}

	/**
	 * @param string[] $headers
	 * @param string[][] $rows
	 */
	#[Override]
	public function table(array $headers, array $rows): void
	{
		/** @var int $terminalWidth */
		$terminalWidth = (new Terminal())->getWidth() - 2;
		$maxHeaderWidth = strlen($headers[0]);
		foreach ($rows as $row) {
			$length = Helper::width(Helper::removeDecoration($this->getFormatter(), $row[0]));

			if ($maxHeaderWidth !== 0 && $length <= $maxHeaderWidth) {
				continue;
			}

			$maxHeaderWidth = $length;
		}

		foreach ($headers as $i => $header) {
			$newHeader = [];
			foreach (explode("\n", $header) as $h) {
				$newHeader[] = sprintf('<info>%s</info>', $h);
			}

			$headers[$i] = implode("\n", $newHeader);
		}

		$table = $this->createTable();
		// -5 because there are 5 padding spaces: One on each side of the table, one on each side of a cell and one between columns.
		$table->setColumnMaxWidth(1, $terminalWidth - $maxHeaderWidth - 5);
		array_unshift($rows, $headers, new TableSeparator());
		$table->setRows($rows);

		$table->render();
		$this->newLine();
	}

	#[Override]
	public function createProgressBar(int $max = 0): ProgressBar
	{
		$this->progressBar = parent::createProgressBar($max);

		$format = $this->getProgressBarFormat();
		if ($format !== null) {
			$this->progressBar->setFormat($format);
		}

		$ci = $this->isCiDetected();
		$this->progressBar->setOverwrite(!$ci);

		if ($ci) {
			$this->progressBar->minSecondsBetweenRedraws(15);
			$this->progressBar->maxSecondsBetweenRedraws(30);
		} elseif (DIRECTORY_SEPARATOR === '\\') {
			$this->progressBar->minSecondsBetweenRedraws(0.5);
			$this->progressBar->maxSecondsBetweenRedraws(2);
		} else {
			$this->progressBar->minSecondsBetweenRedraws(0.1);
			$this->progressBar->maxSecondsBetweenRedraws(0.5);
		}

		return $this->progressBar;
	}

	private function getProgressBarFormat(): ?string
	{
		switch ($this->getVerbosity()) {
			case OutputInterface::VERBOSITY_NORMAL:
				$formatName = ProgressBar::FORMAT_NORMAL;
				break;
			case OutputInterface::VERBOSITY_VERBOSE:
				$formatName = ProgressBar::FORMAT_VERBOSE;
				break;
			case OutputInterface::VERBOSITY_VERY_VERBOSE:
			case OutputInterface::VERBOSITY_DEBUG:
				$formatName = ProgressBar::FORMAT_VERY_VERBOSE;
				break;
			default:
				$formatName = null;
				break;
		}

		if ($formatName === null) {
			return null;
		}

		return ProgressBar::getFormatDefinition($formatName);
	}

	#[Override]
	public function progressStart(int $max = 0): void
	{
		if (!$this->showProgress) {
			return;
		}
		parent::progressStart($max);
	}

	#[Override]
	public function progressAdvance(int $step = 1): void
	{
		if (!$this->showProgress) {
			return;
		}

		parent::progressAdvance($step);
	}

	#[Override]
	public function progressFinish(): void
	{
		if (!$this->showProgress) {
			return;
		}
		parent::progressFinish();
	}

}
