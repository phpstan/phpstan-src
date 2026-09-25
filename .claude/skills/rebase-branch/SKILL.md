---
name: rebase-branch
description: Rebase the current feature branch onto the fresh tip of a base branch (e.g. 2.3.x) — read both sides first, plan every conflict, and keep turbo-shadowed PHP classes and their turbo-ext C++ mirrors identical in each replayed commit
argument-hint: "[base-branch]"
---

# Rebasing a feature branch onto a fresh base

The base branch is `$ARGUMENTS`. If that is empty, use the base of the branch's pull request
(`gh pr view --json baseRefName -q .baseRefName`); if there is no pull request, ask.

Work in the checkout that has the feature branch checked out. Address it by absolute path
(`git -C <path>`), because the shell's working directory resets between commands. Never use `git stash`:
the stash is shared by every worktree of the repository. `git rebase -i` runs here only with a
scripted `GIT_SEQUENCE_EDITOR` (step 4), never with an interactive editor.

Helpers in this skill's directory (the directory this SKILL.md was loaded from):

- `rebase-plan.php <new-base> <old-tip>` prints a read-only report for the planning step.
- `rebase-todo.php` is a `GIT_SEQUENCE_EDITOR` that marks commits `drop` or `edit`.

If the feature branch is older than this skill, the helpers are not in its checkout. Extract them with
`git show origin/2.3.x:.claude/skills/rebase-branch/<file> > <scratchpad>/<file>`.

## 1. Pin the three points

```bash
git fetch origin
git status --porcelain --untracked-files=no        # must print nothing: commit first or ask
BRANCH=$(git branch --show-current)
OLD_TIP=$(git rev-parse HEAD)
NEW_BASE=$(git rev-parse origin/<base>)            # the remote tip, never the possibly stale local branch
OLD_BASE=$(git merge-base "$NEW_BASE" "$OLD_TIP")
git branch -f "backup/$BRANCH-pre-rebase" "$OLD_TIP"
```

Write the three SHAs to the scratchpad. Every later step uses the SHAs, not refs that can move. If
`OLD_BASE` equals `NEW_BASE`, stop, because there is nothing to rebase.

- **The feature's work** is `git diff $OLD_BASE $OLD_TIP`, commit by commit
  `git log --reverse -p $OLD_BASE..$OLD_TIP`.
- **What happened on the base meanwhile** is `git diff $OLD_BASE $NEW_BASE`, commit by commit
  `git log --reverse --no-merges -p $OLD_BASE..$NEW_BASE`.

## 2. Read both sides, plan every conflict

Run `php <skill dir>/rebase-plan.php $NEW_BASE $OLD_TIP`. It reports:

- the base commits,
- merge commits in the feature range,
- feature commits the base already contains,
- turbo bump commits,
- files changed on both sides, with the feature commits that touch each one,
- the conflicts that `git merge-tree` predicts for the two tips,
- the turbo twin table (step 3),
- names that vanished on one side but are still spelled in the other side's C++.

The report tells you where to look; it does not replace reading the changes. Read:

1. The feature's work: `git log --reverse --stat $OLD_BASE..$OLD_TIP`, then the diffs, starting with the
   files changed on both sides.
2. The base's work: every base commit that touches a file changed on both sides, plus any base commit
   that renames, moves or changes the signature of code the feature uses. Those changes break the
   feature silently, without a textual conflict.
3. For each file changed on both sides, put `git diff $OLD_BASE $NEW_BASE -- <file>` next to
   `git log --reverse -p $OLD_BASE..$OLD_TIP -- <file>`. Decide what the combined file must contain.

Then write the plan to the scratchpad. Include every feature commit that will conflict or needs a mirror
port, each with the file, what the base did there, and the intended resolution. A resolution keeps the
intent of both sides. When the feature reshaped code that the base then changed, carry the base's
change into the feature's new shape; don't just pick one side. When the base moved code that the feature
edits, apply the feature's edit at the new location. Where the right resolution is unclear, ask before
you start, not in the middle of the replay.

Decide these before replaying (ask the user if the answer isn't obvious):

- **Merge commits in the feature range**: a rebase flattens them away. Linearize only with the user's
  agreement.
- **Commits the base already contains**: this covers patch-identical commits and adapted twins, such as
  a chunk extracted into its own pull request that has since landed. Drop them instead of replaying
  them. Replaying one conflicts, and resolving it on the branch's side silently wipes out the base's
  version.
- **`Bump expected turbo version` commits**: drop all of them and run one `make bump-turbo` at the end.
  During the replay, a conflict on `EXPECTED_EXTENSION_VERSION` resolves to the base's value.

## 3. Turbo mirrors

Classes carrying `#[ShadowedByTurboExtension(turboClass: ..., implementation: .../turbo-ext/src/X.cpp)]`
have a native C++ twin. Vendored twins live in `VENDORED_PAIRS` in
`build/PHPStan/Build/TurboAttributeCollector.php` and change when their package's version in
`composer.lock` changes. The PHP class and the C++ class must behave identically after every replayed
commit. A mirror change goes into the same commit as the PHP change it mirrors, never into a trailing
"port" commit.

The report's twin table uses these classifications:

- **Shadowed at OLD BASE and still on both sides**: each side already mirrored its own PHP edits.
  Resolve a `.cpp` conflict the same way as the matching `.php` conflict, so the merged `.cpp` mirrors
  the merged `.php`. If a side changed the `.php` but not the `.cpp`, check whether that change needed a
  port (a docblock-only change does not).
- **NEW ON BASE**: the base ported a class that the feature edits. Every feature commit that changes
  that PHP class must make the same change in the base's `.cpp`. Mark those commits `edit`.
- **NEW ON FEATURE**: the feature ports a class that the base changed in the meantime. Port the base's
  PHP change into the `.cpp` in the feature commit that introduces the `.cpp`, and mark that commit
  `edit`. Later feature commits that touch the `.cpp` then replay on top of the port.
- **SHADOWED ON BOTH SIDES INDEPENDENTLY**: reconcile the two ports by hand, and ask which one survives.
- **UNSHADOWED on one side**: the other side's `.cpp` edits no longer apply. Resolve a modify/delete
  conflict by deleting the file.
- **Names spelled in C++**: native code reaches PHP by name, which git cannot see. It calls methods and
  reads properties through method and property sites and string literals. It also copies constants
  (e.g. `PhpVersionFactory::MAX_PHP_VERSION` as `PT_MS_MAX_PHP_VERSION`) and slot numbers (a
  `PT_*_PROP_*` copy of another class's property layout). If a twin gains or loses a property on either
  side, grep turbo-ext for copies of its slot numbers. The report lists removed names and changed
  constant values it found spelled on the other side, but it can't see a changed parameter list.
  `side-by-side.php`, `signature-parity.php` and the tests catch that later.

The turbo machinery itself may differ between the base and the feature. Compare `turbo-ext/CLAUDE.md`
and `turbo-ext/README.md` on both sides: stub shells vs real-name activation, handwritten `reg::Class`
registrations vs generated `sig::` declarations, helper APIs in `reg.h`, `zv.h` and `support.h`. Write
each mirror in the conventions of the commit being replayed. A port that is new on the base has to
follow the feature's conventions from the feature commit that changed them onwards. The report's last
section lists the shared turbo-ext files the feature changed.

Never hand-merge generated files. Regenerate them in the commit being replayed:

| File | Resolution |
| --- | --- |
| `turbo-ext/src/generated/*.h` | `php turbo-ext/bin/generate-declarations.php`, where the generator exists at that commit |
| `turbo-ext/src/parser/ParserRunnerActions*.cpp`, `ParserRunnerActionsSplit.h` | `php turbo-ext/bin/generate-parser-actions.php` |
| `composer.lock` | take the current (base) lock, re-apply the commit's `composer.json` change, then `composer update <the packages it changed>`, or `composer update --lock` when only the hash differs |
| `phpstan-baseline.neon` | apply the replayed commit's own edit (`git show REBASE_HEAD -- phpstan-baseline.neon`) to the current file |
| `src/Turbo/TurboExtensionEnabler.php` version | keep the base's value, bump at the end |

For registries that both sides append to, take the union of both sides' entries. These include
`$covered` / `$coveredElsewhere` in `turbo-ext/tests/smoke.php`, `VENDORED_CLASS_MAP`, and
`pt_class_refs` keys. MINIT registrations and the build files' source lists no longer exist: a
replayed commit that adds a `pt_register_*()` call to `main.cpp`, its declaration to `support.h` or
a `.cpp` name to `config.w32` drops that hunk and defines the function with
`PT_MINIT_REGISTRATION(pt_register_*)` in its own file instead.

## 4. Replay

```bash
GIT_SEQUENCE_EDITOR="php <skill dir>/rebase-todo.php" \
REBASE_DROP="<shas to drop>" REBASE_EDIT="<shas to stop after>" \
git rebase -i --onto "$NEW_BASE" "$OLD_BASE" "$BRANCH"
```

The helper aborts the rebase before anything is replayed if a listed SHA has no pick line. Git's rerere
is enabled on this machine. It may reapply a resolution recorded during an earlier attempt, so review
those hunks (`git rerere diff`) like any other resolution.

At each stop:

- **Conflict.** Run `git diff --name-only --diff-filter=U` to list the conflicted files and
  `git show REBASE_HEAD` to see the commit being replayed. Resolve according to the plan, and make this
  commit's planned mirror edits now as well. A conflicting commit marked `edit` is committed by
  `--continue` without stopping again, so the edit stop never comes. Stage files by name, never a
  directory. Delete any `*.orig` or `*.rej` files. Then run `GIT_EDITOR=true git rebase --continue`.
- **Planned `edit` stop.** Make the mirror edit, `git add <files>`, `git commit --amend --no-edit`, then
  `git rebase --continue`.
- **After touching C++**: rebuild turbo-ext incrementally (`make -C turbo-ext`) and fix compile errors
  in the same commit. After touching PHP, run `php -l` on the files.
- **A commit that unexpectedly became empty** means a resolution dropped its content. Investigate it;
  don't `--skip` it. Skip only commits the plan already marked as contained in the base.
- If a stop reveals something the plan didn't foresee and the right resolution is unclear, stop and ask
  instead of guessing.

## 5. Check nothing was lost

```bash
git range-diff "$OLD_BASE..$OLD_TIP" "$NEW_BASE..HEAD"
```

Review every commit that range-diff reports as changed. Each difference must be an intended conflict
resolution or mirror port.

```bash
# files the rebase changed that the base never touched: exactly the adaptations and mirror ports
comm -23 <(git diff --name-only "$OLD_TIP" HEAD | sort) <(git diff --name-only "$OLD_BASE" "$NEW_BASE" | sort)
# base changes to files the feature never touched must arrive unchanged: expect no output other than
# the planned mirror ports (xargs runs nothing on empty input - a bare `git diff --` would diff everything)
comm -23 <(git diff --name-only "$OLD_BASE" "$NEW_BASE" | sort) <(git diff --name-only "$OLD_BASE" "$OLD_TIP" | sort) | xargs git diff --stat "$NEW_BASE" HEAD --
```

If `git diff --quiet "$NEW_BASE" HEAD -- turbo-ext/src` reports a difference, run `make bump-turbo`. It
creates the single bump commit, or amends the unpushed one.

## 6. Verify

1. Sync the dependencies, because the base may have changed the locks: run `composer install`,
   `composer install -d tests` and `composer dump-autoload`. The dump regenerates `vendor/attributes.php`
   and the turbo manifest. `composer install --dry-run` must then report nothing to install.
2. Run `make phpstan` and `make tests`.
3. If any turbo twin, turbo-ext file or php-parser version differs from `NEW_BASE`, run the verify list
   in `turbo-ext/CLAUDE.md` at HEAD:
   - the strict build,
   - `turbo-ext/tests/smoke.php` (ALL OK),
   - `turbo-ext/bin/side-by-side.php`,
   - `turbo-ext/tests/signature-parity.php`,
   - `turbo-ext/tests/walk-trace.php` where it exists,
   - `turbo-ext/tests/parser-corpus.php` when `turbo-ext/src/parser/` or php-parser changed,
   - `make lint-turbo` when C++ changed,
   - `make tests` with the extension loaded,
   - `--error-format=raw` analysis output that is byte-identical with the extension loaded and not
     loaded.
4. Make sure the build you test is the one that's loaded. The global ini may load a `.so` from another
   worktree. Load this checkout's build through a scratch `PHP_INI_SCAN_DIR` (or
   `php -n -d extension=...`), and confirm that `phpversion('phpstan_turbo')` equals
   `EXPECTED_EXTENSION_VERSION`. On a mismatch, turbo silently stays off.

Fix a failure in the commit that introduced it: `git commit --fixup=<sha>`, then
`GIT_SEQUENCE_EDITOR=: git rebase -i --autosquash "$NEW_BASE"`, then `make bump-turbo` again. Don't add a
trailing fix commit.

## 7. Report and push

Tell the user:

- the old base and the new base,
- which commits were dropped, and why,
- each conflict, with its file and how it was resolved,
- each mirror port, with its class and commit,
- the verification results,
- the backup branch `backup/$BRANCH-pre-rebase`.

Push only when asked: `git push --force-with-lease="$BRANCH:$OLD_TIP" origin "$BRANCH"`. Delete the
backup branch only after the user confirms the result.
