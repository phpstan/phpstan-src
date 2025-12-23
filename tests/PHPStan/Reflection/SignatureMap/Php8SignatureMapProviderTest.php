<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\Php\PhpVersion;
use PHPStan\Php8StubsMap;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_map;
use function array_merge;
use function count;
use const PHP_VERSION_ID;

class Php8SignatureMapProviderTest extends PHPStanTestCase
{

	public static function dataFunctions(): array
	{
		return [
			[
				'curl_init',
				[
					[
						'name' => 'url',
						'optional' => true,
						'type' => new UnionType([
							new StringType(),
							new NullType(),
						]),
						'nativeType' => new UnionType([
							new StringType(),
							new NullType(),
						]),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
					],
				],
				new BenevolentUnionType([
					new ObjectType('CurlHandle'),
					new ConstantBooleanType(false),
				]),
				new UnionType([
					new ObjectType('CurlHandle'),
					new ConstantBooleanType(false),
				]),
				false,
			],
			[
				'curl_exec',
				[
					[
						'name' => 'handle',
						'optional' => false,
						'type' => new ObjectType('CurlHandle'),
						'nativeType' => new ObjectType('CurlHandle'),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
					],
				],
				new UnionType([new StringType(), new BooleanType()]),
				new UnionType([new StringType(), new BooleanType()]),
				false,
			],
			[
				'date_get_last_errors',
				[],
				new UnionType([
					new ConstantBooleanType(false),
					new ConstantArrayType([
						new ConstantStringType('warning_count'),
						new ConstantStringType('warnings'),
						new ConstantStringType('error_count'),
						new ConstantStringType('errors'),
					], [
						IntegerRangeType::fromInterval(0, null),
						new IntersectionType([new ArrayType(IntegerRangeType::fromInterval(0, null), new StringType()), new AccessoryArrayListType()]),
						IntegerRangeType::fromInterval(0, null),
						new IntersectionType([new ArrayType(IntegerRangeType::fromInterval(0, null), new StringType()), new AccessoryArrayListType()]),
					]),
				]),
				new UnionType([
					new ConstantBooleanType(false),
					new ArrayType(new MixedType(), new MixedType()),
				]),
				false,
			],
			[
				'end',
				[
					[
						'name' => 'array',
						'optional' => false,
						'type' => new UnionType([new ArrayType(new MixedType(), new MixedType()), new ObjectWithoutClassType()]),
						'nativeType' => new UnionType([new ArrayType(new MixedType(), new MixedType()), new ObjectWithoutClassType()]),
						'passedByReference' => PassedByReference::createReadsArgument(),
						'variadic' => false,
					],
				],
				new MixedType(true),
				new MixedType(true),
				false,
			],
		];
	}

	/**
	 * @param mixed[] $parameters
	 */
	#[DataProvider('dataFunctions')]
	public function testFunctions(
		string $functionName,
		array $parameters,
		Type $returnType,
		Type $nativeReturnType,
		bool $variadic,
	): void
	{
		$provider = $this->createProvider();
		$reflector = self::getContainer()->getByType(Reflector::class);
		$signatures = $provider->getFunctionSignatures($functionName, null, new ReflectionFunction($reflector->reflectFunction($functionName)))['positional'];
		self::assertCount(1, $signatures);
		self::assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signatures[0]);
	}

	private function createProvider(): Php8SignatureMapProvider
	{
		$phpVersion = new PhpVersion(80000);

		return new Php8SignatureMapProvider(
			new FunctionSignatureMapProvider(
				self::getContainer()->getByType(SignatureMapParser::class),
				self::getContainer()->getByType(InitializerExprTypeResolver::class),
				$phpVersion,
				true,
			),
			self::getContainer()->getByType(FileNodesFetcher::class),
			self::getContainer()->getByType(FileTypeMapper::class),
			$phpVersion,
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			self::getContainer()->getByType(ReflectionProviderProvider::class),
		);
	}

	public static function dataMethods(): array
	{
		return [
			[
				'Closure',
				'bindTo',
				[
					[
						'name' => 'newThis',
						'optional' => false,
						'type' => new UnionType([
							new ObjectWithoutClassType(),
							new NullType(),
						]),
						'nativeType' => new UnionType([
							new ObjectWithoutClassType(),
							new NullType(),
						]),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
					],
					[
						'name' => 'newScope',
						'optional' => true,
						'type' => new UnionType([
							new ObjectWithoutClassType(),
							new ClassStringType(),
							new ConstantStringType('static'),
							new NullType(),
						]),
						'nativeType' => new UnionType([
							new ObjectWithoutClassType(),
							new StringType(),
							new NullType(),
						]),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
					],
				],
				new BenevolentUnionType([
					new ObjectType('Closure'),
					new NullType(),
				]),
				new UnionType([
					new ObjectType('Closure'),
					new NullType(),
				]),
				false,
			],
			[
				'ArrayIterator',
				'uasort',
				[
					[
						'name' => 'callback',
						'optional' => false,
						'type' => new CallableType([
							new NativeParameterReflection('', false, new MixedType(true), PassedByReference::createNo(), false, null),
							new NativeParameterReflection('', false, new MixedType(true), PassedByReference::createNo(), false, null),
						], new IntegerType(), false),
						'nativeType' => new CallableType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
					],
				],
				new VoidType(),
				new MixedType(),
				false,
			],
			[
				'RecursiveArrayIterator',
				'uasort',
				[
					[
						'name' => 'callback',
						'optional' => false,
						'type' => new CallableType([
							new NativeParameterReflection('', false, new MixedType(true), PassedByReference::createNo(), false, null),
							new NativeParameterReflection('', false, new MixedType(true), PassedByReference::createNo(), false, null),
						], new IntegerType(), false),
						'nativeType' => new MixedType(), // todo - because uasort is not found in file with RecursiveArrayIterator
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
					],
				],
				new VoidType(),
				new MixedType(), // todo - because uasort is not found in file with RecursiveArrayIterator
				false,
			],
		];
	}

	/**
	 * @param mixed[] $parameters
	 */
	#[DataProvider('dataMethods')]
	public function testMethods(
		string $className,
		string $methodName,
		array $parameters,
		Type $returnType,
		Type $nativeReturnType,
		bool $variadic,
	): void
	{
		$provider = $this->createProvider();
		$signatures = $provider->getMethodSignatures($className, $methodName, null)['positional'];
		self::assertCount(1, $signatures);
		self::assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signatures[0]);
	}

	/**
	 * @param mixed[] $expectedParameters
	 */
	static private function assertSignature(
		array $expectedParameters,
		Type $expectedReturnType,
		Type $expectedNativeReturnType,
		bool $expectedVariadic,
		FunctionSignature $actualSignature,
	): void
	{
		self::assertCount(count($expectedParameters), $actualSignature->getParameters());
		foreach ($expectedParameters as $i => $expectedParameter) {
			$actualParameter = $actualSignature->getParameters()[$i];
			self::assertSame($expectedParameter['name'], $actualParameter->getName());
			self::assertSame($expectedParameter['optional'], $actualParameter->isOptional());
			self::assertSame($expectedParameter['type']->describe(VerbosityLevel::precise()), $actualParameter->getType()->describe(VerbosityLevel::precise()));
			self::assertSame($expectedParameter['nativeType']->describe(VerbosityLevel::precise()), $actualParameter->getNativeType()->describe(VerbosityLevel::precise()));
			self::assertTrue($expectedParameter['passedByReference']->equals($actualParameter->passedByReference()));
			self::assertSame($expectedParameter['variadic'], $actualParameter->isVariadic());
		}

		self::assertSame($expectedReturnType->describe(VerbosityLevel::precise()), $actualSignature->getReturnType()->describe(VerbosityLevel::precise()));
		self::assertSame($expectedNativeReturnType->describe(VerbosityLevel::precise()), $actualSignature->getNativeReturnType()->describe(VerbosityLevel::precise()));
		self::assertSame($expectedVariadic, $actualSignature->isVariadic());
	}

	public static function dataParseAll(): array
	{
		$map = new Php8StubsMap(PHP_VERSION_ID);
		return array_map(static fn (string $file): array => [__DIR__ . '/../../../../vendor/phpstan/php-8-stubs/' . $file], array_merge($map->classes, $map->functions));
	}

	#[DataProvider('dataParseAll')]
	public function testParseAll(string $stubFile): void
	{
		$parser = $this->getParser();
		$parser->parseFile($stubFile);
		$this->expectNotToPerformAssertions();
	}

}
