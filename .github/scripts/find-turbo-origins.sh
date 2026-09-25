#!/usr/bin/env bash
# Finds, for every turbo extension binary the compile jobs in phar.yml
# produce, the newest earlier run on $BRANCH that compiled it from build
# inputs identical to HEAD's, and writes {"<artifact name>": <run id>} to
# the `origins` step output. A compile leg whose artifact is listed
# downloads it from that run instead of building it again (see the
# turbo-origins job in phar.yml).
#
# The search walks HEAD's first-parent history (on a pull request's merge
# commit, the base branch) and asks the API for the runs of each commit
# that qualifies by git alone. Listing the workflow's runs filtered by
# branch instead cannot be trusted: that list is sometimes stale (observed
# 2026-09-25 with and without event=push: the newest run a week old), and a
# stale list silently means every leg compiles.
#
# Only these runs qualify, each condition checked here rather than trusted
# from the API query:
# - runs of .github/workflows/phar.yml in this repository, triggered by a
#   push to $BRANCH — never a pull request, whose code nobody reviewed yet
#   (a fork's branch can be named $BRANCH too, hence the event and head
#   repository checks) — other than this run itself
# - whose head commit is on HEAD's first-parent history
# - whose build inputs (BUILD_INPUT_PATHS, and the compile jobs' definitions
#   in phar.yml) are identical to HEAD's
# - whose last commit touching turbo-ext/src is HEAD's: the binaries bake
#   their version from it, and a reused leg does not load its binary to
#   check that (a change reverted in between leaves the sources identical)
# - for each artifact separately: the run uploaded it (the upload is the
#   last step of a compile leg, after the version check) and did not
#   mark it as reused itself (turbo-reused-<artifact name>) — so a binary is
#   always taken from the run that compiled it, never from a chain of runs
#   that passed it along, and it stops being reused when that run's
#   artifacts expire.
#
# Anything unexpected (an API error, a malformed response) ends the search;
# nothing here fails the job, since an empty map only means every leg
# compiles.

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
# Commits whose runs are looked up through the API, newest first. The
# commits git alone rules out do not count.
COMMITS_LIMIT=50

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
head_version="$(git log -1 --format=%H "$head" -- turbo-ext/src)" || finish
if ! head_jobs="$(git show "$head:$WORKFLOW_PATH" | yq -o=json "$COMPILE_JOBS_QUERY")"; then
	echo "::warning::could not read the compile jobs of $WORKFLOW_PATH; compiling everything"
	finish
fi
if ! commits="$(git rev-list --first-parent "$head")"; then
	echo "::warning::could not walk the history of HEAD; compiling everything"
	finish
fi

looked_up=0
while read -r sha; do
	# The version only moves forward along the history, so no older commit
	# can have HEAD's once this one has another.
	if [ "$(git log -1 --format=%H "$sha" -- turbo-ext/src)" != "$head_version" ]; then
		echo "commit $sha: the last commit touching turbo-ext/src differs; stopping the search"
		break
	fi
	if ! git diff --quiet "$sha" "$head" -- "${BUILD_INPUT_PATHS[@]}"; then
		echo "commit $sha: build inputs differ"
		continue
	fi
	if ! sha_jobs="$(git show "$sha:$WORKFLOW_PATH" | yq -o=json "$COMPILE_JOBS_QUERY")" || [ "$sha_jobs" != "$head_jobs" ]; then
		echo "commit $sha: compile jobs differ"
		continue
	fi
	if [ "$looked_up" -ge "$COMMITS_LIMIT" ]; then
		echo "looked up the runs of $COMMITS_LIMIT commits; stopping the search"
		break
	fi
	looked_up=$((looked_up + 1))
	if ! runs="$(gh api "repos/$GITHUB_REPOSITORY/actions/workflows/phar.yml/runs?head_sha=$sha&per_page=100" \
		| jq -r --arg repo "$GITHUB_REPOSITORY" --arg branch "$BRANCH" --arg path "$WORKFLOW_PATH" --arg current "${GITHUB_RUN_ID:-}" '
			[.workflow_runs[]
				| select(.event == "push"
					and .head_branch == $branch
					and .path == $path
					and .repository.full_name == $repo
					and .head_repository.full_name == $repo
					and (.id | tostring) != $current)
				| .id]
			| sort | reverse | .[]')"; then
		echo "::warning::could not list the runs of $sha; stopping the search"
		break
	fi
	if [ -z "$runs" ]; then
		echo "commit $sha: no run pushed to $BRANCH"
		continue
	fi
	while read -r run_id; do
		if ! artifacts="$(gh api --paginate "repos/$GITHUB_REPOSITORY/actions/runs/$run_id/artifacts?per_page=100" \
			--jq '.artifacts[] | select(.expired | not) | .name')"; then
			echo "::warning::could not list the artifacts of run $run_id; stopping the search"
			break 2
		fi
		if ! origins="$(jq -c --argjson run "$run_id" --arg artifacts "$artifacts" '
			($artifacts | split("\n")) as $names
			| reduce ($names[] | select(startswith("phpstan_turbo-"))) as $name (.;
				if has($name) or ($names | any(. == "turbo-reused-" + $name)) then . else .[$name] = $run end)
			' <<< "$origins")"; then
			origins='{}'
			finish
		fi
		echo "commit $sha: run $run_id has identical build inputs"
	done <<< "$runs"
done <<< "$commits"

jq -r 'to_entries[] | "\(.key) <- run \(.value)"' <<< "$origins"
finish
