<?php // lint >= 8.2

declare(strict_types=1);

namespace Bug11776;

/**
 * @template TOperation of int|string
 */
interface EnumAsFilterInterface {}

/**
 * @template TOperation of scalar
 */
final readonly class ScalarableChoice
{
	/**
	 * @param class-string<EnumAsFilterInterface<(int|string)&TOperation>> $choiceClassName
	 */
	public function __construct(private string $choiceClassName) {}

	/**
	 * @return class-string<EnumAsFilterInterface<(int|string)&TOperation>>
	 */
	public function getChoiceClassName(): string
	{
		return $this->choiceClassName;
	}
}
