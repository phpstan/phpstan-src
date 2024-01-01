<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Lexer;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\Ignore\IgnoreLexer;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Dependency\ExportedNodeResolver;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Parser\RichParser;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\AlwaysFailRule;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\FileTypeMapper;
use stdClass;
use function array_map;
use function array_merge;
use function assert;
use function count;
use function is_string;
use function sprintf;
use function str_replace;
use function strtoupper;
use function substr;
use const PHP_OS;

class AnalyserTest extends PHPStanTestCase
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

	public function testFileWithAnIgnoredError(): void
	{
		$result = $this->runAnalyser(['#Fail\.#'], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertEmpty($result);
	}

	public function testFileWithAnIgnoredErrorMessage(): void
	{
		$result = $this->runAnalyser([['message' => '#Fail\.#']], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertEmpty($result);
	}

	public function testFileWithAnIgnoredErrorMessageAndWrongIdentifier(): void
	{
		$result = $this->runAnalyser([['message' => '#Fail\.#', 'identifier' => 'wrong.identifier']], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertCount(2, $result);
		assert($result[0] instanceof Error);
		$this->assertSame('Fail.', $result[0]->getMessage());
		assert(is_string($result[1]));
		$this->assertSame('Ignored error pattern #Fail\.# (wrong.identifier) was not matched in reported errors.', $result[1]);
	}

	public function testFileWithAnIgnoredWrongIdentifier(): void
	{
		$result = $this->runAnalyser([['identifier' => 'wrong.identifier']], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertCount(2, $result);
		assert($result[0] instanceof Error);
		$this->assertSame('Fail.', $result[0]->getMessage());
		assert(is_string($result[1]));
		$this->assertSame('Ignored error pattern wrong.identifier was not matched in reported errors.', $result[1]);
	}

	public function testFileWithAnIgnoredErrorMessageAndCorrectIdentifier(): void
	{
		$result = $this->runAnalyser([['message' => '#Fail\.#', 'identifier' => 'tests.alwaysFail']], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertEmpty($result);
	}

	public function testFileWithAnIgnoredErrorIdentifier(): void
	{
		$result = $this->runAnalyser([['identifier' => 'tests.alwaysFail']], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertEmpty($result);
	}

	public function testFileWithAnIgnoredErrorMessages(): void
	{
		$result = $this->runAnalyser([['messages' => ['#Fail\.#']]], true, __DIR__ . '/data/bootstrap-error.php', false);
		$this->assertEquals([], $result);
	}

	public function testIgnoringBrokenConfigurationDoesNotWork(): void
	{
		$this->markTestIncomplete();
		$result = $this->runAnalyser(['#was not found while trying to analyse it#'], true, __DIR__ . '/../../notAutoloaded/Baz.php', false);
		$this->assertCount(2, $result);
		assert($result[0] instanceof Error);
		$this->assertSame('Class PHPStan\Tests\Baz was not found while trying to analyse it - autoloading is probably not configured properly.', $result[0]->getMessage());
		$this->assertSame('Error message "Class PHPStan\Tests\Baz was not found while trying to analyse it - autoloading is probably not configured properly." cannot be ignored, use excludePaths instead.', $result[1]);
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
		$this->assertNoErrors($result);
	}

	public function testIgnoreErrorMultiByPath(): void
	{
		$ignoreErrors = [
			[
				'messages' => [
					'#First fail#',
					'#Second fail#',
				],
				'path' => __DIR__ . '/data/two-different-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-different-fails.php', false);
		$this->assertNoErrors($result);
	}

	public function dataIgnoreErrorByPathAndCount(): iterable
	{
		yield [
			[
				[
					'message' => '#Fail\.#',
					'count' => 3,
					'path' => __DIR__ . '/data/two-fails.php',
				],
			],
		];

		yield [
			[
				[
					'message' => '#Fail\.#',
					'count' => 2,
					'path' => __DIR__ . '/data/two-fails.php',
				],
				[
					'message' => '#Fail\.#',
					'count' => 1,
					'path' => __DIR__ . '/data/two-fails.php',
				],
			],
		];

		yield [
			[
				[
					'message' => '#Fail\.#',
					'count' => 2,
					'path' => __DIR__ . '/data/two-fails.php',
				],
				[
					'message' => '#Fail\.#',
					'path' => __DIR__ . '/data/two-fails.php',
				],
			],
		];
	}

	/**
	 * @dataProvider dataIgnoreErrorByPathAndCount
	 * @param mixed[] $ignoreErrors
	 */
	public function testIgnoreErrorByPathAndCount(array $ignoreErrors): void
	{
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', false);
		$this->assertNoErrors($result);
	}

	public function dataTrueAndFalse(): array
	{
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider dataTrueAndFalse
	 */
	public function testIgnoreErrorByPathAndCountMoreThanExpected(bool $onlyFiles): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'count' => 1,
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', $onlyFiles);
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
		$this->assertStringContainsString('is expected to occur 1 time, but occurred 3 times.', $result[2]->getMessage());
		$this->assertSame(5, $result[2]->getLine());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[2]->getFile());
	}

	/**
	 * @dataProvider dataTrueAndFalse
	 */
	public function testIgnoreErrorByPathAndCountLessThanExpected(bool $onlyFiles): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'count' => 4,
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-fails.php', $onlyFiles);
		$this->assertCount(1, $result);
		$this->assertInstanceOf(Error::class, $result[0]);
		$this->assertStringContainsString('Ignored error pattern #Fail\.#', $result[0]->getMessage());
		$this->assertStringContainsString('is expected to occur 4 times, but occurred only 3 times.', $result[0]->getMessage());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[0]->getFile());
		$this->assertSame(5, $result[0]->getLine());
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

		$this->assertInstanceOf(Error::class, $result[3]);
		$this->assertStringContainsString('Ignored error pattern #Some custom error\.# in path', $result[3]->getMessage());
		$this->assertStringContainsString('was not matched in reported errors.', $result[3]->getMessage());
		$this->assertSamePaths(__DIR__ . '/data/two-fails.php', $result[2]->getFile());
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
		$this->assertNoErrors($result);
	}

	public function testIgnoreErrorMultiByPaths(): void
	{
		$ignoreErrors = [
			[
				'messages' => [
					'#First fail#',
					'#Second fail#',
				],
				'paths' => [__DIR__ . '/data/two-different-fails.php'],
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/two-different-fails.php', false);
		$this->assertNoErrors($result);
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

	public function testIgnoreErrorByPathsUnmatchedExplicitReportUnmatched(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'paths' => [__DIR__ . '/data/bootstrap-error.php', __DIR__ . '/data/another-path.php'],
				'reportUnmatched' => true,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, false, __DIR__ . '/data/bootstrap-error.php', false);
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

	public function testIgnoreErrorNotFoundInPathExplicitReportUnmatched(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/not-existent-path.php',
				'reportUnmatched' => true,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, false, __DIR__ . '/data/empty/empty.php', false);
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
		$this->assertNoErrors($result);
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
		$this->assertSame('Ignored error {"path":"' . $expectedPath . '/data/empty/empty.php"} is missing a message or an identifier.', $result[0]);
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
	 * @dataProvider dataTrueAndFalse
	 */
	public function testDoNotReportUnmatchedIgnoredErrorsFromPathIfPathWasNotAnalysed(bool $onlyFiles): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/bootstrap-error.php',
			],
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/two-fails.php',
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, [
			__DIR__ . '/data/two-fails.php',
		], $onlyFiles);
		$this->assertNoErrors($result);
	}

	/**
	 * @dataProvider dataTrueAndFalse
	 */
	public function testDoNotReportUnmatchedIgnoredErrorsFromPathWithCountIfPathWasNotAnalysed(bool $onlyFiles): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/bootstrap-error.php',
				'count' => 2,
			],
			[
				'message' => '#Fail\.#',
				'path' => __DIR__ . '/data/two-fails.php',
				'count' => 3,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, [
			__DIR__ . '/data/two-fails.php',
		], $onlyFiles);
		$this->assertNoErrors($result);
	}

	/**
	 * @dataProvider dataTrueAndFalse
	 */
	public function testIgnoreNextLine(bool $reportUnmatchedIgnoredErrors): void
	{
		$result = $this->runAnalyser([], $reportUnmatchedIgnoredErrors, [
			__DIR__ . '/data/ignore-next-line.php',
		], true);
		$this->assertCount($reportUnmatchedIgnoredErrors ? 4 : 3, $result);
		foreach ([10, 30, 34] as $i => $line) {
			$this->assertArrayHasKey($i, $result);
			$this->assertInstanceOf(Error::class, $result[$i]);
			$this->assertSame('Fail.', $result[$i]->getMessage());
			$this->assertSame($line, $result[$i]->getLine());
		}

		if (!$reportUnmatchedIgnoredErrors) {
			return;
		}

		$this->assertArrayHasKey(3, $result);
		$this->assertInstanceOf(Error::class, $result[3]);
		$this->assertSame('No error to ignore is reported on line 38.', $result[3]->getMessage());
		$this->assertSame(38, $result[3]->getLine());
	}

	/**
	 * @dataProvider dataTrueAndFalse
	 */
	public function testIgnoreLine(bool $reportUnmatchedIgnoredErrors): void
	{
		$result = $this->runAnalyser([], $reportUnmatchedIgnoredErrors, [
			__DIR__ . '/data/ignore-line.php',
		], true);
		$this->assertCount($reportUnmatchedIgnoredErrors ? 4 : 3, $result);
		foreach ([10, 19, 22] as $i => $line) {
			$this->assertArrayHasKey($i, $result);
			$this->assertInstanceOf(Error::class, $result[$i]);
			$this->assertSame('Fail.', $result[$i]->getMessage());
			$this->assertSame($line, $result[$i]->getLine());
		}

		if (!$reportUnmatchedIgnoredErrors) {
			return;
		}

		$this->assertArrayHasKey(3, $result);
		$this->assertInstanceOf(Error::class, $result[3]);
		$this->assertSame('No error to ignore is reported on line 26.', $result[3]->getMessage());
		$this->assertSame(26, $result[3]->getLine());
	}

	public function testIgnoreErrorExplicitReportUnmatchedDisable(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail#',
				'reportUnmatched' => false,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/bootstrap.php', false);
		$this->assertNoErrors($result);
	}

	public function testIgnoreErrorExplicitReportUnmatchedDisableMulti(): void
	{
		$ignoreErrors = [
			[
				'message' => ['#Fail#'],
				'reportUnmatched' => false,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, true, __DIR__ . '/data/bootstrap.php', false);
		$this->assertNoErrors($result);
	}

	public function testIgnoreErrorExplicitReportUnmatchedEnable(): void
	{
		$ignoreErrors = [
			[
				'message' => '#Fail#',
				'reportUnmatched' => true,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, false, __DIR__ . '/data/bootstrap.php', false);
		$this->assertCount(1, $result);
		$this->assertSame('Ignored error pattern #Fail# was not matched in reported errors.', $result[0]);
	}

	public function testIgnoreErrorExplicitReportUnmatchedEnableMulti(): void
	{
		$ignoreErrors = [
			[
				'messages' => ['#Fail#'],
				'reportUnmatched' => true,
			],
		];
		$result = $this->runAnalyser($ignoreErrors, false, __DIR__ . '/data/bootstrap.php', false);
		$this->assertCount(1, $result);
		$this->assertSame('Ignored error pattern #Fail# was not matched in reported errors.', $result[0]);
	}

	/**
	 * @param mixed[] $ignoreErrors
	 * @param string|string[] $filePaths
	 * @return string[]|Error[]
	 */
	private function runAnalyser(
		array $ignoreErrors,
		bool $reportUnmatchedIgnoredErrors,
		$filePaths,
		bool $onlyFiles,
	): array
	{
		$analyser = $this->createAnalyser($reportUnmatchedIgnoredErrors);

		if (is_string($filePaths)) {
			$filePaths = [$filePaths];
		}

		$ignoredErrorHelper = new IgnoredErrorHelper(
			$this->getFileHelper(),
			$ignoreErrors,
			$reportUnmatchedIgnoredErrors,
		);
		$ignoredErrorHelperResult = $ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			return $ignoredErrorHelperResult->getErrors();
		}

		$normalizedFilePaths = array_map(fn (string $path): string => $this->getFileHelper()->normalizePath($path), $filePaths);

		$analyserResult = $analyser->analyse($normalizedFilePaths);

		$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($analyserResult->getErrors(), $onlyFiles, $normalizedFilePaths, $analyserResult->hasReachedInternalErrorsCountLimit());
		$errors = $ignoredErrorHelperProcessedResult->getNotIgnoredErrors();
		$errors = array_merge($errors, $ignoredErrorHelperProcessedResult->getOtherIgnoreMessages());
		if ($analyserResult->hasReachedInternalErrorsCountLimit()) {
			$errors[] = sprintf('Reached internal errors count limit of %d, exiting...', 50);
		}

		return array_merge(
			$errors,
			$analyserResult->getInternalErrors(),
		);
	}

	private function createAnalyser(bool $reportUnmatchedIgnoredErrors): Analyser
	{
		$ruleRegistry = new DirectRuleRegistry([
			new AlwaysFailRule(),
		]);
		$collectorRegistry = new CollectorRegistry([]);

		$reflectionProvider = $this->createReflectionProvider();
		$fileHelper = $this->getFileHelper();

		$typeSpecifier = self::getContainer()->getService('typeSpecifier');
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);
		$phpDocInheritanceResolver = new PhpDocInheritanceResolver($fileTypeMapper, self::getContainer()->getByType(StubPhpDocProvider::class));

		$nodeScopeResolver = new NodeScopeResolver(
			$reflectionProvider,
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			self::getClassReflectionExtensionRegistryProvider(),
			$this->getParser(),
			$fileTypeMapper,
			self::getContainer()->getByType(StubPhpDocProvider::class),
			self::getContainer()->getByType(PhpVersion::class),
			self::getContainer()->getByType(SignatureMapProvider::class),
			$phpDocInheritanceResolver,
			$fileHelper,
			$typeSpecifier,
			self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
			self::getContainer()->getByType(ReadWritePropertiesExtensionProvider::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			false,
			true,
			[],
			[],
			[stdClass::class],
			true,
			$this->shouldTreatPhpDocTypesAsCertain(),
			self::getContainer()->getParameter('featureToggles')['detectDeadTypeInMultiCatch'],
		);
		$lexer = new Lexer();
		$fileAnalyser = new FileAnalyser(
			$this->createScopeFactory($reflectionProvider, $typeSpecifier),
			$nodeScopeResolver,
			new RichParser(
				new Php7($lexer),
				new NameResolver(),
				self::getContainer(),
				new IgnoreLexer(),
			),
			new DependencyResolver($fileHelper, $reflectionProvider, new ExportedNodeResolver($fileTypeMapper, new ExprPrinter(new Printer())), $fileTypeMapper),
			new RuleErrorTransformer(),
			$reportUnmatchedIgnoredErrors,
		);

		return new Analyser(
			$fileAnalyser,
			$ruleRegistry,
			$collectorRegistry,
			$nodeScopeResolver,
			50,
		);
	}

}
