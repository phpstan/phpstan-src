<?php // lint >= 8.1

namespace Bug7198;

trait TestTrait {
	public function foo(): void
	{
		$this->callee->foo();
	}
}

class TestCallee {
	public function foo(): void
	{
		echo "FOO\n";
	}
}

class TestCaller {
	use TestTrait;

	public function __construct(private readonly TestCallee $callee)
	{
		$this->foo();
	}
}

class TestCaller2 {
	public function foo(): void
	{
		$this->callee->foo();
	}

	public function __construct(private readonly TestCallee $callee)
	{
		$this->foo();
	}
}

class TestCaller3 {

	public function __construct(private readonly TestCallee $callee)
	{
		$this->foo();
	}

	public function foo(): void
	{
		$this->callee->foo();
	}
}

trait Identifiable
{
	public readonly int $id;

	public function __construct()
	{
		$this->id = rand();
	}
}

trait CreateAware
{
	public readonly \DateTimeImmutable $createdAt;

	public function __construct()
	{
		$this->createdAt = new \DateTimeImmutable();
	}
}

abstract class Entity
{
	use Identifiable {
		Identifiable::__construct as private __identifiableConstruct;
	}

	use CreateAware {
		CreateAware::__construct as private __createAwareConstruct;
	}

	public function __construct()
	{
		$this->__identifiableConstruct();
		$this->__createAwareConstruct();
	}
}
