#!/usr/bin/env bash
#
# deploy-to-wordpress.sh
# -----------------------
# Publish the current git `main` of AI Story Maker to the WordPress.org plugin
# SVN repository: syncs trunk, commits it, and creates a matching version tag.
#
# The version is read automatically from the plugin header in ai-story-maker.php,
# so make sure you have already bumped the version (header, AISTMA_VERSION
# constant, readme.txt "Stable tag" + changelog) and merged it to main BEFORE
# running this.
#
# Auth: the FIRST time you ever push, run an `svn commit` interactively once in
# your Terminal so macOS caches your wordpress.org credential in the Keychain.
# After that this script runs non-interactively.
#
# Usage:
#   ./deploy-to-wordpress.sh            # dry-run preview (no commits)
#   ./deploy-to-wordpress.sh --push     # actually commit trunk + create the tag
#
set -euo pipefail

# ----------------------------------------------------------------------------
# Configuration — adjust paths only if your local layout changes.
# ----------------------------------------------------------------------------
GIT_DIR="/Users/hayan/Documents/Documents - HALMAMOUN-MBP/repos/plugins/ai-story-maker"
SVN_DIR="/Users/hayan/Documents/Documents - HALMAMOUN-MBP/repos/projects/ai-story-maker/svn/ai-story-maker"
SVN_USER="hmamoun"
MAIN_PLUGIN_FILE="ai-story-maker.php"

# Files/dirs that live in git but must NEVER ship to WordPress.org.
EXCLUDES=(
  ".svn" ".git" ".github" ".gitignore" ".eslintrc.cjs" ".phpcs.xml"
  "README.md" "ai_plugins_comparison.md" "eslint.config.js"
  "package.json" "package-lock.json" "plugin-script-package.sh"
  "deploy-to-wordpress.sh" "tests" "node_modules" "vendor"
)

# ----------------------------------------------------------------------------
PUSH=false
[[ "${1:-}" == "--push" ]] && PUSH=true

say()  { printf "\n\033[1;34m==>\033[0m %s\n" "$*"; }
warn() { printf "\033[1;33m[warn]\033[0m %s\n" "$*"; }
die()  { printf "\033[1;31m[error]\033[0m %s\n" "$*" >&2; exit 1; }

[[ -d "$GIT_DIR/.git" ]] || die "git repo not found at: $GIT_DIR"
[[ -d "$SVN_DIR/.svn" ]] || die "svn working copy not found at: $SVN_DIR"

# ----------------------------------------------------------------------------
# 1. Make sure git main is the source of truth and is clean & up to date.
# ----------------------------------------------------------------------------
say "Checking git main is clean and current"
cd "$GIT_DIR"
branch=$(git rev-parse --abbrev-ref HEAD)
[[ "$branch" == "main" ]] || die "You are on '$branch', not 'main'. Checkout main first."
# Only TRACKED modifications block a deploy — git archive ships tracked files
# only, so untracked local files (marketing scratch, etc.) can't leak. Warn on
# them so a genuinely-new-but-uncommitted file doesn't silently miss the release.
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  die "git has uncommitted changes to tracked files. Commit or stash first."
fi
untracked=$(git status --porcelain --untracked-files=normal | grep '^??' || true)
if [[ -n "$untracked" ]]; then
  warn "Untracked files present (will NOT ship — only tracked files are exported):"
  echo "$untracked" | sed 's/^/    /'
fi
git fetch origin --quiet
if [[ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ]]; then
  die "Local main differs from origin/main. Run 'git pull' first."
fi

# ----------------------------------------------------------------------------
# 2. Determine the release version from the plugin header.
# ----------------------------------------------------------------------------
VERSION=$(grep -m1 -E "^[[:space:]]*\*[[:space:]]*Version:" "$GIT_DIR/$MAIN_PLUGIN_FILE" \
            | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
[[ -n "$VERSION" ]] || die "Could not read Version from $MAIN_PLUGIN_FILE header."

STABLE=$(grep -m1 "Stable tag:" "$GIT_DIR/readme.txt" | sed -E 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]')
[[ "$STABLE" == "$VERSION" ]] || die "Mismatch: header Version=$VERSION but readme Stable tag=$STABLE. Fix in git first."

say "Releasing version: $VERSION"

# Abort if this tag already exists on the server.
if svn ls "https://plugins.svn.wordpress.org/ai-story-maker/tags/$VERSION" >/dev/null 2>&1; then
  die "tags/$VERSION already exists on WordPress.org. Bump the version or pick a new one."
fi

# ----------------------------------------------------------------------------
# 3. Refresh the SVN working copy.
# ----------------------------------------------------------------------------
say "svn update (pull latest from WordPress.org)"
svn update "$SVN_DIR" --non-interactive

# ----------------------------------------------------------------------------
# 4. Export git-tracked files only (no untracked junk) and rsync into trunk.
# ----------------------------------------------------------------------------
say "Exporting git-tracked files and syncing into trunk"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
git -C "$GIT_DIR" archive --format=tar HEAD | tar -x -C "$TMP"

rsync_excludes=()
for e in "${EXCLUDES[@]}"; do rsync_excludes+=( --exclude="$e" ); done
rsync -rc --delete "${rsync_excludes[@]}" "$TMP/" "$SVN_DIR/trunk/"

# ----------------------------------------------------------------------------
# 5. Stage adds (new files) and deletes (removed files) in trunk.
# ----------------------------------------------------------------------------
say "Staging adds/deletes in trunk"
cd "$SVN_DIR/trunk"
# Schedule unversioned files for addition.
svn add --force . --quiet 2>/dev/null || true
# Schedule missing files for deletion.
svn status | awk '/^!/{ $1=""; sub(/^[ \t]+/,""); print }' | while IFS= read -r f; do
  [[ -n "$f" ]] && svn delete --force "$f" >/dev/null 2>&1 || true
done

say "Pending trunk changes:"
svn status "$SVN_DIR/trunk" || true

# ----------------------------------------------------------------------------
# 6. Commit trunk + create the tag (only with --push).
# ----------------------------------------------------------------------------
if ! $PUSH; then
  warn "Dry run complete. Review the changes above."
  warn "Re-run with --push to commit trunk and create tags/$VERSION."
  exit 0
fi

say "Committing trunk to WordPress.org"
svn commit "$SVN_DIR/trunk" \
  -m "Update trunk to version $VERSION" \
  --username "$SVN_USER" --non-interactive

say "Creating tags/$VERSION (server-side copy)"
svn copy \
  "https://plugins.svn.wordpress.org/ai-story-maker/trunk" \
  "https://plugins.svn.wordpress.org/ai-story-maker/tags/$VERSION" \
  -m "Tagging version $VERSION" \
  --username "$SVN_USER" --non-interactive

say "svn update (pull the new tag into the working copy)"
svn update "$SVN_DIR" --non-interactive >/dev/null

# ----------------------------------------------------------------------------
# 7. Verify.
# ----------------------------------------------------------------------------
say "Verifying remote consistency"
remote_trunk_stable=$(svn cat "https://plugins.svn.wordpress.org/ai-story-maker/trunk/readme.txt" | grep -m1 "Stable tag:" | sed -E 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]')
remote_tag_ver=$(svn cat "https://plugins.svn.wordpress.org/ai-story-maker/tags/$VERSION/$MAIN_PLUGIN_FILE" | grep -m1 -E "Version:" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')

printf "  trunk Stable tag : %s\n" "$remote_trunk_stable"
printf "  tags/%s header  : %s\n" "$VERSION" "$remote_tag_ver"

if [[ "$remote_trunk_stable" == "$VERSION" && "$remote_tag_ver" == "$VERSION" ]]; then
  say "✅ Released $VERSION to WordPress.org. Update notice will roll out shortly."
else
  die "Post-deploy verification mismatch — inspect manually."
fi
