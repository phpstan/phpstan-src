<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class GenericObjectTypeCheckBench
{

	private ?GenericObjectTypeCheck $check = null;

	private ?Type $type = null;

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
		$this->check = BenchContainer::create($this->scratch)->getByType(GenericObjectTypeCheck::class);
		$this->check(self::shape(2));
		$this->type = self::shape($keys);
	}

	/**
	 * A PHPDoc type that references no class cannot contain a generic object type.
	 *
	 * @return list<mixed>
	 */
	#[DataProvider(method: 'keys')]
	#[ExpectedFor(dataSet: 'keys-16', instructions: '46.09K', cycles: '97.47K', memory: '0B')]
	#[ExpectedFor(dataSet: 'keys-64', instructions: '174.5K', cycles: '291.4K', memory: '0B')]
	public function benchCheckTypeWithoutClasses(int $keys): array
	{
		if ($this->type === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->check($this->type);
	}

	/**
	 * @return list<mixed>
	 */
	private function check(Type $type): array
	{
		if ($this->check === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->check->check($type, 'a', 'b', 'c', 'd', 'e', 'f');
	}

	private static function shape(int $keys): ConstantArrayType
	{
		$keyTypes = [];
		$valueTypes = [];
		for ($i = 0; $i < $keys; $i++) {
			$keyTypes[] = new ConstantStringType('key' . $i);
			$valueTypes[] = new UnionType([new IntegerType(), new StringType()]);
		}

		return new ConstantArrayType($keyTypes, $valueTypes);
	}

}
