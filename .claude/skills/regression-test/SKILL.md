---
name: regression-test
description: Add a regression test for an already-fixed PHPStan bug given a GitHub issue number
argument-hint: "[issue-number]"
#allowed-tools: Read, Grep, Glob, Write, Edit, Bash(curl *), Bash(gh *), Bash(git *), Bash(make tests), Bash(vendor/bin/phpunit *), Bash(php *)
---

# Adding a regression test for a fixed PHPStan bug

You are given a GitHub issue number. Your goal is to write a regression test that locks in the fix so the bug cannot silently resurface.

## Step 1 — Gather context from the issue

Fetch the GitHub issue and its comments:

```
gh issue view <number> --repo phpstan/phpstan --json title,body,comments
```

In the comments, look for:

1. **phpstan-bot's commit link** — this identifies the commit that fixed the bug.
2. **PHPStan Playground links** — URLs matching `https://phpstan.org/r/<UUID>`. These contain the reproducing code and the analysis results before and after the fix.

For each playground link, fetch the sample data:

```
curl -s 'https://api.phpstan.org/sample?id=<UUID>'
```

The API returns JSON with:
- `code` — the PHP code that was analysed
- `level` — the PHPStan rule level used
- `config.strictRules`, `config.bleedingEdge`, `config.treatPhpDocTypesAsCertain` — configuration flags
- `versionedErrors` — array of `{phpVersion, errors: [{line, message, identifier}]}`

The playground sample gives you the reproducing code and tells you what errors PHPStan produces (or should no longer produce) at each line. Use this to determine the correct test type and expected outcomes.

## Step 2 — Decide which kind of test to write

### Type-inference test (NSRT)

Use when the bug is about **wrong inferred type, missing type narrowing, or incorrect type after control flow** — i.e., PHPStan reports the wrong type for an expression but no specific rule error is involved.

- Create `tests/PHPStan/Analyser/nsrt/bug-<number>.php`
- Use `assertType()` and optionally `assertNativeType()` to pin the correct types
- The file is **auto-discovered** — no test-case class changes are needed

Example (`tests/PHPStan/Analyser/nsrt/bug-12875.php`):

```php
<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12875;

use function PHPStan\Testing\assertType;

interface HasFoo
{
	public function foo(): int;
}

interface HasBar
{
	public function bar(): int;
}

class HelloWorld
{
	/**
	 * @param "foo"|"bar" $method
	 * @param ($method is "foo" ? HasFoo : HasBar) $a
	 * @param ($method is "foo" ? HasFoo : HasBar) $b
	 */
	public function add(string $method, HasFoo|HasBar $a, HasFoo|HasBar $b): void
	{
		assertType('int', $a->{$method}());
		assertType('int', $b->{$method}());

		$addInArrow = fn () => assertType('int', $a->{$method}());

		$addInAnonymous = function () use ($a, $b, $method): void {
			assertType('int', $a->{$method}());
			assertType('int', $b->{$method}());
		};
	}
}
```

### Rule test

Use when the bug is about a **false positive** (rule reported an error that shouldn't exist) or a **false negative** (rule failed to report an error that should exist).

1. **Find the relevant rule test case.** Search for the rule's identifier or class name:
   ```
   grep -r 'identifier' tests/PHPStan/Rules/ --include='*Test.php'
   ```
   Or look at the error identifier from the playground output (e.g., `argument.type` points to a rule in `Rules/Methods/` or `Rules/Functions/`).

2. **Create the test data file** at `tests/PHPStan/Rules/<Category>/data/bug-<number>.php`.

3. **Add a test method** in the corresponding `*Test.php` rule test case.

Example — test data (`tests/PHPStan/Rules/Methods/data/bug-11470.php`):

```php
<?php declare(strict_types = 1);

namespace Bug11470;

use DateTimeImmutable;

interface HelloWorld
{
	public function sayHello(): dateTimeImmutable;
}

interface HelloWorld2
{
	public function sayHello(dateTimeImmutable $a): void;
}

interface HelloWorld3
{
	public function sayHello(): ?dateTimeImmutable;
}
```

Example — test method in `ExistingClassesInTypehintsRuleTest.php`:

```php
public function testBug11470(): void
{
	$this->analyse([__DIR__ . '/data/bug-11470.php'], [
		[
			'Class DateTimeImmutable referenced with incorrect case: dateTimeImmutable.',
			9,
		],
		[
			'Class DateTimeImmutable referenced with incorrect case: dateTimeImmutable.',
			14,
		],
		[
			'Class DateTimeImmutable referenced with incorrect case: dateTimeImmutable.',
			19,
		],
	]);
}
```

For **false-positive** fixes the expected-errors array is usually empty `[]`, meaning the code should analyse clean.

## Step 3 — Write the test

- Use the reproducing code from the playground sample for the test file. Keep it as close as possible to the original. Once you have a reproducing failing test with the same error as the issue is about, do not edit the tested code sample.
- Put it in the correct `namespace Bug<number>;`.
- Add `use function PHPStan\Testing\assertType;` for NSRT tests.
- If the playground code requires a minimum PHP version, add `// lint >= 8.0` (or whichever version) on the first line.
- For rule tests, determine the expected errors from the playground `versionedErrors`. If the fix eliminated a false positive, expect `[]`. If the fix added a missing error, list the expected `[message, line]` pairs.

## Step 4 — Verify the test

Run the specific test to confirm it passes with the current (fixed) code:

```bash
# NSRT test — run the full NSRT suite or a filtered subset:
vendor/bin/phpunit tests/PHPStan/Analyser/NodeScopeResolverTest.php --filter 'bug-<number>'

# Rule test — run the specific test class:
vendor/bin/phpunit tests/PHPStan/Rules/<Category>/<TestClass>.php --filter testBug<number>
```

## Step 5 — Confirm the test catches a regression

Temporarily revert the fixing commit and verify the test fails:

```bash
git stash
git revert --no-commit <fixing-commit-hash>
vendor/bin/phpunit <test-command-from-step-4>
# Expect failure
git checkout .
git stash pop
```

If the test passes even with the fix reverted, the test is not correctly covering the bug — revisit the assertions or the reproducing code.

## Step 6 — Commit

Stage the new/modified test files and commit.

```bash
git add tests/
git commit -m "Add regression test for #<number>

Closes https://github.com/phpstan/phpstan/issues/<number>"
```

## Step 7 - Adjust pull request description

If you are submitting a pull request, append the PR description with a link to the issue with `Closes https://github.com/phpstan/phpstan/issues/<number>` so the upstream issue is closed automatically.
