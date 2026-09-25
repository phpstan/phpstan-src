#!/usr/bin/env bash
# Finds, for every turbo extension binary the compile jobs in phar.yml
# produce, the newest earlier run on $BRANCH that compiled it from build
# inputs identical to HEAD's, and writes {"<artifact name>": <run id>} to
# the `origins` step output. A compile leg whose artifact is listed
# downloads it from that run instead of building it again (see the
# turbo-origins job in phar.yml).
#
# Only these runs qualify, each condition checked here rather than trusted
# from the API query:
# - runs of .github/workflows/phar.yml in this repository, triggered by a
#   push to $BRANCH — never a pull request, whose code nobody reviewed yet
#   (a fork's branch can be named $BRANCH too, hence the event and head
#   repository checks)
# - whose head commit is an ancestor of HEAD
# - whose build inputs (BUILD_INPUT_PATHS, and the compile jobs' definitions
#   in phar.yml) are identical to HEAD's
# - for each artifact separately: the run uploaded it (the upload is the
#   last step of a compile leg, after the differential tests) and did not
#   mark it as reused itself (turbo-reused-<artifact name>) — so a binary is
#   always taken from the run that compiled it, never from a chain of runs
#   that passed it along, and it stops being reused when that run's
#   artifacts expire.
#
# Anything unexpected (an API error, a commit missing from the checkout)
# disqualifies the run or ends the search; nothing here fails the job, since
# an empty map only means every leg compiles.

BUILD_INPUT_PATHS=(
	turbo-ext/src
	turbo-ext/Makefile
	turbo-ext/config.m4
	turbo-ext/config.w32
	turbo-ext/bin/pgo-train.sh
	.github/turbo-build
	.github/scripts/install-php86-windows.sh
)
WORKFLOW_PATH=".github/workflows/phar.yml"
# Workflow-level env and the three compile jobs, as JSON: comment-only edits
# do not change it.
COMPILE_JOBS_QUERY='[.env, .jobs["turbo-compile"], .jobs["turbo-compile-musl-arm64"], .jobs["turbo-compile-windows"]]'
RUNS_LIMIT=100

origins='{}'
finish() {
	echo "origins=$origins" >> "${GITHUB_OUTPUT:-/dev/stdout}"
	exit 0
}

if [ -z "${BRANCH:-}" ] || [ -z "${GITHUB_REPOSITORY:-}" ]; then
	echo "::warning::BRANCH and GITHUB_REPOSITORY must be set; compiling everything"
	finish
fi

head="$(git rev-parse HEAD)" || finish
if ! head_jobs="$(git show "$head:$WORKFLOW_PATH" | yq -o=json "$COMPILE_JOBS_QUERY")"; then
	echo "::warning::could not read the compile jobs of $WORKFLOW_PATH; compiling everything"
	finish
fi

# The branch filter alone: combined with event=push the endpoint returns a
# stale subset of the runs (observed 2026-09-25: 21 runs, the newest a week
# old). The event is checked below instead.
if ! runs="$(gh api "repos/$GITHUB_REPOSITORY/actions/workflows/phar.yml/runs?branch=$BRANCH&per_page=$RUNS_LIMIT" \
	| jq -r --arg repo "$GITHUB_REPOSITORY" --arg branch "$BRANCH" --arg path "$WORKFLOW_PATH" '
		.workflow_runs[]
		| select(.event == "push"
			and .head_branch == $branch
			and .path == $path
			and .repository.full_name == $repo
			and .head_repository.full_name == $repo)
		| "\(.id) \(.head_sha)"')"; then
	echo "::warning::could not list the runs on $BRANCH; compiling everything"
	finish
fi

while read -r run_id sha; do
	[ -n "$run_id" ] || continue
	if ! git cat-file -e "$sha^{commit}" 2> /dev/null; then
		echo "run $run_id: $sha is not in the checkout"
		continue
	fi
	if ! git merge-base --is-ancestor "$sha" "$head"; then
		echo "run $run_id: $sha is not an ancestor of HEAD"
		continue
	fi
	if ! git diff --quiet "$sha" "$head" -- "${BUILD_INPUT_PATHS[@]}"; then
		echo "run $run_id: build inputs differ at $sha"
		continue
	fi
	if ! run_jobs="$(git show "$sha:$WORKFLOW_PATH" | yq -o=json "$COMPILE_JOBS_QUERY")" || [ "$run_jobs" != "$head_jobs" ]; then
		echo "run $run_id: compile jobs differ at $sha"
		continue
	fi
	if ! artifacts="$(gh api --paginate "repos/$GITHUB_REPOSITORY/actions/runs/$run_id/artifacts?per_page=100" \
		--jq '.artifacts[] | select(.expired | not) | .name')"; then
		echo "::warning::could not list the artifacts of run $run_id; stopping the search"
		break
	fi
	if ! origins="$(jq -c --argjson run "$run_id" --arg artifacts "$artifacts" '
		($artifacts | split("\n")) as $names
		| reduce ($names[] | select(startswith("phpstan_turbo-"))) as $name (.;
			if has($name) or ($names | any(. == "turbo-reused-" + $name)) then . else .[$name] = $run end)
		' <<< "$origins")"; then
		origins='{}'
		finish
	fi
	echo "run $run_id: identical build inputs at $sha"
done <<< "$runs"

jq -r 'to_entries[] | "\(.key) <- run \(.value)"' <<< "$origins"
finish
