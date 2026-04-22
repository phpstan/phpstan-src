<?php declare(strict_types=1);

namespace OptionalPropertiesBlowup;

class OwnerModel {};
class PermissionsModel {};

final readonly class TemplateRepositoryModel implements \JsonSerializable
{
    public function __construct(
        public null|int $id = null,
        public null|string $node_id = null,
        public null|string $name = null,
        public null|string $full_name = null,
        public null|OwnerModel $owner = null,
        public null|bool $private = null,
        public null|string $html_url = null,
        public null|string $description = null,
        public null|bool $fork = null,
        public null|string $url = null,
        public null|string $archive_url = null,
        public null|string $assignees_url = null,
        public null|string $blobs_url = null,
        public null|string $branches_url = null,
        public null|string $collaborators_url = null,
        public null|string $comments_url = null,
        public null|string $commits_url = null,
        public null|string $compare_url = null,
        public null|string $contents_url = null,
        public null|string $contributors_url = null,
        public null|string $deployments_url = null,
        public null|string $downloads_url = null,
        public null|string $events_url = null,
        public null|string $forks_url = null,
        public null|string $git_commits_url = null,
        public null|string $git_refs_url = null,
        public null|string $git_tags_url = null,
        public null|string $git_url = null,
        public null|string $issue_comment_url = null,
        public null|string $issue_events_url = null,
        public null|string $issues_url = null,
        public null|string $keys_url = null,
        public null|string $labels_url = null,
        public null|string $languages_url = null,
        public null|string $merges_url = null,
        public null|string $milestones_url = null,
        public null|string $notifications_url = null,
        public null|string $pulls_url = null,
        public null|string $releases_url = null,
        public null|string $ssh_url = null,
        public null|string $stargazers_url = null,
        public null|string $statuses_url = null,
        public null|string $subscribers_url = null,
        public null|string $subscription_url = null,
        public null|string $tags_url = null,
        public null|string $teams_url = null,
        public null|string $trees_url = null,
        public null|string $clone_url = null,
        public null|string $mirror_url = null,
        public null|string $hooks_url = null,
        public null|string $svn_url = null,
        public null|string $homepage = null,
        public null|string $language = null,
        public null|int $forks_count = null,
        public null|int $stargazers_count = null,
        public null|int $watchers_count = null,
        public null|int $size = null,
        public null|string $default_branch = null,
        public null|int $open_issues_count = null,
        public null|bool $is_template = null,
        /** @var null|list<string> */
        public null|array $topics = null,
        public null|bool $has_issues = null,
        public null|bool $has_projects = null,
        public null|bool $has_wiki = null,
        public null|bool $has_pages = null,
        public null|bool $has_downloads = null,
        public null|bool $archived = null,
        public null|bool $disabled = null,
        public null|string $visibility = null,
        public null|string $pushed_at = null,
        public null|string $created_at = null,
        public null|string $updated_at = null,
        public null|PermissionsModel $permissions = null,
        public null|bool $allow_rebase_merge = null,
        public null|string $template_repository = null,
        public null|string $temp_clone_token = null,
        public null|bool $allow_squash_merge = null,
        public null|bool $delete_branch_on_merge = null,
        public null|bool $allow_merge_commit = null,
        public null|int $subscribers_count = null,
        public null|int $network_count = null,
    ) {}

    /**
     * @return array{
     *     'id'?: int,
     *     'node_id'?: string,
     *     'name'?: string,
     *     'full_name'?: string,
     *     'owner'?: OwnerModel,
     *     'private'?: bool,
     *     'html_url'?: string,
     *     'description'?: string,
     *     'fork'?: bool,
     *     'url'?: string,
     *     'archive_url'?: string,
     *     'assignees_url'?: string,
     *     'blobs_url'?: string,
     *     'branches_url'?: string,
     *     'collaborators_url'?: string,
     *     'comments_url'?: string,
     *     'commits_url'?: string,
     *     'compare_url'?: string,
     *     'contents_url'?: string,
     *     'contributors_url'?: string,
     *     'deployments_url'?: string,
     *     'downloads_url'?: string,
     *     'events_url'?: string,
     *     'forks_url'?: string,
     *     'git_commits_url'?: string,
     *     'git_refs_url'?: string,
     *     'git_tags_url'?: string,
     *     'git_url'?: string,
     *     'issue_comment_url'?: string,
     *     'issue_events_url'?: string,
     *     'issues_url'?: string,
     *     'keys_url'?: string,
     *     'labels_url'?: string,
     *     'languages_url'?: string,
     *     'merges_url'?: string,
     *     'milestones_url'?: string,
     *     'notifications_url'?: string,
     *     'pulls_url'?: string,
     *     'releases_url'?: string,
     *     'ssh_url'?: string,
     *     'stargazers_url'?: string,
     *     'statuses_url'?: string,
     *     'subscribers_url'?: string,
     *     'subscription_url'?: string,
     *     'tags_url'?: string,
     *     'teams_url'?: string,
     *     'trees_url'?: string,
     *     'clone_url'?: string,
     *     'mirror_url'?: string,
     *     'hooks_url'?: string,
     *     'svn_url'?: string,
     *     'homepage'?: string,
     *     'language'?: string,
     *     'forks_count'?: int,
     *     'stargazers_count'?: int,
     *     'watchers_count'?: int,
     *     'size'?: int,
     *     'default_branch'?: string,
     *     'open_issues_count'?: int,
     *     'is_template'?: bool,
     *     'topics'?: list<string>,
     *     'has_issues'?: bool,
     *     'has_projects'?: bool,
     *     'has_wiki'?: bool,
     *     'has_pages'?: bool,
     *     'has_downloads'?: bool,
     *     'archived'?: bool,
     *     'disabled'?: bool,
     *     'visibility'?: string,
     *     'pushed_at'?: string,
     *     'created_at'?: string,
     *     'updated_at'?: string,
     *     'permissions'?: PermissionsModel,
     *     'allow_rebase_merge'?: bool,
     *     'template_repository'?: string,
     *     'temp_clone_token'?: string,
     *     'allow_squash_merge'?: bool,
     *     'delete_branch_on_merge'?: bool,
     *     'allow_merge_commit'?: bool,
     *     'subscribers_count'?: int,
     *     'network_count'?: int,
     * }
     */
    public function jsonSerialize(): array
    {
        $properties = [];
        if ($this->id !== null) {
            $properties['id'] = $this->id;
        }
        if ($this->node_id !== null) {
            $properties['node_id'] = $this->node_id;
        }
        if ($this->name !== null) {
            $properties['name'] = $this->name;
        }
        if ($this->full_name !== null) {
            $properties['full_name'] = $this->full_name;
        }
        if ($this->owner !== null) {
            $properties['owner'] = $this->owner;
        }
        if ($this->private !== null) {
            $properties['private'] = $this->private;
        }
        if ($this->html_url !== null) {
            $properties['html_url'] = $this->html_url;
        }
        if ($this->description !== null) {
            $properties['description'] = $this->description;
        }
        if ($this->fork !== null) {
            $properties['fork'] = $this->fork;
        }
        if ($this->url !== null) {
            $properties['url'] = $this->url;
        }
        if ($this->archive_url !== null) {
            $properties['archive_url'] = $this->archive_url;
        }
        if ($this->assignees_url !== null) {
            $properties['assignees_url'] = $this->assignees_url;
        }
        if ($this->blobs_url !== null) {
            $properties['blobs_url'] = $this->blobs_url;
        }
        if ($this->branches_url !== null) {
            $properties['branches_url'] = $this->branches_url;
        }
        if ($this->collaborators_url !== null) {
            $properties['collaborators_url'] = $this->collaborators_url;
        }
        if ($this->comments_url !== null) {
            $properties['comments_url'] = $this->comments_url;
        }
        if ($this->commits_url !== null) {
            $properties['commits_url'] = $this->commits_url;
        }
        if ($this->compare_url !== null) {
            $properties['compare_url'] = $this->compare_url;
        }
        if ($this->contents_url !== null) {
            $properties['contents_url'] = $this->contents_url;
        }
        if ($this->contributors_url !== null) {
            $properties['contributors_url'] = $this->contributors_url;
        }
        if ($this->deployments_url !== null) {
            $properties['deployments_url'] = $this->deployments_url;
        }
        if ($this->downloads_url !== null) {
            $properties['downloads_url'] = $this->downloads_url;
        }
        if ($this->events_url !== null) {
            $properties['events_url'] = $this->events_url;
        }
        if ($this->forks_url !== null) {
            $properties['forks_url'] = $this->forks_url;
        }
        if ($this->git_commits_url !== null) {
            $properties['git_commits_url'] = $this->git_commits_url;
        }
        if ($this->git_refs_url !== null) {
            $properties['git_refs_url'] = $this->git_refs_url;
        }
        if ($this->git_tags_url !== null) {
            $properties['git_tags_url'] = $this->git_tags_url;
        }
        if ($this->git_url !== null) {
            $properties['git_url'] = $this->git_url;
        }
        if ($this->issue_comment_url !== null) {
            $properties['issue_comment_url'] = $this->issue_comment_url;
        }
        if ($this->issue_events_url !== null) {
            $properties['issue_events_url'] = $this->issue_events_url;
        }
        if ($this->issues_url !== null) {
            $properties['issues_url'] = $this->issues_url;
        }
        if ($this->keys_url !== null) {
            $properties['keys_url'] = $this->keys_url;
        }
        if ($this->labels_url !== null) {
            $properties['labels_url'] = $this->labels_url;
        }
        if ($this->languages_url !== null) {
            $properties['languages_url'] = $this->languages_url;
        }
        if ($this->merges_url !== null) {
            $properties['merges_url'] = $this->merges_url;
        }
        if ($this->milestones_url !== null) {
            $properties['milestones_url'] = $this->milestones_url;
        }
        if ($this->notifications_url !== null) {
            $properties['notifications_url'] = $this->notifications_url;
        }
        if ($this->pulls_url !== null) {
            $properties['pulls_url'] = $this->pulls_url;
        }
        if ($this->releases_url !== null) {
            $properties['releases_url'] = $this->releases_url;
        }
        if ($this->ssh_url !== null) {
            $properties['ssh_url'] = $this->ssh_url;
        }
        if ($this->stargazers_url !== null) {
            $properties['stargazers_url'] = $this->stargazers_url;
        }
        if ($this->statuses_url !== null) {
            $properties['statuses_url'] = $this->statuses_url;
        }
        if ($this->subscribers_url !== null) {
            $properties['subscribers_url'] = $this->subscribers_url;
        }
        if ($this->subscription_url !== null) {
            $properties['subscription_url'] = $this->subscription_url;
        }
        if ($this->tags_url !== null) {
            $properties['tags_url'] = $this->tags_url;
        }
        if ($this->teams_url !== null) {
            $properties['teams_url'] = $this->teams_url;
        }
        if ($this->trees_url !== null) {
            $properties['trees_url'] = $this->trees_url;
        }
        if ($this->clone_url !== null) {
            $properties['clone_url'] = $this->clone_url;
        }
        if ($this->mirror_url !== null) {
            $properties['mirror_url'] = $this->mirror_url;
        }
        if ($this->hooks_url !== null) {
            $properties['hooks_url'] = $this->hooks_url;
        }
        if ($this->svn_url !== null) {
            $properties['svn_url'] = $this->svn_url;
        }
        if ($this->homepage !== null) {
            $properties['homepage'] = $this->homepage;
        }
        if ($this->language !== null) {
            $properties['language'] = $this->language;
        }
        if ($this->forks_count !== null) {
            $properties['forks_count'] = $this->forks_count;
        }
        if ($this->stargazers_count !== null) {
            $properties['stargazers_count'] = $this->stargazers_count;
        }
        if ($this->watchers_count !== null) {
            $properties['watchers_count'] = $this->watchers_count;
        }
        if ($this->size !== null) {
            $properties['size'] = $this->size;
        }
        if ($this->default_branch !== null) {
            $properties['default_branch'] = $this->default_branch;
        }
        if ($this->open_issues_count !== null) {
            $properties['open_issues_count'] = $this->open_issues_count;
        }
        if ($this->is_template !== null) {
            $properties['is_template'] = $this->is_template;
        }
        if ($this->topics !== null) {
            $properties['topics'] = $this->topics;
        }
        if ($this->has_issues !== null) {
            $properties['has_issues'] = $this->has_issues;
        }
        if ($this->has_projects !== null) {
            $properties['has_projects'] = $this->has_projects;
        }
        if ($this->has_wiki !== null) {
            $properties['has_wiki'] = $this->has_wiki;
        }
        if ($this->has_pages !== null) {
            $properties['has_pages'] = $this->has_pages;
        }
        if ($this->has_downloads !== null) {
            $properties['has_downloads'] = $this->has_downloads;
        }
        if ($this->archived !== null) {
            $properties['archived'] = $this->archived;
        }
        if ($this->disabled !== null) {
            $properties['disabled'] = $this->disabled;
        }
        if ($this->visibility !== null) {
            $properties['visibility'] = $this->visibility;
        }
        if ($this->pushed_at !== null) {
            $properties['pushed_at'] = $this->pushed_at;
        }
        if ($this->created_at !== null) {
            $properties['created_at'] = $this->created_at;
        }
        if ($this->updated_at !== null) {
            $properties['updated_at'] = $this->updated_at;
        }
        if ($this->permissions !== null) {
            $properties['permissions'] = $this->permissions;
        }
        if ($this->allow_rebase_merge !== null) {
            $properties['allow_rebase_merge'] = $this->allow_rebase_merge;
        }
        if ($this->template_repository !== null) {
            $properties['template_repository'] = $this->template_repository;
        }
        if ($this->temp_clone_token !== null) {
            $properties['temp_clone_token'] = $this->temp_clone_token;
        }
        if ($this->allow_squash_merge !== null) {
            $properties['allow_squash_merge'] = $this->allow_squash_merge;
        }
        if ($this->delete_branch_on_merge !== null) {
            $properties['delete_branch_on_merge'] = $this->delete_branch_on_merge;
        }
        if ($this->allow_merge_commit !== null) {
            $properties['allow_merge_commit'] = $this->allow_merge_commit;
        }
        if ($this->subscribers_count !== null) {
            $properties['subscribers_count'] = $this->subscribers_count;
        }
        if ($this->network_count !== null) {
            $properties['network_count'] = $this->network_count;
        }
        return $properties;
    }
}
