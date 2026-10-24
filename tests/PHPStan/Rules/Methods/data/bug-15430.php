<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15430Rule;


/** @template T */
final class Box
{
	/** @param T $value */
	public function __construct(private mixed $value)
	{
	}
	
	/** @return T */
	public function get(): mixed
	{
		  return $this->value;
	}
	
	/** @param T $value */
	public function set(mixed $value): void
	{
		  $this->value = $value;
	}
}

/** @param callable(): void $callback */
function repeat(int $times, callable $callback): void
{
	for ($i = 0; $i < $times; $i++) {
		$callback();
	}
}

function test(): void
{
	$count = new Box(0);
	
	repeat(5, function () use ($count): void {
		  $count->set($count->get() + 1);
	});
	
	
	if ($count->get() === 5) {
		  echo 'reached at runtime';
	}
}
