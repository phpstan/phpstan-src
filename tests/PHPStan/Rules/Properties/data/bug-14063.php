<?php // lint >= 8.5

declare(strict_types = 1);

namespace Bug14063;

final readonly class Obj
{
	public function __construct(public string $value) {}

	public function withValue(string $newValue): self
	{
		return clone($this, ['value' => $newValue]);
	}
}

readonly class Base
{
	public function __construct(public string $value) {}
}

readonly class Child extends Base
{
	public function withValue(string $newValue): self
	{
		return clone($this, ['value' => $newValue]);
	}
}

$obj = new Obj('val');
$newObj = clone($obj, ['value' => 'newVal']);

function test(Obj $obj): void {
	clone($obj, ['value' => 'newVal']);
}

function testBase(Base $base): void {
	clone($base, ['value' => 'newVal']);
}
