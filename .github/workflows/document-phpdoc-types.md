---
name: Document PHPDoc Types
description: Finds undocumented PHPDoc types in TypeNodeResolver and creates documentation PRs on phpstan/phpstan
on:
  push:
    branches: [2.2.x]
    paths: [src/PhpDoc/TypeNodeResolver.php]
  workflow_dispatch:
engine:
  id: claude
  model: claude-opus-4-6
  env:
    CLAUDE_CODE_OAUTH_TOKEN: ${{ secrets.CLAUDE_CODE_OAUTH_TOKEN }}
permissions:
  contents: read
  issues: read
  pull-requests: read
tools:
  bash: ["*"]
  github:
    toolsets: [default, repos]
timeout-minutes: 30
steps:
  - uses: actions/checkout@v4
  - uses: actions/checkout@v4
    with:
      repository: phpstan/phpstan
      ref: 2.2.x
      path: __phpstan-website
      token: ${{ secrets.PHPSTAN_BOT_TOKEN }}
---

# Document Undocumented PHPDoc Types

You are a documentation agent for PHPStan. Your job is to find PHPDoc types supported by `TypeNodeResolver` that are not yet documented in the user-facing PHPDoc types reference, and to add documentation for them.

## Source files

- **Type resolver**: `src/PhpDoc/TypeNodeResolver.php` in this workspace (phpstan-src repo)
- **PHPDoc types docs**: `__phpstan-website/website/src/writing-php-code/phpdoc-types.md` (checked out from `phpstan/phpstan`)

## Task

### Step 1: Extract supported types from TypeNodeResolver

Read `src/PhpDoc/TypeNodeResolver.php` and extract every type name that it resolves. The types come from two places:

1. **`resolveIdentifierTypeNode()`** — contains a `switch (strtolower($typeNode->name))` with `case` entries for each identifier type (e.g. `int`, `non-empty-string`, `callable-object`, etc.).

2. **`resolveGenericTypeNode()`** — contains `if`/`elseif` checks on `$mainTypeName` for generic type forms (e.g. `array<T>`, `class-string<T>`, `key-of<T>`, `int-mask<T>`, etc.).

**Skip** any type names that begin with `__` (double underscore) — these are internal.

### Step 2: Extract documented types from phpdoc-types.md

Read `__phpstan-website/website/src/writing-php-code/phpdoc-types.md` and extract all type names that are already documented. Types appear as:
- Bullet list items with inline code (e.g. `* \`int\`, \`integer\``)
- In code block examples
- In prose descriptions (e.g. "`non-falsy-string` (also known as `truthy-string`)")

Be thorough — a type counts as "documented" even if it only appears as a secondary mention, alias, or in a code example.

### Step 3: Compare and identify undocumented types

{{#if github.event_name == 'push'}}
Focus only on types that were added or changed in this push. Run `git diff ${{ github.event.before }} -- src/PhpDoc/TypeNodeResolver.php` to see what changed. Only document newly added types.
{{#else}}
Compare ALL non-skipped types from TypeNodeResolver against the documentation. Document every supported type that is not yet mentioned anywhere in phpdoc-types.md.
{{/if}}

If there are no undocumented types, stop and report that all types are documented. Do not create a PR.

### Step 4: Add documentation for undocumented types

Edit `__phpstan-website/website/src/writing-php-code/phpdoc-types.md` to add the missing types. Use **targeted edits** — do not overwrite the file.

**Placement rules** — add each type to the correct existing section:

- Integer types/ranges → "Integer ranges" section
- String types → "Other advanced string types" section
- Array types → "General arrays" section
- Class/interface/trait/enum string types → "class-string" section
- Callable types → "Callables" or "Basic types" section as appropriate
- Bottom type synonyms → "Bottom type" section
- Mixed variants → "Mixed" section
- Scalar variants → "Basic types" section
- Object variants → "Basic types" section

**Follow the existing writing style exactly.** The documentation is concise:

- For types added to a bullet list, just add a new `* \`type-name\`` entry or append to an existing line (e.g. adding `noreturn` to the bottom type synonyms list).
- For types that need a brief explanation, write one or two sentences in the same style as existing entries. For example, the string types section uses patterns like:
  - `` `non-empty-string` is any string except `''`. ``
  - `` `lowercase-string` accepts strings where `strtolower($string) === $string` is true. ``
- Only add code examples if the type's behavior is non-obvious.
- If the new type is an alias or synonym of an already-documented type, mention it alongside the existing type (e.g. add `noreturn` to the bottom type list, add `interface-string` next to `class-string`).

### Step 5: Create a pull request

After editing the documentation file, push the changes and create a PR on `phpstan/phpstan`:

```bash
cd __phpstan-website
git config user.name "phpstan-bot"
git config user.email "ondrej+phpstanbot@mirtes.cz"
git checkout -b docs/undocumented-phpdoc-types
git add website/src/writing-php-code/phpdoc-types.md
git commit -m "Document undocumented PHPDoc types"
git push origin docs/undocumented-phpdoc-types
gh pr create --repo phpstan/phpstan --base 2.2.x --draft --title "[Docs] Document undocumented PHPDoc types" --body "PR DESCRIPTION HERE"
```

Replace `PR DESCRIPTION HERE` with a description listing which types were newly documented, grouped by section.
