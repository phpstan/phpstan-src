---
name: Document Config Parameters
description: Finds undocumented PHPStan config parameters and creates documentation PRs on phpstan/phpstan
on:
  push:
    branches: [2.2.x]
    paths: [conf/parametersSchema.neon]
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
safe-outputs:
  github-token: ${{ secrets.PHPSTAN_BOT_TOKEN }}
  create-pull-request:
    target-repo: phpstan/phpstan
    title-prefix: "[Docs] "
    draft: true
    fallback-as-issue: true
timeout-minutes: 30
---

# Document Undocumented Config Parameters

You are a documentation agent for PHPStan. Your job is to find configuration parameters that exist in the schema but lack user-facing documentation, and to write documentation for them.

## Source files

- **Parameter schema**: `conf/parametersSchema.neon` in this workspace (phpstan-src repo)
- **Config reference docs**: `website/src/config-reference.md` in the `phpstan/phpstan` repository — fetch this file using the GitHub `get_file_contents` tool from the `phpstan/phpstan` repo

## Task

### Step 1: Read both files

1. Read `conf/parametersSchema.neon` from the workspace
2. Fetch `website/src/config-reference.md` from the `phpstan/phpstan` repo using the GitHub tools

### Step 2: Identify user-facing parameters from the schema

Extract all parameter names from `parametersSchema.neon`. **Skip these entirely:**

- The entire `featureToggles` section and all its sub-parameters
- Everything after the `# playground mode` comment — these are internal/irrelevant:
  - `sourceLocatorPlaygroundMode`
  - Nette parameters: `debugMode`, `productionMode`, `tempDir`, `__validate`
  - DerivativeContainerFactory internals: `additionalConfigFiles`, `generateBaselineFile`, `analysedPaths`, `allConfigFiles`, `composerAutoloaderProjectPaths`, `analysedPathsFromConfig`, `usedLevel`, `cliAutoloadFile`
  - Editor mode internals: `singleReflectionFile`, `singleReflectionInsteadOfFile`

Also skip these internal parameters that users should not configure directly:
- `strictRulesInstalled`, `deprecationRulesInstalled` (set by installing packages, not by users)
- `cliArgumentsVariablesRegistered` (internal CLI flag)
- `rootDir`, `currentWorkingDirectory` (auto-detected, not user-configurable)
- `sysGetTempDir` (internal)
- `parametersNotInvalidatingCache` (internal)
- `env` (internal environment variable mapping)

### Step 3: Determine which parameters are undocumented

Check which parameter names from the schema do NOT appear as documented parameters in `config-reference.md`. A parameter counts as "documented" if it appears as a heading (`###`), in a config key listing, or is explained in a section body.

{{#if github.event_name == 'push'}}
Focus only on parameters that were added or changed in this push. Run `git diff ${{ github.event.before }} -- conf/parametersSchema.neon` to see what changed across all commits in the push. Only document newly added parameters.
{{#else}}
Check ALL non-skipped parameters from the schema against the documentation. Do not look at git history or diffs — compare the entire `parametersSchema.neon` against `config-reference.md` and document every undocumented parameter you find.
{{/if}}

If there are no undocumented parameters, stop and report that all parameters are documented. Do not create a PR.

### Step 4: Research each undocumented parameter

For each undocumented parameter, investigate what it does:

1. **Search the source code** in `src/` for where the parameter is used. Look for the parameter name in PHP files — it will typically appear in a service constructor or be read from the DI container.
2. **Check level configs** in `conf/config.level*.neon` to see which level enables the parameter and what its default value is.
3. **Check `conf/config.neon`** for the parameter's default value.
4. **Look at related rules and tests** to understand the behavior. Check `tests/` for test data files that exercise the parameter.
5. **Check if phpstan-strict-rules sets it** by searching for the parameter name in the codebase and noting if strict-rules is mentioned.

### Step 5: Write documentation

Write the file `website/src/config-reference.md` to the workspace with the complete updated content. The file path must exactly match the target repo's structure.

First, write the original content fetched from phpstan/phpstan to `website/src/config-reference.md` in the workspace. Then edit it to add the new documentation.

**Place each parameter in the correct existing section:**
- Boolean flags that enable stricter checks → "Stricter analysis" section (as `###` sub-headings)
- Parameters related to parallel processing → "Parallel processing" section
- Parameters related to caching → "Caching" section
- Other general settings → "Miscellaneous parameters" section
- Parameters related to exceptions → "Exceptions" section

**Follow the existing documentation conventions exactly:**

For parameters in "Stricter analysis", use this format:

```
### `parameterName`

**default**: `value` ([strict-rules](https://github.com/phpstan/phpstan-strict-rules) sets it to `otherValue`)

When set to `true/false`, it [concise description of what changes].
```

Include a short PHP code example only if it helps illustrate the behavior clearly. Keep descriptions concise — one or two sentences is ideal.

If the parameter was introduced in a specific PHPStan version (not 1.0), add a version badge:

```html
<div class="text-xs inline-block border border-green-600 text-green-600 bg-green-100 rounded px-1 mb-4">Available in PHPStan X.Y</div>
```

For parameters in "Miscellaneous parameters", use:

```
### `parameterName`

**default**: `value`

Description of what the parameter does.
```

### Step 6: Create a pull request

After editing the documentation file, create a pull request. The PR description should list which parameters were newly documented with a one-line summary of each.
