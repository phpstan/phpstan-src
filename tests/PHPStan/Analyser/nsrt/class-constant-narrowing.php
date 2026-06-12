<?php // lint >= 8.1

declare(strict_types = 1);

namespace ClassConstantNarrowing;

use function PHPStan\Testing\assertType;

interface Type
{

	public function isSame(self $other): bool;

}

final class SingleType implements Type
{

	public function __construct(public readonly string $name)
	{
	}

	public function isSame(Type $other): bool
	{
		assertType("'ClassConstantNarrowing\\\\SingleType'", $this::class);
		assertType("'ClassConstantNarrowing\\\\SingleType'", static::class);
		assertType('class-string<ClassConstantNarrowing\Type>&literal-string', $other::class);

		if ($this::class !== $other::class) {
			return false;
		}

		assertType("'ClassConstantNarrowing\\\\SingleType'", $this::class);
		assertType("'ClassConstantNarrowing\\\\SingleType'", static::class);
		assertType("'ClassConstantNarrowing\\\\SingleType'", $other::class);

		return $this->name === $other->name;
	}

}

class NonFinal
{

	public function compare(self $other): bool
	{
		assertType('class-string<$this(ClassConstantNarrowing\NonFinal)>&literal-string', $this::class);
		assertType('class-string<static(ClassConstantNarrowing\NonFinal)>', static::class);
		assertType('class-string<ClassConstantNarrowing\NonFinal>&literal-string', $other::class);

		return $this::class === $other::class;
	}

}
