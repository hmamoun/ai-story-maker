# AI Story Maker — Claude Code Guide

## Project Overview

**AI Story Maker** is a WordPress plugin that provides credit-based AI story generation via OpenAI integration. The plugin connects to a production gateway (`storymakerplugin.com`) for credit management, subscription verification, and story generation.

**Current Version:** v2.2.2 (WordPress.org)  
**Active Development:** `feature/admin-email-startup-credits` branch (includes privacy disclosure note)  
**Production Release:** Main branch + WordPress.org SVN

## Available Skill

This project includes a specialized skill for Story Maker development:

**[ai-story-maker-system](./.openclaw/skills/ai-story-maker-system/SKILL.md)**
- Complete system architecture and call flows
- Server access and SSH credentials
- Key files and code locations with line numbers
- Production gateway database schema
- All documented bugs and fixes with commit references
- Release and deployment procedures
- Debugging workflows for common issues

Claude will automatically use this skill when discussing Story Maker features, gateway issues, subscriptions, email registration, or story generation errors.

## Quick Links

- **Plugin Repos:**
  - Working copy: `.` (current directory)
  - WordPress.org SVN: `/home/hayan/source/ai-story-maker-svn/trunk/`
  - GitHub: `hmamoun/ai-story-maker`

- **Gateway Repo:**
  - Source: `/home/hayan/source/exedotcom-api-gateway/`
  - Branch: `fix/story-generation-auth` (active development)
  - Fix Log: `docs/fixes/2026-05-10-story-generation-fix.md`

- **Deployment Servers:**
  - Production gateway: `storymakerplugin.com`
  - Staging gateway: `stg.exedotcom.ca`
  - Test sites: `urbanslices.com`, `uat.immigration-consultants.net`

## How Claude Uses This Repository

1. **Automatic Skill Loading** — When Claude accesses this repo, `.openclaw/skills/ai-story-maker-system/SKILL.md` is automatically loaded and available.

2. **Skill Triggering** — Claude uses the skill when:
   - Debugging story generation errors
   - Fixing gateway issues
   - Managing subscriptions or email registration
   - Deploying updates
   - Reviewing code changes
   - Discussing architecture

3. **Staying Up-to-Date** — As the project evolves, update `.openclaw/skills/ai-story-maker-system/SKILL.md` to document:
   - New bugs and fixes
   - Architecture changes
   - Deployment procedure updates
   - New key files or breaking changes

## Development Workflow

### For Bug Fixes
1. Identify the issue (use the skill's debugging workflow)
2. Make changes in SVN trunk or active feature branch
3. Test locally
4. Push to GitHub
5. Deploy to staging/production as appropriate
6. Update the skill with new bug documentation

### For Features
1. Create a feature branch
2. Implement on that branch
3. Merge to main when ready
4. Release to WordPress.org SVN
5. Document in the skill

### For Releases
1. Make changes in `/home/hayan/source/ai-story-maker-svn/trunk/`
2. Update version in: `ai-story-maker.php`, CSS, `readme.txt`
3. Run `svn cp trunk tags/X.X.X && svn commit`
4. WordPress.org serves within minutes

## Common Tasks

**View production gateway database:**
- Database: `storymakerplugin_com` on `mysql.storymakerplugin.com`
- Table: `wp_qj98xk_exaig_orders` (subscriptions)
- See skill for useful SQL queries

**Deploy gateway changes:**
```bash
scp <file> storymakerplugin@iad1-shared-b7-03.dreamhost.com:~/storymakerplugin.com/wp-content/plugins/exedotcom-api-gateway/<path>
```

**Debug story generation errors:**
- Test the gateway endpoint directly with curl (see skill)
- Check plugin debug log: `wp-content/debug.log`
- Check gateway error log: `~/logs/storymakerplugin.com/http/error.log`

## Important Notes

- **SSH Key:** Use `~/.ssh/id_ed25519` for DreamHost (NOT `~/.ssh/dreamhost`)
- **Do NOT modify:** WordPress core files or gateway main branch without PR review
- **Always use SVN trunk for releases**, not the local git branch
- **Startup credits:** Default 5 credits, created when user activates plugin (domain-based, not user-based)
- **Email registration:** Sent with every story generation request; gateway updates if empty

---

For detailed technical information, see `.openclaw/skills/ai-story-maker-system/SKILL.md`.
