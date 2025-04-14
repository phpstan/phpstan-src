<?php // lint >= 8.1

namespace CustomDeprecations;

#[\Attribute(\Attribute::TARGET_ALL)]
class CustomDeprecated {

	public ?string $description;

	public function __construct(
		?string $description = null
	) {
		$this->description = $description;
	}
}

#[CustomDeprecated]
enum MyDeprecatedEnum: string
{
	#[CustomDeprecated('custom')]
	case CustomDeprecated = '1';

	/**
	 * @deprecated phpdoc
	 */
	case PhpDocDeprecated = '2';

	#[\Deprecated('native')]
	case NativeDeprecated = '3';

	case NotDeprecated = '4';

}
