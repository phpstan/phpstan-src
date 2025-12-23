<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_map;
use function array_merge;
use function array_unique;
use function sprintf;
use function usort;

#[CoversNothing]
class AnalyserTraitsIntegrationTest extends PHPStanTestCase
{

	private FileHelper $fileHelper;

	#[Override]
	protected function setUp(): void
	{
		$this->fileHelper = self::getContainer()->getByType(FileHelper::class);
	}

	public function testMethodIsInClassUsingTrait(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/Foo.php',
			__DIR__ . '/traits/FooTrait.php',
		]);
		self::assertEmpty($errors);
	}

	public function testMethodDoesNotExist(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/Bar.php',
			__DIR__ . '/traits/FooTrait.php',
		]);
		self::assertCount(1, $errors);
		$error = $errors[0];
		self::assertSame('Call to an undefined method AnalyseTraits\Bar::doFoo().', $error->getMessage());
		self::assertSame(
			sprintf('%s (in context of class AnalyseTraits\Bar)', $this->fileHelper->normalizePath(__DIR__ . '/traits/FooTrait.php')),
			$error->getFile(),
		);
		self::assertSame(10, $error->getLine());
	}

	public function testNestedTraits(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/NestedBar.php',
			__DIR__ . '/traits/NestedFooTrait.php',
			__DIR__ . '/traits/FooTrait.php',
		]);
		self::assertCount(2, $errors);
		$firstError = $errors[0];
		self::assertSame('Call to an undefined method AnalyseTraits\NestedBar::doFoo().', $firstError->getMessage());
		self::assertSame(
			sprintf('%s (in context of class AnalyseTraits\NestedBar)', $this->fileHelper->normalizePath(__DIR__ . '/traits/FooTrait.php')),
			$firstError->getFile(),
		);
		self::assertSame(10, $firstError->getLine());

		$secondError = $errors[1];
		self::assertSame('Call to an undefined method AnalyseTraits\NestedBar::doNestedFoo().', $secondError->getMessage());
		self::assertSame(
			sprintf('%s (in context of class AnalyseTraits\NestedBar)', $this->fileHelper->normalizePath(__DIR__ . '/traits/NestedFooTrait.php')),
			$secondError->getFile(),
		);
		self::assertSame(12, $secondError->getLine());
	}

	public function testTraitsAreNotAnalysedDirectly(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/FooTrait.php']);
		self::assertEmpty($errors);
		$errors = $this->runAnalyse([__DIR__ . '/traits/NestedFooTrait.php']);
		self::assertEmpty($errors);
	}

	public function testClassAndTraitInTheSameFile(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/classAndTrait.php']);
		self::assertEmpty($errors);
	}

	public function testTraitMethodAlias(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/trait-aliases.php']);
		self::assertEmpty($errors);
	}

	public function testFindErrorsInTrait(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/trait-error.php']);
		self::assertCount(3, $errors);
		self::assertSame('Undefined variable: $undefined', $errors[0]->getMessage());
		self::assertSame('Call to an undefined method TraitErrors\MyClass::undefined().', $errors[1]->getMessage());
		self::assertSame('Undefined variable: $undefined', $errors[2]->getMessage());
	}

	public function testTraitInAnonymousClass(): void
	{
		$errors = $this->runAnalyse(
			[
				__DIR__ . '/traits/AnonymousClassUsingTrait.php',
				__DIR__ . '/traits/TraitWithTypeSpecification.php',
			],
		);
		self::assertCount(1, $errors);
		self::assertStringContainsString('Access to an undefined property', $errors[0]->getMessage());
		self::assertSame(18, $errors[0]->getLine());
	}

	public function testDuplicateMethodDefinition(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/duplicateMethod/Lesson.php']);
		self::assertNoErrors($errors);
	}

	public function testWrongPropertyType(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/wrongProperty/Foo.php']);
		self::assertCount(2, $errors);
		self::assertSame(15, $errors[0]->getLine());
		self::assertSame(
			$this->fileHelper->normalizePath(__DIR__ . '/traits/wrongProperty/Foo.php'),
			$errors[0]->getFile(),
		);
		self::assertSame('Property TraitsWrongProperty\Foo::$id (int) does not accept string.', $errors[0]->getMessage());

		self::assertSame(17, $errors[1]->getLine());
		self::assertSame(
			$this->fileHelper->normalizePath(__DIR__ . '/traits/wrongProperty/Foo.php'),
			$errors[1]->getFile(),
		);
		self::assertSame('Property TraitsWrongProperty\Foo::$bar (Ipsum) does not accept int.', $errors[1]->getMessage());
	}

	public function testReturnThis(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/returnThis/Bar.php']);
		self::assertCount(2, $errors);
		self::assertSame(10, $errors[0]->getLine());
		self::assertSame('Call to an undefined method TraitsReturnThis\Foo::doFoo().', $errors[0]->getMessage());
		self::assertSame(11, $errors[1]->getLine());
		self::assertSame('Call to an undefined method TraitsReturnThis\Foo::doFoo().', $errors[1]->getMessage());
	}

	public function testTraitInEval(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/TraitInEvalUse.php']);
		self::assertNoErrors($errors);
	}

	public function testParameterNotFoundCrash(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/parameter-not-found.php']);
		self::assertNoErrors($errors);
	}

	public function testMissingReturnInAbstractTraitMethod(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/TraitWithAbstractMethod.php',
			__DIR__ . '/traits/ClassImplementingTraitWithAbstractMethod.php',
		]);
		self::assertNoErrors($errors);
	}

	#[RequiresPhp('>= 8.1')]
	public function testUnititializedReadonlyPropertyAccessedInTrait(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/uninitializedProperty/FooClass.php',
			__DIR__ . '/traits/uninitializedProperty/FooTrait.php',
		]);
		self::assertCount(3, $errors);
		usort($errors, static fn (Error $a, Error $b) => $a->getLine() <=> $b->getLine());
		$expectedFile = sprintf('%s (in context of class TraitsUnititializedProperty\FooClass)', $this->fileHelper->normalizePath(__DIR__ . '/traits/uninitializedProperty/FooTrait.php'));

		$error = $errors[0];
		self::assertSame('Access to an uninitialized readonly property TraitsUnititializedProperty\FooClass::$x.', $error->getMessage());
		self::assertSame(15, $error->getLine());
		self::assertSame($expectedFile, $error->getFile());

		$error = $errors[1];
		self::assertSame('Access to an uninitialized @readonly property TraitsUnititializedProperty\FooClass::$y.', $error->getMessage());
		self::assertSame(16, $error->getLine());
		self::assertSame($expectedFile, $error->getFile());

		$error = $errors[2];
		self::assertSame('Access to an uninitialized property TraitsUnititializedProperty\FooClass::$z.', $error->getMessage());
		self::assertSame(17, $error->getLine());
		self::assertSame($expectedFile, $error->getFile());
	}

	/**
	 * @param string[] $files
	 * @return Error[]
	 */
	private function runAnalyse(array $files): array
	{
		$files = array_map(fn (string $file): string => $this->getFileHelper()->normalizePath($file), $files);
		/** @var Analyser $analyser */
		$analyser = self::getContainer()->getByType(Analyser::class);

		return $analyser->analyse($files)->getErrors();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_unique(
			array_merge(
				parent::getAdditionalConfigFiles(),
				[
					__DIR__ . '/../../../conf/bleedingEdge.neon',
					__DIR__ . '/traits-integration.neon',
				],
			),
		);
	}

}
