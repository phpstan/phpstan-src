---
name: Document PHPDoc Tags
description: Finds undocumented PHPDoc tags supported by PHPStan and creates documentation PRs on phpstan/phpstan
on:
  push:
    branches: [2.2.x]
    paths: [src/PhpDoc/PhpDocNodeResolver.php]
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

# Document Undocumented PHPDoc Tags

You are a documentation agent for PHPStan. Your job is to find PHPDoc tags that PHPStan supports but are not documented on the website, and to write documentation for them.

## Source files

- **Tag handling code**: `src/PhpDoc/PhpDocNodeResolver.php` in this workspace (phpstan-src repo)
- **Valid tag list**: `src/Rules/PhpDoc/InvalidPHPStanDocTagRule.php` in this workspace — contains `POSSIBLE_PHPSTAN_TAGS` listing all recognized `@phpstan-*` tags
- **PHPDocs basics page**: `__phpstan-website/website/src/writing-php-code/phpdocs-basics.md` (checked out from `phpstan/phpstan`)
- **PHPDoc types page**: `__phpstan-website/website/src/writing-php-code/phpdoc-types.md` (checked out from `phpstan/phpstan`)
- **All website docs**: `__phpstan-website/website/src/` directory — search here for tags that may be documented on other pages
- **Source code for research**: `src/`, `conf/`, and `tests/` directories in this workspace (phpstan-src repo)

## Task

### Step 1: Extract all supported tags from source code

1. Read `src/PhpDoc/PhpDocNodeResolver.php` and extract every PHPDoc tag name it processes. Tags appear as string literals in arrays like `['@var', '@phan-var', '@psalm-var', '@phpstan-var']` and in `getTagsByName()` calls.
2. Read `src/Rules/PhpDoc/InvalidPHPStanDocTagRule.php` and extract the list of recognized `@phpstan-*` tags.
3. Build a complete list of **base tags** that PHPStan supports. For tags that have `@phpstan-`/`@psalm-`/`@phan-` prefix variants, the base tag is the unprefixed form (e.g., `@param` is the base for `@phpstan-param`). For tags that only exist with a `@phpstan-` prefix (e.g., `@phpstan-type`, `@phpstan-assert`), keep the prefixed form.

### Step 2: Check which tags are documented on the website

1. Read `__phpstan-website/website/src/writing-php-code/phpdocs-basics.md`
2. Read `__phpstan-website/website/src/writing-php-code/phpdoc-types.md`
3. Search the entire `__phpstan-website/website/src/` directory for each tag name to check if it's documented on any page

A tag counts as "documented" if it appears on any website page with an explanation of what it does. A tag does NOT count as documented if it only appears in passing examples without explanation, or only in the "Prefixed tags" section.

### Step 3: Determine which tags need documentation

**Important — prefix variants are already handled:**

The "Prefixed tags" section of `phpdocs-basics.md` already explains that tags like `@param`, `@return`, `@var`, and generics-related tags can be prefixed with `@phpstan-` (and `@psalm-`, `@phan-`). Do NOT create separate documentation for prefix variants. Only document the base tag (e.g., `@param`, not `@phpstan-param`). Exception: tags that ONLY exist with a prefix (like `@phpstan-type`, `@phpstan-assert`) need to be documented with their prefix.

**Important — verify tag name accuracy:**

When checking whether a tag is documented, verify the exact tag name matches between the source code and the documentation. Flag and fix any mismatches (e.g., if docs use a slightly different tag name than the code).

{{#if github.event_name == 'push'}}
Focus primarily on tags that were added or changed in this push. Run `git diff ${{ github.event.before }} -- src/PhpDoc/PhpDocNodeResolver.php` to see what changed. Document newly added or changed tags, but also briefly check if any other tags remain undocumented and include those too.
{{#else}}
Check ALL tags from the source code against the documentation. Do not look at git history or diffs — compare the full tag list against all website documentation and document every undocumented tag you find.
{{/if}}

If there are no undocumented tags (and no mismatched tag names), stop and report that all tags are documented. Do not create a PR.

### Step 4: Research each undocumented tag

For each undocumented tag, investigate what it does:

1. **Read the resolver method** in `PhpDocNodeResolver.php` to understand how the tag is parsed.
2. **Search the source code** in `src/` for where the resolved tag data is used. For example, search for related method names in `ResolvedPhpDocBlock.php` and in rules under `src/Rules/`.
3. **Look at related rules** in `src/Rules/` that enforce or check the tag's semantics.
4. **Check tests** in `tests/` for test data files that exercise the tag — these show exactly what behavior the tag enables.

### Step 5: Write documentation

Edit `__phpstan-website/website/src/writing-php-code/phpdocs-basics.md` to add documentation for missing tags. Do NOT overwrite the file — use targeted edits to insert new sections.

**Follow the existing writing style exactly:**

- Use section headings at the same level as similar existing sections
- Provide a concise description (one or two sentences)
- Include a short PHP code example showing the tag in use
- If the tag interacts with specific rules or features, mention that briefly
- Use fenced code blocks with `php` language annotation
- If the tag was introduced in a specific PHPStan version, add a version badge:

```html
<div class="text-xs inline-block border border-green-600 text-green-600 bg-green-100 rounded px-1 mb-4">Available in PHPStan X.Y</div>
```

**Placement:** Insert new sections near related existing content. For example, property-related tags go near `@readonly`, class-level tags go near other class-level tags, etc.

**Also fix any tag name mismatches** between documentation and source code to ensure the documented tag names match what the code actually accepts.

### Step 6: Create a pull request

After editing the documentation file, push the changes and create a PR on `phpstan/phpstan`:

```bash
cd __phpstan-website
git config user.name "phpstan-bot"
git config user.email "ondrej+phpstanbot@mirtes.cz"
git checkout -b docs/undocumented-phpdoc-tags
git add website/src/writing-php-code/phpdocs-basics.md
git commit -m "Document undocumented PHPDoc tags"
git push origin docs/undocumented-phpdoc-tags
gh pr create --repo phpstan/phpstan --base 2.2.x --draft --title "[Docs] Document undocumented PHPDoc tags" --body "PR DESCRIPTION HERE"
```

Replace `PR DESCRIPTION HERE` with a description listing which tags were newly documented with a one-line summary of each, any tag name mismatches that were fixed, and a note that prefix variants are already covered by the "Prefixed tags" section.
