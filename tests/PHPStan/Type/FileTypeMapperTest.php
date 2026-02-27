<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DependentPhpDocs\Foo;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use function file_put_contents;
use function microtime;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class FileTypeMapperTest extends PHPStanTestCase
{

	public function testGetResolvedPhpDoc(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$resolvedA = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/annotations.php', 'TestAnnotations\\Foo', null, null, '/**
 * @property int | float $numericBazBazProperty
 * @property X $singleLetterObjectName
 *
 * @method void simpleMethod()
 * @method string returningMethod()
 * @method ?float returningNullableScalar()
 * @method ?\stdClass returningNullableObject()
 * @method void complicatedParameters(string $a, ?int|?float|?\stdClass $b, \stdClass $c = null, string|?int $d)
 * @method Image rotate(float $angle, $backgroundColor)
 * @method int | float paramMultipleTypesWithExtraSpaces(string | null $string, stdClass | null $object)
 */');
		$this->assertCount(0, $resolvedA->getVarTags());
		$this->assertCount(0, $resolvedA->getParamTags());
		$this->assertCount(2, $resolvedA->getPropertyTags());
		$this->assertArrayHasKey('numericBazBazProperty', $resolvedA->getPropertyTags());
		$this->assertNull($resolvedA->getReturnTag());
		$this->assertNotNull($resolvedA->getPropertyTags()['numericBazBazProperty']->getReadableType());
		$this->assertNotNull($resolvedA->getPropertyTags()['numericBazBazProperty']->getWritableType());
		$this->assertSame('float|int', $resolvedA->getPropertyTags()['numericBazBazProperty']->getReadableType()->describe(VerbosityLevel::precise()));
		$this->assertSame('float|int', $resolvedA->getPropertyTags()['numericBazBazProperty']->getWritableType()->describe(VerbosityLevel::precise()));
		$this->assertArrayHasKey('singleLetterObjectName', $resolvedA->getPropertyTags());
		$this->assertNotNull($resolvedA->getPropertyTags()['singleLetterObjectName']->getReadableType());
		$this->assertNotNull($resolvedA->getPropertyTags()['singleLetterObjectName']->getWritableType());
		$this->assertSame('TestAnnotations\\X', $resolvedA->getPropertyTags()['singleLetterObjectName']->getReadableType()->describe(VerbosityLevel::precise()));
		$this->assertSame('TestAnnotations\\X', $resolvedA->getPropertyTags()['singleLetterObjectName']->getWritableType()->describe(VerbosityLevel::precise()));

		$this->assertCount(6, $resolvedA->getMethodTags());
		$this->assertArrayNotHasKey('complicatedParameters', $resolvedA->getMethodTags()); // ambiguous parameter types
		$this->assertArrayHasKey('simpleMethod', $resolvedA->getMethodTags());
		$simpleMethod = $resolvedA->getMethodTags()['simpleMethod'];
		$this->assertSame('void', $simpleMethod->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($simpleMethod->isStatic());
		$this->assertCount(0, $simpleMethod->getParameters());

		$this->assertArrayHasKey('returningMethod', $resolvedA->getMethodTags());
		$returningMethod = $resolvedA->getMethodTags()['returningMethod'];
		$this->assertSame('string', $returningMethod->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($returningMethod->isStatic());
		$this->assertCount(0, $returningMethod->getParameters());

		$this->assertArrayHasKey('returningNullableScalar', $resolvedA->getMethodTags());
		$returningNullableScalar = $resolvedA->getMethodTags()['returningNullableScalar'];
		$this->assertSame('float|null', $returningNullableScalar->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($returningNullableScalar->isStatic());
		$this->assertCount(0, $returningNullableScalar->getParameters());

		$this->assertArrayHasKey('returningNullableObject', $resolvedA->getMethodTags());
		$returningNullableObject = $resolvedA->getMethodTags()['returningNullableObject'];
		$this->assertSame('stdClass|null', $returningNullableObject->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($returningNullableObject->isStatic());
		$this->assertCount(0, $returningNullableObject->getParameters());

		$this->assertArrayHasKey('rotate', $resolvedA->getMethodTags());
		$rotate = $resolvedA->getMethodTags()['rotate'];
		$this->assertSame('TestAnnotations\\Image', $rotate->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($rotate->isStatic());
		$this->assertCount(2, $rotate->getParameters());
		$this->assertArrayHasKey('angle', $rotate->getParameters());
		$this->assertSame('float', $rotate->getParameters()['angle']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($rotate->getParameters()['angle']->passedByReference()->no());
		$this->assertFalse($rotate->getParameters()['angle']->isOptional());
		$this->assertFalse($rotate->getParameters()['angle']->isVariadic());
		$this->assertArrayHasKey('backgroundColor', $rotate->getParameters());
		$this->assertSame('mixed', $rotate->getParameters()['backgroundColor']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($rotate->getParameters()['backgroundColor']->passedByReference()->no());
		$this->assertFalse($rotate->getParameters()['backgroundColor']->isOptional());
		$this->assertFalse($rotate->getParameters()['backgroundColor']->isVariadic());

		$this->assertArrayHasKey('paramMultipleTypesWithExtraSpaces', $resolvedA->getMethodTags());
		$paramMultipleTypesWithExtraSpaces = $resolvedA->getMethodTags()['paramMultipleTypesWithExtraSpaces'];
		$this->assertSame('float|int', $paramMultipleTypesWithExtraSpaces->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->isStatic());
		$this->assertCount(2, $paramMultipleTypesWithExtraSpaces->getParameters());
		$this->assertArrayHasKey('string', $paramMultipleTypesWithExtraSpaces->getParameters());
		$this->assertSame('string|null', $paramMultipleTypesWithExtraSpaces->getParameters()['string']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($paramMultipleTypesWithExtraSpaces->getParameters()['string']->passedByReference()->no());
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['string']->isOptional());
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['string']->isVariadic());
		$this->assertArrayHasKey('object', $paramMultipleTypesWithExtraSpaces->getParameters());
		$this->assertSame('TestAnnotations\\stdClass|null', $paramMultipleTypesWithExtraSpaces->getParameters()['object']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($paramMultipleTypesWithExtraSpaces->getParameters()['object']->passedByReference()->no());
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['object']->isOptional());
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['object']->isVariadic());
	}

	public function testFileWithDependentPhpDocs(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$realpath = realpath(__DIR__ . '/data/dependent-phpdocs.php');
		if ($realpath === false) {
			throw new ShouldNotHappenException();
		}

		$resolved = $fileTypeMapper->getResolvedPhpDoc(
			$realpath,
			Foo::class,
			null,
			'addPages',
			'/** @param Foo[]|Foo|\Iterator $pages */',
		);

		$this->assertCount(1, $resolved->getParamTags());
		$this->assertArrayHasKey('pages', $resolved->getParamTags());
		$this->assertSame(
			'(DependentPhpDocs\Foo&iterable<DependentPhpDocs\Foo>)|(iterable<DependentPhpDocs\Foo>&Iterator)',
			$resolved->getParamTags()['pages']->getType()->describe(VerbosityLevel::precise()),
		);
	}

	public function testFileThrowsPhpDocs(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$realpath = realpath(__DIR__ . '/data/throws-phpdocs.php');
		if ($realpath === false) {
			throw new ShouldNotHappenException();
		}

		$resolved = $fileTypeMapper->getResolvedPhpDoc($realpath, \ThrowsPhpDocs\Foo::class, null, 'throwRuntimeException', '/**
 * @throws RuntimeException
 */');

		$this->assertNotNull($resolved->getThrowsTag());
		$this->assertSame(
			RuntimeException::class,
			$resolved->getThrowsTag()->getType()->describe(VerbosityLevel::precise()),
		);

		$resolved = $fileTypeMapper->getResolvedPhpDoc($realpath, \ThrowsPhpDocs\Foo::class, null, 'throwRuntimeAndLogicException', '/**
 * @throws RuntimeException|LogicException
 */');

		$this->assertNotNull($resolved->getThrowsTag());
		$this->assertSame(
			'LogicException|RuntimeException',
			$resolved->getThrowsTag()->getType()->describe(VerbosityLevel::precise()),
		);

		$resolved = $fileTypeMapper->getResolvedPhpDoc($realpath, \ThrowsPhpDocs\Foo::class, null, 'throwRuntimeAndLogicException2', '/**
 * @throws RuntimeException
 * @throws LogicException
 */');

		$this->assertNotNull($resolved->getThrowsTag());
		$this->assertSame(
			'LogicException|RuntimeException',
			$resolved->getThrowsTag()->getType()->describe(VerbosityLevel::precise()),
		);
	}

	public function testFileWithCyclicPhpDocs(): void
	{
		self::createReflectionProvider();

		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$realpath = realpath(__DIR__ . '/data/cyclic-phpdocs.php');
		if ($realpath === false) {
			throw new ShouldNotHappenException();
		}

		$resolved = $fileTypeMapper->getResolvedPhpDoc(
			$realpath,
			\CyclicPhpDocs\Foo::class,
			null,
			'getIterator',
			'/** @return iterable<Foo> | Foo */',
		);

		/** @var ReturnTag $returnTag */
		$returnTag = $resolved->getReturnTag();
		$this->assertSame('CyclicPhpDocs\Foo|iterable<CyclicPhpDocs\Foo>', $returnTag->getType()->describe(VerbosityLevel::precise()));
	}

	public function testLargeStubFileLazyPhpDocParsing(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		// Generate a large stub file with many PHPDoc comments, simulating
		// WordPress-style stubs where a single file declares many functions.
		$tmpFile = tempnam(sys_get_temp_dir(), 'phpstan_stub_perf_');
		if ($tmpFile === false) {
			throw new ShouldNotHappenException();
		}
		$tmpFile .= '.php';

		try {
			$code = "<?php declare(strict_types = 1);\n\nnamespace StubPhpDocPerformance;\n\n";

			// 10000 functions with complex PHPDocs but no template/type-alias tags
			for ($i = 1; $i <= 10000; $i++) {
				$code .= "/**\n";
				$code .= " * @param array<string, array<int, string>> \$param1\n";
				$code .= " * @param callable(int, string): array<string, mixed> \$param2\n";
				$code .= " * @param list<array{id: int, name: string, data: array<string, mixed>}> \$param3\n";
				$code .= " * @return array<int, array{key: string, value: mixed, metadata: array<string, string>}>\n";
				$code .= " */\n";
				$code .= "function stub_{$i}(array \$param1, callable \$param2, array \$param3): array {}\n\n";
			}

			// A class with @template - this PHPDoc must still be parsed
			$code .= "/**\n * @template T\n */\n";
			$code .= "class GenericContainer\n{\n";
			$code .= "    /** @var T */\n    private \$value;\n\n";
			$code .= "    /** @param T \$value */\n";
			$code .= "    public function __construct(\$value) { \$this->value = \$value; }\n\n";
			$code .= "    /** @return T */\n";
			$code .= "    public function getValue() { return \$this->value; }\n";
			$code .= "}\n";

			file_put_contents($tmpFile, $code);

			$start = microtime(true);

			// Resolve the template class - should work despite 10000 other PHPDocs in file
			$resolved = $fileTypeMapper->getResolvedPhpDoc(
				$tmpFile,
				'StubPhpDocPerformance\\GenericContainer',
				null,
				'getValue',
				'/** @return T */',
			);

			$elapsed = microtime(true) - $start;

			$returnTag = $resolved->getReturnTag();
			$this->assertNotNull($returnTag);
			$this->assertSame(
				'T (class StubPhpDocPerformance\GenericContainer, parameter)',
				$returnTag->getType()->describe(VerbosityLevel::precise()),
			);

			// With lazy PHPDoc parsing, only PHPDocs with @template or type-alias
			// tags are parsed during name scope map creation. Without the optimization,
			// all 10000+ PHPDocs must be parsed, taking >2 seconds on typical hardware.
			$this->assertLessThan(
				3.0,
				$elapsed,
				'FileTypeMapper should skip PHPDoc parsing for entries without template/type-alias tags',
			);
		} finally {
			@unlink($tmpFile);
		}
	}

	public function testFilesWithIdenticalPhpDocsUsingDifferentAliases(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$doc1 = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/alias-collision1.php', null, null, null, '/** @var Foo $x */');
		$doc2 = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/alias-collision2.php', null, null, null, '/** @var Foo $x */');

		$this->assertArrayHasKey('x', $doc1->getVarTags());
		$this->assertSame('AliasCollisionNamespace1\Foo', $doc1->getVarTags()['x']->getType()->describe(VerbosityLevel::precise()));
		$this->assertArrayHasKey('x', $doc2->getVarTags());
		$this->assertSame('AliasCollisionNamespace2\Foo', $doc2->getVarTags()['x']->getType()->describe(VerbosityLevel::precise()));
	}

}
