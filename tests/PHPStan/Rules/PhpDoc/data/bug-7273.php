<?php declare(strict_types=1);

namespace Bug7273;

/**
 * @template I
 * @template O
 */
interface ConfigValueProcessorInterface
{
	/**
	 * @param I $value
	 *
	 * @return O
	 */
	public function process(mixed $value, mixed $options): mixed;

	/**
	 * @param O $value
	 *
	 * @return I
	 */
	public function unprocess(mixed $value, mixed $options): mixed;
}

/**
 * @implements ConfigValueProcessorInterface<string, int>
 */
abstract class SomeValueProcessor implements ConfigValueProcessorInterface
{
}

interface ConfigKey
{
	public const ACTIVITY__EXPORT__TYPE = 'activity.export.type';
	public const ACTIVITY__TAGS__MULTI = 'activity.tags.multi';
	// ...
}

interface Module
{
	public const ABSENCEREQUEST = 'absencerequest';
	public const ACTIVITY = 'activity';
	// ...
}

interface ConfigRepositoryInterface
{
	/**
	 * @var array<ConfigKey::*, array{
	 *     type?: non-empty-string,
	 *     default: mixed,
	 *     acl: non-empty-string[],
	 *     linked_module?: Module::*,
	 *     value_processors?: array<class-string<ConfigValueProcessorInterface<mixed, mixed>>, mixed>,
	 * }>
	 */
	public const CONFIGURATIONS = [
		ConfigKey::ACTIVITY__EXPORT__TYPE => [
			'type' => "'SomeExport'|'SomeOtherExport'|null",
			'default' => null,
			'acl' => ['superadmin'],
			'linked_module' => Module::ACTIVITY,
		],
		ConfigKey::ACTIVITY__TAGS__MULTI => [
			'default' => false,
			'acl' => ['admin'],
			'linked_module' => Module::ACTIVITY,
			'value_processors' => [
				SomeValueProcessor::class => ['someOption' => true],
			],
		],
		// ...
	];
}
