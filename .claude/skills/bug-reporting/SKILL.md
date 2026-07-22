---
name: bug-reporting
description: Turn an observation that PHPStan misbehaves into a verified root cause and a GitHub issue that reproduces
argument-hint: "[claim, playground URL, or short description]"
#allowed-tools: Read, Grep, Glob, Write, Edit, Bash(curl *), Bash(gh *), Bash(git *), Bash(php *), Agent
---

# Reporting a PHPStan bug

You are given an observation that PHPStan gets something wrong: a false positive, a false negative, a wrong inferred type, a crash, a performance regression, or a stale-cache result. Your goal is to reach a **verified root cause** and write an issue that anyone can reproduce. Do not write the issue until you have reproduced the bug yourself.

A bug report is only as good as its reproduction. A plausible-sounding report that nobody can reproduce wastes everyone's time, including the AI that will be asked to fix it.

## Step 1 — Pin down the claim

State, in one line, what PHPStan does and what it should do instead. If you cannot state the expected behaviour precisely, you do not understand the bug yet.

If you were given a PHPStan Playground link (`https://phpstan.org/r/<UUID>`), fetch the sample so you have the exact code, level, and config:

```
curl -s 'https://api.phpstan.org/sample?id=<UUID>'
```

The JSON gives you `code`, `level`, `config.*` flags, and `versionedErrors` (what PHPStan reported per PHP version). The reproducer for the issue comes from this link, not from prose paraphrased later in a thread.

## Step 2 — Reproduce it verbatim

Copy the reproducing snippet **as-is** into a `test.php` at the repo root. Do not tidy it, rename things, or trim it yet.

```bash
php bin/phpstan analyse -l <level> test.php --debug
# add -vvv for hangs, infinite loops, or memory blowups
```

Beware the repo's own config: at the repo root, `bin/phpstan` auto-loads `phpstan.neon.dist`, which forces strict-rules, bleedingEdge, and the deprecation and PHPUnit rules on. That can add errors that are not the bug, or hide one that only appears under the playground's settings. Reproduce with the playground's own `level` and `config.*` flags — point `-c` at a throwaway config that matches them, or analyse from a scratch directory outside the repo.

Confirm you observe the reported misbehaviour with your own eyes. If you cannot reproduce it, stop: either the report is incomplete (note exactly what is missing) or there is no bug. Never write an issue for something you have not seen happen.

Reach for the debug helpers before guessing:
- `\PHPStan\dumpType($expr)` in the analysed code prints the inferred type at that point.
- `\PHPStan\debugScope()` prints the current scope.
- Inside `NodeScopeResolver`, `var_dump($scope->debug())` is the canonical inspection point.

## Step 3 — Root-cause to a mechanism

An RCA is not "it seems related to X." It is: **this exact `file:line` does this, which produces the wrong result, because of this reason.** Trace it until you can point at the line and explain the mechanism. If you are reporting a performance or cache-soundness bug, back the mechanism with a counter or a before/after observation, not a hunch — profiles can be silently truncated and still look internally consistent.

You do not have to fix the bug to report it, but you should understand it well enough that the fix location is not a mystery.

## Step 4 — Verify any regression claim

If the issue will say "regression from <version>" or "worked before <commit>", you must observe **both** sides. Check out the earlier version (or `git revert --no-commit <suspect-commit>`), reproduce, and confirm the old behaviour differs. Do not assert a regression you have only seen one side of. A wrong "regression from" claim sends the fix hunting in the wrong place.

## Step 5 — Scan adjacent code

Bugs in the type system rarely live alone. A fault in one accessory type usually has counterparts in the sibling accessory types; a fault in property handling often repeats for methods and constants; a fault at one call site of a `Type` API often repeats at the others. Note the parallel code paths so the issue (and later the fix) can name them.

## Step 6 — Write the issue

Structure it so a stranger can act on it:

- **What's wrong** — one or two sentences.
- **Reproduction** — the minimal code (verbatim from the playground, trimmed only if you re-verified the trim still reproduces), the level, and the relevant config flags. Use only clearly fictional placeholders for names, IDs, and values. Never paste real customer data, production credentials, API keys, tokens, or connection strings into a public issue.
- **Root cause** — the `file:line` and mechanism from Step 3, if you have it.
- **Expected output** — what should happen instead.
- Whether you intend to open a PR.

Write it plainly, in your own prose. Describe the behaviour, not who found it.

## Step 7 — Verify (do not skip)

Read the output of each check; an exit code alone is not evidence.

1. **Re-reproduce from clean.** `git stash` any local edits (including your `test.php` scaffolding if it changed source), reproduce the bug once more from a clean tree, and confirm the symptom is exactly what the issue describes.
2. **Every claim is backed.** Walk the issue body sentence by sentence. Each factual claim must map to something you observed: the repro output, the per-version behaviour for a regression claim, the counter for a perf claim. Delete any claim you cannot back.
3. **Confidentiality gate.** Search the whole issue body for internal names, real identifiers, and secrets before it leaves your machine. Only fictional placeholders and public project names belong in a public issue.
4. **Adversarial double-check (non-trivial RCA).** Spawn a fresh-context verifier subagent (Agent tool) given only the drafted issue body and the reproducer. Task it to (a) reproduce the bug independently from the issue text alone, and (b) list any claim in the body not supported by evidence it can see. Treat its findings as blocking: fix the body or the repro before proceeding.

## Step 8 — Post (outward action)

Posting a public issue is an outward action. Confirm with the human before posting unless they have already told you to post it. Then:

```
gh issue create --repo phpstan/phpstan --title "<title>" --body-file <path>
```

If you will follow up with a fix, keep the reproducer you built: the `bug-fixing` skill starts from exactly this failing state.
