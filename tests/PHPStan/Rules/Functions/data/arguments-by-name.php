<?php // lint >= 8.0

namespace ArgumentsByNameFunction;

/**
 * @optional-arguments-by-name
 */
function optional(string $dialect, string $group = 'default', bool $allowExtraAttributes = true, string ...$rest): void
{

}

/**
 * @arguments-by-name Parameter names are stable, their order is not.
 */
function all(string $dialect, string $group = 'default', string ...$rest): void
{

}

/**
 * @stable-parameter-names
 */
function stable(string $dialect, string $group = 'default'): void
{

}

/**
 * @no-named-arguments
 * @arguments-by-name
 */
function conflicting(string $dialect, string $group = 'default'): void
{

}

function (): void {
	optional('openapi-3.1');
	optional('openapi-3.1', group: 'read');
	optional(dialect: 'openapi-3.1', allowExtraAttributes: false);
	optional('openapi-3.1', 'read');
	optional('openapi-3.1', 'read', false, 'a', 'b');

	all(dialect: 'openapi-3.1', group: 'read');
	all('openapi-3.1', group: 'read');
	all('openapi-3.1', 'read', 'a');

	stable('openapi-3.1', 'read');
	stable(dialect: 'openapi-3.1', group: 'read');

	conflicting('openapi-3.1', 'read');
};

/**
 * @param list<string> $list
 * @param non-empty-list<string> $nonEmptyList
 * @param array<string, string> $named
 * @param array<string> $unknown
 * @param non-empty-array<string> $nonEmptyUnknown
 */
function unpacking(array $list, array $nonEmptyList, array $named, array $unknown, array $nonEmptyUnknown): void
{
	optional(...['openapi-3.1', 'read']);
	optional(...['openapi-3.1', 'group' => 'read']);
	optional('openapi-3.1', ...['group' => 'read']);
	optional(...$list);
	optional('openapi-3.1', ...$nonEmptyList);
	optional(...$named);
	optional(...$unknown);

	all(...['openapi-3.1']);
	all(...['dialect' => 'openapi-3.1']);
	all(...$nonEmptyList);
	all(...$list);
	all(...$named);
	all(...$unknown);
	all(...$nonEmptyList, ...$nonEmptyList);
	all(...$nonEmptyUnknown);
}
