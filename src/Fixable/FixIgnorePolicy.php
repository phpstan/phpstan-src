<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;
use function array_key_exists;

/**
 * @internal
 */
final class FixIgnorePolicy
{

	/**
	 * @param array<string, array<int, non-empty-list<array{name: string, comment: string|null}>|null>> $linesToIgnore
	 * @param array<string, array<string, true>> $witnessedIdentifiersByFixingFile
	 */
	public function __construct(
		private array $linesToIgnore,
		private array $witnessedIdentifiersByFixingFile,
	)
	{
	}

	public function shouldDrop(Error $error): bool
	{
		$line = $error->getLine();
		if ($line !== null) {
			$fileKey = $error->getFile();
			if (array_key_exists($line, $this->linesToIgnore[$fileKey] ?? [])) {
				$lineIgnores = $this->linesToIgnore[$fileKey][$line];
				if ($lineIgnores === null) {
					return true;
				}
				$errorIdentifier = $error->getIdentifier();
				foreach ($lineIgnores as $entry) {
					if ($entry['name'] === $errorIdentifier) {
						return true;
					}
				}
			}
		}

		$errorIdentifier = $error->getIdentifier();
		if ($errorIdentifier === null) {
			return false;
		}
		$fixingFile = $error->getTraitFilePath() ?? $error->getFilePath();

		return isset($this->witnessedIdentifiersByFixingFile[$fixingFile][$errorIdentifier]);
	}

}
