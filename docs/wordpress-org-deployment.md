# Deploying DynaCat to WordPress.org

GitHub is the source of truth for DynaCat. The manually triggered maintenance
workflow is a release workflow: it tests the plugin against the latest stable
WordPress, updates the release metadata, creates a Git commit and tag, builds an
installable ZIP, and deploys the same package to WordPress.org.

## One-time repository setup

1. Confirm that the WordPress.org account has commit access to the `dynacat`
   plugin repository.
2. In the GitHub repository, open **Settings → Secrets and variables → Actions**
   and add these repository secrets:
   - `SVN_USERNAME`: the WordPress.org username with commit access.
   - `SVN_PASSWORD`: that account's WordPress.org password.
3. Keep the plugin slug as `dynacat`. The workflow publishes to that
   WordPress.org SVN repository.
4. Ensure GitHub Actions has permission to write repository contents and that
   the `main` branch rules allow this workflow to push the generated release
   commit and tag.

Use a dedicated WordPress.org release account where possible. Never add either
credential to the workflow file or commit it to Git.

## Prepare a release

1. Make any plugin changes in a pull request, obtain approval, and merge them to
   `main`.
2. Choose the next plugin version. It must be greater than the current `Version`
   and must not already exist as a Git or WordPress.org SVN tag.
3. Do not edit `Version`, `Stable tag`, `Tested up to`, or the changelog manually;
   the workflow updates them only after the compatibility tests pass.

## Validate and deploy

1. Open **Actions → Engage Web Plugin Maintenance → Run workflow**.
2. Select `main` in **Use workflow from**.
3. Enter the new plugin version in **New plugin version**.
4. Select **Test, version, tag, and deploy this release to WordPress.org**.
5. Select **Run workflow** and review every job result.

The workflow installs the test dependencies, starts the latest stable WordPress,
runs the functional tests, and reads that installation's WordPress version. Once
the tests pass, it sets `Version` and `Stable tag` to the requested plugin
version, sets `Tested up to` to the tested WordPress major/minor version, and adds
a changelog entry.

The updated package then passes WordPress Plugin Check. The workflow creates
`dynacat.zip` and uploads it as an artifact retained for 14 days, commits the
metadata to `main`, creates the matching Git tag, and publishes `trunk` plus the
new version tag to the WordPress.org SVN repository. The deployment checks out
`trunk` directly, commits its synchronized contents, and then creates the tag
from the committed remote trunk. This avoids depending on a sparse local
checkout when creating the release tag. The package and deployment steps reject
any `node_modules` directory. During deployment, files removed from the clean
package are also scheduled for deletion in Subversion. A legacy `node_modules`
tree is scheduled for deletion before package synchronization, while it still
exists in the working copy, so Subversion can remove the directory as one tree
instead of failing while processing thousands of individually missing paths.
Any failure stops later steps, so a failed compatibility test cannot update
metadata or deploy.

The workflow only runs its release job from `main`, and the confirmation checkbox
must be selected. It cannot be used as a check-only workflow.

## Verify the release

After a successful workflow run:

1. Review the workflow log for the WordPress.org SVN revision and tag.
2. Check the public DynaCat page and download package on WordPress.org after its
   caches update.
3. Install the published package on a clean WordPress site and confirm that it
   activates without warnings.
4. Edit a post and confirm that the category selector appears, category search
   returns matches, a category can be selected, and the selection remains after
   saving.

Do not retry deployment from a different commit using the same version tag. If
the published artifact needs a code change, prepare and review a new release.
