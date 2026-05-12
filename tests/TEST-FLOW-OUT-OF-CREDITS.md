# E2E Test Flow: Out of Credits Scenario

**Scenario:** User runs out of credits, tries to generate, gets blocked, sees upgrade prompt

**Test Environment:** WordPress with AI Story Maker installed and configured
**Duration:** ~5 minutes
**Tester:** QA
**Date:** ___________
**Result:** ☐ PASS ☐ FAIL

---

## Setup

- [ ] Admin user has 5 starting credits
- [ ] User account to test with has been created (editor role)
- [ ] Gateway is properly configured
- [ ] Story generation API is working

---

## Step-by-Step Flow

### Step 1: Use All Startup Credits
**Action:** Generate and save 5 stories, exhausting the 5 startup credits

**Sub-steps:**

**Story 1:**
- [ ] Generate story (prompt: any)
- [ ] Preview shows
- [ ] Save story
- [ ] Credits: 5 → 4
- [ ] Post created

**Story 2:**
- [ ] Generate story
- [ ] Save story
- [ ] Credits: 4 → 3
- [ ] Post created

**Story 3:**
- [ ] Generate story
- [ ] Save story
- [ ] Credits: 3 → 2
- [ ] Post created

**Story 4:**
- [ ] Generate story
- [ ] Save story
- [ ] Credits: 2 → 1
- [ ] Post created

**Story 5:**
- [ ] Generate story
- [ ] Save story
- [ ] Credits: 1 → 0
- [ ] Post created
- [ ] **Now at 0 credits**

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL

**Final Credit Balance:** 0

---

### Step 2: Try to Generate with 0 Credits
**Action:** Attempt to generate another story with 0 credits

**Expected Outcomes:**
- [ ] "Generate" button is **disabled** (greyed out/not clickable)
  OR "Generate" button is clickable but shows error after clicking
- [ ] Error message displayed:
  - [ ] "You have no credits remaining"
  - [ ] Or similar clear message
- [ ] Error is clear and not in technical jargon
- [ ] No AJAX request is sent (if button is properly disabled)
- [ ] No PHP error logs (graceful handling)

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL

**Error Message Shown:** _______________

---

### Step 3: "Buy More Credits" / Upgrade Prompt
**Action:** With 0 credits, look for upgrade/purchase prompt

**Expected Outcomes:**
- [ ] "Buy more credits" button/link is visible
- [ ] Button/link is prominent (not hidden)
- [ ] Button text: "Buy credits" or "Upgrade now" or similar
- [ ] Clicking button redirects to:
  - [ ] Payment page (if native billing)
  - [ ] Gateway upgrade page
  - [ ] WordPress.org or third-party marketplace
- [ ] OR: Admin is shown option to manually grant credits

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL

**Button/Link Text:** _______________
**Redirect URL:** _______________

---

### Step 4: Weekly Cron Skips Generation (No Credits)
**Action:** Manually trigger cron with user still at 0 credits

**Setup:**
- [ ] Weekly generation was enabled in previous tests
- [ ] User is still at 0 credits
- [ ] 7+ days have passed since last weekly generation (or timestamp manually updated for testing)

**Expected Outcomes:**
- [ ] Cron runs without error
- [ ] **No new story is generated** (silent skip)
- [ ] No credit deduction (obviously, since failed)
- [ ] Log shows: "Skipped weekly generation for user X (no credits)"
  OR log shows nothing (depends on implementation)
- [ ] User is **not notified** of failed weekly (silent)
- [ ] No error in PHP logs

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL

**New Posts Created:** 0

---

### Step 5: Admin Grants Credits via Gateway
**Action:** Admin grants user 10 credits via gateway (or manually)

**Expected Outcomes:**
- [ ] Credits are added to user's balance
- [ ] User now has 10 credits
- [ ] "Generate" button becomes enabled again
- [ ] User can now generate stories

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL

**New Credit Balance:** 10

---

### Step 6: User Can Generate Again
**Action:** With restored credits, try to generate a story

**Expected Outcomes:**
- [ ] "Generate" button is now enabled (clickable)
- [ ] Can click without errors
- [ ] Story generates successfully
- [ ] Preview shows
- [ ] Can save
- [ ] Credits deduct normally (10 → 9)
- [ ] No errors in logs

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL

**Credits After Save:** 9

---

## Summary

| Step | Description | Pass | Fail |
|------|-------------|------|------|
| 1 | Use All Credits | ☐ | ☐ |
| 2 | Try Generate at 0 | ☐ | ☐ |
| 3 | Upgrade Prompt | ☐ | ☐ |
| 4 | Weekly Skips | ☐ | ☐ |
| 5 | Admin Grants Credits | ☐ | ☐ |
| 6 | Generate Again | ☐ | ☐ |

**Total Steps:** 6
**Passed:** ___
**Failed:** ___
**Pass Rate:** _____%

---

## Bugs Found

| Bug # | Severity | Title | Description | Status |
|-------|----------|-------|-------------|--------|
| | | | | |
| | | | | |

---

## Tester Sign-Off

**Tester Name:** _______________
**Date:** _______________
**Overall Result:** ☐ PASS ☐ FAIL
**Issues:** _______________

---

## Key Behaviors to Verify

- [ ] **No partial generation:** User with 0 credits cannot create any story (draft or publish)
- [ ] **Silent cron failure:** Weekly cron doesn't error when no credits, just skips
- [ ] **Clear user messaging:** If out of credits, user sees clear message with action (buy/upgrade)
- [ ] **No surprise deductions:** Credits only deducted on successful save, never on failed attempt
- [ ] **Admin can override:** Admin can grant credits to allow generation
- [ ] **UI stays consistent:** Button state accurately reflects credit availability

