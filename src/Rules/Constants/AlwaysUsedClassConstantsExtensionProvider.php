<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

interface AlwaysUsedClassConstantsExtensionProvider
{

	public const EXTENSION_TAG = 'phpstan.constants.alwaysUsedClassConstantsExtension';

	/**
	 * @return AlwaysUsedClassConstantsExtension[]
	 */
	public function getExtensions(): array;

}
