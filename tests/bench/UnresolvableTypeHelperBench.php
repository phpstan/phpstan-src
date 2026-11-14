<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Closure;
use LogicException;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use ReflectionClass;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class UnresolvableTypeHelperBench
{

	private ?Type $type = null;

	/** @var (Closure(Type): mixed)|null getUnresolvableType(), named containsUnresolvableType() before 2.2.10 */
	private ?Closure $find = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function keys(): iterable
	{
		foreach ([16, 64] as $keys) {
			yield 'keys-' . $keys => [$keys];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		$helper = BenchContainer::create($this->scratch)->getByType(UnresolvableTypeHelper::class);
		$class = new ReflectionClass($helper);
		$method = $class->hasMethod('getUnresolvableType') ? 'getUnresolvableType' : 'containsUnresolvableType';
		$this->find = $class->getMethod($method)->getClosure($helper);
		$this->find(self::shape(2));
		$this->type = self::shape($keys);
	}

	/**
	 * An array shape whose first value is unresolvable and whose other values are array shapes:
	 * once the first value is found, the rest of the type is not traversed.
	 */
	#[DataProvider(method: 'keys')]
	public function benchUnresolvableFirst(int $keys): bool
	{
		if ($this->type === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->find($this->type);
	}

	private function find(Type $type): bool
	{
		if ($this->find === null) {
			throw new LogicException('boot() did not run.');
		}

		$result = ($this->find)($type);

		return $result !== null && $result !== false;
	}

	private static function shape(int $keys): ConstantArrayType
	{
		$innerKeys = [];
		$innerValues = [];
		for ($i = 0; $i < 16; $i++) {
			$innerKeys[] = new ConstantStringType('inner' . $i);
			$innerValues[] = new UnionType([new IntegerType(), new StringType()]);
		}

		$outerKeys = [];
		$outerValues = [];
		for ($i = 0; $i < $keys; $i++) {
			$outerKeys[] = new ConstantStringType('key' . $i);
			$outerValues[] = $i === 0 ? new ErrorType() : new ConstantArrayType($innerKeys, $innerValues);
		}

		return new ConstantArrayType($outerKeys, $outerValues);
	}

}
