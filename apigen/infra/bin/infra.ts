#!/usr/bin/env node
import * as cdk from 'aws-cdk-lib';
import { ApirefStack } from '../lib/apiref-stack';
import { OidcRolesStack } from '../lib/oidc-roles-stack';

const app = new cdk.App();

const account = process.env.CDK_DEFAULT_ACCOUNT ?? '928192134594';
const region = 'us-east-1';
const env = { account, region };

const githubOrg = 'phpstan';
const githubRepo = 'phpstan-src';
const deployBranch = '2.2.x';

// Account-wide OIDC provider, created originally in the phpstan-dist repo's
// CDK app. We reference it by ARN — never instantiate a new one, IAM rejects
// duplicates of the same provider URL.
const oidcProviderArn = `arn:aws:iam::${account}:oidc-provider/token.actions.githubusercontent.com`;

const hostedZoneId = 'Z3OJGVJEUUWZDN';
const hostedZoneName = 'phpstan.org';
const apirefDomain = 'apiref.phpstan.org';

const productionAlias = app.node.tryGetContext('productionAlias') === true;

new OidcRolesStack(app, 'PhpstanApirefOidcRoles', {
	env,
	githubOrg,
	githubRepo,
	deployBranch,
	oidcProviderArn,
	description: 'IAM role for the apiref-infra GitHub Actions workflow (OIDC).',
});

new ApirefStack(app, 'PhpstanApirefWebsite', {
	env,
	githubOrg,
	githubRepo,
	deployBranch,
	oidcProviderArn,
	hostedZoneId,
	hostedZoneName,
	apirefDomain,
	productionAlias,
	description: `apiref.phpstan.org website (S3 + CloudFront + CF Function). productionAlias=${productionAlias}`,
});

app.synth();
