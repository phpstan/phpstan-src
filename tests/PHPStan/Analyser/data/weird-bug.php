<?php

namespace WeirdBug;

use function PHPStan\Testing\assertType;

class Model {
	/**
	 * @return Builder<static>
	 */
	public static function getBuilder(): Builder
	{
		return new Builder(new static()); // @phpstan-ignore-line
	}
}

class SubModel extends Model {}

/**
 * @template T of Model
 */
class Builder {

	/**
	 * @param T $model
	 */
	public function __construct(private Model $model) // @phpstan-ignore-line
	{
	}

	public function methodWithCallback(\Closure $callback): void
	{
		$callback($this);
	}
}

class AnotherBuilder {
	public function someMethod(): self
	{
		return $this;
	}
}

function test(): void
{
	SubModel::getBuilder()->methodWithCallback(function (Builder $builder, $value) {
		assertType('WeirdBug\Builder<WeirdBug\SubModel>', $builder);
		return $builder->someMethod();
	});
}
