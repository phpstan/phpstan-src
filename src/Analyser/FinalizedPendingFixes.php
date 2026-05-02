<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * @internal
 */
final class FinalizedPendingFixes
{

	/**
	 * @param array<int, FixedErrorDiff> $diffsByErrorId
	 * @param array<int, string> $skipReasonByErrorId
	 */
	public function __construct(
		public readonly array $diffsByErrorId,
		public readonly array $skipReasonByErrorId,
	)
	{
	}

}
