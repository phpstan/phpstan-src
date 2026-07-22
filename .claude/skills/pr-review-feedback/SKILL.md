---
name: pr-review-feedback
description: Apply PHPStan PR review feedback with critical evaluation, re-run the gate, and confirm CI is green before replying
argument-hint: "[PR number and/or review/comment URL]"
#allowed-tools: Read, Grep, Glob, Write, Edit, Bash(gh *), Bash(git *), Bash(make *), Bash(php *), Bash(vendor/bin/phpunit *), Agent
---

# Applying PHPStan PR review feedback

You have review feedback on an open PR. Your goal is to address every **valid** ask fully, re-prove the change with the gate, and confirm CI is green before you tell anyone it is done. This skill reuses the `bug-fixing` Verify gate.

## Core principle — evaluate, do not apply blindly

**"Address every ask fully" is not "apply every suggestion."** Any reviewer can be wrong, and a suggested fix can be worse than the code it replaces. Judge each piece of feedback on its merits before you touch anything, and weight it by who wrote it:

- **A maintainer or another human** is usually right about *what* is wrong, but their proposed fix is a suggestion, not a spec. Implement the intent; if you disagree or see a better option, discuss rather than comply silently.
- **A bot or automated reviewer** (CI annotations, Copilot, and the like) is frequently wrong. Be skeptical of "dead code" (may be deliberately unused), generic security warnings (verify a real issue exists), and "missing type hint" nags (the repo's own rules may already cover it).
- **Your own adversarial-verifier subagent** is feedback too — and it can be mistaken or propose a suboptimal fix. Verify each finding before acting: a claim about git history, a fixture's state, or a command's behaviour is checkable, so check it.

Before acting on any ask, run it through: does it improve correctness or clarity? does it match the repo's conventions and a recent deliberate decision? is it a subjective preference? is it simply wrong? Apply the ones that hold up; skip or push back on the rest with a reason. **Verify factual claims in the feedback before you rebuild around them** — that verification is exactly what catches a reviewer (human or bot) who is confidently wrong.

## Step 1 — Read the feedback precisely

Fetch the review and comments so you work from the exact words:

```
gh pr view <n> --repo phpstan/phpstan-src --json title,body,reviews,comments,statusCheckRollup,headRefOid
gh api repos/phpstan/phpstan-src/pulls/<n>/comments   # inline review comments, if needed
```

Separate the distinct asks and restate each in one line, tagged with who raised it (maintainer / bot / self). If an ask is ambiguous, ask — do not guess what a maintainer meant and build the wrong thing.

## Step 2 — Make the change (only for asks that hold up)

For each ask you judged valid, make the smallest change that **fully** addresses it, and keep the PR to one concern. When a comment points at a specific example, decide whether it is really pointing at a general gap: if a reviewer says "this misses X-style packages," fix the class of the problem, not just that one example — papering over the named case leaves the reviewer to find the next one. For an ask you are skipping or disagree with, draft a short reason instead of a change.

## Step 3 — Verify (before you push)

Re-run the full `bug-fixing` Verify gate on the **exact files this response touched** — fails-before/passes-after for any new test, `make tests`, `make phpstan`, `php build-cs/vendor/bin/phpcs <touched files>`, and the relevant `e2e/` fixture plus YAML validation if you touched config, DI, cache, or the parser. In addition:

1. **Check every committed fixture and baseline is in its intended pre-change state.** Validation runs mutate fixtures (a `patch` step, a `composer update`, a generated cache). It is easy to `git commit --amend` with a fixture left in its post-run state — for example a package fixture committed at its patched `v2` instead of the `v1` baseline the test expects. Confirm with `git show HEAD:<fixture-path>` that what is committed is the baseline, then reset your working tree so a stray validation artifact does not get re-staged.
2. **phpcs before the push, not after.** On an unreviewed-since branch, prefer `git commit --amend` and `git push --force-with-lease` to keep one clean commit — but run phpcs on the touched files *first*. Force-pushing and then discovering a style violation costs a second force-push and a red CI in between.
3. **Adversarial double-check (non-trivial response).** Spawn a fresh-context verifier subagent (Agent tool) with the diff and the claim "this response fully addresses the feedback, the gate passes, and every fixture is at its baseline." Task it to refute — especially the fixture-state check above, which a fresh reader catches and the author's own context rationalises away. Then run *its* findings through the core principle: confirm each before acting, since the verifier can be wrong too.

## Step 4 — Push, then wait for CI to settle green

Pushing is not "done." Read the actual check rollup and let it finish:

```
gh pr view <n> --repo phpstan/phpstan-src --json headRefOid,mergeable,statusCheckRollup \
  --jq '{head:.headRefOid, mergeable:.mergeable, failing:[.statusCheckRollup[]|select(.conclusion=="FAILURE")|.name], pending:[.statusCheckRollup[]|select(.status!="COMPLETED")|.name]}'
```

Confirm the reported head matches the commit you pushed. Distinguish **your** checks from repo-wide-flaky ones (some integration jobs fail on every recent PR regardless of the change — verify a failure reproduces on the target branch before blaming your change). Do not report success while any of your checks are red or still pending.

## Step 5 — Reply and resolve (outward action)

Reply only once CI is green. Posting to a public PR is an outward action, and replying to or resolving a **maintainer's** thread is theirs to expect, not yours to auto-do:

- **Never auto-reply or auto-resolve a maintainer's review thread** without the human's go-ahead in the current conversation. Applying the code change they asked for is within intent; speaking on the PR on their behalf is not. Present your replies as drafts and let the human send them, unless they have said to post.
- Keep any reply short, in your own voice, and acknowledge what the review surfaced when it genuinely improved the change. Do not repeat the reviewer's comment back to them.

If the Step 3 double-check found a real problem, that is a signal to *fix and re-verify*, not to post "done" and hope.
