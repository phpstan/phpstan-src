<?php declare(strict_types = 1);

namespace AnonymousClassInTraitInternalTag;

trait TraitWithAnonymousClass
{

	public function createAnonymousClass(): object
	{
		$anonymous = new class {

			/** @internal */
			public ?string $property = null;

			/** @internal */
			public static ?string $staticProperty = null;

			/** @internal */
			public const CONSTANT = 'foo';

			/** @internal */
			public function method(): void
			{
			}

			/** @internal */
			public static function staticMethod(): void
			{
			}

		};

		echo $anonymous->property;
		echo $anonymous::$staticProperty;
		echo $anonymous::CONSTANT;
		$anonymous->method();
		$anonymous::staticMethod();

		return $anonymous;
	}

}
