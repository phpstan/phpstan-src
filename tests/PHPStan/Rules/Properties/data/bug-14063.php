<?php // lint >= 8.5

declare(strict_types = 1);

namespace Bug14063;

final readonly class Obj
{
	public function __construct(public string $value) {}

	public function doFoo(): void
	{
		clone($this, ['value' => 'newVal']);
	}
}

class Bar
{
	public readonly string $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	public function doFoo(): void
	{
		clone($this, ['value' => 'newVal']);
	}
}

readonly class Baz
{
	public function __construct(
		public string $pub,
		protected string $prot,
		private string $priv,
	) {}

	public function doFoo(): void
	{
		clone($this, [
			'pub' => 'newVal',
			'prot' => 'newVal',
			'priv' => 'newVal',
		]);
	}
}

// non-readonly class with promoted public readonly property
final class Qux
{
	public function __construct(public readonly string $value) {}

	public function doFoo(): void
	{
		clone($this, ['value' => 'newVal']);
	}
}

$obj = new Obj('val');
$newObj = clone($obj, ['value' => 'newVal']);

$qux = new Qux('val');
$newQux = clone($qux, ['value' => 'newVal']);

$bar = new Bar('val');
$newBar = clone($bar, ['value' => 'newVal']);

function (Baz $baz): void {
	clone($baz, [
		'pub' => 'newVal',
		'prot' => 'newVal',
		'priv' => 'newVal',
	]);
};
