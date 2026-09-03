<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Closure;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;

final class ResultCacheProcessResult
{

	/**
	 * @param Closure(list<Error>): bool $saveCallback
	 */
	public function __construct(private AnalyserResult $analyserResult, private Closure $saveCallback)
	{
	}

	public function getAnalyserResult(): AnalyserResult
	{
		return $this->analyserResult;
	}

	/**
	 * Writes the result cache, and says whether it was written.
	 *
	 * The rules built on collected data run on the merged result this object carries, so their errors
	 * exist only after process() is over and are never part of the cache. The files they are reported
	 * in are handed over here all the same: they have to be watched the same way the files with
	 * ordinary errors are, so that a symbol appearing somewhere re-analyses them and their collected
	 * data is recomputed instead of staying as it was.
	 *
	 * @param list<Error> $collectorErrors
	 */
	public function save(array $collectorErrors): bool
	{
		return ($this->saveCallback)($collectorErrors);
	}

}
