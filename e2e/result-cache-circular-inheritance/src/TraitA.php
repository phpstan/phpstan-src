<?php declare(strict_types = 1);

namespace ResultCacheE2ECircularInheritance;

trait TraitA
{

	use TraitB;

}
