# Deploying DynaCat to WordPress.org

GitHub is the source of truth for DynaCat. The maintenance workflow validates the
plugin and only deploys when it is manually run against a Git tag. A run against
a branch performs the checks but deliberately skips deployment.

## One-time repository setup

1. Confirm that the WordPress.org account has commit access to the `dynacat`
   plugin repository.
2. In the GitHub repository, open **Settings → Secrets and variables → Actions**
   and add these repository secrets:
   - `SVN_USERNAME`: the WordPress.org username with commit access.
   - `SVN_PASSWORD`: that account's WordPress.org password.
3. Keep the plugin slug as `dynacat`. The workflow passes this slug to the
   WordPress.org deploy action.

Use a dedicated WordPress.org release account where possible. Never add either
credential to the workflow file or commit it to Git.

## Prepare a release

1. Make the release changes in a pull request and obtain approval.
2. Update the `Version` header in `dynacat-plugin.php` and the `Stable tag` in
   `readme.txt` to the same version.
3. Add that version to the changelog. Only update `Tested up to` after testing
   successfully against that WordPress version.
4. Merge the approved pull request into the default branch.
5. Create and push a Git tag matching the release version. For example, for
   version `1.34`:

   ```sh
   git switch main
   git pull --ff-only
   git tag -a 1.34 -m "DynaCat 1.34"
   git push origin 1.34
   ```

Pushing the tag does not deploy the plugin by itself. It makes the immutable
release ref available for the explicitly triggered workflow.

## Validate and deploy

1. Open **Actions → Engage Web Plugin Maintenance → Run workflow**.
2. In the **Use workflow from** selector, choose the release tag, not `main` or
   another branch.
3. Select **Run workflow** and review every job result.

The workflow installs the test dependencies, starts the latest stable WordPress,
runs the functional tests, builds a clean package, and runs WordPress Plugin
Check. Only after those steps pass does the WordPress.org deploy action run. It
uses `.distignore` to keep development files out of the published plugin.

If the workflow is run from a branch, the deployment step is skipped and the
workflow prints an explanation. Re-run the workflow using the approved release
tag when deployment is intended.

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
