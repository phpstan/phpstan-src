<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

use function array_keys;
use function count;
use function max;
use function str_contains;

/**
 * Excludes PHP versions on which a code snippet newly stopped parsing.
 *
 * When PHP-Parser gains or fixes reverse emulation of newer syntax (like arrow
 * functions being turned back into plain identifiers on PHP < 7.4), snippets
 * using that syntax start reporting a parse error on old PHP versions instead
 * of analysis results. Such a change says nothing about the analysed issue, so
 * these versions are dropped from both sides of the comparison - but only as
 * long as the newest analysed version still parses.
 */
class NewlyUnparsableVersionFilter
{

	private const PARSE_ERROR_IDENTIFIER = 'phpstan.parse';

	/**
	 * @param array<int, list<PlaygroundError>> $originalErrors
	 * @param array<int, list<PlaygroundError>> $newErrors
	 * @return array{array<int, list<PlaygroundError>>, array<int, list<PlaygroundError>>}
	 */
	public function filter(array $originalErrors, array $newErrors): array
	{
		if (count($newErrors) === 0) {
			return [$originalErrors, $newErrors];
		}

		$newestVersion = max(array_keys($newErrors));
		if ($this->isParseErrorOnly($newErrors[$newestVersion])) {
			// the snippet no longer parses at all, report that as a change
			return [$originalErrors, $newErrors];
		}

		$filteredOriginalErrors = $originalErrors;
		$filteredNewErrors = $newErrors;
		foreach ($newErrors as $phpVersion => $errors) {
			if ($phpVersion === $newestVersion) {
				continue;
			}
			if (!$this->isParseErrorOnly($errors)) {
				continue;
			}
			if ($this->hasParseError($originalErrors[$phpVersion] ?? [])) {
				continue;
			}

			unset($filteredOriginalErrors[$phpVersion], $filteredNewErrors[$phpVersion]);
		}

		if (count($filteredOriginalErrors) === 0) {
			return [$originalErrors, $newErrors];
		}

		return [$filteredOriginalErrors, $filteredNewErrors];
	}

	/**
	 * @param list<PlaygroundError> $errors
	 */
	private function isParseErrorOnly(array $errors): bool
	{
		if (count($errors) === 0) {
			return false;
		}

		foreach ($errors as $error) {
			if (!$this->isParseError($error)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param list<PlaygroundError> $errors
	 */
	private function hasParseError(array $errors): bool
	{
		foreach ($errors as $error) {
			if ($this->isParseError($error)) {
				return true;
			}
		}

		return false;
	}

	private function isParseError(PlaygroundError $error): bool
	{
		if ($error->getIdentifier() === self::PARSE_ERROR_IDENTIFIER) {
			return true;
		}

		// old playground snapshots do not have error identifiers
		return $error->getIdentifier() === null && str_contains($error->getMessage(), 'Syntax error');
	}

}
