<?php

namespace ExportedPhpDocNamespaceUses;

use ExportedPhpDocNamespaceUses\Models\ModelOne;
use ExportedPhpDocNamespaceUses\Models\ModelTwo;
use const ExportedPhpDocNamespaceUses\Models\SOME_CONSTANT;

/**
 * @template T
 */
class Foo
{

	/** @var ModelOne */
	public $one;

	/** @return ModelOne */
	public function one()
	{
	}

	/**
	 * @param ModelTwo $two
	 * @return ModelTwo
	 */
	public function two($two)
	{
	}

}

/** @return ModelOne */
function one()
{
}
