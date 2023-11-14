<?php // lint >= 8.1

namespace Bug10077;

interface MediaQueryMergeResult
{
}


enum MediaQuerySingletonMergeResult implements MediaQueryMergeResult
{
	case empty;
	case unrepresentable;
}

// In actual code, this is a final class implementing its methods
abstract class CssMediaQuery implements MediaQueryMergeResult
{
	abstract public function merge(CssMediaQuery $other): MediaQueryMergeResult;
}


/**
 * Returns a list of queries that selects for contexts that match both
 * $queries1 and $queries2.
 *
 * Returns the empty list if there are no contexts that match both $queries1
 * and $queries2, or `null` if there are contexts that can't be represented
 * by media queries.
 *
 * @param CssMediaQuery[] $queries1
 * @param CssMediaQuery[] $queries2
 *
 * @return list<CssMediaQuery>|null
 */
function mergeMediaQueries(array $queries1, array $queries2): ?array
{
	$queries = [];

	foreach ($queries1 as $query1) {
		foreach ($queries2 as $query2) {
			$result = $query1->merge($query2);

			if ($result === MediaQuerySingletonMergeResult::empty) {
				continue;
			}

			if ($result === MediaQuerySingletonMergeResult::unrepresentable) {
				return null;
			}

			$queries[] = $result;
		}
	}

	return $queries;
}
