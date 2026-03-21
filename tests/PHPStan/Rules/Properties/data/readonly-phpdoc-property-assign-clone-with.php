<?php // lint >= 8.5

namespace ReadonlyPhpDocPropertyAssignCloneWith;

class Foo
{

	/** @readonly */
	private int $priv;

	/** @readonly */
	protected int $prot;

	/** @readonly */
	public int $pub;

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
		'pub' => 1, // reported here - @readonly property assigned outside declaring class
	]);
};
