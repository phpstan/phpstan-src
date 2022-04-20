<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Bug4288\MyClass;
use Bug4713\Service;
use ExtendingKnownClassWithCheck\Foo;
use PHPStan\File\FileHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function array_reverse;
use function extension_loaded;
use function method_exists;
use function restore_error_handler;
use const PHP_VERSION_ID;

class AnalyserIntegrationTest extends PHPStanTestCase
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
		$this->assertNoErrors($errors);
	}

	public function testMissingClassErrorAboutMisconfiguredAutoloader(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../../notAutoloaded/Bar.php');
		$this->assertNoErrors($errors);
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
		$this->assertNoErrors($errors);
	}

	public function testNestedFunctionCallsDoNotCauseExcessiveFunctionNesting(): void
	{
		if (extension_loaded('xdebug')) {
			$this->markTestSkipped('This test takes too long with XDebug enabled.');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/nested-functions.php');
		$this->assertNoErrors($errors);
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
		$this->assertNoErrors($errors);

		$reflectionProvider = $this->createReflectionProvider();
		$this->assertTrue($reflectionProvider->hasClass(Foo::class));
	}

	public function testInfiniteRecursionWithCallable(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/Foo-callable.php');
		$this->assertNoErrors($errors);
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
		$this->assertNoErrors($errors);
	}

	public function testAnonymousClassWithWrongFilename(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/anonymous-class-wrong-filename-regression.php');
		$this->assertCount(5, $errors);
		$this->assertStringContainsString('Method', $errors[0]->getMessage());
		$this->assertStringContainsString('has invalid return type', $errors[0]->getMessage());
		$this->assertSame(16, $errors[0]->getLine());
		$this->assertStringContainsString('Method', $errors[1]->getMessage());
		$this->assertStringContainsString('has invalid return type', $errors[1]->getMessage());
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
		$this->assertNoErrors($errors);
	}

	public function testArrayDestructuringArrayDimFetch(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/array-destructuring-array-dim-fetch.php');
		$this->assertNoErrors($errors);
	}

	public function testNestedNamespaces(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/nested-namespaces.php');
		$this->assertCount(2, $errors);
		$this->assertSame('Property y\x::$baz has unknown class x\baz as its type.', $errors[0]->getMessage());
		$this->assertSame(15, $errors[0]->getLine());
		$this->assertSame('Parameter $baz of method y\x::__construct() has invalid type x\baz.', $errors[1]->getMessage());
		$this->assertSame(16, $errors[1]->getLine());
	}

	public function testClassExistsAutoloadingError(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/class-exists.php');
		$this->assertNoErrors($errors);
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
		$this->assertNoErrors($errors);
	}

	public function testBug2823(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-2823.php');
		$this->assertNoErrors($errors);
	}

	public function testTwoSameClassesInSingleFile(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/two-same-classes.php');
		$this->assertCount(5, $errors);

		$error = $errors[0];
		$this->assertSame('Property TwoSame\Foo::$prop (string) does not accept default value of type int.', $error->getMessage());
		$this->assertSame(9, $error->getLine());

		$error = $errors[1];
		$this->assertSame('Access to undefined constant TwoSame\Foo::FOO_CONST.', $error->getMessage());
		$this->assertSame(13, $error->getLine());

		$error = $errors[2];
		$this->assertSame('If condition is always false.', $error->getMessage());
		$this->assertSame(26, $error->getLine());

		$error = $errors[3];
		$this->assertSame('Property TwoSame\Foo::$prop (int) does not accept default value of type string.', $error->getMessage());
		$this->assertSame(33, $error->getLine());

		$error = $errors[4];
		$this->assertSame('Property TwoSame\Foo::$prop2 (int) does not accept default value of type string.', $error->getMessage());
		$this->assertSame(36, $error->getLine());
	}

	public function testBug6936(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6936.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3405(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3405.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3415(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Methods/data/bug-3415.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3415Two(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Methods/data/bug-3415-2.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3468(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3468.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3686(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3686.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3379(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3379.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Constant SOME_UNKNOWN_CONST not found.', $errors[0]->getMessage());
	}

	public function testBug3798(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3798.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3909(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3909.php');
		$this->assertNoErrors($errors);
	}

	public function testBug4097(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4097.php');
		$this->assertNoErrors($errors);
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
		$this->assertNoErrors($errors);
	}

	public function testBug1871(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-1871.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3309(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3309.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6872(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6872.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3769(): void
	{
		require_once __DIR__ . '/../Rules/Generics/data/bug-3769.php';
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Generics/data/bug-3769.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6301(): void
	{
		require_once __DIR__ . '/../Rules/Generics/data/bug-6301.php';
		$errors = $this->runAnalyse(__DIR__ . '/../Rules/Generics/data/bug-6301.php');
		$this->assertNoErrors($errors);
	}

	public function testBug3922(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-3922-integration.php');
		$this->assertNoErrors($errors);
	}

	public function testBug1843(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-1843.php');
		$this->assertNoErrors($errors);
	}

	public function testBug4713(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4713.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Method Bug4713\Service::createInstance() should return Bug4713\Service but returns object.', $errors[0]->getMessage());

		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass(Service::class);
		$parameter = ParametersAcceptorSelector::selectSingle($class->getNativeMethod('createInstance')->getVariants())->getParameters()[0];
		$defaultValue = $parameter->getDefaultValue();
		$this->assertInstanceOf(ConstantStringType::class, $defaultValue);
		$this->assertSame(Service::class, $defaultValue->getValue());
	}

	public function testBug4288(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4288.php');
		$this->assertNoErrors($errors);

		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass(MyClass::class);
		$parameter = ParametersAcceptorSelector::selectSingle($class->getNativeMethod('paginate')->getVariants())->getParameters()[0];
		$defaultValue = $parameter->getDefaultValue();
		$this->assertInstanceOf(ConstantIntegerType::class, $defaultValue);
		$this->assertSame(10, $defaultValue->getValue());

		$nativeProperty = $class->getNativeReflection()->getProperty('test');
		if (!method_exists($nativeProperty, 'getDefaultValue')) {
			return;
		}

		$this->assertSame(10, $nativeProperty->getDefaultValue());
	}

	public function testBug4702(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4702.php');
		$this->assertNoErrors($errors);
	}

	public function testFunctionThatExistsOn72AndLater(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/ldap-exop-passwd.php');
		if (PHP_VERSION_ID < 80100) {
			$this->assertNoErrors($errors);
			return;
		}

		$this->assertCount(1, $errors);
		$this->assertSame('Parameter #1 $ldap of function ldap_exop_passwd expects LDAP\Connection, resource given.', $errors[0]->getMessage());
	}

	public function testBug4715(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4715.php');
		$this->assertNoErrors($errors);
	}

	public function testBug4734(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4734.php');
		$this->assertCount(3, $errors);

		$this->assertSame('Unsafe access to private property Bug4734\Foo::$httpMethodParameterOverride through static::.', $errors[0]->getMessage());
		$this->assertSame('Access to an undefined static property static(Bug4734\Foo)::$httpMethodParameterOverride3.', $errors[1]->getMessage());
		$this->assertSame('Access to an undefined property Bug4734\Foo::$httpMethodParameterOverride4.', $errors[2]->getMessage());
	}

	public function testBug5231(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5231.php');
		$this->assertNotEmpty($errors);
	}

	public function testBug5231Two(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5231_2.php');
		$this->assertNotEmpty($errors);
	}

	public function testBug5529(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5529.php');
		$this->assertNoErrors($errors);
	}

	public function testBug5527(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5527.php');
		$this->assertNoErrors($errors);
	}

	public function testBug5639(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5639.php');
		$this->assertNoErrors($errors);
	}

	public function testBug5657(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5657.php');
		$this->assertNoErrors($errors);
	}

	public function testBug5951(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5951.php');
		$this->assertNoErrors($errors);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/enums-integration.php');
		$this->assertCount(3, $errors);
		$this->assertSame('Access to an undefined property EnumIntegrationTest\Foo::TWO::$value.', $errors[0]->getMessage());
		$this->assertSame(22, $errors[0]->getLine());
		$this->assertSame('Access to undefined constant EnumIntegrationTest\Bar::NONEXISTENT.', $errors[1]->getMessage());
		$this->assertSame(49, $errors[1]->getLine());
		$this->assertSame('Strict comparison using === between EnumIntegrationTest\Foo::ONE and EnumIntegrationTest\Foo::TWO will always evaluate to false.', $errors[2]->getMessage());
		$this->assertSame(79, $errors[2]->getLine());
	}

	public function testBug6255(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6255.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6300(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6300.php');
		$this->assertCount(2, $errors);
		$this->assertSame('Call to an undefined method Bug6300\Bar::get().', $errors[0]->getMessage());
		$this->assertSame(23, $errors[0]->getLine());

		$this->assertSame('Access to an undefined property Bug6300\Bar::$fooProp.', $errors[1]->getMessage());
		$this->assertSame(24, $errors[1]->getLine());
	}

	public function testBug6466(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6466.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6494(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6494.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6253(): void
	{
		$errors = $this->runAnalyse(
			__DIR__ . '/data/bug-6253.php',
			[
				__DIR__ . '/data/bug-6253.php',
				__DIR__ . '/data/bug-6253-app-scope-trait.php',
				__DIR__ . '/data/bug-6253-collection-trait.php',
			],
		);
		$this->assertNoErrors($errors);
	}

	public function testBug6442(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6442.php');
		$this->assertCount(2, $errors);
		$this->assertSame('Dumped type: \'Bug6442\\\A\'', $errors[0]->getMessage());
		$this->assertSame(9, $errors[0]->getLine());
		$this->assertSame('Dumped type: \'Bug6442\\\B\'', $errors[1]->getMessage());
		$this->assertSame(9, $errors[1]->getLine());
	}

	public function testBug6375(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6375.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6501(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6501.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6114(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6114.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6681(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6681.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6212(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6212.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6740(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6740-b.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6866(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6866.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6649(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6649.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6842(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6842.php');
		$this->assertCount(1, $errors);
		$this->assertSame('Generator expects value type T of DateTimeInterface, DateTime|DateTimeImmutable|T of DateTimeInterface given.', $errors[0]->getMessage());
		$this->assertSame(28, $errors[0]->getLine());
	}

	public function testBug6896(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6896.php');
		$this->assertCount(2, $errors);
	}

	public function testBug6940(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6940.php');
		$this->assertNoErrors($errors);
	}

	public function testBug1447(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-1447.php');
		$this->assertNoErrors($errors);
	}

	public function testBug5081(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-5081.php');
		$this->assertNoErrors($errors);
	}

	public function testBug1388(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-1388.php');
		$this->assertNoErrors($errors);
	}

	public function testBug4308(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4308.php');
		$this->assertNoErrors($errors);
	}

	public function testBug4732(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-4732.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6160(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6160.php');
		$this->assertCount(2, $errors);
		$this->assertSame('Parameter #1 $flags of static method Bug6160\HelloWorld::split() expects 0|1|2, 94561 given.', $errors[0]->getMessage());
		$this->assertSame(19, $errors[0]->getLine());
		$this->assertSame('Parameter #1 $flags of static method Bug6160\HelloWorld::split() expects 0|1|2, \'sdf\' given.', $errors[1]->getMessage());
		$this->assertSame(23, $errors[1]->getLine());
	}

	public function testBug6979(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6979.php');
		$this->assertNoErrors($errors);
	}

	public function testBug7030(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-7030.php');
		$this->assertCount(1, $errors);
		$this->assertSame('PHPDoc tag @method has invalid value (array  getItemsForID($id, $quantity, $shippingPostCode = null, $wholesalerList = null, $shippingLatitude =
	null, $shippingLongitude = null, $shippingNeutralShipping = null)): Unexpected token "\n * ", expected type at offset 193', $errors[0]->getMessage());
	}

	public function testBug7012(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/bug-7012.php');
		$this->assertNoErrors($errors);
	}

	public function testBug6192(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$errors = $this->runAnalyse(__DIR__ . '/data/bug-6192.php');
		$this->assertNoErrors($errors);
	}

	public function testBug7068(): void
	{
		$errors = $this->runAnalyse(__DIR__ . '/data/bug-7068.php');
		$this->assertNoErrors($errors);
	}

	/**
	 * @param string[]|null $allAnalysedFiles
	 * @return Error[]
	 */
	private function runAnalyse(string $file, ?array $allAnalysedFiles = null): array
	{
		$file = $this->getFileHelper()->normalizePath($file);
		/** @var Analyser $analyser */
		$analyser = self::getContainer()->getByType(Analyser::class);
		/** @var FileHelper $fileHelper */
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		/** @var Error[] $errors */
		$errors = $analyser->analyse([$file], null, null, true, $allAnalysedFiles)->getErrors();
		foreach ($errors as $error) {
			$this->assertSame($fileHelper->normalizePath($file), $error->getFilePath());
		}

		return $errors;
	}

}
