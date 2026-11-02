<?php declare(strict_types = 1);

namespace AttributeServicesFixtures\AutowiredExtensions;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Rules\Rule;

#[AutowiredService]
final class BadAutowiredExtensionsService
{

	/**
	 * @param ExtensionsCollection<Rule> $rules
	 */
	public function __construct(
		#[AutowiredExtensions(of: Rule::class)]
		private ExtensionsCollection $rules,
	)
	{
	}

}
