<?php declare(strict_types = 1);

namespace AnonymousClassInternalTag;

trait AnonymousClassTrait
{

	/** @internal */
	private ?string $traitProperty = null;

	/** @internal */
	private static ?string $traitStaticProperty = null;

	/** @internal */
	private const TRAIT_CONSTANT = 'foo';

	/** @internal */
	private function traitMethod(): void
	{
	}

	/** @internal */
	private static function traitStaticMethod(): void
	{
	}

	public function useTraitMembers(): void
	{
		echo $this->traitProperty;
		echo self::$traitStaticProperty;
		echo self::TRAIT_CONSTANT;
		$this->traitMethod();
		self::traitStaticMethod();
	}

}

class Foo
{

	public function createAnonymousClass(): object
	{
		$anonymous = new class {

			use AnonymousClassTrait;

			/** @internal */
			public ?string $property = null;

			/** @internal */
			public static ?string $staticProperty = null;

			/** @internal */
			public const CONSTANT = 'foo';

			/** @internal */
			public function method(): void
			{
				echo $this->property;
				echo self::$staticProperty;
				echo self::CONSTANT;
				$this->method();
				self::staticMethod();
			}

			/** @internal */
			public static function staticMethod(): void
			{
			}

			public function methodWithClosures(): void
			{
				$closure = function (): void {
					echo $this->property;
					echo self::$staticProperty;
					echo self::CONSTANT;
					$this->method();
					self::staticMethod();
				};
				$closure();

				$arrow = fn (): ?string => $this->property;
				$arrow();
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
