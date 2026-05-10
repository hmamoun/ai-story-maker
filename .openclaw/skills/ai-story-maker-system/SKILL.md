---
name: ai-story-maker-system
description: Architecture knowledge, known bugs, debugging patterns, and deployment procedures for the AI Story Maker WordPress plugin and the exedotcom-api-gateway. Use when debugging story generation errors, subscription issues, gateway connectivity problems, or deploying gateway fixes.
triggers:
  - ai story maker
  - story generation
  - story maker plugin
  - aistma
  - exedotcom gateway
  - generate story
  - storymakerplugin
  - urbanslices
  - story temporarily unavailable
  - gateway plugin
  - exaig
  - startup credits
  - subscription email
---

# AI Story Maker + Gateway — Debug Reference

## System Architecture

```
WordPress site (plugin)
  └── ai-story-maker plugin (v2.2.2 on WordPress.org)
        ├── calls → storymakerplugin.com/wp-json/exaig/v1/generate-story  (story gen)
        ├── calls → storymakerplugin.com/wp-json/exaig/v1/verify-subscription  (status check)
        ├── calls → storymakerplugin.com/wp-json/exaig/v1/ensure-startup-credits  (wizard load)
        └── fallback → direct OpenAI API call (only if personal API key is configured)

storymakerplugin.com (production gateway)
  └── exedotcom-api-gateway WordPress plugin
        ├── validates domain + credits from wp_qj98xk_exaig_orders table
        ├── calls OpenAI API using gateway's own key
        └── deducts credits on success
```

## Server Access

| Server | SSH | Hosts |
|--------|-----|-------|
| Production gateway | `storymakerplugin@iad1-shared-b7-03.dreamhost.com` | storymakerplugin.com |
| Staging gateway | `dh_v7gsk9@iad1-shared-b7-03.dreamhost.com` | stg.exedotcom.ca |
| urbanslices.com | `dh_dsiw53@iad1-shared-b7-03.dreamhost.com` | urbanslices.com |

All DreamHost servers use `~/.ssh/id_ed25519` key (NOT `~/.ssh/dreamhost`).

## Key Source Repos (local)

| Repo | Path | Notes |
|------|------|-------|
| Plugin | `/home/hayan/source/ai-story-maker` | Local working copy (PR #98 branch) |
| Plugin SVN | `/home/hayan/source/ai-story-maker-svn` | WordPress.org SVN — use this for releases |
| Gateway plugin | `/home/hayan/source/exedotcom-api-gateway` | GitHub `hmamoun/exedotcom-api-gateway` |

**Plugin versions:** SVN trunk and `tags/2.2.2` are the release source. The local `ai-story-maker` path is on the `feature/privacy-disclosure-note` branch (PR #98, not yet merged). Make targeted fixes in SVN trunk, not the local branch.

Active development branch on gateway: **`fix/story-generation-auth`**
Do not merge to main without a pull request.

Fix log document: `docs/fixes/2026-05-10-story-generation-fix.md` in the gateway repo.

## Key Files — Plugin (`ai-story-maker`)

| File | What it does |
|------|-------------|
| `ai-story-maker.php:190` | `aistma_get_api_url()` — default gateway URL is `https://www.storymakerplugin.com`, overridable with `AISTMA_MASTER_API` constant in wp-config.php |
| `includes/class-aistma-story-generator.php:153` | `generate_ai_story()` — main entry point |
| `includes/class-aistma-story-generator.php:310` | `generate_story_via_master_api()` — calls gateway; sends `domain`, `prompt_text`, `settings`, `email` |
| `includes/class-aistma-story-generator.php:371` | Throws "Story generation temporarily unavailable" when gateway returns non-200 AND no personal OpenAI key |
| `includes/class-aistma-story-generator.php:1188` | `aistma_get_subscription_status()` — gets domain from `$_SERVER['HTTP_HOST']`, calls `verify-subscription` (no email sent) |
| `admin/class-aistma-admin.php:1919` | `aistma_ensure_startup_credits()` — wizard AJAX; grants local credits if balance=0, always calls `create_startup_credits_account()` |
| `admin/class-aistma-admin.php` | `create_startup_credits_account($email)` — POSTs to gateway `ensure-startup-credits` with domain + admin_email |
| `includes/class-aistma-log-manager.php` | Log cleanup — **has a SQL bug** (see Known Bugs) |

## Key Files — Gateway (`exedotcom-api-gateway`)

| File | What it does |
|------|-------------|
| `includes/class-exedotcom-authentication.php` | API key auth — accepts `Authorization: Bearer`, `X-API-Key` header, or `api_key` param |
| `modules/aistma/class-exaig-aistma-story-generation-api.php` | `generate-story` REST endpoint. `authorize_story_generation()` returns `true` (auth gate is domain+credits check) |
| `modules/aistma/class-exaig-aistma-subscription-repository.php` | `get_subscription_by_domain()` — queries `exaig_orders` with www. fallback; `update_email_if_empty()` — updates both domain variants |
| `modules/aistma/class-exaig-aistma-subscription-management.php` | `aistma_get_subscription_status()` REST endpoint; `ensure_startup_credits_subscription()` — creates or updates startup subscription |
| `modules/aistma/class-exaig-aistma-stripe-webhook.php` | Stripe checkout webhook; auto-cancels Free/Startup subscription on paid purchase |
| `templates/ai-plans-template.php` | Plans page — shows info notice (not blocker) when user has existing subscription |
| `modules/aistma/class-exaig-aistma-story-generator.php` | Calls OpenAI, expects JSON response with `title` and `content` fields |

## Production Gateway Database

- DB name: `storymakerplugin_com`, host: `mysql.storymakerplugin.com`
- DB user: `storymakerplugin` (credentials in `wp-config.php`)
- Table prefix: `wp_qj98xk_`
- Subscriptions table: `wp_qj98xk_exaig_orders`

Key columns in `exaig_orders`: `domain`, `package_name`, `credits_total`, `credits_used`, `status`, `user_email`

Useful queries:
```sql
-- Check a domain's subscription (search both www. and bare)
SELECT id, domain, package_name, user_email, credits_total, credits_used, status, created_at
FROM wp_qj98xk_exaig_orders
WHERE domain LIKE '%example.com%'
ORDER BY created_at DESC;

-- Activate a subscription
UPDATE wp_qj98xk_exaig_orders SET status = 'active' WHERE domain = 'example.com' AND status = 'inactive';
```

## Bugs Fixed (branch: fix/story-generation-auth — deployed to production)

### 1. Gateway API key auth blocking all story generation
**Error:** `{"code":"missing_api_key","message":"API key is required"}` HTTP 401
**Fix:** `authorize_story_generation()` returns `true`. Domain + credits = auth gate.
**File:** `modules/aistma/class-exaig-aistma-story-generation-api.php:88`

---

### 2. Domain www. mismatch — "No valid subscription found"
**Error:** HTTP 403 when plugin sends `urbanslices.com` but subscription stored as `www.urbanslices.com`
**Fix:** `get_subscription_by_domain()` tries the alternate www./bare form if exact match returns nothing. Same fallback added to `aistma_get_subscription_status()` and `update_email_if_empty()`.
**Files:** `class-exaig-aistma-subscription-repository.php:21`, `class-exaig-aistma-subscription-management.php`

---

### 3. verify-subscription endpoint bypassed www. fallback (Bug B)
**Cause:** `aistma_get_subscription_status()` had its own direct SQL queries without www. fallback — independent of Fix 2.
**Fix:** Same fallback pattern applied directly in the handler.
**File:** `modules/aistma/class-exaig-aistma-subscription-management.php:1944`

---

### 4. Plans page blocked upgrade when startup plan active
**Cause:** Packages with a different active subscription showed a blocker notice with `pointer-events: none`.
**Fix:** Changed to a non-blocking info notice; button always shows. On Stripe purchase, webhook auto-cancels any active Free/Startup subscription for the domain.
**Files:** `templates/ai-plans-template.php`, `modules/aistma/class-exaig-aistma-stripe-webhook.php`

---

### 5. Email not saved on startup subscription creation or story generation
**Cause (plugin):** `generate_story_via_master_api()` never sent `email` in the request. `aistma_ensure_startup_credits()` only called `create_startup_credits_account()` when local balance was 0, and `create_startup_credits_account()` didn't exist in SVN trunk at all.
**Cause (gateway):** `ensure_startup_credits_subscription()` created duplicate rows instead of updating existing; `update_email_if_empty()` used exact domain match only.
**Fix (plugin — v2.2.2):** Added `email` param to `generate_story_via_master_api()`. Added `create_startup_credits_account()` to SVN trunk and call it on every wizard load.
**Fix (gateway):** `ensure_startup_credits_subscription()` finds existing row and updates email in place. `update_email_if_empty()` covers both domain variants.
**Files:** SVN `admin/class-aistma-admin.php`, SVN `includes/class-aistma-story-generator.php`, `class-exaig-aistma-subscription-management.php`, `class-exaig-aistma-subscription-repository.php`

## Known Bugs (not yet fixed)

### SQL bug in plugin log cleanup
**Error in debug.log (fires weekly):**
```
WordPress database error Unknown column 'created_at <' in 'where clause'
for query DELETE FROM wp_95asyc_aistma_log_table WHERE `created_at <` = '...'
```
**Cause:** Backtick wraps `created_at <` as a column name — `<` operator is inside the backtick.
**File:** `includes/class-aistma-log-manager.php` — fix: `WHERE created_at < %s`
**Impact:** Log table grows indefinitely. No impact on story generation.

## Plugin Release Procedure

1. Make changes in `/home/hayan/source/ai-story-maker-svn/trunk/`
2. Update version in: `ai-story-maker.php`, `public/css/aistma-style.css`, `includes/shortcode-story-scroller.php`, `readme.txt` (Stable tag + Changelog)
3. `svn cp trunk tags/X.X.X`
4. `svn commit -m "Release X.X.X — description"`
5. WordPress.org serves the new tag within minutes

## Gateway Deployment Procedure

No GitHub deploy key on servers. Deploy via scp, then push to GitHub:

```bash
# Deploy a gateway file to production
scp -i ~/.ssh/id_ed25519 /home/hayan/source/exedotcom-api-gateway/<path/to/file.php> \
  storymakerplugin@iad1-shared-b7-03.dreamhost.com:~/storymakerplugin.com/wp-content/plugins/exedotcom-api-gateway/<path/to/file.php>

# Push branch to GitHub
GIT_SSH_COMMAND="ssh -i ~/.ssh/github_key -o StrictHostKeyChecking=no" git push origin fix/story-generation-auth
```

## Debugging Workflow

When a user reports "Story generation temporarily unavailable":

1. **Check the gateway endpoint directly:**
```bash
curl -s -w "\nHTTP:%{http_code}" -X POST \
  "https://www.storymakerplugin.com/wp-json/exaig/v1/generate-story" \
  -H "Content-Type: application/json" \
  -d '{"domain":"DOMAIN","prompt_id":"test","prompt_text":"test story","settings":{"model":"gpt-4-turbo","max_tokens":100},"recent_posts":[],"category":"","photos":0,"email":""}'
```

2. **Decode the response:**
   - `401 missing_api_key` → auth middleware re-enabled, revert `authorize_story_generation()`
   - `403 No valid subscription` → domain not in orders table or inactive; check `exaig_orders` for both www. and bare domain
   - `500 Invalid content structure` → OpenAI returned non-JSON or missing `title`/`content`; check gateway OpenAI key and system_content prompt
   - `WP_Error / timeout` → network issue; check `AISTMA_MASTER_API` in wp-config.php

3. **Check plugin debug log on the WordPress site:**
```bash
ssh -i ~/.ssh/id_ed25519 USER@iad1-shared-b7-03.dreamhost.com \
  "tail -100 ~/DOMAIN/wp-content/debug.log | grep -i 'aistma\|story\|subscription\|error'"
```

4. **Check production gateway error log:**
```bash
ssh -i ~/.ssh/id_ed25519 storymakerplugin@iad1-shared-b7-03.dreamhost.com \
  "tail -100 ~/logs/storymakerplugin.com/http/error.log 2>/dev/null"
```

## OpenAI Response Format Required by Gateway

The gateway's story generator requires OpenAI to return valid JSON with at minimum:
```json
{ "title": "Story title", "content": "Story body..." }
```
The `system_content` prompt setting **must contain the word "json"** (case-insensitive), otherwise the gateway uses a generic fallback that may not produce the right structure.
