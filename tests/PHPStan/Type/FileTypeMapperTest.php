<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DependentPhpDocs\Foo;
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
		self::assertCount(0, $resolvedA->getVarTags());
		self::assertCount(0, $resolvedA->getParamTags());
		self::assertCount(2, $resolvedA->getPropertyTags());
		self::assertArrayHasKey('numericBazBazProperty', $resolvedA->getPropertyTags());
		self::assertNull($resolvedA->getReturnTag());
		self::assertNotNull($resolvedA->getPropertyTags()['numericBazBazProperty']->getReadableType());
		self::assertNotNull($resolvedA->getPropertyTags()['numericBazBazProperty']->getWritableType());
		self::assertSame('float|int', $resolvedA->getPropertyTags()['numericBazBazProperty']->getReadableType()->describe(VerbosityLevel::precise()));
		self::assertSame('float|int', $resolvedA->getPropertyTags()['numericBazBazProperty']->getWritableType()->describe(VerbosityLevel::precise()));
		self::assertArrayHasKey('singleLetterObjectName', $resolvedA->getPropertyTags());
		self::assertNotNull($resolvedA->getPropertyTags()['singleLetterObjectName']->getReadableType());
		self::assertNotNull($resolvedA->getPropertyTags()['singleLetterObjectName']->getWritableType());
		self::assertSame('TestAnnotations\\X', $resolvedA->getPropertyTags()['singleLetterObjectName']->getReadableType()->describe(VerbosityLevel::precise()));
		self::assertSame('TestAnnotations\\X', $resolvedA->getPropertyTags()['singleLetterObjectName']->getWritableType()->describe(VerbosityLevel::precise()));

		self::assertCount(6, $resolvedA->getMethodTags());
		self::assertArrayNotHasKey('complicatedParameters', $resolvedA->getMethodTags()); // ambiguous parameter types
		self::assertArrayHasKey('simpleMethod', $resolvedA->getMethodTags());
		$simpleMethod = $resolvedA->getMethodTags()['simpleMethod'];
		self::assertSame('void', $simpleMethod->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertFalse($simpleMethod->isStatic());
		self::assertCount(0, $simpleMethod->getParameters());

		self::assertArrayHasKey('returningMethod', $resolvedA->getMethodTags());
		$returningMethod = $resolvedA->getMethodTags()['returningMethod'];
		self::assertSame('string', $returningMethod->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertFalse($returningMethod->isStatic());
		self::assertCount(0, $returningMethod->getParameters());

		self::assertArrayHasKey('returningNullableScalar', $resolvedA->getMethodTags());
		$returningNullableScalar = $resolvedA->getMethodTags()['returningNullableScalar'];
		self::assertSame('float|null', $returningNullableScalar->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertFalse($returningNullableScalar->isStatic());
		self::assertCount(0, $returningNullableScalar->getParameters());

		self::assertArrayHasKey('returningNullableObject', $resolvedA->getMethodTags());
		$returningNullableObject = $resolvedA->getMethodTags()['returningNullableObject'];
		self::assertSame('stdClass|null', $returningNullableObject->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertFalse($returningNullableObject->isStatic());
		self::assertCount(0, $returningNullableObject->getParameters());

		self::assertArrayHasKey('rotate', $resolvedA->getMethodTags());
		$rotate = $resolvedA->getMethodTags()['rotate'];
		self::assertSame('TestAnnotations\\Image', $rotate->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertFalse($rotate->isStatic());
		self::assertCount(2, $rotate->getParameters());
		self::assertArrayHasKey('angle', $rotate->getParameters());
		self::assertSame('float', $rotate->getParameters()['angle']->getType()->describe(VerbosityLevel::precise()));
		self::assertTrue($rotate->getParameters()['angle']->passedByReference()->no());
		self::assertFalse($rotate->getParameters()['angle']->isOptional());
		self::assertFalse($rotate->getParameters()['angle']->isVariadic());
		self::assertArrayHasKey('backgroundColor', $rotate->getParameters());
		self::assertSame('mixed', $rotate->getParameters()['backgroundColor']->getType()->describe(VerbosityLevel::precise()));
		self::assertTrue($rotate->getParameters()['backgroundColor']->passedByReference()->no());
		self::assertFalse($rotate->getParameters()['backgroundColor']->isOptional());
		self::assertFalse($rotate->getParameters()['backgroundColor']->isVariadic());

		self::assertArrayHasKey('paramMultipleTypesWithExtraSpaces', $resolvedA->getMethodTags());
		$paramMultipleTypesWithExtraSpaces = $resolvedA->getMethodTags()['paramMultipleTypesWithExtraSpaces'];
		self::assertSame('float|int', $paramMultipleTypesWithExtraSpaces->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertFalse($paramMultipleTypesWithExtraSpaces->isStatic());
		self::assertCount(2, $paramMultipleTypesWithExtraSpaces->getParameters());
		self::assertArrayHasKey('string', $paramMultipleTypesWithExtraSpaces->getParameters());
		self::assertSame('string|null', $paramMultipleTypesWithExtraSpaces->getParameters()['string']->getType()->describe(VerbosityLevel::precise()));
		self::assertTrue($paramMultipleTypesWithExtraSpaces->getParameters()['string']->passedByReference()->no());
		self::assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['string']->isOptional());
		self::assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['string']->isVariadic());
		self::assertArrayHasKey('object', $paramMultipleTypesWithExtraSpaces->getParameters());
		self::assertSame('TestAnnotations\\stdClass|null', $paramMultipleTypesWithExtraSpaces->getParameters()['object']->getType()->describe(VerbosityLevel::precise()));
		self::assertTrue($paramMultipleTypesWithExtraSpaces->getParameters()['object']->passedByReference()->no());
		self::assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['object']->isOptional());
		self::assertFalse($paramMultipleTypesWithExtraSpaces->getParameters()['object']->isVariadic());
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

		self::assertCount(1, $resolved->getParamTags());
		self::assertArrayHasKey('pages', $resolved->getParamTags());
		self::assertSame(
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

		self::assertNotNull($resolved->getThrowsTag());
		self::assertSame(
			RuntimeException::class,
			$resolved->getThrowsTag()->getType()->describe(VerbosityLevel::precise()),
		);

		$resolved = $fileTypeMapper->getResolvedPhpDoc($realpath, \ThrowsPhpDocs\Foo::class, null, 'throwRuntimeAndLogicException', '/**
 * @throws RuntimeException|LogicException
 */');

		self::assertNotNull($resolved->getThrowsTag());
		self::assertSame(
			'LogicException|RuntimeException',
			$resolved->getThrowsTag()->getType()->describe(VerbosityLevel::precise()),
		);

		$resolved = $fileTypeMapper->getResolvedPhpDoc($realpath, \ThrowsPhpDocs\Foo::class, null, 'throwRuntimeAndLogicException2', '/**
 * @throws RuntimeException
 * @throws LogicException
 */');

		self::assertNotNull($resolved->getThrowsTag());
		self::assertSame(
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
		self::assertSame('CyclicPhpDocs\Foo|iterable<CyclicPhpDocs\Foo>', $returnTag->getType()->describe(VerbosityLevel::precise()));
	}

	public function testFilesWithIdenticalPhpDocsUsingDifferentAliases(): void
	{
		/** @var FileTypeMapper $fileTypeMapper */
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		$doc1 = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/alias-collision1.php', null, null, null, '/** @var Foo $x */');
		$doc2 = $fileTypeMapper->getResolvedPhpDoc(__DIR__ . '/data/alias-collision2.php', null, null, null, '/** @var Foo $x */');

		self::assertArrayHasKey('x', $doc1->getVarTags());
		self::assertSame('AliasCollisionNamespace1\Foo', $doc1->getVarTags()['x']->getType()->describe(VerbosityLevel::precise()));
		self::assertArrayHasKey('x', $doc2->getVarTags());
		self::assertSame('AliasCollisionNamespace2\Foo', $doc2->getVarTags()['x']->getType()->describe(VerbosityLevel::precise()));
	}

}
