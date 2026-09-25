<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DependentPhpDocs\Foo;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\Cache\CacheItem;
use PHPStan\Cache\CacheStorage;
use PHPStan\File\FileContentHasher;
use PHPStan\File\FileHelper;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;
use function array_values;
use function realpath;
use function serialize;
use function substr_count;
use function unserialize;

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

	public function testNameScopeMapStoresImportsOnce(): void
	{
		$storage = new class implements CacheStorage {

			/** @var array<string, string> */
			public array $storage = [];

			/**
			 * @return mixed|null
			 */
			public function load(string $key, string $variableKey)
			{
				if (!isset($this->storage[$key])) {
					return null;
				}

				$item = unserialize($this->storage[$key]);
				if (!$item instanceof CacheItem || !$item->isVariableKeyValid($variableKey)) {
					return null;
				}

				return $item->getData();
			}

			/**
			 * @param mixed $data
			 */
			public function save(string $key, string $variableKey, $data): void
			{
				$this->storage[$key] = serialize(new CacheItem($variableKey, $data));
			}

		};

		$fileName = __DIR__ . '/data/bug-15304.php';
		$this->assertReturnType($this->createFileTypeMapper($storage), $fileName, 'Bug15304\\Foo', 'one', '/** @return ModelOne */', 'Bug15304\\Models\\ModelOne');

		$this->assertCount(1, $storage->storage);
		$payload = array_values($storage->storage)[0];

		// the use statements before Foo are stored once for Foo and its methods, and once more
		// together with the use statement before Bar, not once per method
		$this->assertSame(2, substr_count($payload, 'Bug15304\\Models\\ModelThree'));
		$this->assertSame(2, substr_count($payload, 'Bug15304\\Models\\SOME_CONSTANT'));
		$this->assertSame(1, substr_count($payload, 'Bug15304\\Models\\ModelFour'));

		$fileTypeMapper = $this->createFileTypeMapper($storage);
		$this->assertReturnType($fileTypeMapper, $fileName, 'Bug15304\\Foo', 'three', '/** @return ModelThree */', 'Bug15304\\Models\\ModelThree');
		$this->assertReturnType($fileTypeMapper, $fileName, 'Bug15304\\Foo', 'four', '/**
	 * @template U
	 * @param U $u
	 * @return T|U
	 */', 'T (class Bug15304\\Foo, parameter)|U (method Bug15304\\Foo::four(), parameter)');
		$this->assertReturnType($fileTypeMapper, $fileName, 'Bug15304\\Bar', 'one', '/** @return ModelFour */', 'Bug15304\\Models\\ModelFour');
		$this->assertReturnType($fileTypeMapper, $fileName, 'Bug15304\\Bar', 'two', '/** @return ModelOne */', 'Bug15304\\Models\\ModelOne');
		$this->assertReturnType($fileTypeMapper, $fileName, 'Bug15304\\Other\\Baz', 'one', '/** @return ModelOne */', 'Bug15304\\Other\\ModelOne');
	}

	private function assertReturnType(FileTypeMapper $fileTypeMapper, string $fileName, string $className, string $functionName, string $docComment, string $expectedType): void
	{
		$returnTag = $fileTypeMapper->getResolvedPhpDoc($fileName, $className, null, $functionName, $docComment)->getReturnTag();
		$this->assertNotNull($returnTag);
		$this->assertSame($expectedType, $returnTag->getType()->describe(VerbosityLevel::precise()));
	}

	private function createFileTypeMapper(CacheStorage $storage): FileTypeMapper
	{
		$container = self::getContainer();

		return new FileTypeMapper(
			$container->getByType(ReflectionProviderProvider::class),
			$container->getService('defaultAnalysisParser'),
			$container->getByType(PhpDocStringResolver::class),
			$container->getByType(PhpDocNodeResolver::class),
			$container->getByType(AnonymousClassNameHelper::class),
			$container->getByType(FileHelper::class),
			new Cache($storage),
			$container->getByType(FileContentHasher::class),
			2048,
			128,
		);
	}

}
