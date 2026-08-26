<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Marks an extension interface and associates it with its service tag.
 *
 * Services registered with #[AutowiredService] that implement the interface
 * get the tag automatically. ValidateServiceTagsExtension checks that manually
 * tagged services implement the associated interface.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * Extensions distributed outside phpstan-src list the directories to look for
 * this attribute in through the `autowiredServiceDirectories` parameter.
 *
 * @api
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ExtensionInterface
{

	public function __construct(public string $tag)
	{
	}

}
