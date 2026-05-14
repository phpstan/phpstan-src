<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FixIgnorePolicyTest extends PHPStanTestCase
{

	public static function dataShouldDrop(): iterable
	{
		yield 'no ignores → keep' => [
			'linesToIgnore' => [],
			'witnessedIdentifiers' => [],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => false,
		];

		yield 'wildcard line ignore drops any error on that line' => [
			'linesToIgnore' => ['F.php' => [10 => null]],
			'witnessedIdentifiers' => [],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => true,
		];

		yield 'wildcard line ignore on different line keeps error' => [
			'linesToIgnore' => ['F.php' => [11 => null]],
			'witnessedIdentifiers' => [],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => false,
		];

		yield 'identifier line ignore drops matching identifier' => [
			'linesToIgnore' => ['F.php' => [10 => [['name' => 'rule.id', 'comment' => null]]]],
			'witnessedIdentifiers' => [],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => true,
		];

		yield 'identifier line ignore keeps non-matching identifier' => [
			'linesToIgnore' => ['F.php' => [10 => [['name' => 'other.id', 'comment' => null]]]],
			'witnessedIdentifiers' => [],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => false,
		];

		yield 'baseline witness drops error of witnessed identifier' => [
			'linesToIgnore' => [],
			'witnessedIdentifiers' => ['F.php' => ['rule.id' => true]],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => true,
		];

		yield 'baseline witness for different file keeps error' => [
			'linesToIgnore' => [],
			'witnessedIdentifiers' => ['G.php' => ['rule.id' => true]],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => false,
		];

		yield 'baseline witness for different identifier keeps error' => [
			'linesToIgnore' => [],
			'witnessedIdentifiers' => ['F.php' => ['other.id' => true]],
			'error' => self::error('F.php', 10, 'rule.id'),
			'expected' => false,
		];

		yield 'error without identifier is never baseline-dropped' => [
			'linesToIgnore' => [],
			'witnessedIdentifiers' => ['F.php' => ['rule.id' => true]],
			'error' => self::error('F.php', 10, null),
			'expected' => false,
		];

		yield 'trait error uses traitFilePath for witness lookup' => [
			'linesToIgnore' => [],
			'witnessedIdentifiers' => ['T.php' => ['rule.id' => true]],
			'error' => self::traitError('T.php', 'A.php', 4, 'rule.id'),
			'expected' => true,
		];
	}

	/**
	 * @param array<string, array<int, non-empty-list<array{name: string, comment: string|null}>|null>> $linesToIgnore
	 * @param array<string, array<string, true>> $witnessedIdentifiers
	 */
	#[DataProvider('dataShouldDrop')]
	public function testShouldDrop(array $linesToIgnore, array $witnessedIdentifiers, Error $error, bool $expected): void
	{
		$policy = new FixIgnorePolicy($linesToIgnore, $witnessedIdentifiers);
		self::assertSame($expected, $policy->shouldDrop($error));
	}

	private static function error(string $filePath, int $line, ?string $identifier): Error
	{
		return new Error(
			'msg',
			$filePath,
			$line,
			true,
			$filePath,
			null,
			null,
			$line,
			null,
			$identifier,
		);
	}

	private static function traitError(string $traitFilePath, string $consumerFilePath, int $line, ?string $identifier): Error
	{
		return new Error(
			'msg',
			$traitFilePath,
			$line,
			true,
			$consumerFilePath,
			$traitFilePath,
			null,
			$line,
			null,
			$identifier,
		);
	}

}
