<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * @internal
 */
final class FinalizedPendingFixes
{

	/**
	 * @param array<string, FileFix> $fixesByFixingFile
	 * @param array<int, string> $skipReasonByErrorId
	 */
	public function __construct(
		public readonly array $fixesByFixingFile,
		public readonly array $skipReasonByErrorId,
	)
	{
	}

}
