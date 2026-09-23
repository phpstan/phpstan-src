<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15292;

class BigContainer
{

}

enum Modality: string
{

	case First = 'first';

	public const ALLOWED = [
		'Bug15292\BigContainer',
		'enum_constant_ident',
	];

}

final class FooLeaking
{

	private const ALLOWED_MODALITIES = [
		'Bug15292\BigContainer',
		'arbitrary_ident',
	];

	/** @var list<string> */
	private array $allowedProperty = [
		'Bug15292\BigContainer',
		'property_ident',
	];

	public function isAllowed(string $item): bool
	{
		return in_array($item, self::ALLOWED_MODALITIES, true)
			|| in_array($item, $this->allowedProperty, true)
			|| in_array($item, Modality::ALLOWED, true)
			|| in_array('Bug15292\BigContainer::string_ident', [$item], true);
	}

}
