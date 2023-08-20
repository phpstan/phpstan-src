<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DependentPhpDocs\Foo;
use PHPStan\Broker\Broker;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use function realpath;

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
		$this->assertNull($resolvedA->getReturnTag());
		$this->assertNotNull($resolvedA->getPropertyTags()['numericBazBazProperty']->getReadableType());
		$this->assertNotNull($resolvedA->getPropertyTags()['numericBazBazProperty']->getWritableType());
		$this->assertSame('float|int', $resolvedA->getPropertyTags()['numericBazBazProperty']->getReadableType()->describe(VerbosityLevel::precise()));
		$this->assertSame('float|int', $resolvedA->getPropertyTags()['numericBazBazProperty']->getWritableType()->describe(VerbosityLevel::precise()));
		$this->assertNotNull($resolvedA->getPropertyTags()['singleLetterObjectName']->getReadableType());
		$this->assertNotNull($resolvedA->getPropertyTags()['singleLetterObjectName']->getWritableType());
		$this->assertSame('TestAnnotations\\X', $resolvedA->getPropertyTags()['singleLetterObjectName']->getReadableType()->describe(VerbosityLevel::precise()));
		$this->assertSame('TestAnnotations\\X', $resolvedA->getPropertyTags()['singleLetterObjectName']->getWritableType()->describe(VerbosityLevel::precise()));

		$this->assertCount(6, $resolvedA->getMethodTags());
		$this->assertArrayNotHasKey('complicatedParameters', $resolvedA->getMethodTags()); // ambiguous parameter types
		$simpleMethod = $resolvedA->getMethodTags()['simpleMethod'];
		$this->assertSame('void', $simpleMethod->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($simpleMethod->isStatic());
		$this->assertCount(0, $simpleMethod->getParameters());

		$returningMethod = $resolvedA->getMethodTags()['returningMethod'];
		$this->assertSame('string', $returningMethod->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($returningMethod->isStatic());
		$this->assertCount(0, $returningMethod->getParameters());

		$returningNullableScalar = $resolvedA->getMethodTags()['returningNullableScalar'];
		$this->assertSame('float|null', $returningNullableScalar->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($returningNullableScalar->isStatic());
		$this->assertCount(0, $returningNullableScalar->getParameters());

		$returningNullableObject = $resolvedA->getMethodTags()['returningNullableObject'];
		$this->assertSame('stdClass|null', $returningNullableObject->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($returningNullableObject->isStatic());
		$this->assertCount(0, $returningNullableObject->getParameters());

		$rotate = $resolvedA->getMethodTags()['rotate'];
		$this->assertSame('TestAnnotations\\Image', $rotate->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($rotate->isStatic());
		$this->assertCount(2, $rotate->getParameters());
		$this->assertSame('float', $rotate->getParameters()['angle']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($rotate->getParameters()['angle']->passedByReference()->no());
		$this->assertFalse($rotate->getParameters()['angle']->isOptional());
		$this->assertFalse($rotate->getParameters()['angle']->isVariadic());
		$this->assertSame('mixed', $rotate->getParameters()['backgroundColor']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($rotate->getParameters()['backgroundColor']->passedByReference()->no());
		$this->assertFalse($rotate->getParameters()['backgroundColor']->isOptional());
		$this->assertFalse($rotate->getParameters()['backgroundColor']->isVariadic());

		$paramMultipleTypesWithExtraSpaces = $resolvedA->getMethodTags()['paramMultipleTypesWithExtraSpaces'];
		$this->assertSame('float|int', $paramMultipleTypesWithExtraSpaces->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->isStatic());
		$this->assertCount(2, $paramMultipleTypesWithExtraSpaces->getParameters());
		$this->assertSame('string|null', $paramMultipleTypesWithExtraSpaces->getParameters()['string']->getType()->describe(VerbosityLevel::precise()));
		$this->assertTrue($paramMultipleTypesWithExtraSpaces->getParameters()['string']->passedByReference()->no());
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['string']->isOptional());
		$this->assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['string']->isVariadic());
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
		self::getContainer()->getByType(Broker::class);

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

	public function testFilesWithIdenticalPhpDocsUsingDifferentAliases(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$doc1 = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/alias-collision1.php', null, null, null, '/** @var Foo $x */');
		$doc2 = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/alias-collision2.php', null, null, null, '/** @var Foo $x */');

		$this->assertSame('AliasCollisionNamespace1\Foo', $doc1->getVarTags()['x']->getType()->describe(VerbosityLevel::precise()));
		$this->assertSame('AliasCollisionNamespace2\Foo', $doc2->getVarTags()['x']->getType()->describe(VerbosityLevel::precise()));
	}

}
