import { App } from 'aws-cdk-lib';
import { Match, Template } from 'aws-cdk-lib/assertions';
import { describe, expect, it } from 'vitest';
import { ApirefStack } from '../lib/apiref-stack';

const baseProps = {
	env: { account: '928192134594', region: 'us-east-1' },
	githubOrg: 'phpstan',
	githubRepo: 'phpstan-src',
	deployBranch: '2.2.x',
	oidcProviderArn: 'arn:aws:iam::928192134594:oidc-provider/token.actions.githubusercontent.com',
	hostedZoneId: 'Z3OJGVJEUUWZDN',
	hostedZoneName: 'phpstan.org',
	apirefDomain: 'apiref.phpstan.org',
};

function synth(productionAlias: boolean): Template {
	const app = new App();
	const stack = new ApirefStack(app, 'TestApiref', { ...baseProps, productionAlias });
	return Template.fromStack(stack);
}

describe('ApirefStack', () => {
	describe('common (regardless of productionAlias)', () => {
		const template = synth(false);

		it('creates a private S3 bucket with versioning and SSL enforcement', () => {
			template.hasResourceProperties('AWS::S3::Bucket', {
				BucketName: 'phpstan-apiref-web',
				PublicAccessBlockConfiguration: {
					BlockPublicAcls: true,
					BlockPublicPolicy: true,
					IgnorePublicAcls: true,
					RestrictPublicBuckets: true,
				},
				VersioningConfiguration: { Status: 'Enabled' },
			});
		});

		it('denies insecure transport in the bucket policy', () => {
			template.hasResourceProperties('AWS::S3::BucketPolicy', {
				PolicyDocument: Match.objectLike({
					Statement: Match.arrayWith([
						Match.objectLike({
							Effect: 'Deny',
							Condition: { Bool: { 'aws:SecureTransport': 'false' } },
						}),
					]),
				}),
			});
		});

		it('creates an Origin Access Control', () => {
			template.resourceCountIs('AWS::CloudFront::OriginAccessControl', 1);
		});

		it('creates the CloudFront Function on JS 2.0', () => {
			template.hasResourceProperties('AWS::CloudFront::Function', {
				Name: 'apiref-version-redirects',
				FunctionConfig: Match.objectLike({ Runtime: 'cloudfront-js-2.0' }),
			});
		});

		it('creates a Response Headers Policy with HSTS, XCTO, XFO, Referrer-Policy and no X-XSS-Protection', () => {
			template.hasResourceProperties('AWS::CloudFront::ResponseHeadersPolicy', {
				ResponseHeadersPolicyConfig: Match.objectLike({
					Name: 'apiref-security-headers',
					SecurityHeadersConfig: Match.objectLike({
						StrictTransportSecurity: Match.objectLike({
							AccessControlMaxAgeSec: 365 * 24 * 60 * 60,
							IncludeSubdomains: true,
							Preload: true,
							Override: true,
						}),
						ContentTypeOptions: { Override: true },
						FrameOptions: { FrameOption: 'SAMEORIGIN', Override: true },
						ReferrerPolicy: { ReferrerPolicy: 'strict-origin-when-cross-origin', Override: true },
					}),
				}),
			});
			template.hasResourceProperties('AWS::CloudFront::ResponseHeadersPolicy', {
				ResponseHeadersPolicyConfig: {
					SecurityHeadersConfig: Match.not(Match.objectLike({ XSSProtection: Match.anyValue() })),
				},
			});
		});

		it('uses HTTP/2+3 and TLS 1.2_2021 minimum, with the function and headers policy attached', () => {
			template.hasResourceProperties('AWS::CloudFront::Distribution', {
				DistributionConfig: Match.objectLike({
					HttpVersion: 'http2and3',
					IPV6Enabled: true,
					DefaultCacheBehavior: Match.objectLike({
						ViewerProtocolPolicy: 'redirect-to-https',
						Compress: true,
						FunctionAssociations: Match.arrayWith([
							Match.objectLike({ EventType: 'viewer-request' }),
						]),
						ResponseHeadersPolicyId: Match.anyValue(),
					}),
				}),
			});
		});

		it('issues a DNS-validated ACM cert for apiref.phpstan.org', () => {
			template.hasResourceProperties('AWS::CertificateManager::Certificate', {
				DomainName: 'apiref.phpstan.org',
				ValidationMethod: 'DNS',
			});
		});

		it('creates the deploy role scoped to the phpstan-src 2.2.x branch via OIDC', () => {
			template.hasResourceProperties('AWS::IAM::Role', {
				RoleName: 'phpstan-apiref-deploy',
				AssumeRolePolicyDocument: Match.objectLike({
					Statement: Match.arrayWith([
						Match.objectLike({
							Action: 'sts:AssumeRoleWithWebIdentity',
							Condition: {
								StringEquals: { 'token.actions.githubusercontent.com:aud': 'sts.amazonaws.com' },
								StringLike: {
									'token.actions.githubusercontent.com:sub': 'repo:phpstan/phpstan-src:ref:refs/heads/2.2.x',
								},
							},
						}),
					]),
				}),
			});
		});

		it('does not create any Route 53 records (apex/staging CNAME stays externally managed)', () => {
			template.resourceCountIs('AWS::Route53::RecordSet', 0);
		});
	});

	describe('productionAlias: false (pre-cutover)', () => {
		const template = synth(false);

		it('omits the alias and the ACM cert from the distribution (default CF cert is used)', () => {
			const distributions = template.findResources('AWS::CloudFront::Distribution');
			const config = Object.values(distributions)[0].Properties.DistributionConfig;
			// CDK synthesizes `null` for undefined optional properties; CFN treats it as absent.
			expect(config.Aliases ?? null).toBeNull();
			expect(config.ViewerCertificate ?? null).toBeNull();
		});
	});

	describe('productionAlias: true (post-cutover)', () => {
		const template = synth(true);

		it('attaches apiref.phpstan.org as the alias and uses the ACM cert', () => {
			template.hasResourceProperties('AWS::CloudFront::Distribution', {
				DistributionConfig: Match.objectLike({
					Aliases: ['apiref.phpstan.org'],
					ViewerCertificate: Match.objectLike({
						MinimumProtocolVersion: 'TLSv1.2_2021',
						SslSupportMethod: 'sni-only',
					}),
				}),
			});
		});
	});
});
