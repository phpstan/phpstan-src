<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;
use function var_export;

final class FileFixTest extends PHPStanTestCase
{

	public function testRoundTripsViaSetState(): void
	{
		$diff = new FixedErrorDiff('hash-abc', 'unified-diff-body');
		$errorRefs = [
			['line' => 12, 'identifier' => 'rule.id'],
			['line' => 24, 'identifier' => null],
		];

		$original = new FileFix($diff, $errorRefs);

		$exported = var_export($original, true);
		$reconstructed = eval('return ' . $exported . ';');

		self::assertInstanceOf(FileFix::class, $reconstructed);
		self::assertSame($diff->originalHash, $reconstructed->diff->originalHash);
		self::assertSame($diff->diff, $reconstructed->diff->diff);
		self::assertSame($errorRefs, $reconstructed->errorRefs);
	}

}
