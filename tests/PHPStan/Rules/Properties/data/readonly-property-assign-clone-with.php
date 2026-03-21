<?php // lint >= 8.5

namespace ReadonlyPropertyAssignCloneWith;

class Foo
{

	private readonly int $priv;

	protected readonly int $prot;

	public readonly int $pub;

	public public(set) readonly int $pubSet;

	public function doFoo(): void
	{
		clone ($this, [
			'priv' => 1,
			'prot' => 1,
			'pub' => 1,
		]);
	}

}

function (Foo $foo): void {
	clone ($foo, [
		'priv' => 1, // reported in AccessPropertiesInAssignRule
		'prot' => 1, // reported in AccessPropertiesInAssignRule
		'pub' => 1, // reported in AccessPropertiesInAssignRule
		'pubSet' => 1, // reported here - readonly property assigned outside declaring class
	]);
};

final readonly class Bar
{
	public function __construct(public string $value) {}

	public function withValue(string $value): self
	{
		return clone($this, ['value' => $value]); // OK - inside declaring class
	}
}

function (Bar $bar): void {
	clone ($bar, [
		'value' => 'newVal', // reported in AccessPropertiesInAssignRule (protected(set)) - and also here
	]);
};
