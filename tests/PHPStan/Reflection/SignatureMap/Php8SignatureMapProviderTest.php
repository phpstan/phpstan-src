<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Php\PhpVersion;
use PHPStan\Php8StubsMap;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Testing\TestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
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

class Php8SignatureMapProviderTest extends TestCase
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
						'name' => 'ch',
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
						new IntegerType(),
						new ArrayType(new IntegerType(), new StringType()),
						new IntegerType(),
						new ArrayType(new IntegerType(), new StringType()),
					]),
				]),
				new UnionType([
					new ConstantBooleanType(false),
					new ArrayType(new MixedType(true), new MixedType(true)),
				]),
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
		bool $variadic
	): void
	{
		$provider = $this->createProvider();
		$signature = $provider->getFunctionSignature($functionName, null);
		$this->assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signature);
	}

	private function createProvider(): Php8SignatureMapProvider
	{
		return new Php8SignatureMapProvider(
			new FunctionSignatureMapProvider(
				self::getContainer()->getByType(SignatureMapParser::class),
				new PhpVersion(80000)
			),
			self::getContainer()->getByType(FileNodesFetcher::class),
			self::getContainer()->getByType(FileTypeMapper::class)
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
						'name' => 'new',
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
						'name' => 'newscope',
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
						'name' => 'cmp_function',
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
				new BooleanType(),
				new MixedType(),
				false,
			],
			[
				'RecursiveArrayIterator',
				'uasort',
				[
					[
						'name' => 'cmp_function',
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
		bool $variadic
	): void
	{
		$provider = $this->createProvider();
		$signature = $provider->getMethodSignature($className, $methodName, null);
		$this->assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signature);
	}

	/**
	 * @param mixed[] $expectedParameters
	 * @param Type $expectedReturnType
	 * @param Type $expectedNativeReturnType
	 * @param bool $expectedVariadic
	 * @param FunctionSignature $actualSignature
	 */
	private function assertSignature(
		array $expectedParameters,
		Type $expectedReturnType,
		Type $expectedNativeReturnType,
		bool $expectedVariadic,
		FunctionSignature $actualSignature
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
		return array_map(static function (string $file): array {
			return [__DIR__ . '/../../../../vendor/phpstan/php-8-stubs/' . $file];
		}, array_merge(Php8StubsMap::CLASSES, Php8StubsMap::FUNCTIONS));
	}

	/**
	 * @dataProvider dataParseAll
	 * @param string $stubFile
	 */
	public function testParseAll(string $stubFile): void
	{
		$parser = $this->getParser();
		$parser->parseFile($stubFile);
		$this->expectNotToPerformAssertions();
	}

}
