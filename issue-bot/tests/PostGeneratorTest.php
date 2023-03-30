<?php declare(strict_types = 1);

namespace PHPStan\IssueBot;

use PHPStan\IssueBot\Comment\BotComment;
use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPStan\IssueBot\Playground\PlaygroundExample;
use PHPStan\IssueBot\Playground\PlaygroundResultTab;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class PostGeneratorTest extends TestCase
{

	/**
	 * @return iterable<array{string, list<PlaygroundResultTab>, list<PlaygroundResultTab>, BotComment[], string|null}>
	 */
	public function dataGeneratePosts(): iterable
	{
		$diff = '@@ @@
-1: abc
+1: def
';

		$details = "| Line | Error |
|---|---|
| 1 | `def` |
";

		yield [
			'abc-def',
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'abc', null),
			])],
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'abc', null),
			])],
			[],
			null,
			null,
		];

		yield [
			'abc-def',
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'abc', null),
			])],
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'def', null),
			])],
			[],
			$diff,
			$details,
		];

		yield [
			'abc-def',
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'abc', null),
			])],
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'def', null),
			])],
			[
				new BotComment('<text>', new PlaygroundExample('', 'abc-def'), 'some diff'),
			],
			$diff,
			$details,
		];

		yield [
			'abc-def',
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'abc', null),
			])],
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'def', null),
			])],
			[
				new BotComment('<text>', new PlaygroundExample('', 'abc-def'), $diff),
			],
			null,
			null,
		];

		yield [
			'abc-def',
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'abc', null),
			])],
			[new PlaygroundResultTab('PHP 7.1', [
				new PlaygroundError(1, 'Internal error', null),
			])],
			[],
			null,
			null,
		];
	}

	/**
	 * @dataProvider dataGeneratePosts
	 * @param list<PlaygroundResultTab> $originalTabs
	 * @param list<PlaygroundResultTab> $currentTabs
	 * @param BotComment[] $botComments
	 */
	public function testGeneratePosts(
		string $hash,
		array $originalTabs,
		array $currentTabs,
		array $botComments,
		?string $expectedDiff,
		?string $expectedDetails
	): void
	{
		$generator = new PostGenerator(new Differ(new UnifiedDiffOutputBuilder('')));
		$text = $generator->createText(
			$hash,
			$originalTabs,
			$currentTabs,
			$botComments,
		);
		if ($text === null) {
			self::assertNull($expectedDiff);
			self::assertNull($expectedDetails);
			return;
		}

		self::assertSame($expectedDiff, $text['diff']);
		self::assertSame($expectedDetails, $text['details']);
	}

}
