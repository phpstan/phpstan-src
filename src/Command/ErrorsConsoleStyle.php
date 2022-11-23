<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
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
use function str_starts_with;
use function strlen;
use function wordwrap;
use const DIRECTORY_SEPARATOR;

class ErrorsConsoleStyle extends SymfonyStyle
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
	public function table(array $headers, array $rows): void
	{
		/** @var int $terminalWidth */
		$terminalWidth = (new Terminal())->getWidth() - 2;
		$maxHeaderWidth = strlen($headers[0]);
		foreach ($rows as $row) {
			$length = strlen($row[0]);
			if ($maxHeaderWidth !== 0 && $length <= $maxHeaderWidth) {
				continue;
			}

			$maxHeaderWidth = $length;
		}

		// manual wrapping could be replaced with $table->setColumnMaxWidth()
		// but it's buggy for <href> lines
		// https://github.com/symfony/symfony/issues/45520
		// https://github.com/symfony/symfony/issues/45521
		$headers = $this->wrap($headers, $terminalWidth, $maxHeaderWidth);
		foreach ($headers as $i => $header) {
			$newHeader = [];
			foreach (explode("\n", $header) as $h) {
				$newHeader[] = sprintf('<info>%s</info>', $h);
			}

			$headers[$i] = implode("\n", $newHeader);
		}

		foreach ($rows as $i => $row) {
			$rows[$i] = $this->wrap($row, $terminalWidth, $maxHeaderWidth);
		}

		$table = $this->createTable();
		array_unshift($rows, $headers, new TableSeparator());
		$table->setRows($rows);

		$table->render();
		$this->newLine();
	}

	/**
	 * @param string[] $rows
	 * @return string[]
	 */
	private function wrap(array $rows, int $terminalWidth, int $maxHeaderWidth): array
	{
		foreach ($rows as $i => $column) {
			$columnRows = explode("\n", $column);
			foreach ($columnRows as $k => $columnRow) {
				if (str_starts_with($columnRow, '✏️')) {
					continue;
				}
				$columnRows[$k] = wordwrap(
					$columnRow,
					$terminalWidth - $maxHeaderWidth - 5,
					"\n",
					true,
				);
			}

			$rows[$i] = implode("\n", $columnRows);
		}

		return $rows;
	}

	public function createProgressBar(int $max = 0): ProgressBar
	{
		$this->progressBar = parent::createProgressBar($max);
		$this->progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

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

	public function progressStart(int $max = 0): void
	{
		if (!$this->showProgress) {
			return;
		}
		parent::progressStart($max);
	}

	public function progressAdvance(int $step = 1): void
	{
		if (!$this->showProgress) {
			return;
		}

		parent::progressAdvance($step);
	}

	public function progressFinish(): void
	{
		if (!$this->showProgress) {
			return;
		}
		parent::progressFinish();
	}

}
