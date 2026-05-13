import * as cdk from 'aws-cdk-lib';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as cloudfront from 'aws-cdk-lib/aws-cloudfront';
import * as origins from 'aws-cdk-lib/aws-cloudfront-origins';
import * as iam from 'aws-cdk-lib/aws-iam';
import * as route53 from 'aws-cdk-lib/aws-route53';
import * as s3 from 'aws-cdk-lib/aws-s3';
import { Construct } from 'constructs';
import * as path from 'node:path';

export interface ApirefStackProps extends cdk.StackProps {
	readonly githubOrg: string;
	readonly githubRepo: string;
	readonly deployBranch: string;
	readonly oidcProviderArn: string;
	readonly hostedZoneId: string;
	readonly hostedZoneName: string;
	readonly apirefDomain: string;
	readonly productionAlias: boolean;
}

// The apiref.phpstan.org infrastructure: private S3 bucket served via
// CloudFront with OAC, a CloudFront Function 2.0 for per-version landing-page
// redirects, a Response Headers Policy for security headers, an ACM cert
// (DNS-validated), and the IAM role used by the apiref.yml workflow.
//
// The `productionAlias` flag toggles whether `apiref.phpstan.org` is attached
// to the distribution. We start at `false` so the first deploy succeeds while
// the alias still lives on the legacy distribution. After the cutover script
// moves the alias, we flip to `true` so CDK code matches reality.
//
// Route 53: the `apiref.phpstan.org` CNAME is created and managed out-of-band
// during the cutover (matches the apex/www pattern from the main site).
export class ApirefStack extends cdk.Stack {
	readonly bucket: s3.Bucket;
	readonly distribution: cloudfront.Distribution;
	readonly deployRole: iam.Role;

	constructor(scope: Construct, id: string, props: ApirefStackProps) {
		super(scope, id, props);

		this.bucket = new s3.Bucket(this, 'ApirefBucket', {
			bucketName: 'phpstan-apiref-web',
			blockPublicAccess: s3.BlockPublicAccess.BLOCK_ALL,
			encryption: s3.BucketEncryption.S3_MANAGED,
			versioned: true,
			removalPolicy: cdk.RemovalPolicy.RETAIN,
			enforceSSL: true,
		});

		const hostedZone = route53.HostedZone.fromHostedZoneAttributes(this, 'HostedZone', {
			hostedZoneId: props.hostedZoneId,
			zoneName: props.hostedZoneName,
		});

		const certificate = new acm.Certificate(this, 'Certificate', {
			domainName: props.apirefDomain,
			validation: acm.CertificateValidation.fromDns(hostedZone),
		});

		const edgeFunction = new cloudfront.Function(this, 'VersionRedirectsFunction', {
			functionName: 'apiref-version-redirects',
			comment: 'Viewer-request: per-version landing-page redirects for apiref.phpstan.org.',
			runtime: cloudfront.FunctionRuntime.JS_2_0,
			code: cloudfront.FunctionCode.fromFile({
				filePath: path.join(__dirname, '..', 'functions', 'apiref-version-redirects.js'),
			}),
		});

		const responseHeadersPolicy = new cloudfront.ResponseHeadersPolicy(this, 'SecurityHeadersPolicy', {
			responseHeadersPolicyName: 'apiref-security-headers',
			comment: 'HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy for apiref.phpstan.org.',
			securityHeadersBehavior: {
				strictTransportSecurity: {
					accessControlMaxAge: cdk.Duration.days(365),
					includeSubdomains: true,
					preload: true,
					override: true,
				},
				contentTypeOptions: { override: true },
				frameOptions: {
					frameOption: cloudfront.HeadersFrameOption.SAMEORIGIN,
					override: true,
				},
				referrerPolicy: {
					referrerPolicy: cloudfront.HeadersReferrerPolicy.STRICT_ORIGIN_WHEN_CROSS_ORIGIN,
					override: true,
				},
			},
		});

		const domainNames = props.productionAlias ? [props.apirefDomain] : undefined;
		const distributionCertificate = props.productionAlias ? certificate : undefined;

		this.distribution = new cloudfront.Distribution(this, 'Distribution', {
			comment: `apiref.phpstan.org (productionAlias=${props.productionAlias})`,
			domainNames,
			certificate: distributionCertificate,
			defaultRootObject: 'index.html',
			minimumProtocolVersion: cloudfront.SecurityPolicyProtocol.TLS_V1_2_2021,
			priceClass: cloudfront.PriceClass.PRICE_CLASS_100,
			httpVersion: cloudfront.HttpVersion.HTTP2_AND_3,
			enableIpv6: true,
			defaultBehavior: {
				origin: origins.S3BucketOrigin.withOriginAccessControl(this.bucket),
				viewerProtocolPolicy: cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
				allowedMethods: cloudfront.AllowedMethods.ALLOW_GET_HEAD,
				cachedMethods: cloudfront.CachedMethods.CACHE_GET_HEAD,
				compress: true,
				cachePolicy: cloudfront.CachePolicy.CACHING_OPTIMIZED,
				responseHeadersPolicy,
				functionAssociations: [{
					function: edgeFunction,
					eventType: cloudfront.FunctionEventType.VIEWER_REQUEST,
				}],
			},
		});

		this.deployRole = this.createDeployRole(props);

		new cdk.CfnOutput(this, 'BucketName', {
			value: this.bucket.bucketName,
			description: 'S3 bucket name for the apiref content',
			exportName: 'PhpstanApirefBucketName',
		});
		new cdk.CfnOutput(this, 'DistributionId', {
			value: this.distribution.distributionId,
			description: 'CloudFront distribution ID for apiref',
			exportName: 'PhpstanApirefDistributionId',
		});
		new cdk.CfnOutput(this, 'DistributionDomain', {
			value: this.distribution.distributionDomainName,
			description: 'CloudFront default domain (used for pre-cutover testing)',
		});
		new cdk.CfnOutput(this, 'DeployRoleArn', {
			value: this.deployRole.roleArn,
			description: 'Role ARN for the apiref content GitHub Actions workflow',
			exportName: 'PhpstanApirefDeployRoleArn',
		});
		new cdk.CfnOutput(this, 'CertificateArn', {
			value: certificate.certificateArn,
			description: 'ACM cert for apiref.phpstan.org (attached only when productionAlias=true)',
		});
	}

	private createDeployRole(props: ApirefStackProps): iam.Role {
		const role = new iam.Role(this, 'DeployRole', {
			roleName: 'phpstan-apiref-deploy',
			description: 'Assumed by the apiref.yml GitHub Actions workflow to sync the bucket and invalidate CloudFront.',
			assumedBy: new iam.FederatedPrincipal(
				props.oidcProviderArn,
				{
					StringEquals: {
						'token.actions.githubusercontent.com:aud': 'sts.amazonaws.com',
					},
					StringLike: {
						'token.actions.githubusercontent.com:sub': `repo:${props.githubOrg}/${props.githubRepo}:ref:refs/heads/${props.deployBranch}`,
					},
				},
				'sts:AssumeRoleWithWebIdentity',
			),
			maxSessionDuration: cdk.Duration.hours(1),
		});

		this.bucket.grantReadWrite(role);
		this.bucket.grantDelete(role);

		role.addToPolicy(new iam.PolicyStatement({
			actions: [
				'cloudfront:CreateInvalidation',
				'cloudfront:GetInvalidation',
				'cloudfront:ListInvalidations',
			],
			resources: [
				`arn:aws:cloudfront::${this.account}:distribution/${this.distribution.distributionId}`,
			],
		}));

		return role;
	}
}
