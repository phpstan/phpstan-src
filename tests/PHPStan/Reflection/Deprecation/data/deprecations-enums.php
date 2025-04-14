<?php // lint >= 8.1

namespace CustomDeprecations;

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
