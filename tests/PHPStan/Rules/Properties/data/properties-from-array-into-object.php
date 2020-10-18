<?php declare(strict_types = 1);

namespace PropertiesFromArrayIntoObject;

class Foo
{
	/**
	 * @var string
	 */
	public $foo = '';

	/**
	 * @var float
	 */
	public $float_test = 0.0;

	/**
	 * @var int
	 */
	public $lall = 0;

	/**
	 * @var int|null
	 */
	public $test;

	/**
	 * @phpstan-return array{float_test: float, foo: 'bar', lall: string, noop: int, test: int}
	 */
	public function data(): array
	{
		/** @var mixed $array */
		$array = [];

		return $array;
	}

	public function create_simple_0(): self {
		$self = new self();

		foreach($this->data() as $property => $value) {
			$self->{$property} = $value;
		}

		return $self;
	}

	public function create_simple_1(): self {
		$self = new self();

		$data = $this->data();

		foreach($data as $property => $value) {
			$self->{$property} = $value;
		}

		return $self;
	}

	public function create_complex(): self {
		$self = new self();

		foreach($this->data() as $property => $value) {
			if ($property === 'test') {
				if ($self->{$property} === null) {
					$self->{$property} = new \stdClass();
				}
			} else {
				$self->{$property} = $value;
			}

			if ($property === 'foo') {
				$self->{$property} += 1;
			}
			if ($property === 'foo') {
				$self->{$property} .= ' ';
			}
			if ($property === 'lall') {
				$self->{$property} += 1;
			}
			$tmp = 1.1;
			if ($property === 'foo') {
				$self->{$property} += $tmp;
			}
		}

		return $self;
	}

	public function create_simple_2(): self {
		$self = new self();

		$data = $this->data();

		$property = 'foo';
		foreach($data as $value) {
			$self->{$property} = $value;
		}

		return $self;
	}

	public function create_double_loop(): self {
		$self = new self();

		$data = $this->data();

		foreach($data as $property => $value) {
			foreach([1, 2, 3] as $value_2) {
				$self->{$property} = $value;
			}
		}

		return $self;
	}
}


class FooBar
{
	/**
	 * @var string
	 */
	public $foo = '';

	/**
	 * @var null|\stdClass
	 */
	public $lall;

	public function data(): array
	{
		return ['foo' => 'bar', 'lall' => 'lall', 'noop' => 1];
	}

	public function create(): self {
		$self = new self();

		foreach($this->data() as $property => $value) {
			$this->{$property} = $value;

			if ($property === 'lall') {
				$this->{$property} = null;
			}

			if ($property === 'foo') {
				$this->{$property} = 1.1;
			}
		}

		return $self;
	}
}