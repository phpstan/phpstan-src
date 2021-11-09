<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use FilesystemIterator;
use PDO;
use PHPStan\Php\PhpVersion;
use PHPStan\Php8StubsMap;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FloatType;
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
use function sprintf;

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
						'defaultValue' => new NullType(),
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
						'defaultValue' => null,
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
						'defaultValue' => null,
					],
				],
				new MixedType(true),
				new MixedType(true),
				false,
			],
			[
				'microtime',
				[
					[
						'name' => 'as_float',
						'optional' => true,
						'type' => new BooleanType(),
						'nativeType' => new BooleanType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => new ConstantBooleanType(false),
					],
				],
				new UnionType([new StringType(), new FloatType()]),
				new UnionType([new StringType(), new FloatType()]),
				false,
			],
			[
				'session_start',
				[
					[
						'name' => 'options',
						'optional' => true,
						'type' => new ArrayType(new MixedType(), new MixedType()),
						'nativeType' => new ArrayType(new MixedType(), new MixedType()),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => new ConstantArrayType([], []),
					],
				],
				new BooleanType(),
				new BooleanType(),
				false,
			],
			[
				'cal_info',
				[
					[
						'name' => 'calendar',
						'optional' => true,
						'type' => new IntegerType(),
						'nativeType' => new IntegerType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => new ConstantIntegerType(-1),
					],
				],
				new ArrayType(new MixedType(), new MixedType()),
				new ArrayType(new MixedType(), new MixedType()),
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
		$signature = $provider->getFunctionSignature($functionName, null);
		$this->assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signature);
	}

	private function createProvider(): Php8SignatureMapProvider
	{
		return new Php8SignatureMapProvider(
			new FunctionSignatureMapProvider(
				self::getContainer()->getByType(SignatureMapParser::class),
				new PhpVersion(80000),
			),
			self::getContainer()->getByType(FileNodesFetcher::class),
			self::getContainer()->getByType(FileTypeMapper::class),
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
						'defaultValue' => null,
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
						'defaultValue' => new ConstantStringType('static'),
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
						'defaultValue' => null,
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
						'defaultValue' => null,
					],
				],
				new VoidType(),
				new MixedType(), // todo - because uasort is not found in file with RecursiveArrayIterator
				false,
			],
			[
				'mysqli',
				'store_result',
				[
					[
						'name' => 'mode',
						'optional' => true,
						'type' => new IntegerType(),
						'nativeType' => new IntegerType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => new ConstantIntegerType(0),
					],
				],
				new UnionType([new ObjectType('mysqli_result'), new ConstantBooleanType(false)]),
				new MixedType(),
				false,
			],
			[
				'RecursiveDirectoryIterator',
				'__construct',
				[
					[
						'name' => 'directory',
						'optional' => false,
						'type' => new StringType(),
						'nativeType' => new StringType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => null,
					],
					[
						'name' => 'flags',
						'optional' => true,
						'type' => new IntegerType(),
						'nativeType' => new IntegerType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => new ConstantIntegerType(FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO),
					],
				],
				new VoidType(),
				new MixedType(),
				false,
			],
			[
				'PDO',
				'quote',
				[
					[
						'name' => 'string',
						'optional' => false,
						'type' => new StringType(),
						'nativeType' => new StringType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => null,
					],
					[
						'name' => 'type',
						'optional' => true,
						'type' => new IntegerType(),
						'nativeType' => new IntegerType(),
						'passedByReference' => PassedByReference::createNo(),
						'variadic' => false,
						'defaultValue' => new ConstantIntegerType(PDO::PARAM_STR),
					],
				],
				new StringType(),
				new MixedType(),
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
		$signature = $provider->getMethodSignature($className, $methodName, null);
		$this->assertSignature($parameters, $returnType, $nativeReturnType, $variadic, $signature);
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

			if ($expectedParameter['defaultValue'] !== null) {
				$this->assertInstanceOf(
					Type::class,
					$actualParameter->getDefaultValue(),
					sprintf("Mismatch for parameter '%s'.", $actualParameter->getName()),
				);
				$this->assertSame($expectedParameter['defaultValue']->describe(VerbosityLevel::precise()), $actualParameter->getDefaultValue()->describe(VerbosityLevel::precise()));
			} else {
				$this->assertNull($actualParameter->getDefaultValue());
			}
		}

		$this->assertSame($expectedReturnType->describe(VerbosityLevel::precise()), $actualSignature->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertSame($expectedNativeReturnType->describe(VerbosityLevel::precise()), $actualSignature->getNativeReturnType()->describe(VerbosityLevel::precise()));
		$this->assertSame($expectedVariadic, $actualSignature->isVariadic());
	}

	public function dataParseAll(): array
	{
		return array_map(static fn (string $file): array => [__DIR__ . '/../../../../vendor/phpstan/php-8-stubs/' . $file], array_merge(Php8StubsMap::CLASSES, Php8StubsMap::FUNCTIONS));
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
