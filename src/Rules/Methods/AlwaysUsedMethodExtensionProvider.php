<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

interface AlwaysUsedMethodExtensionProvider
{

	public const EXTENSION_TAG = 'phpstan.methods.alwaysUsedMethodExtension';

	/**
	 * @return AlwaysUsedMethodExtension[]
	 */
	public function getExtensions(): array;

}
