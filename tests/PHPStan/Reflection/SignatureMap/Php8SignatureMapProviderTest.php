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
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function array_map;
use function array_merge;
use function count;
use const PHP_VERSION_ID;

class Php8SignatureMapProviderTest extends PHPStanTestCase
{

	public function dataFunctions(): array
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
				new UnionType([
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
						new ArrayType(new IntegerType(), new StringType()),
						IntegerRangeType::fromInterval(0, null),
						new ArrayType(new IntegerType(), new StringType()),
					]),
				]),
				new UnionType([
					new ConstantBooleanType(false),
					new ArrayType(new MixedType(true), new MixedType(true)),
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
	 * @dataProvider dataFunctions
	 * @param mixed[] $parameters
	 */
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
		$this->assertCount(1, $signatures);
		$this->assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signatures[0]);
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
		);
	}

	public function dataMethods(): array
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
							new StringType(),
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
				new UnionType([
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
	 * @dataProvider dataMethods
	 * @param mixed[] $parameters
	 */
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
		$this->assertCount(1, $signatures);
		$this->assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signatures[0]);
	}

	/**
	 * @param mixed[] $expectedParameters
	 */
	private function assertSignature(
		array $expectedParameters,
		Type $expectedReturnType,
		Type $expectedNativeReturnType,
		bool $expectedVariadic,
		FunctionSignature $actualSignature,
	): void
	{
		$this->assertCount(count($expectedParameters), $actualSignature->getParameters());
		foreach ($expectedParameters as $i => $expectedParameter) {
			$actualParameter = $actualSignature->getParameters()[$i];
			$this->assertSame($expectedParameter['name'], $actualParameter->getName());
			$this->assertSame($expectedParameter['optional'], $actualParameter->isOptional());
			$this->assertSame($expectedParameter['type']->describe(VerbosityLevel::precise()), $actualParameter->getType()->describe(VerbosityLevel::precise()));
			$this->assertSame($expectedParameter['nativeType']->describe(VerbosityLevel::precise()), $actualParameter->getNativeType()->describe(VerbosityLevel::precise()));
			$this->assertTrue($expectedParameter['passedByReference']->equals($actualParameter->passedByReference()));
			$this->assertSame($expectedParameter['variadic'], $actualParameter->isVariadic());
		}

		$this->assertSame($expectedReturnType->describe(VerbosityLevel::precise()), $actualSignature->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertSame($expectedNativeReturnType->describe(VerbosityLevel::precise()), $actualSignature->getNativeReturnType()->describe(VerbosityLevel::precise()));
		$this->assertSame($expectedVariadic, $actualSignature->isVariadic());
	}

	public function dataParseAll(): array
	{
		$map = new Php8StubsMap(PHP_VERSION_ID);
		return array_map(static fn (string $file): array => [__DIR__ . '/../../../../vendor/phpstan/php-8-stubs/' . $file], array_merge($map->classes, $map->functions));
	}

	/**
	 * @dataProvider dataParseAll
	 */
	public function testParseAll(string $stubFile): void
	{
		$parser = $this->getParser();
		$parser->parseFile($stubFile);
		$this->expectNotToPerformAssertions();
	}

}
