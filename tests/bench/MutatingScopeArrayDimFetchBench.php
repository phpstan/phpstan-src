<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class MutatingScopeArrayDimFetchBench
{

	private ?MutatingScope $scope = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function arrays(): iterable
	{
		// 0 is array<string, int>, otherwise a shape with that many keys
		yield 'general-array' => [0];
		yield 'shape-32' => [32];
	}

	#[Before]
	public function boot(int $keys): void
	{
		$container = BenchContainer::create($this->scratch);
		$scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create($this->scratch->path() . '/file.php'));
		foreach ([0, 4] as $warmupKeys) {
			$warmup = $scope->assignVariable('warmup', self::arrayType($warmupKeys), self::arrayType($warmupKeys), TrinaryLogic::createYes());
			$warmup->specifyExpressionType(self::dimFetch('warmup'), new ConstantIntegerType(1), new IntegerType(), TrinaryLogic::createYes());
		}

		$this->scope = $scope->assignVariable('a', self::arrayType($keys), self::arrayType($keys), TrinaryLogic::createYes());
	}

	/**
	 * Specifying an offset also specifies the array: an array is offset-accessible already.
	 */
	#[DataProvider(method: 'arrays')]
	public function benchSpecifyArrayDimFetchType(int $keys): MutatingScope
	{
		if ($this->scope === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->scope->specifyExpressionType(self::dimFetch('a'), new ConstantIntegerType(1), new IntegerType(), TrinaryLogic::createYes());
	}

	private static function dimFetch(string $variable): ArrayDimFetch
	{
		return new ArrayDimFetch(new Variable($variable), new String_('k0'));
	}

	private static function arrayType(int $keys): Type
	{
		if ($keys === 0) {
			return new ArrayType(new StringType(), new IntegerType());
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		for ($i = 0; $i < $keys; $i++) {
			$builder->setOffsetValueType(new ConstantStringType('k' . $i), new IntegerType());
		}

		return $builder->getArray();
	}

}
