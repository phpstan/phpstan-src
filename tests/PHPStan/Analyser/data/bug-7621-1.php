<?php declare(strict_types = 1);

namespace Bug7621One;

use function PHPStan\Testing\assertType;

class Foo
{

	private const GROUP_PUBLIC_CONSTANTS = 'public constants';
	private const GROUP_PROTECTED_CONSTANTS = 'protected constants';
	private const GROUP_PRIVATE_CONSTANTS = 'private constants';
	private const GROUP_PUBLIC_PROPERTIES = 'public properties';
	private const GROUP_PUBLIC_STATIC_PROPERTIES = 'public static properties';
	private const GROUP_PROTECTED_PROPERTIES = 'protected properties';
	private const GROUP_PROTECTED_STATIC_PROPERTIES = 'protected static properties';
	private const GROUP_PRIVATE_PROPERTIES = 'private properties';
	private const GROUP_PRIVATE_STATIC_PROPERTIES = 'private static properties';
	private const GROUP_CONSTRUCTOR = 'constructor';
	private const GROUP_STATIC_CONSTRUCTORS = 'static constructors';
	private const GROUP_DESTRUCTOR = 'destructor';
	private const GROUP_MAGIC_METHODS = 'magic methods';
	private const GROUP_PUBLIC_METHODS = 'public methods';
	private const GROUP_PUBLIC_ABSTRACT_METHODS = 'public abstract methods';
	private const GROUP_PUBLIC_FINAL_METHODS = 'public final methods';
	private const GROUP_PUBLIC_STATIC_METHODS = 'public static methods';
	private const GROUP_PUBLIC_STATIC_ABSTRACT_METHODS = 'public static abstract methods';
	private const GROUP_PUBLIC_STATIC_FINAL_METHODS = 'public static final methods';
	private const GROUP_PROTECTED_METHODS = 'protected methods';
	private const GROUP_PROTECTED_ABSTRACT_METHODS = 'protected abstract methods';
	private const GROUP_PROTECTED_FINAL_METHODS = 'protected final methods';
	private const GROUP_PROTECTED_STATIC_METHODS = 'protected static methods';
	private const GROUP_PROTECTED_STATIC_ABSTRACT_METHODS = 'protected static abstract methods';
	private const GROUP_PROTECTED_STATIC_FINAL_METHODS = 'protected static final methods';
	private const GROUP_PRIVATE_METHODS = 'private methods';
	private const GROUP_PRIVATE_STATIC_METHODS = 'private static methods';

	private const GROUP_SHORTCUT_CONSTANTS = 'constants';
	private const GROUP_SHORTCUT_PROPERTIES = 'properties';
	private const GROUP_SHORTCUT_STATIC_PROPERTIES = 'static properties';
	private const GROUP_SHORTCUT_METHODS = 'methods';
	private const GROUP_SHORTCUT_PUBLIC_METHODS = 'all public methods';
	private const GROUP_SHORTCUT_PROTECTED_METHODS = 'all protected methods';
	private const GROUP_SHORTCUT_PRIVATE_METHODS = 'all private methods';
	private const GROUP_SHORTCUT_STATIC_METHODS = 'static methods';
	private const GROUP_SHORTCUT_ABSTRACT_METHODS = 'abstract methods';
	private const GROUP_SHORTCUT_FINAL_METHODS = 'final methods';

	private const SHORTCUTS = [
		self::GROUP_SHORTCUT_CONSTANTS => [
			self::GROUP_PUBLIC_CONSTANTS,
			self::GROUP_PROTECTED_CONSTANTS,
			self::GROUP_PRIVATE_CONSTANTS,
		],
		self::GROUP_SHORTCUT_STATIC_PROPERTIES => [
			self::GROUP_PUBLIC_STATIC_PROPERTIES,
			self::GROUP_PROTECTED_STATIC_PROPERTIES,
			self::GROUP_PRIVATE_STATIC_PROPERTIES,
		],
		self::GROUP_SHORTCUT_PROPERTIES => [
			self::GROUP_SHORTCUT_STATIC_PROPERTIES,
			self::GROUP_PUBLIC_PROPERTIES,
			self::GROUP_PROTECTED_PROPERTIES,
			self::GROUP_PRIVATE_PROPERTIES,
		],
		self::GROUP_SHORTCUT_PUBLIC_METHODS => [
			self::GROUP_PUBLIC_FINAL_METHODS,
			self::GROUP_PUBLIC_STATIC_FINAL_METHODS,
			self::GROUP_PUBLIC_ABSTRACT_METHODS,
			self::GROUP_PUBLIC_STATIC_ABSTRACT_METHODS,
			self::GROUP_PUBLIC_STATIC_METHODS,
			self::GROUP_PUBLIC_METHODS,
		],
		self::GROUP_SHORTCUT_PROTECTED_METHODS => [
			self::GROUP_PROTECTED_FINAL_METHODS,
			self::GROUP_PROTECTED_STATIC_FINAL_METHODS,
			self::GROUP_PROTECTED_ABSTRACT_METHODS,
			self::GROUP_PROTECTED_STATIC_ABSTRACT_METHODS,
			self::GROUP_PROTECTED_STATIC_METHODS,
			self::GROUP_PROTECTED_METHODS,
		],
		self::GROUP_SHORTCUT_PRIVATE_METHODS => [
			self::GROUP_PRIVATE_STATIC_METHODS,
			self::GROUP_PRIVATE_METHODS,
		],
		self::GROUP_SHORTCUT_FINAL_METHODS => [
			self::GROUP_PUBLIC_FINAL_METHODS,
			self::GROUP_PROTECTED_FINAL_METHODS,
			self::GROUP_PUBLIC_STATIC_FINAL_METHODS,
			self::GROUP_PROTECTED_STATIC_FINAL_METHODS,
		],
		self::GROUP_SHORTCUT_ABSTRACT_METHODS => [
			self::GROUP_PUBLIC_ABSTRACT_METHODS,
			self::GROUP_PROTECTED_ABSTRACT_METHODS,
			self::GROUP_PUBLIC_STATIC_ABSTRACT_METHODS,
			self::GROUP_PROTECTED_STATIC_ABSTRACT_METHODS,
		],
		self::GROUP_SHORTCUT_STATIC_METHODS => [
			self::GROUP_STATIC_CONSTRUCTORS,
			self::GROUP_PUBLIC_STATIC_FINAL_METHODS,
			self::GROUP_PROTECTED_STATIC_FINAL_METHODS,
			self::GROUP_PUBLIC_STATIC_ABSTRACT_METHODS,
			self::GROUP_PROTECTED_STATIC_ABSTRACT_METHODS,
			self::GROUP_PUBLIC_STATIC_METHODS,
			self::GROUP_PROTECTED_STATIC_METHODS,
			self::GROUP_PRIVATE_STATIC_METHODS,
		],
		self::GROUP_SHORTCUT_METHODS => [
			self::GROUP_SHORTCUT_FINAL_METHODS,
			self::GROUP_SHORTCUT_ABSTRACT_METHODS,
			self::GROUP_SHORTCUT_STATIC_METHODS,
			self::GROUP_CONSTRUCTOR,
			self::GROUP_DESTRUCTOR,
			self::GROUP_PUBLIC_METHODS,
			self::GROUP_PROTECTED_METHODS,
			self::GROUP_PRIVATE_METHODS,
			self::GROUP_MAGIC_METHODS,
		],
	];

	/**
	 * @param array<int, string> $supportedGroups
	 * @return array<int, string>
	 */
	public function unpackShortcut(string $shortcut, array $supportedGroups): array
	{
		$groups = [];

		foreach (self::SHORTCUTS[$shortcut] as $groupOrShortcut) {
			if (in_array($groupOrShortcut, $supportedGroups, true)) {
				$groups[] = $groupOrShortcut;
				assertType("array{'public final methods', 'protected final methods', 'public static final methods', 'protected static final methods'}", self::SHORTCUTS[self::GROUP_SHORTCUT_FINAL_METHODS]);
			} elseif (
				!array_key_exists($groupOrShortcut, self::SHORTCUTS)
			) {
				// Nothing
				assertType("array{constants: array{'public constants', 'protected constants', 'private constants'}, static properties: array{'public static properties', 'protected static properties', 'private static properties'}, properties: array{'static properties', 'public properties', 'protected properties', 'private properties'}, all public methods: array{'public final methods', 'public static final methods', 'public abstract methods', 'public static abstract methods', 'public static methods', 'public methods'}, all protected methods: array{'protected final methods', 'protected static final methods', 'protected abstract methods', 'protected static abstract methods', 'protected static methods', 'protected methods'}, all private methods: array{'private static methods', 'private methods'}, final methods: array{'public final methods', 'protected final methods', 'public static final methods', 'protected static final methods'}, abstract methods: array{'public abstract methods', 'protected abstract methods', 'public static abstract methods', 'protected static abstract methods'}, static methods: array{'static constructors', 'public static final methods', 'protected static final methods', 'public static abstract methods', 'protected static abstract methods', 'public static methods', 'protected static methods', 'private static methods'}, methods: array{'final methods', 'abstract methods', 'static methods', 'constructor', 'destructor', 'public methods', 'protected methods', 'private methods', 'magic methods'}}", self::SHORTCUTS);
				assertType("array{'public final methods', 'protected final methods', 'public static final methods', 'protected static final methods'}", self::SHORTCUTS[self::GROUP_SHORTCUT_FINAL_METHODS]);
			} else {
				$groups = array_merge($groups, $this->unpackShortcut($groupOrShortcut, $supportedGroups));
				assertType("array{'public final methods', 'protected final methods', 'public static final methods', 'protected static final methods'}", self::SHORTCUTS[self::GROUP_SHORTCUT_FINAL_METHODS]);
			}
		}

		return $groups;
	}

}

