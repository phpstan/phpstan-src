---
name: pullrequest-coverage
description: Review test coverage of the changed lines in a pull request using the downloaded Clover report
argument-hint: "[pull-request-number]"
---

# Review pull request coverage

Review the pull request given by `$ARGUMENTS`, or the current branch's pull request if no number is
given. Produce a summary of changed files and lines that lack test coverage; do not modify code or
post a review unless asked.

## 1. Identify the pull request and its coverage run

Use `gh pr view` to obtain the PR number, base and head branches, head SHA, and repository. Determine
the merge base of the PR base and head and compare that merge base with the PR head, not with the
working tree. Fetch the necessary refs if they are missing. Preserve any local changes; never switch
branches over them. If the PR or its base cannot be identified, ask the user.

`make download-coverage` selects the latest `tests.yml` run in `phpstan/phpstan-src` for the
**current branch's configured upstream branch name**. It does not select a PR number or verify the
commit. Check the upstream (`git rev-parse --abbrev-ref --symbolic-full-name '@{upstream}'`) and
inspect the selected run with `gh run list` and `gh run view` before relying on it. If the current
checkout does not correspond to the requested PR, or the selected run is not for that PR and revision,
stop and explain the mismatch rather than reporting unrelated coverage.

The workflow may test a synthetic PR merge commit rather than the head commit. Verify that the
downloaded report corresponds to the PR head being reviewed: inspect the run's event, PR association,
and revision, and, for a merge commit, verify its PR head parent and that the contents and line numbers
of each reported changed file match the PR head. If they do not match, do not compare the report
against the head diff; explain that coverage for the requested revision is unavailable. Do not silently
use a stale run, an unrelated run, or a report for local uncommitted changes.

## 2. Download coverage

The target deletes `tmp/coverage-download` and overwrites `tmp/coverage/clover.xml`. Before running it,
preserve any existing data at those paths in the session workspace; do not delete user data. Ensure
`tmp/coverage` exists, then run:

```bash
make download-coverage
```

Read `tmp/coverage-download/clover.xml`. If the command fails, the artifact is missing, or the XML
cannot be parsed, report the failure and stop; never fall back silently to an old report. The artifact
is produced by the mutation-testing job, which only runs for qualifying PR changes and restricts
coverage to selected source files. A missing artifact or file is not proof of missing tests.

## 3. Compare changed lines with Clover

Generate the PR diff from the merge base to the pinned head SHA, with rename detection and zero
context, for example:

```bash
git diff --no-ext-diff --find-renames --unified=0 <merge-base> <head-sha> --
```

Parse the diff hunks to collect **added and modified lines on the new side** for each destination
path. Count every `+` content line at its new line number, advancing the new line number for context
and additions, but not deletions; ignore headers and `\ No newline at end of file` markers. Handle
renames, new files, multiple hunks and zero-length ranges. Deleted-only lines have no new-side line
number and cannot be checked against head coverage.

Parse Clover as XML and match each `<file name="...">` to a repository-relative destination path.
The download target rewrites the CI checkout prefix to the local checkout path; account for this when
normalizing paths. Match exact paths, not just basenames, and handle paths safely rather than splitting
them on whitespace. If a path cannot be mapped unambiguously, report it as unavailable.

For each changed line in a matched file:

- A `<line type="stmt" num="N" count="0"/>` is an uncovered executable statement; a positive count
  means it was executed. If multiple statement records exist for the same line, consider it covered
  if any has a positive count.
- A changed line with no statement record is **not automatically uncovered**. It may be a comment,
  declaration, continuation line, or otherwise outside the report's instrumentation. Inspect the
  code and report any meaningful coverage uncertainty rather than inventing a zero count.
- Method records are separate from statement records; do not double-count them as statements. If an
  uncovered `type="method"` record on a changed line helps identify an untested method, mention it
  separately, without treating it as proof of branch coverage.
- For a changed file absent from the report, distinguish unavailable coverage from confirmed uncovered
  statements. The report's restricted source selection, tests, documentation, generated code, deleted
  files, or other excluded files may explain the absence.

Read the surrounding changed code and relevant tests for each uncovered statement to describe the
behavior or path that needs a test. Line execution alone does not establish that assertions validate
the behavior, or that all branches and edge cases are tested; do not claim otherwise.

## 4. Summarize

Report the PR, reviewed head SHA, coverage run and any limitations. List each file with confirmed
uncovered changed statement line numbers (or ranges), the relevant behavior, and a concise suggested
test scenario. Separately list changed files or meaningful changed lines whose coverage could not be
determined, with the reason. If none are confirmed uncovered, say so without claiming full coverage
when data is missing. Do not include unchanged uncovered lines in the missing-tests list.
