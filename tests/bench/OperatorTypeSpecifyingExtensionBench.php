<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Php\BcMathNumberOperatorTypeSpecifyingExtension;
use PHPStan\Type\Php\GmpOperatorTypeSpecifyingExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

/**
 * The operator extensions are asked about each arithmetic and comparison operation in the analysed code.
 */
final class OperatorTypeSpecifyingExtensionBench
{

	private const OPERATIONS = 64;

	private ?OperatorTypeSpecifyingExtension $extension = null;

	/** @var list<array{Type, Type}> */
	private array $operands = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{class-string<OperatorTypeSpecifyingExtension>, string}>
	 */
	public static function extensions(): iterable
	{
		foreach (['gmp' => GmpOperatorTypeSpecifyingExtension::class, 'bcmath' => BcMathNumberOperatorTypeSpecifyingExtension::class] as $name => $extension) {
			// most operations in the code have no operand of the extension's class
			yield $name . '-integers' => [$extension, 'integers'];
			yield $name . '-objects' => [$extension, 'objects'];
		}
	}

	/**
	 * @param class-string<OperatorTypeSpecifyingExtension> $extension
	 */
	#[Before]
	public function boot(string $extension, string $operands): void
	{
		BenchContainer::create($this->scratch);
		$this->extension = $extension === BcMathNumberOperatorTypeSpecifyingExtension::class
			? new BcMathNumberOperatorTypeSpecifyingExtension(new PhpVersion(80400))
			: new $extension();
		$class = $extension === BcMathNumberOperatorTypeSpecifyingExtension::class ? 'BcMath\Number' : 'GMP';
		// reflecting the class from its stub happens once per analysis
		$this->extension->isOperatorSupported('+', new IntegerType(), new StringType());
		$this->extension->isOperatorSupported('+', new ObjectType($class), new IntegerType());
		$this->extension->specifyType('+', new ObjectType($class), new IntegerType());

		for ($i = 0; $i < self::OPERATIONS; $i++) {
			$this->operands[] = $operands === 'integers'
				? [new ConstantIntegerType($i), new IntegerType()]
				: [new ObjectType($class), new ConstantIntegerType($i)];
		}
	}

	/**
	 * @param class-string<OperatorTypeSpecifyingExtension> $extension
	 */
	#[DataProvider(method: 'extensions')]
	#[ExpectedFor(dataSet: 'bcmath-integers', instructions: '309.3K', cycles: '380.8K', memory: '0B')]
	#[ExpectedFor(dataSet: 'bcmath-objects', instructions: '651.3K', cycles: '786.5K', memory: '216B')]
	#[ExpectedFor(dataSet: 'gmp-integers', instructions: '294.4K', cycles: '365.1K', memory: '0B')]
	#[ExpectedFor(dataSet: 'gmp-objects', instructions: '593.5K', cycles: '718.6K', memory: '216B')]
	public function benchSpecify(string $extension, string $operands): int
	{
		if ($this->extension === null) {
			throw new LogicException('boot() did not run.');
		}

		$specified = 0;
		foreach ($this->operands as [$left, $right]) {
			if (!$this->extension->isOperatorSupported('+', $left, $right)) {
				continue;
			}

			$this->extension->specifyType('+', $left, $right);
			$specified++;
		}

		return $specified;
	}

}
