---
name: bug-fixing
description: Fix a PHPStan bug the way it gets merged — reproduce, write the failing test first, fix the root cause, pass the full gate, and open the PR
argument-hint: "[issue-number or bug description]"
#allowed-tools: Read, Grep, Glob, Write, Edit, Bash(curl *), Bash(gh *), Bash(git *), Bash(make *), Bash(php *), Bash(vendor/bin/phpunit *), Agent
---

# Fixing a PHPStan bug

You are given a bug (an issue number, or one you have already reproduced). Your goal is a change that a maintainer would merge as-is: the fix, a regression test that proves it, all gates green, and adjacent occurrences of the same bug covered. This skill assumes the principles in `CLAUDE.md` (never `instanceof *Type`, never sort/compare via `describe()`, prefer adding a `Type` method over scattered checks, fix the algorithm not the limit, named `_LIMIT` constants) and does not repeat them.

The rule this skill exists to enforce: **"tests pass" is not the finish line.** That only proves you did not break the suite. The finish line is a fix proven to fail-before and pass-after, with every gate read, not just run.

## Step 1 — Reproduce first

You cannot fix what you cannot reproduce. If given an issue number, fetch it and its playground reproducer (`gh issue view <n> --repo phpstan/phpstan --json title,body,comments`, then `curl -s 'https://api.phpstan.org/sample?id=<UUID>'`). Get to a state where you observe the bug — a failing `vendor/bin/phpunit` run or `php bin/phpstan analyse -l <level> test.php --debug`. Match the playground's level and `config.*` flags rather than the repo's own `phpstan.neon.dist`, which forces strict-rules and bleedingEdge on and can add or mask errors (see `bug-reporting` Step 2 for how).

## Step 2 — Write the failing test first

Pick the test home by what the bug is about (see `CLAUDE.md` "Choosing the test home for a regression"):
- Wrong inferred type / missing narrowing → `tests/PHPStan/Analyser/nsrt/bug-<n>.php` with `assertType()` (auto-discovered).
- Rule reports a wrong or missing error → `tests/PHPStan/Rules/<Category>/data/bug-<n>.php` plus a test method in the rule's `*Test.php`.
- Cache, config, DI, or parser behaviour → an `e2e/` fixture (see the existing `result-cache-*` projects for the shape).
- If the bug touches more than one, add a test for each.

Copy the reproducer verbatim from the playground. Run the test and watch it **fail for the right reason** — the same symptom the issue describes, not an unrelated error. This failing test is the anchor for everything that follows. Once it fails correctly, do not rephrase the tested code.

## Step 3 — Fix the root cause

Fix the mechanism, not the symptom. Teach inference or the engine to handle the original code; do not rewrite the code under analysis, and do not lower or cap a limit to dodge slowness (find the algorithmic cause — a temporary raise to *expose* the slowness is fine, but restore it). When the check you need is "is this type a Foo?", look for an existing `Type` method first and add one if the check belongs on every type.

## Step 4 — Scan adjacent code and cover it

After the fix passes its test, look for the same bug in the sibling code paths (other accessory types, methods vs properties vs constants, other call sites of the changed API). For each occurrence you find, add its own failing test and fix it in the same change. If the siblings are genuinely unaffected, say why. Skipping this step is how the same bug gets reported again a month later.

## Step 5 — Verify (the gate — nothing is done until all pass)

Read the **output** of each command, not just its exit code.

1. **Fails-before / passes-after.** This is the proof the test is real. Stash only the source fix by path so the test stays in place (plain `git stash` would also stash an uncommitted test unless it is a brand-new untracked file):
   ```bash
   git stash push -- src/              # stash ONLY the source fix; adjust to wherever it lives
   vendor/bin/phpunit <your-test>      # MUST fail, for the right reason
   git stash pop
   vendor/bin/phpunit <your-test>      # MUST pass
   ```
   Confirm the stash actually captured the fix (`git stash show` should list your source files): a fix can live under `stubs/`, `conf/`, or `resources/` rather than `src/`, and an empty stash silently leaves the fix applied, so the test passes and fails-before proves nothing. If the test still passes with the fix genuinely stashed, it does not cover the bug — fix the test before touching anything else. If it errors with "no tests found," you stashed the test too: narrow the stash path to the source files only.
2. **Full suite.** `make tests` (filter to your test during iteration, full suite before commit).
3. **Self-analysis.** `make phpstan` — clean. This catches things unit tests miss, notably constructor-signature changes that break instantiation sites in test base classes.
4. **Coding standard on the files you touched.** `php build-cs/vendor/bin/phpcs <the exact files you changed>` (run `make cs-install` once first if `build-cs/` is not yet present; `make cs` scans everything). Do this **before** you commit or push — a `ReferenceViaFallbackGlobalName` or missing `use function` will turn CI red after the fact.
5. **e2e and YAML.** If you touched config, DI, result-cache, or the parser, run the relevant `e2e/` fixture locally and validate any YAML you edited. There is no `make e2e` target: each fixture's exact steps live in `.github/workflows/e2e-tests.yml` (`cd e2e/<fixture> && …`), so replicate them by hand. Top-level `e2e/` is not phpcs- or self-analysis-scanned, so its correctness rides entirely on that run.
6. **Adjacent-bug scan complete.** You looked (Step 4) and either fixed the siblings or recorded why they are safe.
7. **Adversarial double-check (non-trivial change).** Spawn a fresh-context verifier subagent (Agent tool). Give it the diff and the claim: *"this fix is complete, all gates pass, there is no regression, and it does not over-reach."* Task it to refute, specifically:
   - Does the test actually fail before the fix and pass after (re-derive, do not trust the claim)?
   - Is every committed test fixture in its intended baseline state, not a mutated/patched state left over from a validation run?
   - Are there sibling code paths with the same unfixed bug?
   - Does the fix over-reach — break a fast path, over-invalidate a cache, widen a type that should stay narrow?
   A fresh context catches what the context that wrote the code rationalises away. Treat its findings as blocking.

## Step 6 — Commit

One fix, one commit. The test, any bench script, and the source change go **together** in the same commit. The commit subject describes the **change**, not the bug it closes ("Do not subtract TemplateType from TemplateType", not "Fix #14459"). Issue closure belongs in the PR body (`Closes https://github.com/phpstan/phpstan/issues/<n>`), not the subject. Follow the repo's commit-trailer conventions.

```bash
git add tests/ src/ <any other touched paths>   # stage specific paths, never `git add -A`
git commit
```

Stage specific paths. `git add -A` sweeps up stray tmp/cache artifacts and, via rename detection, can pair unrelated identical files.

## Step 7 — Open the PR

Branch and base matter. Do the work on a feature branch off the correct target: PHPStan fixes go on the active minor branch (`upstream/2.2.x` at the time of writing — confirm which minor is currently active before you branch), never straight onto a default branch. If you committed on the base branch by accident, branch first, then reset the base back.

```bash
git checkout -b <short-descriptive-branch> upstream/2.2.x   # if not already on a feature branch
git push -u <your-fork-remote> <branch>
gh pr create --repo phpstan/phpstan-src --base 2.2.x \
  --title "<describes the change, not the issue number>" \
  --body "<what changed and why, in plain prose; end with:> Closes https://github.com/phpstan/phpstan/issues/<n>"
```

Title and body describe the **change**, not the bug they close; the `Closes` link lives in the body. Keep the PR to **one concern** — split anything that mixes, say, a behavioural fix with an unrelated refactor. Before you propose, read the git history and referenced PRs of the files you touch, so the change does not fight a recent deliberate decision. Opening a PR is an outward action — confirm with the human first unless they have already said to. Then watch the first CI run settle green before calling it done: read the check rollup, tell your checks apart from repo-wide-flaky jobs, and never report success on red or pending. Handling review feedback afterwards is the `pr-review-feedback` skill.
