#!/usr/bin/env bash
#
# deploy-to-wordpress.sh
# -----------------------
# Publish the current git `main` to WordPress.org SVN following the official
# plugin handbook: https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
#
# Official flow (single commit — tag is NEVER touched after creation):
#   1. svn up
#   2. Sync git-tracked files → trunk/
#   3. svn add / svn delete to stage changes in trunk
#   4. svn cp trunk tags/VERSION   (LOCAL copy — no server round-trip yet)
#   5. svn ci                      (ONE commit covers trunk + new tag together)
#
# Prerequisites:
#   - Version bumped in ai-story-maker.php header, AISTMA_VERSION constant,
#     readme.txt Stable tag + changelog, README.md — all merged to main.
#   - SVN credentials cached in macOS Keychain (run one interactive svn commit
#     to seed it, then all subsequent commits work non-interactively).
#
# Usage:
#   ./deploy-to-wordpress.sh            # dry-run: preview changes, no commits
#   ./deploy-to-wordpress.sh --push     # commit trunk + tag in one SVN commit
#
set -euo pipefail

# ----------------------------------------------------------------------------
# Configuration
# ----------------------------------------------------------------------------
GIT_DIR="/Users/hayan/Documents/Documents - HALMAMOUN-MBP/repos/plugins/ai-story-maker"
SVN_DIR="/Users/hayan/Documents/Documents - HALMAMOUN-MBP/repos/projects/ai-story-maker/svn/ai-story-maker"
SVN_USER="hmamoun"
PLUGIN_FILE="ai-story-maker.php"

# Files/dirs in git that must NEVER ship to WordPress.org
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
# 1. Verify git main is clean and up to date
# ----------------------------------------------------------------------------
say "Checking git main is clean and current"
cd "$GIT_DIR"
branch=$(git rev-parse --abbrev-ref HEAD)
[[ "$branch" == "main" ]] || die "You are on '$branch', not 'main'. Checkout main first."

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  die "git has uncommitted changes to tracked files. Commit or stash first."
fi

untracked=$(git status --porcelain --untracked-files=normal | grep '^??' || true)
if [[ -n "$untracked" ]]; then
  warn "Untracked files present (will NOT ship — git archive exports tracked files only):"
  echo "$untracked" | sed 's/^/    /'
fi

git fetch origin --quiet
[[ "$(git rev-parse HEAD)" == "$(git rev-parse origin/main)" ]] || \
  die "Local main differs from origin/main. Run 'git pull' first."

# ----------------------------------------------------------------------------
# 2. Read and validate version
# ----------------------------------------------------------------------------
VERSION=$(grep -m1 -E "^[[:space:]]*\*[[:space:]]*Version:" "$GIT_DIR/$PLUGIN_FILE" \
            | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
[[ -n "$VERSION" ]] || die "Could not read Version from $PLUGIN_FILE header."

STABLE=$(grep -m1 "Stable tag:" "$GIT_DIR/readme.txt" \
           | sed -E 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]')
[[ "$STABLE" == "$VERSION" ]] || \
  die "Mismatch: plugin header Version=$VERSION but readme.txt Stable tag=$STABLE. Fix in git first."

say "Releasing version: $VERSION"

# Guard: abort if tag already exists — NEVER modify an existing tag
if svn ls "https://plugins.svn.wordpress.org/ai-story-maker/tags/$VERSION" >/dev/null 2>&1; then
  die "tags/$VERSION already exists on WordPress.org. A published tag must never be modified. Bump the version."
fi

# ----------------------------------------------------------------------------
# 3. svn up — get latest from WordPress.org
# ----------------------------------------------------------------------------
say "svn update"
svn update "$SVN_DIR" --non-interactive

# ----------------------------------------------------------------------------
# 4. Export only git-tracked files into trunk (no untracked junk)
# ----------------------------------------------------------------------------
say "Syncing git-tracked files into trunk"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
git -C "$GIT_DIR" archive --format=tar HEAD | tar -x -C "$TMP"

rsync_excludes=()
for e in "${EXCLUDES[@]}"; do rsync_excludes+=( --exclude="$e" ); done
rsync -rc --delete "${rsync_excludes[@]}" "$TMP/" "$SVN_DIR/trunk/"

# ----------------------------------------------------------------------------
# 5. Stage adds and deletes — scoped to trunk/ only
#    (old tags on macOS show case-sensitivity ! artifacts — never touch those)
# ----------------------------------------------------------------------------
say "Staging adds/deletes in trunk"
cd "$SVN_DIR/trunk"
svn add --force . --quiet 2>/dev/null || true
svn status . | awk '/^!/{ $1=""; sub(/^[ \t]+/,""); print }' | while IFS= read -r f; do
  [[ -n "$f" ]] && svn delete --force "$f" >/dev/null 2>&1 || true
done

say "Pending trunk changes:"
svn status "$SVN_DIR/trunk" || true

# ----------------------------------------------------------------------------
# 6. (--push only) LOCAL copy trunk → tags/VERSION, then ONE svn commit
#    Official handbook: svn cp trunk tags/X.X  →  svn ci
#    This is a single atomic commit — the tag is created clean and never
#    touched again, which is required for WP.org to generate the zip.
# ----------------------------------------------------------------------------
if ! $PUSH; then
  warn "Dry run complete — no commits made."
  warn "Re-run with --push to release tags/$VERSION."
  exit 0
fi

say "Creating local tag copy: svn cp trunk → tags/$VERSION"
cd "$SVN_DIR"
[[ -d "tags/$VERSION" ]] && die "tags/$VERSION already exists locally. Run 'svn update' and check."
svn cp trunk "tags/$VERSION"

say "Single svn commit: trunk + tags/$VERSION together"
svn commit \
  -m "Release version $VERSION" \
  --username "$SVN_USER" --non-interactive

say "svn update (pull new tag into working copy)"
svn update "$SVN_DIR" --non-interactive >/dev/null

# ----------------------------------------------------------------------------
# 7. Verify remote state
# ----------------------------------------------------------------------------
say "Verifying remote consistency"
remote_stable=$(svn cat "https://plugins.svn.wordpress.org/ai-story-maker/trunk/readme.txt" \
  | grep -m1 "Stable tag:" | sed -E 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]')
remote_tag_ver=$(svn cat "https://plugins.svn.wordpress.org/ai-story-maker/tags/$VERSION/$PLUGIN_FILE" \
  | grep -m1 -E "^\s*\* Version:" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')

printf "  trunk Stable tag    : %s\n" "$remote_stable"
printf "  tags/%s Version : %s\n" "$VERSION" "$remote_tag_ver"

if [[ "$remote_stable" == "$VERSION" && "$remote_tag_ver" == "$VERSION" ]]; then
  say "✅ Released $VERSION to WordPress.org (single commit, clean tag). Zip will generate within minutes."
else
  die "Post-deploy verification mismatch — check SVN manually."
fi
