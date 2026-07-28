<?php

namespace IncludeRelativeInTrait;

trait IncludeRelativeTrait
{

	public function relativeInTraitDir(): void
	{
		include 'only-in-trait-dir.php';
	}

	public function relativeInClassDir(): void
	{
		include 'only-in-class-dir.php';
	}

}
