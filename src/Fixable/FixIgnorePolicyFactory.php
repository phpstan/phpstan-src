<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredError;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\Ignore\IgnoredErrorHelperResult;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileHelper;
use function is_array;

/**
 * @internal
 */
#[AutowiredService]
final class FixIgnorePolicyFactory
{

	private ?IgnoredErrorHelperResult $cachedResult = null;

	public function __construct(
		private IgnoredErrorHelper $ignoredErrorHelper,
		private FileHelper $fileHelper,
	)
	{
	}

	/**
	 * @param array<string, list<Error>> $errorsByFixingFile
	 * @param array<string, array<int, non-empty-list<array{name: string, comment: string|null}>|null>> $linesToIgnore
	 */
	public function buildForFiles(array $errorsByFixingFile, array $linesToIgnore): FixIgnorePolicy
	{
		$witnessed = [];
		foreach ($errorsByFixingFile as $fixingFile => $errors) {
			$witnessed[$fixingFile] = $this->collectWitnesses($errors);
		}

		return new FixIgnorePolicy($linesToIgnore, $witnessed);
	}

	/**
	 * @param list<Error> $errors
	 * @return array<string, true>
	 */
	private function collectWitnesses(array $errors): array
	{
		$entries = $this->getExpandedEntries();
		$witnessed = [];
		foreach ($errors as $error) {
			$identifier = $error->getIdentifier();
			if ($identifier === null) {
				continue;
			}
			if (isset($witnessed[$identifier])) {
				continue;
			}
			foreach ($entries as $entry) {
				if (!is_array($entry)) {
					continue;
				}
				if (!IgnoredError::shouldIgnore(
					$this->fileHelper,
					$error,
					$entry['message'] ?? null,
					$entry['rawMessage'] ?? null,
					$entry['identifier'] ?? null,
					$entry['path'] ?? null,
				)) {
					continue;
				}
				$witnessed[$identifier] = true;
				break;
			}
		}

		return $witnessed;
	}

	/**
	 * @return (string|mixed[])[]
	 */
	private function getExpandedEntries(): array
	{
		$this->cachedResult ??= $this->ignoredErrorHelper->initialize();

		return $this->cachedResult->getIgnoreErrors();
	}

}
