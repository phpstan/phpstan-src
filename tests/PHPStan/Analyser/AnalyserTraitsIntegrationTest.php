<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use function array_map;
use function sprintf;

class AnalyserTraitsIntegrationTest extends PHPStanTestCase
{

	private FileHelper $fileHelper;

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
		$this->assertEmpty($errors);
	}

	public function testMethodDoesNotExist(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/Bar.php',
			__DIR__ . '/traits/FooTrait.php',
		]);
		$this->assertCount(1, $errors);
		$error = $errors[0];
		$this->assertSame('Call to an undefined method AnalyseTraits\Bar::doFoo().', $error->getMessage());
		$this->assertSame(
			sprintf('%s (in context of class AnalyseTraits\Bar)', $this->fileHelper->normalizePath(__DIR__ . '/traits/FooTrait.php')),
			$error->getFile(),
		);
		$this->assertSame(10, $error->getLine());
	}

	public function testNestedTraits(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/NestedBar.php',
			__DIR__ . '/traits/NestedFooTrait.php',
			__DIR__ . '/traits/FooTrait.php',
		]);
		$this->assertCount(2, $errors);
		$firstError = $errors[0];
		$this->assertSame('Call to an undefined method AnalyseTraits\NestedBar::doFoo().', $firstError->getMessage());
		$this->assertSame(
			sprintf('%s (in context of class AnalyseTraits\NestedBar)', $this->fileHelper->normalizePath(__DIR__ . '/traits/FooTrait.php')),
			$firstError->getFile(),
		);
		$this->assertSame(10, $firstError->getLine());

		$secondError = $errors[1];
		$this->assertSame('Call to an undefined method AnalyseTraits\NestedBar::doNestedFoo().', $secondError->getMessage());
		$this->assertSame(
			sprintf('%s (in context of class AnalyseTraits\NestedBar)', $this->fileHelper->normalizePath(__DIR__ . '/traits/NestedFooTrait.php')),
			$secondError->getFile(),
		);
		$this->assertSame(12, $secondError->getLine());
	}

	public function testTraitsAreNotAnalysedDirectly(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/FooTrait.php']);
		$this->assertEmpty($errors);
		$errors = $this->runAnalyse([__DIR__ . '/traits/NestedFooTrait.php']);
		$this->assertEmpty($errors);
	}

	public function testClassAndTraitInTheSameFile(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/classAndTrait.php']);
		$this->assertEmpty($errors);
	}

	public function testTraitMethodAlias(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/trait-aliases.php']);
		$this->assertEmpty($errors);
	}

	public function testFindErrorsInTrait(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/trait-error.php']);
		$this->assertCount(3, $errors);
		$this->assertSame('Undefined variable: $undefined', $errors[0]->getMessage());
		$this->assertSame('Call to an undefined method TraitErrors\MyClass::undefined().', $errors[1]->getMessage());
		$this->assertSame('Undefined variable: $undefined', $errors[2]->getMessage());
	}

	public function testTraitInAnonymousClass(): void
	{
		$errors = $this->runAnalyse(
			[
				__DIR__ . '/traits/AnonymousClassUsingTrait.php',
				__DIR__ . '/traits/TraitWithTypeSpecification.php',
			],
		);
		$this->assertCount(2, $errors);
		$this->assertStringContainsString('$string is never read, only written.', $errors[0]->getMessage());
		$this->assertSame(9, $errors[0]->getLine());
		$this->assertStringContainsString('Access to an undefined property', $errors[1]->getMessage());
		$this->assertSame(18, $errors[1]->getLine());
	}

	public function testDuplicateMethodDefinition(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/duplicateMethod/Lesson.php']);
		$this->assertNoErrors($errors);
	}

	public function testWrongPropertyType(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/wrongProperty/Foo.php']);
		$this->assertCount(2, $errors);
		$this->assertSame(15, $errors[0]->getLine());
		$this->assertSame(
			$this->fileHelper->normalizePath(__DIR__ . '/traits/wrongProperty/Foo.php'),
			$errors[0]->getFile(),
		);
		$this->assertSame('Property TraitsWrongProperty\Foo::$id (int) does not accept string.', $errors[0]->getMessage());

		$this->assertSame(17, $errors[1]->getLine());
		$this->assertSame(
			$this->fileHelper->normalizePath(__DIR__ . '/traits/wrongProperty/Foo.php'),
			$errors[1]->getFile(),
		);
		$this->assertSame('Property TraitsWrongProperty\Foo::$bar (Ipsum) does not accept int.', $errors[1]->getMessage());
	}

	public function testReturnThis(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/returnThis/Bar.php']);
		$this->assertCount(2, $errors);
		$this->assertSame(10, $errors[0]->getLine());
		$this->assertSame('Call to an undefined method TraitsReturnThis\Foo::doFoo().', $errors[0]->getMessage());
		$this->assertSame(11, $errors[1]->getLine());
		$this->assertSame('Call to an undefined method TraitsReturnThis\Foo::doFoo().', $errors[1]->getMessage());
	}

	public function testTraitInEval(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/TraitInEvalUse.php']);
		$this->assertNoErrors($errors);
	}

	public function testParameterNotFoundCrash(): void
	{
		$errors = $this->runAnalyse([__DIR__ . '/traits/parameter-not-found.php']);
		$this->assertNoErrors($errors);
	}

	public function testMissingReturnInAbstractTraitMethod(): void
	{
		$errors = $this->runAnalyse([
			__DIR__ . '/traits/TraitWithAbstractMethod.php',
			__DIR__ . '/traits/ClassImplementingTraitWithAbstractMethod.php',
		]);
		$this->assertNoErrors($errors);
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
		/** @var Error[] $errors */
		$errors = $analyser->analyse($files)->getErrors();
		return $errors;
	}

}
