<?php

namespace Bug15015;

trait Bug15015Trait
{

	public function magicDirInTraitDir(): void
	{
		include __DIR__ . '/only-in-trait-dir.php';
	}

	public function magicDirInClassDir(): void
	{
		include __DIR__ . '/only-in-class-dir.php';
	}

}
