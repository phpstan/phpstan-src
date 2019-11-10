<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\Parser\DirectParser;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\Rules\AlwaysFailRule;
use PHPStan\Rules\Registry;
use PHPStan\Type\FileTypeMapper;

class AnalyserTest extends \PHPStan\Testing\TestCase
{

	public function testReturnErrorIfIgnoredMessagesDoesNotOccur(): void
	{
		$result = $this->runAnalyser(['#Unknown error#'], true, __DIR__ . '/data/empty/empty.php', false);
		$this->assertSame([
			'Ignored error pattern #Unknown error# was not matched in reported errors.',
		], $result);
	}

	public function testDoNotReturnErrorIfIgnoredMessagesDoesNotOccurWithReportUnmatchedIgnoredErrorsOff(): void
	{
		$result = $this->runAnalyser(['#Unknown error#'], false, __DIR__ . '/data/empty/empty.php', false);
		$this->assertEmpty($result);
	}

	public function testDoNotReturnErrorIfIgnoredMessagesDoNotOccurWhileAnalysingIndividualFiles(): void
	{
		$result = $this->runAnalyser(['#Unknown error#'], true, __DIR__ . '/data/empty/empty.php', true);
		$this->assertEmpty($result);
	}

	public function testReportInvalidIgnorePatternEarly(): void
	{
		$result = $this->runAnalyser(['#Regexp syntax error'], true, __DIR__ . '/data/parse-error.php', false);
		$this->assertSame([
			"No ending delimiter '#' found in pattern: #Regexp syntax error",
		], $result);
	}

	public function testFileWithAnIgnoredError(): void
	{
		$result = $this->runAnalyser(['#Fail\.#'], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertEmpty($result);
	}

	public function testIgnoringBrokenConfigurationDoesNotWork(): void
	{
		$result = $this->runAnalyser(['#was not found while trying to analyse it#'], true, __DIR__ . '/../../notAutoloaded/Baz.php', false);
		$this->assertCount(2, $result);
		assert($result[0] instanceof Error);
		$this->assertSame('Class PHPStan\Tests\Baz was not found while trying to analyse it - autoloading is probably not configured properly.', $result[0]->getMessage());
		$this->assertSame('Error message "Class PHPStan\Tests\Baz was not found while trying to analyse it - autoloading is probably not configured properly." cannot be ignored, use excludes_analyse instead.', $result[1]);
	}

	public function testIgnoreErrorByPath(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/bootstrap-error.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertCount(0, $result);
	}

	public function testIgnoreErrorByPathAndCount(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'count' => 3,
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', false);
		$this->assertCount(0, $result);
	}

	public function testIgnoreErrorByPathAndCountMoreThanExpected(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'count' => 1,
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', false);
		$this->assertCount(3, $result);
		$this->assertInstanceOf(Error::class, $result[0]);
		$this->assertSame('Fail.', $result[0]->getMessage());
		$this->assertSame(6, $result[0]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[0]->getFile());

		$this->assertInstanceOf(Error::class, $result[1]);
		$this->assertSame('Fail.', $result[1]->getMessage());
		$this->assertSame(7, $result[1]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[1]->getFile());

		$this->assertInstanceOf(Error::class, $result[2]);
		$this->assertStringContainsString('Ignored error pattern #Fail\.#', $result[2]->getMessage());
		$this->assertStringContainsString('is expected to occur 1 time, but occured 3 times.', $result[2]->getMessage());
		$this->assertSame(6, $result[2]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[2]->getFile());
	}

	public function testIgnoreErrorByPathAndCountLessThanExpected(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'count' => 4,
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', false);
		$this->assertCount(1, $result);
		$this->assertIsString($result[0]);
		$this->assertStringContainsString('Ignored error pattern #Fail\.#', $result[0]);
		$this->assertStringContainsString('is expected to occur 4 times, but occured only 3 times.', $result[0]);
	}

	public function testIgnoreErrorByPathAndCountMissing(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Some custom error\.#',
				'count' => 2,
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', false);
		$this->assertCount(4, $result);
		$this->assertInstanceOf(Error::class, $result[0]);
		$this->assertSame('Fail.', $result[0]->getMessage());
		$this->assertSame(5, $result[0]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[0]->getFile());

		$this->assertInstanceOf(Error::class, $result[1]);
		$this->assertSame('Fail.', $result[1]->getMessage());
		$this->assertSame(6, $result[1]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[1]->getFile());

		$this->assertInstanceOf(Error::class, $result[2]);
		$this->assertSame('Fail.', $result[2]->getMessage());
		$this->assertSame(7, $result[2]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[2]->getFile());

		$this->assertIsString($result[3]);
		$this->assertStringContainsString('Ignored error pattern #Some custom error\.# in path', $result[3]);
		$this->assertStringContainsString('was not matched in reported errors.', $result[3]);
	}

	public function testIgnoreErrorByPaths(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'paths' => [__DIR__ . '/data/bootstrap-error.php'],
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertCount(0, $result);
	}

	public function testIgnoreErrorByPathsMultipleUnmatched(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'paths' => [__DIR__ . '/data/bootstrap-error.php', __DIR__ . '/data/another-path.php', '/data/yet-another-path.php'],
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertCount(1, $result);
		$this->assertIsString($result[0]);
		$this->assertStringContainsString('Ignored error pattern #Fail\.# in paths: ', $result[0]);
		$this->assertStringContainsString('was not matched in reported errors', $result[0]);
	}

	public function testIgnoreErrorByPathsUnmatched(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'paths' => [__DIR__ . '/data/bootstrap-error.php', __DIR__ . '/data/another-path.php'],
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertCount(1, $result);
		$this->assertIsString($result[0]);
		$this->assertStringContainsString('Ignored error pattern #Fail\.# in path ', $result[0]);
		$this->assertStringContainsString('was not matched in reported errors', $result[0]);
	}

	public function testIgnoreErrorNotFoundInPath(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/not-existent-path.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/empty/empty.php', false);
		$this->assertCount(1, $result);
		$this->assertSame('Ignored error pattern #Fail\.# in path ' . __DIR__ . '/data/not-existent-path.php was not matched in reported errors.', $result[0]);
	}

	public function dataIgnoreErrorInTraitUsingClassFilePath(): array
	{
		return [
			[
				__DIR__ . '/data/traits-ignore/Foo.php',
			],
			[
				__DIR__ . '/data/traits-ignore/FooTrait.php',
			],
		];
	}

	/**
	 * @dataProvider dataIgnoreErrorInTraitUsingClassFilePath
	 * @param string $pathToIgnore
	 */
	public function testIgnoreErrorInTraitUsingClassFilePath(string $pathToIgnore): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'path' => $pathToIgnore,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, [
			__DIR__ . '/data/traits-ignore/Foo.php',
			__DIR__ . '/data/traits-ignore/FooTrait.php',
		], true);
		$this->assertCount(0, $result);
	}

	public function testIgnoredErrorMissingMessage(): void
	{
		$ignoreErrors = [
			[
				'path' => __DIR__ . '/data/empty/empty.php',
			],
		];

		$expectedPath = __DIR__;

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$expectedPath = str_replace('\\', '\\\\', $expectedPath);
		}

		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/empty/empty.php', false);
		$this->assertCount(1, $result);
		$this->assertSame('Ignored error {"path":"' . $expectedPath . '/data/empty/empty.php"} is missing a message.', $result[0]);
	}

	public function testIgnoredErrorMissingPath(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/empty/empty.php', false);
		$this->assertCount(1, $result);
		$this->assertSame('Ignored error {"message":"#Fail\\\\.#"} is missing a path.', $result[0]);
	}

	public function testIgnoredErrorMessageStillValidatedIfMissingAPath(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/empty/empty.php', false);
		$this->assertCount(2, $result);
		$this->assertSame('Ignored error {"message":"#Fail\\\\."} is missing a path.', $result[0]);
		$this->assertSame('No ending delimiter \'#\' found in pattern: #Fail\.', $result[1]);
	}

	public function testReportMultipleParserErrorsAtOnce(): void
	{
		$result = $this->runAnalyser([], false, __DIR__ . '/data/multipleParseErrors.php', false);
		$this->assertCount(2, $result);

		/** @var Error $errorOne */
		$errorOne = $result[0];
		$this->assertSame('Syntax error, unexpected T_IS_EQUAL, expecting T_VARIABLE on line 3', $errorOne->getMessage());
		$this->assertSame(3, $errorOne->getLine());

		/** @var Error $errorTwo */
		$errorTwo = $result[1];
		$this->assertSame('Syntax error, unexpected EOF on line 10', $errorTwo->getMessage());
		$this->assertSame(10, $errorTwo->getLine());
	}

	/**
	 * @param mixed[] $ignoreErrors
	 * @param bool $reportUnmatchedIgnoredErrors
	 * @param string|string[] $filePaths
	 * @param bool $onlyFiles
	 * @return string[]|\PHPStan\Analyser\Error[]
	 */
	private function runAnalyser(
		array $ignoreErrors,
		bool $reportUnmatchedIgnoredErrors,
		$filePaths,
		bool $onlyFiles
	): array
	{
		$analyser = $this->createAnalyser(
			$ignoreErrors,
			$reportUnmatchedIgnoredErrors
		);

		if (is_string($filePaths)) {
			$filePaths = [$filePaths];
		}

		return $analyser->analyse(array_map(function (string $path): string {
			return $this->getFileHelper()->normalizePath($path);
		}, $filePaths), $onlyFiles);
	}

	/**
	 * @param string[]|array<array<string, string>> $ignoreErrors
	 * @param bool $reportUnmatchedIgnoredErrors
	 * @return Analyser
	 */
	private function createAnalyser(
		array $ignoreErrors,
		bool $reportUnmatchedIgnoredErrors = true
	): \PHPStan\Analyser\Analyser
	{
		$registry = new Registry([
			new AlwaysFailRule(),
		]);

		$traverser = new \PhpParser\NodeTraverser();
		$traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver());

		$broker = $this->createBroker();
		$printer = new \PhpParser\PrettyPrinter\Standard();
		$fileHelper = self::getContainer()->getByType(FileHelper::class);

		/** @var RelativePathHelper $relativePathHelper */
		$relativePathHelper = self::getContainer()->getService('relativePathHelper');
		$phpDocStringResolver = self::getContainer()->getByType(PhpDocStringResolver::class);
		$typeSpecifier = $this->createTypeSpecifier($printer, $broker);
		return new Analyser(
			$this->createScopeFactory($broker, $typeSpecifier),
			new DirectParser(new \PhpParser\Parser\Php7(new \PhpParser\Lexer()), $traverser),
			$registry,
			new NodeScopeResolver(
				$broker,
				$this->getParser(),
				new FileTypeMapper($this->getParser(), $phpDocStringResolver, $this->createMock(Cache::class), new AnonymousClassNameHelper($fileHelper, $relativePathHelper), new \PHPStan\PhpDoc\TypeNodeResolver([], self::getContainer())),
				$fileHelper,
				$typeSpecifier,
				false,
				false,
				true,
				[],
				[]
			),
			$fileHelper,
			$ignoreErrors,
			$reportUnmatchedIgnoredErrors,
			50
		);
	}

}
