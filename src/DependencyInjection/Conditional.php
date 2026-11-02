<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;
use PHPStan\ShouldNotHappenException;
use function is_array;

/**
 * Registers the class with the wrapped attribute, but adds the tags only when the condition is met:
 * the rule tag for RegisteredRule, the collector tag for RegisteredCollector,
 * and the tags of the implemented extension interfaces for AutowiredService.
 * The service is always registered so that it can still be autowired.
 *
 * The condition has the same syntax as values in the conditionalTags config section:
 * a parameter reference like '%featureToggles.foo%', its negation 'not(%featureToggles.foo%)',
 * or a list of them that all must be met.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * @api
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class Conditional
{

	public AutowiredService|RegisteredCollector|RegisteredRule $attribute;

	/**
	 * On PHP < 8.1, simple-downgrader writes `new` in attribute arguments as the array,
	 * and composer-attribute-collector turns it back into the object in vendor/attributes.php.
	 *
	 * @api
	 * @param AutowiredService|RegisteredCollector|RegisteredRule|array{
	 *     'olvlvl\ComposerAttributeCollector\NewExpression': array{
	 *         class-string<AutowiredService>,
	 *         array{0?: string|null, 1?: string|null, 2?: true|list<class-string>|class-string, 3?: bool}
	 *         |array{name?: string|null, factory?: string|null, as?: true|list<class-string>|class-string, autoTag?: bool},
	 *     }|array{
	 *         class-string<RegisteredCollector>|class-string<RegisteredRule>,
	 *         array{int}|array{level: int},
	 *     },
	 * } $attribute
	 * @param string|non-empty-list<string> $if
	 */
	public function __construct(
		AutowiredService|RegisteredCollector|RegisteredRule|array $attribute,
		public string|array $if,
	)
	{
		if (is_array($attribute)) {
			throw new ShouldNotHappenException('The attribute should have been instantiated in vendor/attributes.php.');
		}

		$this->attribute = $attribute;
	}

}
