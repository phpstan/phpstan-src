<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\FileFix;
use PHPStan\Analyser\FixedErrorDiff;
use PHPUnit\Framework\TestCase;

final class FileFixAggregatorTest extends TestCase
{

	public function testEmptyInput(): void
	{
		self::assertSame([], FileFixAggregator::aggregate([]));
	}

	public function testSingleAnalysedFileSingleFixingFile(): void
	{
		$diff = new FixedErrorDiff('hash-A', 'diff-body-A');
		$fileFix = new FileFix($diff, [['line' => 10, 'identifier' => 'rule.id']]);

		$result = FileFixAggregator::aggregate([
			'A.php' => ['T.php' => $fileFix],
		]);

		self::assertArrayHasKey('T.php', $result);
		self::assertCount(1, $result);
		self::assertSame($fileFix, $result['T.php']);
	}

	public function testTwoAnalysedFilesAgreeingDiffMergesErrorRefsDeduped(): void
	{
		$diff = new FixedErrorDiff('hash-A', 'diff-body-A');
		$fileFixA = new FileFix($diff, [['line' => 10, 'identifier' => 'rule.id']]);
		$fileFixB = new FileFix($diff, [['line' => 10, 'identifier' => 'rule.id']]);

		$result = FileFixAggregator::aggregate([
			'A.php' => ['T.php' => $fileFixA],
			'B.php' => ['T.php' => $fileFixB],
		]);

		self::assertArrayHasKey('T.php', $result);
		self::assertSame($diff, $result['T.php']->diff);
		self::assertCount(1, $result['T.php']->errorRefs);
		self::assertSame(['line' => 10, 'identifier' => 'rule.id'], $result['T.php']->errorRefs[0]);
	}

	public function testTwoAnalysedFilesAgreeingDiffMergesDistinctErrorRefs(): void
	{
		$diff = new FixedErrorDiff('hash-A', 'diff-body-A');
		$fileFixA = new FileFix($diff, [['line' => 10, 'identifier' => 'rule.id']]);
		$fileFixB = new FileFix($diff, [['line' => 20, 'identifier' => 'rule.other']]);

		$result = FileFixAggregator::aggregate([
			'A.php' => ['T.php' => $fileFixA],
			'B.php' => ['T.php' => $fileFixB],
		]);

		self::assertArrayHasKey('T.php', $result);
		self::assertCount(2, $result['T.php']->errorRefs);
	}

	public function testTwoAnalysedFilesDisagreeingDiffDropsTheFixingFile(): void
	{
		$diffA = new FixedErrorDiff('hash-A', 'diff-body-A');
		$diffB = new FixedErrorDiff('hash-B', 'diff-body-B');
		$fileFixA = new FileFix($diffA, [['line' => 10, 'identifier' => 'rule.id']]);
		$fileFixB = new FileFix($diffB, [['line' => 10, 'identifier' => 'rule.id']]);

		$result = FileFixAggregator::aggregate([
			'A.php' => ['T.php' => $fileFixA],
			'B.php' => ['T.php' => $fileFixB],
		]);

		self::assertArrayNotHasKey('T.php', $result);
	}

	public function testDifferentFixingFilesPassedThrough(): void
	{
		$diff1 = new FixedErrorDiff('hash-1', 'diff-1');
		$diff2 = new FixedErrorDiff('hash-2', 'diff-2');

		$result = FileFixAggregator::aggregate([
			'A.php' => ['T1.php' => new FileFix($diff1, [['line' => 1, 'identifier' => 'r.a']])],
			'B.php' => ['T2.php' => new FileFix($diff2, [['line' => 2, 'identifier' => 'r.b']])],
		]);

		self::assertArrayHasKey('T1.php', $result);
		self::assertArrayHasKey('T2.php', $result);
		self::assertCount(2, $result);
	}

}
