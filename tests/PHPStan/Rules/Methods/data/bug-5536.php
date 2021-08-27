<?php

namespace Bug5536;

/**
 * @mixin Builder<static>
 */
class Model
{
	/**
	 * @param array<int, mixed>  $args
	 */
	public function __call(string $method, array $args)
	{
		return $this->$method;
	}

	/**
	 * @param array<int, mixed>  $args
	 */
	public static function __callStatic(string $method, array $args)
	{
		return (new static)->$method(...$args);
	}
}

/**
 * @template TModel of Model
 */
class Builder
{
	/**
	 * @return array<int, TModel>
	 */
	public function all(): array
	{
		return [];
	}
}

class User extends Model {}

function (): void {
	User::all();
	$user = new User();
	$user->all();
};
