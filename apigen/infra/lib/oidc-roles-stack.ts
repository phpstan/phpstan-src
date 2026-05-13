import * as cdk from 'aws-cdk-lib';
import * as iam from 'aws-cdk-lib/aws-iam';
import { Construct } from 'constructs';

export interface OidcRolesStackProps extends cdk.StackProps {
	readonly githubOrg: string;
	readonly githubRepo: string;
	readonly deployBranch: string;
	readonly oidcProviderArn: string;
}

// IAM role used by the apiref-infra GitHub Actions workflow to run
// `cdk diff` / `cdk deploy`. Reuses the account-wide OIDC provider that
// already exists (created by the phpstan-dist repo's CDK app); we do NOT
// create a new `OpenIdConnectProvider` because IAM rejects duplicates.
export class OidcRolesStack extends cdk.Stack {
	readonly infraDeployRole: iam.Role;

	constructor(scope: Construct, id: string, props: OidcRolesStackProps) {
		super(scope, id, props);

		const subjectPrefix = `repo:${props.githubOrg}/${props.githubRepo}`;
		const allowedSubjects = [
			`${subjectPrefix}:ref:refs/heads/${props.deployBranch}`,
			`${subjectPrefix}:pull_request`,
		];

		this.infraDeployRole = new iam.Role(this, 'InfraDeployRole', {
			roleName: 'phpstan-apiref-infra-deploy',
			description: 'Assumed by the apiref-infra GitHub Actions workflow to run cdk diff / cdk deploy.',
			assumedBy: new iam.FederatedPrincipal(
				props.oidcProviderArn,
				{
					StringEquals: {
						'token.actions.githubusercontent.com:aud': 'sts.amazonaws.com',
					},
					StringLike: {
						'token.actions.githubusercontent.com:sub': allowedSubjects,
					},
				},
				'sts:AssumeRoleWithWebIdentity',
			),
			maxSessionDuration: cdk.Duration.hours(1),
		});

		this.infraDeployRole.addToPolicy(new iam.PolicyStatement({
			actions: ['sts:AssumeRole'],
			resources: [`arn:aws:iam::${this.account}:role/cdk-*`],
		}));

		new cdk.CfnOutput(this, 'InfraDeployRoleArn', {
			value: this.infraDeployRole.roleArn,
			description: 'Role ARN for the apiref-infra GitHub Actions workflow',
			exportName: 'PhpstanApirefInfraDeployRoleArn',
		});
	}
}
