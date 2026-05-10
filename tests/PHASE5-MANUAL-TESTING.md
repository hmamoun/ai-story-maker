# Phase 5: Manual Testing Checklist
## AI Story Maker v2.2.0

**Test Environment:** WordPress 6.4+, PHP 8.0+
**Tester:** QA Team
**Date Started:** 2026-04-28
**Date Completed:** [In Progress]

---

## Pre-Test Setup

- [ ] Fresh WordPress installation on staging server
- [ ] AI Story Maker plugin installed
- [ ] Gateway integration configured
- [ ] API keys configured
- [ ] Test user accounts created (Admin, Editor, Contributor)
- [ ] 5 test credits assigned to domain
- [ ] Browser console open (F12) to check for errors
- [ ] Query Monitor plugin active (optional, for performance)

---

## Test Scenarios

### Scenario 1: Fresh Install Activation

**Prerequisites:** Clean WordPress install, plugin not yet activated

**Steps:**
1. Go to Plugins page in WordPress Admin
2. Find "AI Story Maker"
3. Click "Activate"
4. Check browser console for errors

**Expected Outcomes:**
- [ ] Plugin activates without errors
- [ ] No fatal PHP errors in error log
- [ ] No JavaScript console errors
- [ ] Database tables created (check wp_aistma_* tables)
- [ ] Redirect to Welcome dashboard (if first activation)
- [ ] Navigation menu shows "AI Story Maker" or "Stories"

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 2: Activation Wizard Display

**Prerequisites:** Plugin activated, user logged in as Admin

**Steps:**
1. Log in as Admin user
2. Navigate to WordPress Dashboard (top-level)
3. Look for activation wizard modal
4. Check that modal only shows once

**Expected Outcomes:**
- [ ] Activation wizard modal appears on first visit
- [ ] Modal shows before any page content
- [ ] Modal has title "Welcome to AI Story Maker"
- [ ] Modal shows 4 steps (or as designed)
- [ ] "Dismiss" button visible
- [ ] Modal does NOT show on subsequent visits (after dismiss)
- [ ] Dismiss state persists across page reloads

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 3: Wizard Prompt Selection

**Prerequisites:** Activation wizard is displayed

**Steps:**
1. View activation wizard modal
2. Look at prompt list (should show 10 default prompts)
3. Click on first prompt
4. Verify prompt highlights
5. Click on another prompt
6. Verify new selection highlights

**Expected Outcomes:**
- [ ] Exactly 10 prompts visible in list
- [ ] Each prompt has: Name, Description, Icon (if designed)
- [ ] Prompt is clickable (cursor changes to pointer)
- [ ] Selected prompt has highlight/active state
- [ ] Can change selection
- [ ] Prompts have correct structure (id, name, description)
- [ ] Scrollbar appears if list exceeds container height

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 4: Story Generation (AJAX)

**Prerequisites:** Prompt is selected, user has 5+ credits

**Steps:**
1. Click "Generate Story" button in wizard
2. Watch for loading spinner
3. Wait for story preview to appear
4. Check generated story content

**Expected Outcomes:**
- [ ] Loading spinner appears immediately
- [ ] No console errors during generation
- [ ] Story generates within 5 seconds (adjust based on API)
- [ ] Preview shows: Title, excerpt, featured image
- [ ] Credits display updates (should show 4 remaining)
- [ ] AJAX request completes successfully
- [ ] `aistma_prompt_selected` event is logged

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 5: Credit Deduction

**Prerequisites:** Story is generated and user can see preview

**Steps:**
1. Note current credits displayed (should be 5)
2. Generate a story
3. Check credits after generation

**Expected Outcomes:**
- [ ] Credits shown: 5 → 4 after first generation
- [ ] Credit deduction happens immediately after AJAX
- [ ] Credit total is accurate (no duplicate deductions)
- [ ] Deduction logs in gateway (check order status)
- [ ] UI reflects new credit count
- [ ] Database reflects new balance

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 6: Preview Modal

**Prerequisites:** Story preview is displayed

**Steps:**
1. Look at preview modal
2. Check all sections (title, excerpt, image)
3. Click "Edit Story" button (if available)
4. Check edit form opens correctly
5. Close edit form

**Expected Outcomes:**
- [ ] Preview modal displays cleanly
- [ ] Title is editable (if design allows)
- [ ] Excerpt is editable (if design allows)
- [ ] Featured image is displayed
- [ ] Edit mode transitions smoothly
- [ ] Modal can be closed without generating new story
- [ ] Modal close button (X) works
- [ ] No layout shift when editing

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 7: Save Post

**Prerequisites:** Story preview is open, user has 4+ credits

**Steps:**
1. In preview modal, click "Save Story"
2. Wait for success message
3. Navigate to Posts
4. Verify post exists with correct title

**Expected Outcomes:**
- [ ] "Save Story" button is clickable
- [ ] Loading spinner appears during save
- [ ] Success message shown ("Post saved!")
- [ ] Credit deducts by 1 (4 → 3)
- [ ] Post created as Draft or Published (as designed)
- [ ] Post appears in Posts list
- [ ] Post title matches preview title
- [ ] Featured image is attached
- [ ] `aistma_story_generated` event is logged
- [ ] Gateway logs the deduction

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 8: Rating Modal (After 5th Generation)

**Prerequisites:** User has generated 5 stories (need to set up: generate → save, repeat 5x)

**Steps:**
1. Generate and save 4 stories (3 credits remaining after scenario 7)
2. Generate 5th story
3. Look for rating modal
4. Click stars or feedback button
5. Submit rating

**Expected Outcomes:**
- [ ] After 5th save, rating modal appears
- [ ] Modal shows: "Love this plugin? Rate us!"
- [ ] Star rating (1-5) is clickable
- [ ] Optional feedback text field
- [ ] "Submit" button is functional
- [ ] Modal only shows once (7-day cooldown)
- [ ] "Don't ask again" option available
- [ ] Closing modal doesn't prevent story creation
- [ ] Rating is logged

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 9: Weekly Toggle (Checked by Default)

**Prerequisites:** At least one story generated, user has credits

**Steps:**
1. After saving a story, look for "Enable Weekly" option
2. Check if checkbox is visible and checked
3. Read the description
4. Toggle off, then back on

**Expected Outcomes:**
- [ ] "Enable Weekly" checkbox appears after story save (or in dashboard)
- [ ] Checkbox is checked by default
- [ ] Description explains weekly auto-generation
- [ ] Toggle on/off works smoothly
- [ ] No console errors when toggling
- [ ] State persists after page reload (if checked)

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 10: Weekly Confirmation Modal

**Prerequisites:** Weekly toggle is checked

**Steps:**
1. Check the "Enable Weekly" checkbox
2. Wait for confirmation modal
3. Review the modal content
4. Select a prompt for weekly generation
5. Click "Confirm"

**Expected Outcomes:**
- [ ] Confirmation modal appears immediately after checking
- [ ] Modal shows: "Confirm weekly auto-generation"
- [ ] Dropdown lists available prompts
- [ ] Selected prompt is highlighted
- [ ] "Confirm" button is functional
- [ ] Modal closes after confirmation
- [ ] Weekly toggle remains checked

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 11: Weekly Generation (Cron)

**Prerequisites:** Weekly is enabled with prompt selected, cron is running

**Steps:**
1. Enable weekly generation with a prompt
2. Manually trigger cron: `wp cron event run aistma_weekly_generation` (or wait 7 days)
3. Check dashboard for new story
4. Check credit deduction

**Expected Outcomes:**
- [ ] Cron runs without errors
- [ ] New story is generated with selected prompt
- [ ] Story is created as draft or published (as designed)
- [ ] 1 credit is deducted
- [ ] `_aistma_weekly_last_generated` timestamp is updated
- [ ] If no credits, story is not generated (silent skip)
- [ ] Log shows: "Weekly generation completed" (if logging enabled)

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 12: Out of Credits Flow

**Prerequisites:** User has 0 credits

**Steps:**
1. Use all remaining credits (generate stories until 0)
2. Try to generate one more story
3. Look for error message
4. Check for "Buy more credits" prompt

**Expected Outcomes:**
- [ ] "Generate" button is disabled (greyed out) when 0 credits
- [ ] Or, clicking "Generate" shows error: "You have no credits remaining"
- [ ] Error message is clear and helpful
- [ ] "Buy more credits" button/link visible
- [ ] Link directs to payment/upgrade page (if applicable)
- [ ] Weekly generation is skipped (no partial generation)
- [ ] No hidden credit deductions

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 13: Admin Settings Panel

**Prerequisites:** Logged in as Admin

**Steps:**
1. Navigate to AI Story Maker settings page
2. Look at all tabs/sections
3. Check for API keys section
4. Check for prompt management
5. Check for credit settings (if admin)
6. Check for logs

**Expected Outcomes:**
- [ ] Settings page loads without errors
- [ ] All tabs are accessible (Welcome, Settings, Logs, etc.)
- [ ] API keys section visible
- [ ] Prompt editor shows custom prompts
- [ ] Save settings works (click Save, see confirmation)
- [ ] Logs show recent activity
- [ ] No sensitive data exposed in UI
- [ ] Admin can view/manage users' credits (if design allows)

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 14: Responsive Design - Mobile (375px)

**Prerequisites:** Plugin installed, wizard/modals visible

**Steps:**
1. Open Chrome DevTools (F12)
2. Set viewport to 375px width (iPhone SE)
3. View activation wizard
4. Test preview modal on mobile
5. Test rating modal
6. Test buttons (are they clickable with thumb?)

**Expected Outcomes:**
- [ ] Modals are full-width on mobile
- [ ] Text is readable (font size >= 14px)
- [ ] Buttons are large enough to tap (min 44px height)
- [ ] No horizontal scroll
- [ ] Images scale properly
- [ ] Form inputs are accessible
- [ ] Modal close button is easy to tap
- [ ] No layout shift on modal open/close

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 15: Responsive Design - Tablet (768px)

**Prerequisites:** Plugin installed, wizard/modals visible

**Steps:**
1. Set DevTools viewport to 768px (iPad)
2. View activation wizard
3. Test all modals (preview, rating, weekly)
4. Verify layout

**Expected Outcomes:**
- [ ] Modals use appropriate width (not full-width, not too narrow)
- [ ] Content is well-centered
- [ ] Images are appropriately sized
- [ ] Buttons are well-spaced
- [ ] No overlapping elements
- [ ] Readable on landscape orientation

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

### Scenario 16: Responsive Design - Desktop (1920px)

**Prerequisites:** Plugin installed, wizard/modals visible

**Steps:**
1. Set DevTools viewport to 1920px (large desktop)
2. View activation wizard and modals
3. Check layout

**Expected Outcomes:**
- [ ] Modals are not stretched (max-width maintained)
- [ ] Content is centered and not too wide
- [ ] Images maintain aspect ratio
- [ ] Whitespace is balanced
- [ ] No horizontal scrollbar

**Actual Outcome:** _______________
**Pass/Fail:** ☐ PASS ☐ FAIL
**Notes:** _______________

---

## Bug Summary

| Bug ID | Severity | Title | Description | Status | Notes |
|--------|----------|-------|-------------|--------|-------|
| B001 | | | | ☐ NEW ☐ FIXED | |
| B002 | | | | ☐ NEW ☐ FIXED | |
| B003 | | | | ☐ NEW ☐ FIXED | |

**Severity Levels:**
- **Critical:** Plugin crashes, blocks main features, data loss
- **High:** Feature broken, user can't complete task
- **Medium:** UI issue, minor inconvenience
- **Low:** Cosmetic, typo, enhancement

---

## Test Result Summary

| Scenario | Pass | Fail | Notes |
|----------|------|------|-------|
| 1. Fresh Install | ☐ | ☐ | |
| 2. Wizard Display | ☐ | ☐ | |
| 3. Prompt Selection | ☐ | ☐ | |
| 4. Story Generation | ☐ | ☐ | |
| 5. Credit Deduction | ☐ | ☐ | |
| 6. Preview Modal | ☐ | ☐ | |
| 7. Save Post | ☐ | ☐ | |
| 8. Rating Modal | ☐ | ☐ | |
| 9. Weekly Toggle | ☐ | ☐ | |
| 10. Weekly Confirmation | ☐ | ☐ | |
| 11. Weekly Generation | ☐ | ☐ | |
| 12. Out of Credits | ☐ | ☐ | |
| 13. Admin Settings | ☐ | ☐ | |
| 14. Mobile (375px) | ☐ | ☐ | |
| 15. Tablet (768px) | ☐ | ☐ | |
| 16. Desktop (1920px) | ☐ | ☐ | |

**Total Tests:** 16
**Passed:** ___
**Failed:** ___
**Pass Rate:** ____%

---

## Sign-Off

**Tester Name:** ________________
**Date:** ________________
**Result:** ☐ READY FOR UNIT TESTS ☐ RETEST NEEDED

**Critical Bugs Remaining:** ________
**All Bugs Documented:** ☐ Yes ☐ No

---

## Notes for QA Team

- Document any errors found in browser console (F12 → Console tab)
- Take screenshots of bugs
- Test on at least 2 different WordPress installations if possible
- Verify gateway integration is working (check gateway logs)
- Check PHP error logs for warnings
- Document any timing issues (AJAX taking >2s, etc.)

