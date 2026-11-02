<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\FixedErrorDiff;
use PHPUnit\Framework\TestCase;
use function hash;

class PatcherTest extends TestCase
{

	public function testGroupIsSkippedInAllFilesWhenItConflictsInOne(): void
	{
		$x = "x1\nx2\nx3\n";
		$y = "y1\ny2\ny3\n";
		$diff = static fn (string $file, string $contents, string $diff): FixedErrorDiff => new FixedErrorDiff($file, hash('sha256', $contents), $diff);

		$result = (new Patcher())->apply(['x.php' => $x, 'y.php' => $y], [
			[
				$diff('x.php', $x, "@@ -2,1 +2,1 @@\n-x2\n+A\n"),
				$diff('y.php', $y, "@@ -1,1 +1,1 @@\n-y1\n+A\n"),
			],
			[
				// conflicts with the first group in x.php, so its change of y.php is not applied either
				$diff('x.php', $x, "@@ -2,1 +2,1 @@\n-x2\n+B\n"),
				$diff('y.php', $y, "@@ -3,1 +3,1 @@\n-y3\n+B\n"),
			],
			[
				// computed against different contents
				$diff('y.php', "changed\n", "@@ -2,1 +2,1 @@\n-y2\n+C\n"),
			],
		]);

		$this->assertSame(['x.php' => "x1\nA\nx3\n", 'y.php' => "A\ny2\ny3\n"], $result['contents']);
		$this->assertSame(1, $result['appliedCount']);
		$this->assertSame(2, $result['skippedCount']);
	}

}
