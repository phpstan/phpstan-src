<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use DateInterval;
use DateTime;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use ReflectionParameter;
use Throwable;
use function array_keys;
use function count;
use function explode;
use function sprintf;
use function strpos;

class SignatureMapParserTest extends PHPStanTestCase
{

	public function dataGetFunctions(): array
	{
		$reflectionProvider = $this->createReflectionProvider();
		return [
			[
				['int', 'fp' => 'resource', 'fields' => 'array', 'delimiter=' => 'string', 'enclosure=' => 'string', 'escape_char=' => 'string'],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'fp',
							false,
							new ResourceType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'fields',
							false,
							new ArrayType(new MixedType(), new MixedType()),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'delimiter',
							true,
							new StringType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'enclosure',
							true,
							new StringType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'escape_char',
							true,
							new StringType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
					],
					new IntegerType(),
					new MixedType(),
					false,
				),
			],
			[
				['bool', 'fp' => 'resource'],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'fp',
							false,
							new ResourceType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
					],
					new BooleanType(),
					new MixedType(),
					false,
				),
			],
			[
				['bool', '&rw_array_arg' => 'array'],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'array_arg',
							false,
							new ArrayType(new MixedType(), new MixedType()),
							new MixedType(),
							PassedByReference::createReadsArgument(),
							false,
						),
					],
					new BooleanType(),
					new MixedType(),
					false,
				),
			],
			[
				['bool', 'csr' => 'string|resource', '&w_out' => 'string', 'notext=' => 'bool'],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'csr',
							false,
							new UnionType([
								new StringType(),
								new ResourceType(),
							]),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'out',
							false,
							new StringType(),
							new MixedType(),
							PassedByReference::createCreatesNewVariable(),
							false,
						),
						new ParameterSignature(
							'notext',
							true,
							new BooleanType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
					],
					new BooleanType(),
					new MixedType(),
					false,
				),
			],
			[
				['(?Throwable)|(?Foo)'],
				null,
				new FunctionSignature(
					[],
					new UnionType([
						new ObjectType(Throwable::class),
						new ObjectType('Foo'),
						new NullType(),
					]),
					new MixedType(),
					false,
				),
			],
			[
				[''],
				null,
				new FunctionSignature(
					[],
					new MixedType(),
					new MixedType(),
					false,
				),
			],
			[
				['array', 'arr1' => 'array', 'arr2' => 'array', '...=' => 'array'],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'arr1',
							false,
							new ArrayType(new MixedType(), new MixedType()),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'arr2',
							false,
							new ArrayType(new MixedType(), new MixedType()),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'...',
							true,
							new ArrayType(new MixedType(), new MixedType()),
							new MixedType(),
							PassedByReference::createNo(),
							true,
						),
					],
					new ArrayType(new MixedType(), new MixedType()),
					new MixedType(),
					true,
				),
			],
			[
				['resource', 'callback' => 'callable', 'event' => 'string', '...' => ''],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'callback',
							false,
							new CallableType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'event',
							false,
							new StringType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'...',
							true,
							new MixedType(),
							new MixedType(),
							PassedByReference::createNo(),
							true,
						),
					],
					new ResourceType(),
					new MixedType(),
					true,
				),
			],
			[
				['string', 'format' => 'string', '...args=' => ''],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'format',
							false,
							new StringType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'args',
							true,
							new MixedType(),
							new MixedType(),
							PassedByReference::createNo(),
							true,
						),
					],
					new StringType(),
					new MixedType(),
					true,
				),
			],
			[
				['string', 'format' => 'string', '...args' => ''],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'format',
							false,
							new StringType(),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
						new ParameterSignature(
							'args',
							true,
							new MixedType(),
							new MixedType(),
							PassedByReference::createNo(),
							true,
						),
					],
					new StringType(),
					new MixedType(),
					true,
				),
			],
			[
				['array<int,ReflectionParameter>'],
				null,
				new FunctionSignature(
					[],
					new ArrayType(new IntegerType(), new ObjectType(ReflectionParameter::class)),
					new MixedType(),
					false,
				),
			],
			[
				['static', 'interval' => 'DateInterval'],
				DateTime::class,
				new FunctionSignature(
					[
						new ParameterSignature(
							'interval',
							false,
							new ObjectType(DateInterval::class),
							new MixedType(),
							PassedByReference::createNo(),
							false,
						),
					],
					new StaticType($reflectionProvider->getClass(DateTime::class)),
					new MixedType(),
					false,
				),
			],
			[
				['bool', '&rw_string' => 'string', '&...rw_strings=' => 'string'],
				null,
				new FunctionSignature(
					[
						new ParameterSignature(
							'string',
							false,
							new StringType(),
							new MixedType(),
							PassedByReference::createReadsArgument(),
							false,
						),
						new ParameterSignature(
							'strings',
							true,
							new StringType(),
							new MixedType(),
							PassedByReference::createReadsArgument(),
							true,
						),
					],
					new BooleanType(),
					new MixedType(),
					true,
				),
			],
		];
	}

	/**
	 * @dataProvider dataGetFunctions
	 * @param mixed[] $map
	 */
	public function testGetFunctions(
		array $map,
		?string $className,
		FunctionSignature $expectedSignature,
	): void
	{
		/** @var SignatureMapParser $parser */
		$parser = self::getContainer()->getByType(SignatureMapParser::class);
		$functionSignature = $parser->getFunctionSignature($map, $className);
		$this->assertCount(
			count($expectedSignature->getParameters()),
			$functionSignature->getParameters(),
			'Number of parameters does not match.',
		);

		foreach ($functionSignature->getParameters() as $i => $parameterSignature) {
			$expectedParameterSignature = $expectedSignature->getParameters()[$i];
			$this->assertSame(
				$expectedParameterSignature->getName(),
				$parameterSignature->getName(),
				sprintf('Name of parameter #%d does not match.', $i),
			);
			$this->assertSame(
				$expectedParameterSignature->isOptional(),
				$parameterSignature->isOptional(),
				sprintf('Optionality of parameter $%s does not match.', $parameterSignature->getName()),
			);
			$this->assertSame(
				$expectedParameterSignature->getType()->describe(VerbosityLevel::precise()),
				$parameterSignature->getType()->describe(VerbosityLevel::precise()),
				sprintf('Type of parameter $%s does not match.', $parameterSignature->getName()),
			);
			$this->assertTrue(
				$expectedParameterSignature->passedByReference()->equals($parameterSignature->passedByReference()),
				sprintf('Passed-by-reference of parameter $%s does not match.', $parameterSignature->getName()),
			);
			$this->assertSame(
				$expectedParameterSignature->isVariadic(),
				$parameterSignature->isVariadic(),
				sprintf('Variadicity of parameter $%s does not match.', $parameterSignature->getName()),
			);
		}

		$this->assertSame(
			$expectedSignature->getReturnType()->describe(VerbosityLevel::precise()),
			$functionSignature->getReturnType()->describe(VerbosityLevel::precise()),
			'Return type does not match.',
		);
		$this->assertSame(
			$expectedSignature->isVariadic(),
			$functionSignature->isVariadic(),
			'Variadicity does not match.',
		);
	}

	public function dataParseAll(): array
	{
		return [
			[70400],
			[80000],
			[80100],
		];
	}

	/**
	 * @dataProvider dataParseAll
	 */
	public function testParseAll(int $phpVersionId): void
	{
		$parser = self::getContainer()->getByType(SignatureMapParser::class);
		$provider = new FunctionSignatureMapProvider($parser, new PhpVersion($phpVersionId));
		$signatureMap = $provider->getSignatureMap();

		$count = 0;
		foreach (array_keys($signatureMap) as $functionName) {
			$className = null;
			if (strpos($functionName, '::') !== false) {
				$parts = explode('::', $functionName);
				$className = $parts[0];
			}

			try {
				$signature = $provider->getFunctionSignature($functionName, $className);
				$count++;
			} catch (ParserException $e) {
				$this->fail(sprintf('Could not parse %s: %s.', $functionName, $e->getMessage()));
			}

			self::assertNotInstanceOf(ErrorType::class, $signature->getReturnType(), $functionName);
			$optionalOcurred = false;
			foreach ($signature->getParameters() as $parameter) {
				if ($parameter->isOptional()) {
					$optionalOcurred = true;
				} elseif ($optionalOcurred) {
					$this->fail(sprintf('%s contains required parameter after optional.', $functionName));
				}
				self::assertNotInstanceOf(ErrorType::class, $parameter->getType(), sprintf('%s (parameter %s)', $functionName, $parameter->getName()));
			}
		}

		$this->assertGreaterThan(0, $count);
	}

}
