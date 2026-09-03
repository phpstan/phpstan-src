<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * An error whose existence depends on files other than the one being analysed - a path named in the
 * code that has to exist, a data file read at analysis time. The result cache watches the files
 * listed here and re-analyses the file the error is reported in when one of them appears, changes or
 * is deleted, which nothing else would do: the dependency graph is built from reflected symbols, and
 * a path is not a symbol.
 *
 * Paths must be absolute and are watched whether or not they exist, so an error about a missing file
 * is cleared by that file being created.
 *
 * @api
 * @api-do-not-implement
 */
interface FileDependenciesRuleError extends RuleError
{

	/**
	 * @return list<string>
	 */
	public function getFileDependencies(): array;

}
