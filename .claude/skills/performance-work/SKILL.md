---
name: performance-work
description: Investigate a PHPStan performance problem and land it as a measured fix or a well-framed issue, without dumbing down analysis
argument-hint: "[perf symptom, issue number, or profile]"
#allowed-tools: Read, Grep, Glob, Write, Edit, Bash(curl *), Bash(gh *), Bash(git *), Bash(make *), Bash(php *), Bash(hyperfine *), Bash(vendor/bin/phpunit *), Agent
---

# Landing a PHPStan performance change

You are given a performance symptom: something is slow, memory-heavy, or a run regressed. Your goal is either a **measured** fix that a maintainer merges, or a **well-framed issue** that lets someone else fix it — and to know which one you are aiming for before you write code.

Set expectations honestly up front. In our experience most per-op micro-optimizations come back **sub-1%**: PHPStan's hot paths (reflection, parsing, PHPDoc resolution) are already heavily cached, so the eliminable per-op residual is usually tiny. The perf changes that actually land are **algorithmic** (a genuine super-linear blowup) or about **what a long-lived process retains** (cache policy, eviction, reference sharing, retained graphs), not about shaving cheap calls. This skill assumes the principles in `CLAUDE.md`; one is load-bearing enough to restate: **solve the algorithm, not the limit** — never "fix" slowness by lowering or capping iterations, recursion depth, fan-out, or collection size (a temporary raise to *expose* the cost is fine; restore it once the real fix lands, and keep every threshold in a named `_LIMIT` constant).

## Step 1 — Decide: fix (PR) or report (issue)?

First separate two things people both call "performance": a **slow-but-correct** run (the subject of this skill) versus a **cache-soundness** bug where a fast path returns the *wrong* result (stale cache, under-invalidation). A soundness bug has wrong output and therefore *can* have a failing test — treat it as a correctness bug via `bug-reporting` / `bug-fixing`, not here.

A genuinely slow-but-correct run produces **correct output**, so there is **no failing test**. Benches under `tests/bench/data/` guard a *landed* optimization or a *fixed* blowup; there is no standalone "this is still slow" bench, so submitting a bench for code you have not fixed does not fit. Route the work by where the cost lives:

- **A localized fast-path, or a retention / cache-policy fix** (memoize a repeated computation, add an eviction bound, restore reference sharing) — usually a **PR**, and these are the changes that most reliably land. Continue here, then ship it through `bug-fixing`'s gate.
- **The cost is the caller generating O(N²⁺) cheap calls** deep in the engine (`MutatingScope`, `TypeSpecifier`, `NodeScopeResolver`) — maintainer-grade. Aim for an **issue**: a minimal synthetic reproducer, a generator one-liner, a scaling table, and a *narrowed* diagnosis (which call count explodes, plus hypotheses you already disproved to shrink the fix-space). Use the `bug-reporting` skill; file it on phpstan/phpstan as a **Feature request** so the issue-template bot does not demand a playground link (the Bug-report path requires one, and a perf blowup is too large to paste on the playground anyway).

Do not start writing a fast-path before you know the cost is localized. Fixing the symptom in the wrong layer is the most common wasted-effort mode here.

## Step 2 — Establish an honest baseline

Reproduce on a **real-world codebase**, not a microbenchmark — maintainers reject microbenchmark-only wins ("did you run this on a real project?"). Measure two clean checkouts (before and after) with `hyperfine`, and clear PHPStan's result cache before every timed run so you measure analysis, not a warm-cache restore:

```bash
hyperfine --warmup 1 \
  -L bin before/bin/phpstan,after/bin/phpstan \
  --prepare '{bin} clear-result-cache -c <config>' \
  '{bin} analyse -c <config> --no-progress'
```

The result cache lives in a config-keyed temp dir *outside* the checkouts, so without `--prepare` the warmup populates it and every timed run is incremental — dominated by startup plus cache restore, near-zero re-analysis, the opposite of what you meant to measure. State whether you are timing **cold** (cleared each run, as above) or **warm** (cache pre-populated). For a warm comparison, give each build its own `-c <config>` (and thus its own `tmpDir`) so the two cannot share a cache; the cold command above sidesteps this by clearing before every run.

State the scope with every number: corpus and file count, PHP version, arch, and **wall time vs user-CPU separately**. Interleave the two builds (alternate A/B) rather than all-A-then-all-B, so machine drift does not masquerade as a delta.

Validate on **more than one kind of project**. A lever can be a win on one and flat or negative on another: a Doctrine/Symfony codebase and a Laravel + larastan codebase stress different paths (type diversity vs extension and DI load), and a change that helps one can regress the other. A single-corpus "win" is not validated.

## Step 3 — Find the algorithmic root cause

Measure the **warm residual**, never `redundancy × whole-run-per-op-cost`. That product overcounts every time the expensive part is shared or cached: the first-hit reflection deserialize is paid once (the cache's job), not redundantly, so counting it as eliminable invents a lever that is not there. Warm the caches, then isolate the thin per-op cost that actually repeats.

Convert every count to the real metric before believing it. A huge call count or object-dedup count implies neither CPU nor memory: leaf `Type` objects are on the order of tens of bytes, so a 22× dedup can be ~0 MB, and a 46M no-op dispatch count can be well under 1% CPU. **Count is not time and count is not MB** — gate on an interleaved CPU A/B or a peak-memory A/B, not on the count.

## Step 4 — Validate profile numbers with a counter

Profiles lie by omission. An xdebug/callgrind profile can be **silently truncated** and still parse cleanly and look internally consistent — consistency checks do not catch truncation (in one real case the headline count was undercounted by roughly 66×). Before you publish or act on any profile-derived call count, confirm the headline number with a **direct instrumented counter**: patch a `$GLOBALS` counter plus a shutdown dump into the run. It is cheap, exact, and immune to the truncation failure mode. Interleaved *timing* conclusions are independent of this and can be trusted.

## Step 5 — Verify (the gate — nothing is done until all pass)

Read the **output** of each check, not just its exit code.

1. **Footprint + correctness, both halves.** A *no-win* bug — the optimization silently never fires — is output-correct by construction, so it passes byte-identical and unit tests trivially. Only a footprint assertion catches it:
   - **Footprint:** trigger the opt narrowly and assert only the expected subset is recomputed; trigger an *irrelevant* change and assert it recomputes **zero** / is served entirely from the fast path.
   - **Correctness:** the optimized result is **byte-identical** to the unoptimized (cold / opt-disabled) result, so nothing is under-invalidated or stale.
2. **Both axes, on real corpora.** Peak memory **and** CPU — a memory win can cost CPU and the reverse (a retention fix that cut memory cost a few percent CPU on one corpus). Gate both; a self-analysis-only number over-represents wins.
3. **Measure, do not infer.** If you believe an existing PR or a related change already fixes this, run its reproducer against that branch and confirm — a maintainer's "this will likely improve it" is a hypothesis, and two similarly-named code paths are often orthogonal axes.
4. **If code changed, run the full `bug-fixing` gate** (a perf fix still ships a test or a `tests/bench/data` guard): fails-before/passes-after, `make tests`, `make phpstan`, `phpcs` on touched files.
5. **Adversarial double-check.** Spawn a fresh-context verifier subagent (Agent tool) with the diff, the numbers, and the claim: *"this is a real win, it actually fires, it is not a no-op, the numbers are not a profile artifact, and it generalizes beyond one corpus."* Task it to refute each clause — re-derive the footprint, re-check the byte-identical result, question the measurement method, and test the second-corpus claim. Treat its findings as blocking.

## Step 6 — Frame it for acceptance

Perf review is blunt. What converts it to a merge:

- **Honesty about scope.** If the win is niche or synthetic (fires only on a shape no real project hits), say so plainly — "anti-pattern cleanup plus a scaling demo," not a claimed real-world hotspot. Maintainers merge an honest niche cleanup and reject an overclaimed one.
- **Numbers with explicit scope.** hyperfine before/after, corpus and file count, PHP, arch, wall vs CPU. Overblown or unscoped numbers get the PR closed.
- **One concern per PR.** Split aggressively — a hash-algorithm change and a cache-policy change are two PRs, not one.
- **Read the prior art.** Read the git history and referenced PRs of every file you touch before proposing, so you do not fight a recent deliberate decision (much of what looks like an easy win was already tried and rejected for portability or correctness).
- **Try the maintainer's counter-idea.** If a reviewer proposes a different home for the change, actually build and A/B it, then report the trade-off honestly and offer to switch.

If Step 1 sent you to an issue, this framing is the issue body. If it is a PR, open it through `bug-fixing` Step 7 and carry these points into the description.
