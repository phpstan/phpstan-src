import * as core from "@actions/core";
import * as github from "@actions/github";

interface Inputs {
	github: ReturnType<typeof github.getOctokit>;
	context: typeof github.context;
	core: typeof core;
}

module.exports = async ({github, context, core}: Inputs) => {
	const commitSha = process.env.BASE_SHA;
	const artifactName = process.env.ARTIFACT_NAME;
	const workflowName = process.env.WORKFLOW_NAME;

	// Get all workflow runs for this commit
	const runs = await github.rest.actions.listWorkflowRunsForRepo({
		owner: context.repo.owner,
		repo: context.repo.repo,
		per_page: 20,
		event: "push",
		head_sha: commitSha
	});

	if (runs.data.workflow_runs.length === 0) {
		core.setFailed(`No workflow runs found for commit ${commitSha}`);
		return;
	}

	const workflowRuns = runs.data.workflow_runs;
	if (workflowRuns.length === 0) {
		core.setFailed(`No workflow runs found for commit ${commitSha}`);
		return;
	}

	let found = false;
	for (const run of workflowRuns) {
		if (run.status !== "completed" || run.conclusion !== "success") {
			continue;
		}

		if (run.name !== workflowName) {
			continue;
		}

		const artifactsResp = await github.rest.actions.listWorkflowRunArtifacts({
			owner: context.repo.owner,
			repo: context.repo.repo,
			run_id: run.id,
		});

		const artifact = artifactsResp.data.artifacts.find(a => a.name === artifactName);
		if (artifact) {
			core.setOutput("artifact_id", artifact.id.toString());
			core.setOutput("run_id", run.id.toString());
			found = true;
			break;
		}
	}

	if (!found) {
		core.setFailed(`No artifact named '${artifactName}' found for commit ${commitSha}`);
	}
}
