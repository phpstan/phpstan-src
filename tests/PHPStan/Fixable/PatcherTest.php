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

	public function testRunsAreSplicedInTheOrderOfTheirLines(): void
	{
		$code = "1\n2\n3\n4\n5\n6\n";
		$group = static fn (string $diff): array => [new FixedErrorDiff('a.php', hash('sha256', $code), $diff)];

		$result = (new Patcher())->apply(['a.php' => $code], [
			$group("@@ -4,1 +4,1 @@\n-4\n+D\n"),
			// before the run of an earlier error
			$group("@@ -2,1 +2,1 @@\n-2\n+B\n"),
			// one run before the accepted ones and one after them
			$group("@@ -1,1 +1,1 @@\n-1\n+A\n@@ -6,1 +6,1 @@\n-6\n+F\n"),
			// between them
			$group("@@ -5,1 +5,1 @@\n-5\n+E\n"),
			// the same change as another error
			$group("@@ -4,1 +4,1 @@\n-4\n+D\n"),
		]);

		$this->assertSame(['a.php' => "A\nB\n3\nD\nE\nF\n"], $result['contents']);
		$this->assertSame(5, $result['appliedCount']);
	}

}
