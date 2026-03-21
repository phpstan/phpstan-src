<?php // lint >= 8.5

declare(strict_types = 1);

namespace Bug14063;

final readonly class Obj
{
	public function __construct(public string $value) {}

	public function withValue(string $value): self
	{
		return clone($this, ['value' => $value]); // OK - inside declaring class
	}
}

$obj = new Obj('val');
$newObj = clone($obj, ['value' => 'newVal']);
