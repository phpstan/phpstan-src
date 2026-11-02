<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use function array_key_exists;
use function array_values;
use function is_file;

/**
 * Applies the fixes of errors to the files on disk in one go, like one round of `phpstan analyse --fix`.
 *
 * The fix of an error is applied whole or not at all. It is skipped when it overlaps with a fix
 * of an earlier error or when a file it changes was changed since the analysis.
 * Fixing errors caused by other fixes needs a new analysis, which `phpstan analyse --fix` runs in rounds.
 *
 * @api
 */
#[AutowiredService]
final class ErrorFixer
{

	public function __construct(private Patcher $patcher)
	{
	}

	/**
	 * Errors that are not fixable are ignored.
	 *
	 * @param list<Error> $errors
	 */
	public function fix(array $errors): FixResult
	{
		$fixableErrors = [];
		$diffGroups = [];
		$contentsByFile = [];
		foreach ($errors as $error) {
			if (!$error->isFixable()) {
				continue;
			}

			$fixableErrors[] = $error;
			$diffGroups[] = $error->getFixedErrorDiffs();
			foreach ($error->getFixedErrorDiffs() as $diff) {
				if (array_key_exists($diff->file, $contentsByFile) || !is_file($diff->file)) {
					continue;
				}

				$contentsByFile[$diff->file] = FileReader::read($diff->file);
			}
		}

		$result = $this->patcher->apply($contentsByFile, $diffGroups);

		$changedFiles = [];
		foreach ($result['contents'] as $file => $contents) {
			if ($contents === $contentsByFile[$file]) {
				continue;
			}

			FileWriter::write($file, $contents);
			$changedFiles[] = $file;
		}

		$skippedErrors = [];
		foreach ($result['skippedGroups'] as $key) {
			$skippedErrors[] = $fixableErrors[$key];
			unset($fixableErrors[$key]);
		}

		return new FixResult(array_values($fixableErrors), $skippedErrors, $changedFiles);
	}

}
