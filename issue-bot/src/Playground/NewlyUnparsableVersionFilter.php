<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

use function array_keys;
use function count;
use function max;

/**
 * Excludes PHP versions on which a code snippet does not parse.
 *
 * When PHP-Parser gains or fixes reverse emulation of newer syntax (like arrow
 * functions being turned back into plain identifiers on PHP < 7.4), snippets
 * using that syntax start reporting a parse error on old PHP versions instead
 * of analysis results - or report a different set of parse errors than before.
 * Such changes say nothing about the analysed issue, so these versions are
 * dropped from both sides of the comparison - but only as long as the newest
 * analysed version still parses. A version where a parse error is replaced by
 * analysis errors (the snippet newly started parsing) is kept.
 *
 * The returned original errors may come out empty - every version the original
 * snapshot covered may no longer parse. There is nothing left to compare then
 * and the snippet should be skipped.
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

			unset($filteredOriginalErrors[$phpVersion], $filteredNewErrors[$phpVersion]);
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
			if ($error->getIdentifier() !== self::PARSE_ERROR_IDENTIFIER) {
				return false;
			}
		}

		return true;
	}

}
