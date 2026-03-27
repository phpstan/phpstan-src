<?php // lint >= 8.0

namespace Bug13474;

/**
 * @template TValue of mixed
 */
interface ModelInterface
{
	/**
	 * @return TValue
	 */
	public function getValue(): mixed;
}

/**
 * @implements ModelInterface<int>
 */
class ModelA implements ModelInterface
{
	#[\Override]
	public function getValue(): int
	{
		return 0;
	}
}

/**
 * @implements ModelInterface<string>
 */
class ModelB implements ModelInterface
{
	#[\Override]
	public function getValue(): string
	{
		return 'foo';
	}
}

/**
 * @template T of ModelInterface
 */
trait ModelTrait
{
	/**
	 * @return T
	 */
	abstract function model(): ModelInterface;

	/**
	 * @return template-type<T, ModelInterface, 'TValue'>
	 */
	public function getValue(): mixed
	{
		return $this->model()->getValue();
	}

	public function test(): void
	{
		if (is_string($this->getValue())) {
			echo 'string';
			return;
		}

		echo 'other';
	}
}

class TestA
{
	/** @use ModelTrait<ModelA> */
	use ModelTrait;

	#[\Override]
	function model(): ModelA
	{
		return new ModelA();
	}
}

class TestB
{
	/** @use ModelTrait<ModelB> */
	use ModelTrait;

	#[\Override]
	function model(): ModelB
	{
		return new ModelB();
	}
}
