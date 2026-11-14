<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Php\ArrayChunkFunctionReturnTypeExtension;
use PHPStan\Type\Php\ArrayColumnFunctionReturnTypeExtension;
use PHPStan\Type\Php\DateFunctionReturnTypeExtension;
use PHPStan\Type\Php\RangeFunctionReturnTypeExtension;
use PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Attribute\Warmup;
use Tally\ScratchDirectory;
use function sprintf;
use function str_repeat;

/**
 * A dynamic return type extension inferring the calls of its functions in a piece of code.
 */
#[Warmup(calls: 1)]
final class ReturnTypeExtensionBench
{

	private ?DynamicFunctionReturnTypeExtension $extension = null;

	/** @var list<array{FunctionReflection, FuncCall, MutatingScope}> */
	private array $calls = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{class-string<DynamicFunctionReturnTypeExtension>, string}>
	 */
	public static function extensions(): iterable
	{
		// the extensions read the arguments of the call once
		yield 'array-chunk' => [ArrayChunkFunctionReturnTypeExtension::class, self::functionBody('array $a', 'array_chunk($a, 2, true);')];
		yield 'array-column' => [ArrayColumnFunctionReturnTypeExtension::class, self::functionBody('array $a', 'array_column($a, \'id\', \'key\');')];

		// a subject that is not mixed does not need the return type of the selected variant
		yield 'str-replace-string-subject' => [ReplaceFunctionsDynamicReturnTypeExtension::class, self::functionBody('string $s', 'str_replace(\'a\', \'b\', $s);')];
		yield 'preg-replace-string-subject' => [ReplaceFunctionsDynamicReturnTypeExtension::class, self::functionBody('string $s', 'preg_replace(\'/a/\', \'b\', $s);')];

		// a format that is not a constant string is not a union of the types of its constant strings
		yield 'date-non-constant-format' => [DateFunctionReturnTypeExtension::class, self::functionBody('string $format', 'date($format);')];

		// the length of a range longer than the array count limit is computed instead of creating the array
		yield 'range-100000' => [RangeFunctionReturnTypeExtension::class, self::functionBody('', 'range(0, 100000);')];
	}

	/**
	 * @param class-string<DynamicFunctionReturnTypeExtension> $extension
	 */
	#[Before]
	public function boot(string $extension, string $code): void
	{
		$analysed = AnalysedCode::analyse($this->scratch, $code);
		$this->extension = $analysed->get($extension);
		$reflectionProvider = $analysed->getContainer()->getByType(ReflectionProvider::class);
		foreach ($analysed->nodes(FuncCall::class) as [$call, $scope]) {
			if (!$call->name instanceof Name) {
				continue;
			}

			$function = $reflectionProvider->getFunction($call->name, $scope);
			if (!$this->extension->isFunctionSupported($function)) {
				continue;
			}

			$this->calls[] = [$function, $call, $scope];
		}

		if ($this->calls === []) {
			throw new LogicException(sprintf('The code calls no function %s supports.', $extension));
		}
	}

	/**
	 * @param class-string<DynamicFunctionReturnTypeExtension> $extension
	 * @return list<?Type>
	 */
	#[DataProvider(method: 'extensions')]
	#[ExpectedFor(dataSet: 'array-chunk', instructions: '622.4K', cycles: '1.076M', memory: '23.70KiB')]
	#[ExpectedFor(dataSet: 'array-column', instructions: '2.033M', cycles: '3.088M', memory: '10.32KiB')]
	#[ExpectedFor(dataSet: 'date-non-constant-format', instructions: '72.79K', cycles: '163.2K', memory: '1016B')]
	#[ExpectedFor(dataSet: 'preg-replace-string-subject', instructions: '1.029M', cycles: '1.676M', memory: '10.78KiB')]
	#[ExpectedFor(dataSet: 'range-100000', instructions: '526.1K', cycles: '878.5K', memory: '16.39KiB')]
	#[ExpectedFor(dataSet: 'str-replace-string-subject', instructions: '530.5K', cycles: '874.6K', memory: '2.594KiB')]
	public function benchGetTypeFromFunctionCall(string $extension, string $code): array
	{
		if ($this->extension === null) {
			throw new LogicException('boot() did not run.');
		}

		$types = [];
		foreach ($this->calls as [$function, $call, $scope]) {
			$types[] = $this->extension->getTypeFromFunctionCall($function, $call, $scope);
		}

		return $types;
	}

	private static function functionBody(string $parameters, string $statement): string
	{
		return sprintf('<?php function test(%s): void { %s }', $parameters, str_repeat($statement . ' ', 16));
	}

}
