<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ErrorsConsoleStyle extends \Symfony\Component\Console\Style\SymfonyStyle
{

	public const OPTION_NO_PROGRESS = 'no-progress';

	private bool $showProgress;

	private \Symfony\Component\Console\Helper\ProgressBar $progressBar;

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
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $max
	 */
	public function createProgressBar($max = 0): ProgressBar
	{
		$this->progressBar = parent::createProgressBar($max);
		$this->progressBar->setOverwrite(!$this->isCiDetected());
		return $this->progressBar;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $max
	 */
	public function progressStart($max = 0): void
	{
		if (!$this->showProgress) {
			return;
		}
		parent::progressStart($max);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $step
	 */
	public function progressAdvance($step = 1): void
	{
		if (!$this->showProgress) {
			return;
		}

		if (!$this->isCiDetected() && $step > 0) {
			$stepTime = (time() - $this->progressBar->getStartTime()) / $step;
			if ($stepTime > 0 && $stepTime < 1) {
				$this->progressBar->setRedrawFrequency((int) (1 / $stepTime));
			} else {
				$this->progressBar->setRedrawFrequency(1);
			}
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
