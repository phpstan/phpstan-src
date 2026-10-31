<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use function array_map;
use function implode;
use function sprintf;

#[AutowiredService]
final class RestartDiagnoseExtension implements DiagnoseExtension
{

	public function print(Output $output): void
	{
		$output->writeLineFormatted('<info>Process restart:</info>');

		$detected = RestartExtensionRegistry::describeDetectedExtensions();
		$output->writeLineFormatted(sprintf(
			'Coverage/profilers found:  %s',
			$detected === [] ? 'none' : implode(', ', $detected),
		));

		$performed = RestartManager::getPerformedRestartReasons();
		if ($performed !== []) {
			$output->writeLineFormatted(sprintf(
				'Restarted for:             %s',
				implode(', ', $performed),
			));
		}

		$output->writeLineFormatted(sprintf(
			'Restart if started now:    %s',
			$this->describeRestartIfStartedNow(),
		));
		$output->writeLineFormatted('Never restarts:            under Blackfire, and the worker, diagnose and cache commands');
		$output->writeLineFormatted('');
	}

	private function describeRestartIfStartedNow(): string
	{
		// the diagnose command does not restart, so it reports the answer an
		// analyse run started the same way would get. --xdebug is a per-run
		// option, so it is reported as not given
		$reasons = RestartExtensionRegistry::collectReasons(false, null);
		if ($reasons === []) {
			return 'no, nothing to neutralize or load';
		}

		$descriptions = array_map(
			static fn (RestartReason $reason): string => sprintf('%s (%s)', $reason->getExtension(), $reason->getDescription()),
			$reasons,
		);

		if (!RestartExecutor::isSupported()) {
			return sprintf(
				'not possible - neither pcntl_exec() nor proc_open() is usable, so %s stays as it is',
				implode(', ', $descriptions),
			);
		}

		return sprintf('yes - %s', implode(', ', $descriptions));
	}

}
