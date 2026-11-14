<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FiniteTypeSet;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

final class FiniteTypeSetBench
{

	private const TYPES = 64;

	/** @var list<Type> */
	private array $types = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		$enum = BenchEnumFile::create($this->scratch, 'Cases' . self::TYPES, self::TYPES);
		$this->types = [new NullType()];
		foreach (BenchEnumFile::cases($enum, self::TYPES) as $i => $case) {
			$this->types[] = new ConstantStringType('value' . $i);
			$this->types[] = new ConstantIntegerType($i);
			$this->types[] = $case;
		}
		foreach ($this->types as $type) {
			FiniteTypeSet::key($type);
		}
	}

	/**
	 * Every value a union is compared with is keyed.
	 */
	public function benchKey(): ?string
	{
		$key = null;
		foreach ($this->types as $type) {
			$key = FiniteTypeSet::key($type);
		}

		return $key;
	}

}
