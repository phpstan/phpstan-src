<?php // lint >= 8.0
declare(strict_types = 1);
namespace Bug11067;

interface BuilderInterface
{
	public function __construct(string $field);
}

class BaseBuilder implements BuilderInterface
{
	public function __construct(
		protected string $field,
		bool $checkType = true,
	) {
		var_dump($field, $checkType);
	}
}

class BooleanBuilder extends BaseBuilder
{
	public function __construct(string $field)
	{
		parent::__construct($field, false);

	}
}

class BaseBuilder2 implements BuilderInterface
{
	final public function __construct(
		protected string $field,
		bool $checkType = true,
	) {
		var_dump($field, $checkType);
	}
}

class BooleanBuilder2 extends BaseBuilder2
{
	public function __construct(string $field)
	{
		parent::__construct($field, false);

	}
}
