<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class TypeUtilsBench
{

	private ?Type $type = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function members(): iterable
	{
		foreach ([16, 128] as $members) {
			yield 'members-' . $members => [$members];
		}
	}

	#[Before]
	public function boot(int $members): void
	{
		BenchContainer::create($this->scratch);
		TypeUtils::resolveLateResolvableTypes(self::union('warmup', 4));

		$this->type = self::union('member', $members);
	}

	/**
	 * The traverser visits every member of a union that holds a conditional type to resolve it.
	 */
	#[DataProvider(method: 'members')]
	public function benchResolveLateResolvableTypes(int $members): Type
	{
		if ($this->type === null) {
			throw new LogicException('boot() did not run.');
		}

		return TypeUtils::resolveLateResolvableTypes($this->type);
	}

	private static function union(string $prefix, int $members): Type
	{
		$types = [new ConditionalType(new IntegerType(), new IntegerType(), new StringType(), new IntegerType(), false)];
		for ($i = 1; $i < $members; $i++) {
			$types[] = new ConstantStringType($prefix . $i);
		}

		return new UnionType($types);
	}

}
