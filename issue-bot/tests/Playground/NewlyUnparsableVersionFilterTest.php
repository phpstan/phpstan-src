<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

use PHPStan\IssueBot\PostGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function array_keys;

class NewlyUnparsableVersionFilterTest extends TestCase
{

	/**
	 * Original results of https://phpstan.org/r/cd95baf9-67a2-460d-ac83-52cfe91c58bb,
	 * a snippet with an arrow function, recorded before PHP-Parser 5.8.0
	 * started reverse-emulating `fn` back into a plain identifier on PHP < 7.4.
	 *
	 * @return list<PlaygroundError>
	 */
	private static function fnSnippetErrors(): array
	{
		return [
			new PlaygroundError(6, 'Dumped type: array<mixed, mixed>', 'phpstan.dumpType'),
			new PlaygroundError(8, 'Function array_find not found.', 'function.notFound'),
			new PlaygroundError(9, 'Dumped type: array<mixed, mixed>', 'phpstan.dumpType'),
			new PlaygroundError(11, 'Parameter #2 $array of function implode expects array<string>, array<mixed, mixed> given.', 'argument.type'),
		];
	}

	/**
	 * @return list<PlaygroundError>
	 */
	private static function fnSnippetErrorsSince84(): array
	{
		return [
			new PlaygroundError(6, 'Dumped type: array<mixed, mixed>', 'phpstan.dumpType'),
			new PlaygroundError(9, 'Dumped type: array<mixed, mixed>', 'phpstan.dumpType'),
			new PlaygroundError(11, 'Parameter #2 $array of function implode expects array<string>, array<mixed, mixed> given.', 'argument.type'),
		];
	}

	/**
	 * @return list<PlaygroundError>
	 */
	private static function fnSnippetParseErrors(): array
	{
		return [
			new PlaygroundError(8, 'Syntax error, unexpected \')\' on line 8', 'phpstan.parse'),
			new PlaygroundError(8, 'Syntax error, unexpected T_DOUBLE_ARROW, expecting \')\' on line 8', 'phpstan.parse'),
		];
	}

	/**
	 * @return iterable<string, array{array<int, list<PlaygroundError>>, array<int, list<PlaygroundError>>, array<int, list<PlaygroundError>>, array<int, list<PlaygroundError>>}>
	 */
	static public function dataFilter(): iterable
	{
		$fnOriginal = [
			70200 => self::fnSnippetErrors(),
			70300 => self::fnSnippetErrors(),
			70400 => self::fnSnippetErrors(),
			80000 => self::fnSnippetErrors(),
			80100 => self::fnSnippetErrors(),
			80200 => self::fnSnippetErrors(),
			80300 => self::fnSnippetErrors(),
			80400 => self::fnSnippetErrorsSince84(),
			80500 => self::fnSnippetErrorsSince84(),
		];
		$fnNew = $fnOriginal;
		$fnNew[70200] = self::fnSnippetParseErrors();
		$fnNew[70300] = self::fnSnippetParseErrors();

		$fnExpectedOriginal = $fnOriginal;
		unset($fnExpectedOriginal[70200], $fnExpectedOriginal[70300]);
		$fnExpectedNew = $fnNew;
		unset($fnExpectedNew[70200], $fnExpectedNew[70300]);

		yield 'arrow function snippet stops parsing on PHP < 7.4' => [
			$fnOriginal,
			$fnNew,
			$fnExpectedOriginal,
			$fnExpectedNew,
		];

		$unparsableEverywhere = [
			70400 => self::fnSnippetParseErrors(),
			80500 => self::fnSnippetParseErrors(),
		];
		yield 'snippet no longer parses even on the newest version' => [
			[70400 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrors()],
			$unparsableEverywhere,
			[70400 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrors()],
			$unparsableEverywhere,
		];

		yield 'original already knew about the parse error (old snapshot without identifiers)' => [
			[
				70300 => [new PlaygroundError(2, 'Syntax error, unexpected T_DOUBLE_ARROW on line 2', null)],
				80500 => self::fnSnippetErrors(),
			],
			[
				70300 => self::fnSnippetParseErrors(),
				80500 => self::fnSnippetErrors(),
			],
			[
				70300 => [new PlaygroundError(2, 'Syntax error, unexpected T_DOUBLE_ARROW on line 2', null)],
				80500 => self::fnSnippetErrors(),
			],
			[
				70300 => self::fnSnippetParseErrors(),
				80500 => self::fnSnippetErrors(),
			],
		];

		yield 'original already knew about the parse error (with identifier)' => [
			[
				70300 => [new PlaygroundError(2, 'Syntax error, unexpected T_DOUBLE_ARROW on line 2', 'phpstan.parse')],
				80500 => self::fnSnippetErrors(),
			],
			[
				70300 => self::fnSnippetParseErrors(),
				80500 => self::fnSnippetErrors(),
			],
			[
				70300 => [new PlaygroundError(2, 'Syntax error, unexpected T_DOUBLE_ARROW on line 2', 'phpstan.parse')],
				80500 => self::fnSnippetErrors(),
			],
			[
				70300 => self::fnSnippetParseErrors(),
				80500 => self::fnSnippetErrors(),
			],
		];

		$mixedErrors = [
			new PlaygroundError(8, 'Syntax error, unexpected T_DOUBLE_ARROW, expecting \')\' on line 8', 'phpstan.parse'),
			new PlaygroundError(11, 'Function array_find not found.', 'function.notFound'),
		];
		yield 'new result mixes a parse error with analysis errors' => [
			[70300 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrors()],
			[70300 => $mixedErrors, 80500 => self::fnSnippetErrors()],
			[70300 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrors()],
			[70300 => $mixedErrors, 80500 => self::fnSnippetErrors()],
		];

		yield 'unparsable version not present in original results' => [
			[70400 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrorsSince84()],
			[
				70200 => self::fnSnippetParseErrors(),
				70300 => self::fnSnippetParseErrors(),
				70400 => self::fnSnippetErrors(),
				80500 => self::fnSnippetErrorsSince84(),
			],
			[70400 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrorsSince84()],
			[70400 => self::fnSnippetErrors(), 80500 => self::fnSnippetErrorsSince84()],
		];

		yield 'filtering would leave no original versions to compare' => [
			[70300 => self::fnSnippetErrors()],
			[70300 => self::fnSnippetParseErrors(), 80500 => self::fnSnippetErrors()],
			[70300 => self::fnSnippetErrors()],
			[70300 => self::fnSnippetParseErrors(), 80500 => self::fnSnippetErrors()],
		];

		yield 'no new results' => [
			[70300 => self::fnSnippetErrors()],
			[],
			[70300 => self::fnSnippetErrors()],
			[],
		];
	}

	/**
	 * @param array<int, list<PlaygroundError>> $originalErrors
	 * @param array<int, list<PlaygroundError>> $newErrors
	 * @param array<int, list<PlaygroundError>> $expectedOriginalErrors
	 * @param array<int, list<PlaygroundError>> $expectedNewErrors
	 */
	#[DataProvider('dataFilter')]
	public function testFilter(
		array $originalErrors,
		array $newErrors,
		array $expectedOriginalErrors,
		array $expectedNewErrors,
	): void
	{
		$filter = new NewlyUnparsableVersionFilter();
		[$filteredOriginalErrors, $filteredNewErrors] = $filter->filter($originalErrors, $newErrors);

		self::assertSame(array_keys($expectedOriginalErrors), array_keys($filteredOriginalErrors));
		self::assertSame(array_keys($expectedNewErrors), array_keys($filteredNewErrors));
		self::assertEquals($expectedOriginalErrors, $filteredOriginalErrors);
		self::assertEquals($expectedNewErrors, $filteredNewErrors);
	}

	public function testNoCommentForSnippetNewlyUnparsableOnOldVersions(): void
	{
		$originalErrors = [
			70200 => self::fnSnippetErrors(),
			70300 => self::fnSnippetErrors(),
			70400 => self::fnSnippetErrors(),
			80000 => self::fnSnippetErrors(),
			80100 => self::fnSnippetErrors(),
			80200 => self::fnSnippetErrors(),
			80300 => self::fnSnippetErrors(),
			80400 => self::fnSnippetErrorsSince84(),
			80500 => self::fnSnippetErrorsSince84(),
		];
		$newErrors = $originalErrors;
		$newErrors[70200] = self::fnSnippetParseErrors();
		$newErrors[70300] = self::fnSnippetParseErrors();

		$tabCreator = new TabCreator();
		$postGenerator = new PostGenerator(new Differ(new UnifiedDiffOutputBuilder('')));

		// without the filter the bot would post a comment about the new parse errors
		$text = $postGenerator->createText(
			'cd95baf9-67a2-460d-ac83-52cfe91c58bb',
			$tabCreator->create($originalErrors),
			$tabCreator->create($newErrors),
			[],
		);
		self::assertNotNull($text);

		[$filteredOriginalErrors, $filteredNewErrors] = (new NewlyUnparsableVersionFilter())->filter($originalErrors, $newErrors);
		$text = $postGenerator->createText(
			'cd95baf9-67a2-460d-ac83-52cfe91c58bb',
			$tabCreator->create($filteredOriginalErrors),
			$tabCreator->create($filteredNewErrors),
			[],
		);
		self::assertNull($text);
	}

}
