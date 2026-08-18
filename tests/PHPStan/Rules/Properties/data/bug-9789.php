<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug9789;

trait T {
	public function __construct(public readonly string $value) {}
}

class C {

	use T {
		__construct as protected init;
	}

	public function __construct(string $value) {
		$this->init($value);
		if (!$this->isValid()) {
			throw new \Exception();
		}
	}

	private function isValid(): bool {
		return !empty($this->value);
	}
}

class ReadInConstructor
{

	use T {
		__construct as protected init;
	}

	public function __construct(string $value)
	{
		$this->init($value);
		echo $this->value;
	}

}

class ReadBeforeInit
{

	use T {
		__construct as protected init;
	}

	public function __construct(string $value)
	{
		echo $this->value;
		$this->init($value);
	}

}

class ConditionalInit
{

	use T {
		__construct as protected init;
	}

	public function __construct(string $value, bool $condition)
	{
		if ($condition) {
			$this->init($value);
		}
		echo $this->value;
	}

}

class InitNeverCalled
{

	use T {
		__construct as protected init;
	}

	public function __construct(public int $code)
	{
	}

}

class InitOnAnotherObject
{

	use T {
		__construct as public init;
	}

	public function __construct(string $value, self $other)
	{
		$other->init($value);
		echo $this->value;
	}

}

trait NotReadOnlyT {
	public function __construct(public string $value) {}
}

class NotReadOnly
{

	use NotReadOnlyT {
		__construct as protected init;
	}

	public function __construct(string $value)
	{
		$this->init($value);
		echo $this->value;
	}

}

class NotReadOnlyReadBeforeInit
{

	use NotReadOnlyT {
		__construct as protected init;
	}

	public function __construct(string $value)
	{
		echo $this->value;
		$this->init($value);
	}

}
