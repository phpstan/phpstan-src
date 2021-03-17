<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Bug4713\Service;
use PHPStan\Broker\Broker;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Type\Constant\ConstantStringType;
use const PHP_VERSION_ID;
use function array_reverse;

class AnalyserIntegrationTest extends \PHPStan\Testing\TestCase
{

	public function testUndefinedVariableFromAssignErrorHasLine(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/undefined-variable-assign.php');
		$this->assertCount(2, $errors);
		$error = $errors[0];
		$this->assertSame('Undefined variable: $bar', $error->getMessage());
		$this->assertSame(3, $error->getLine());

		$error = $errors[1];
		$this->assertSame('Variable $foo might not be defined.', $error->getMessage());
		$this->assertSame(6, $error->getLine());
	}

	public function testMissingPropertyAndMethod(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../../notAutoloaded/Foo.php');
		$this->assertCount(0, $errors);
	}

	public function testMissingClassErrorAboutMisconfiguredAutoloader(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../../notAutoloaded/Bar.php');
		$this->assertCount(0, $errors);
	}

	public function testMissingFunctionErrorAboutMisconfiguredAutoloader(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../../notAutoloaded/functionFoo.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Function doSomething not found.', $errors[0]->getMessage());
		$this->assertSame(7, $errors[0]->getLine());
	}

	public function testAnonymousClassWithInheritedConstructor(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/anonymous-class-with-inherited-constructor.php');
		$this->assertCount(0, $errors);
	}

	public function testNestedFunctionCallsDoNotCauseExcessiveFunctionNesting(): void
	{
		if (extension_loaded('xdebug')) {
			$this->markTestSkipped('This test takes too long with XDebug enabled.');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/nested-functions.php');
		$this->assertCount(0, $errors);
	}

	public function testExtendingUnknownClass(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/extending-unknown-class.php');
		$this->assertCount(1, $errors);

		if (self::$useStaticReflectionProvider) {
			$this->assertSame(5, $errors[0]->getLine());
			$this->assertSame('Class ExtendingUnknownClass\Foo extends unknown class ExtendingUnknownClass\Bar.', $errors[0]->getMessage());
		} else {
			$this->assertNull($errors[0]->getLine());
			$this->assertSame('Class ExtendingUnknownClass\Bar not found.', $errors[0]->getMessage());
		}
	}

	public function testExtendingKnownClassWithCheck(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/extending-known-class-with-check.php');
		$this->assertCount(0, $errors);

		$broker = self::getContainer()->getByType(Broker::class);
		$this->assertTrue($broker->hasClass(\ExtendingKnownClassWithCheck\Foo::class));
	}

	public function testInfiniteRecursionWithCallable(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/Foo-callable.php');
		$this->assertCount(0, $errors);
	}

	public function testClassThatExtendsUnknownClassIn3rdPartyPropertyTypeShouldNotCauseAutoloading(): void
	{
		// no error about PHPStan\Tests\Baz not being able to be autoloaded
		$errors = $this->runAnalyse(__DIR__ . '/data/ExtendsClassWithUnknownPropertyType.php');
		$this->assertCount(1, $errors);
		//$this->assertSame(11, $errors[0]->getLine());
		$this->assertSame('Call to an undefined method ExtendsClassWithUnknownPropertyType::foo().', $errors[0]->getMessage());
	}

	public function testAnonymousClassesWithComments(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/AnonymousClassesWithComments.php');
		$this->assertCount(3, $errors);
		foreach ($errors as $error) {
			$this->assertStringContainsString('Call to an undefined method', $error->getMessage());
		}
	}

	public function testUniversalObjectCrateIssue(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/universal-object-crate.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Parameter #1 $i of method UniversalObjectCrate\Foo::doBaz() expects int, string given.', $errors[0]->getMessage());
		$this->assertSame(19, $errors[0]->getLine());
	}

	public function testCustomFunctionWithNameEquivalentInSignatureMap(): void
	{
		$signatureMapProvider = self::getContainer()->getByType(SignatureMapProvider::class);
		if (!$signatureMapProvider->hasFunctionSignature('bcompiler_write_file')) {
			$this->fail();
		}
		require_once __DIR__ . '/data/custom-function-in-signature-map.php';
		$errors = $this->runAnalyse(__DIR__ . '/data/custom-function-in-signature-map.php');
		$this->assertCount(0, $errors);
	}

	public function testAnonymousClassWithWrongFilename(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/anonymous-class-wrong-filename-regression.php');
		$this->assertCount(5, $errors);
		$this->assertStringContainsString('Return typehint of method', $errors[0]->getMessage());
		$this->assertSame(16, $errors[0]->getLine());
		$this->assertStringContainsString('Return typehint of method', $errors[1]->getMessage());
		$this->assertSame(16, $errors[1]->getLine());
		$this->assertSame('Instantiated class AnonymousClassWrongFilename\Bar not found.', $errors[2]->getMessage());
		$this->assertSame(18, $errors[2]->getLine());
		$this->assertStringContainsString('Parameter #1 $test of method', $errors[3]->getMessage());
		$this->assertStringContainsString('$this(AnonymousClassWrongFilename\Foo) given', $errors[3]->getMessage());
		$this->assertSame(23, $errors[3]->getLine());
		$this->assertSame('Call to method test() on an unknown class AnonymousClassWrongFilename\Bar.', $errors[4]->getMessage());
		$this->assertSame(24, $errors[4]->getLine());
	}

	public function testExtendsPdoStatementCrash(): void
	{
		if (PHP_VERSION_ID >= 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped();
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/extends-pdo-statement.php');
		$this->assertCount(0, $errors);
	}

	public function testArrayDestructuringArrayDimFetch(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/array-destructuring-array-dim-fetch.php');
		$this->assertCount(0, $errors);
	}

	public function testNestedNamespaces(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/nested-namespaces.php');
		$this->assertCount(2, $errors);
		$this->assertSame('Property y\x::$baz has unknown class x\baz as its type.', $errors[0]->getMessage());
		$this->assertSame(15, $errors[0]->getLine());
		$this->assertSame('Parameter $baz of method y\x::__construct() has invalid typehint type x\baz.', $errors[1]->getMessage());
		$this->assertSame(16, $errors[1]->getLine());
	}

	public function testClassExistsAutoloadingError(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/class-exists.php');
		$this->assertCount(0, $errors);
	}

	public function testCollectWarnings(): void
	{
		if (PHP_VERSION_ID >= 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Fatal error in PHP 8.0');
		}
		restore_error_handler();
		$errors = $this->runAnalyse(__DIR__ . '/data/declaration-warning.php');
		if (self::$useStaticReflectionProvider) {
			$this->assertCount(1, $errors);
			$this->assertSame('Parameter #1 $i of method DeclarationWarning\Bar::doFoo() is not optional.', $errors[0]->getMessage());
			$this->assertSame(22, $errors[0]->getLine());
			return;
		}
		$this->assertCount(2, $errors);
		$messages = [
			'Declaration of DeclarationWarning\Bar::doFoo(int $i): void should be compatible with DeclarationWarning\Foo::doFoo(): void',
			'Parameter #1 $i of method DeclarationWarning\Bar::doFoo() is not optional.',
		];
		if (PHP_VERSION_ID < 70400) {
			$messages = array_reverse($messages);
		}
		foreach ($messages as $i => $message) {
			$this->assertSame($message, $errors[$i]->getMessage());
		}
	}

	public function testPropertyAssignIntersectionStaticTypeBug(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/property-assign-intersection-static-type-bug.php');
		$this->assertCount(0, $errors);
	}

	public function testBug2823(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-2823.php');
		$this->assertCount(0, $errors);
	}

	public function testTwoSameClassesInSingleFile(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/two-same-classes.php');
		$this->assertCount(4, $errors);
		$error = $errors[0];
		$this->assertSame('Property TwoSame\Foo::$prop (string) does not accept default value of type int.', $error->getMessage());
		$this->assertSame(9, $error->getLine());

		$error = $errors[1];
		$this->assertSame('Access to undefined constant TwoSame\Foo::FOO_CONST.', $error->getMessage());
		$this->assertSame(13, $error->getLine());

		$error = $errors[2];
		$this->assertSame('Property TwoSame\Foo::$prop (int) does not accept default value of type string.', $error->getMessage());
		$this->assertSame(25, $error->getLine());

		$error = $errors[3];
		$this->assertSame('Property TwoSame\Foo::$prop2 (int) does not accept default value of type string.', $error->getMessage());
		$this->assertSame(28, $error->getLine());
	}

	public function testBug3405(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3405.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3415(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Methods/data/bug-3415.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3415Two(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Methods/data/bug-3415-2.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3468(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3468.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3686(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3686.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3379(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3379.php');
		$this->assertCount(2, $errors);
		$this->assertSame('Constant SOME_UNKNOWN_CONST not found.', $errors[0]->getMessage());
		$this->assertSame('Reflection error: Could not locate constant "SOME_UNKNOWN_CONST" while evaluating expression in Bug3379\Foo at line 8', $errors[1]->getMessage());
	}

	public function testBug3798(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3798.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3909(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3909.php');
		$this->assertCount(0, $errors);
	}

	public function testBug4097(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4097.php');
		$this->assertCount(0, $errors);
	}

	public function testBug4300(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4300.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Comparison operation ">" between 0 and 0 is always false.', $errors[0]->getMessage());
		$this->assertSame(13, $errors[0]->getLine());
	}

	public function testBug4513(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4513.php');
		$this->assertCount(0, $errors);
	}

	public function testBug1871(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-1871.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3309(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3309.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3769(): void
	{
		require_once __DIR__ . '/../Rules/Generics/data/bug-3769.php';
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Generics/data/bug-3769.php');
		$this->assertCount(0, $errors);
	}

	public function testBug3922(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3922-integration.php');
		$this->assertCount(0, $errors);
	}

	public function testBug1843(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-1843.php');
		$this->assertCount(0, $errors);
	}

	public function testBug4713(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4713.php');
		$this->assertCount(0, $errors);

		$reflectionProvider = $this->createBroker();
		$class = $reflectionProvider->getClass(Service::class);
		$parameter = ParametersAcceptorSelector::selectSingle($class->getNativeMethod('createInstance')->getVariants())->getParameters()[0];
		$defaultValue = $parameter->getDefaultValue();
		$this->assertInstanceOf(ConstantStringType::class, $defaultValue);
		$this->assertSame(Service::class, $defaultValue->getValue());
	}

	/**
	 * @param string $file
	 * @return \PHPStan\Analyser\Error[]
	 */
	private function runAnalyse(string $file): array
	{
		$file = $this->getFileHelper()->normalizePath($file);
		/** @var \PHPStan\Analyser\Analyser $analyser */
		$analyser = self::getContainer()->getByType(Analyser::class);
		/** @var \PHPStan\File\FileHelper $fileHelper */
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		/** @var \PHPStan\Analyser\Error[] $errors */
		$errors = $analyser->analyse([$file])->getErrors();
		foreach ($errors as $error) {
			$this->assertSame($fileHelper->normalizePath($file), $error->getFilePath());
		}

		return $errors;
	}

}
