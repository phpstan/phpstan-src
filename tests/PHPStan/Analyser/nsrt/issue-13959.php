<?php

namespace Issue13959;

use function PHPStan\Testing\assertType;

class Id {}
class TagId extends Id {}

/**
 * Test case from GitHub issue #13959
 * https://github.com/phpstan/phpstan/issues/13959
 *
 * The issue was that type narrowing in foreach loops did not
 * propagate back to the array itself, preventing proper type
 * inference when iterating over union types.
 */
function testIssue13959(): void
{
    /** @var list<Id|string> $ids */
    $ids = [];

    foreach ($ids as $id) {
        if ($id instanceof TagId) {
            // Before fix: Type was still list<Id|string>
            // After fix: Type is narrowed to list<TagId>
            assertType('non-empty-list<Issue13959\TagId>', $ids);

            // The narrowed type allows us to call TagId-specific methods
            // (if TagId had any specific methods)
        }
    }

    // Type should be restored after the loop
    assertType('list<Issue13959\Id|string>', $ids);
}

/**
 * Test with more specific scenario from the issue
 */
class User {}
class Admin extends User {
    public function adminMethod(): void {}
}

/** @param list<User|string> $users */
function processUsers(array $users): void
{
    foreach ($users as $user) {
        if ($user instanceof Admin) {
            // Before fix: $users is list<User|string> - cannot call adminMethod()
            // After fix: $users is list<Admin> - can call adminMethod()
            assertType('non-empty-list<Issue13959\Admin>', $users);

            // This should work now:
            $user->adminMethod();
        }
    }
}

/**
 * Test with direct array access (also part of the issue)
 */
/** @param array<int, User> $users */
function testDirectAccess(array $users): void
{
    if ($users[0] instanceof Admin) {
        // Before fix: Type was array<int, User>
        // After fix: Type is narrowed with hasOffsetValue metadata
        assertType('non-empty-array<int, Issue13959\User>&hasOffsetValue(0, Issue13959\Admin)', $users);
    }
}

/**
 * Test with list marker preservation
 */
/** @param list<User> $users */
function testListMarker(array $users): void
{
    foreach ($users as $user) {
        if ($user instanceof Admin) {
            // The list marker should be preserved
            assertType('non-empty-list<Issue13959\Admin>', $users);
        }
    }
}
