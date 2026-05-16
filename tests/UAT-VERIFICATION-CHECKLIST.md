# v2.3.0 UAT Verification Checklist

**Purpose**: Comprehensive checklist to verify all fixes and features working correctly before production release.
**Date**: May 15, 2026
**Tester**: (Fill in name)
**Environment**: STG (stg.exedotcom.ca) and UAT (uat.immigration-consultants.net)

---

## Phase 1: Environment Setup

- [ ] Login to STG gateway as admin
- [ ] Login to UAT settings as admin
- [ ] Open Chrome DevTools (F12) for console monitoring
- [ ] Verify both sites are running latest deployed code

---

## Phase 2: Dashboard Widget Button (STG)

**Goal**: Verify the "Create a Story Now" button on the dashboard works properly

### Setup
1. Navigate to STG dashboard: https://stg.exedotcom.ca/wp-admin/
2. Open Chrome DevTools → Console tab (F12)
3. Scroll to find "Create Story with AI" widget

### Tests
- [ ] **Test 1**: Console shows widget loading logs
  - Expected: "AISTMA: Widget script loaded (inline)" appears in console
  - Expected: "AISTMA: jQuery is available, version X.X.X" appears

- [ ] **Test 2**: Console shows document ready
  - Expected: "AISTMA: Document ready event fired" appears

- [ ] **Test 3**: Console shows button found
  - Expected: "AISTMA: openWizardFromWidget() called" appears
  - Expected: "AISTMA: Button lookup result - length: 1" appears
  - Expected: "AISTMA: Click handler attached to button" appears

- [ ] **Test 4**: Button click triggers modal
  - Click: "Create a Story Now" button
  - Expected: "AISTMA: Widget button clicked" appears in console
  - Expected: Wizard modal appears on screen
  - Expected: Wizard modal contains prompt cards

- [ ] **Test 5**: Modal initialization is correct
  - Expected: "AISTMA: Modal found, opening" in console
  - Expected: Modal shows all available prompts
  - Expected: Prompt cards are clickable

### If Tests Fail
- [ ] Check console for error messages starting with "AISTMA: Error" or "AISTMA: Found"
- [ ] Report the specific console output to team
- [ ] Note any JavaScript errors in the browser console
- [ ] Take screenshot of DevTools console output

### Debug Notes
- If button not found: "AISTMA: Widget button #aistma-widget-open-wizard not found in DOM"
- If modal not found: "AISTMA: Found X elements with class .aistma-wizard-modal"

---

## Phase 3: Orders Page Default Filtering (STG Gateway)

**Goal**: Verify orders page shows active orders by default with "Show All" toggle

### Setup
1. Navigate to: https://stg.exedotcom.ca/wp-admin/admin.php?page=exaig-orders
2. Take note of current orders displayed

### Tests
- [ ] **Test 1**: Only active orders display by default
  - Expected: Page shows only orders with "active" status
  - Expected: URL does NOT contain `&show_all=1`
  - Expected: Status filter dropdown is hidden or shows "Active" as default

- [ ] **Test 2**: "Show All Orders" checkbox exists
  - Expected: Checkbox labeled "☐ Show All Orders" appears near filter button
  - Expected: Checkbox is unchecked by default

- [ ] **Test 3**: Checkbox enables "Show All" filter
  - Action: Click "Show All Orders" checkbox
  - Expected: Checkbox becomes checked
  - Expected: URL now contains `&show_all=1`
  - Expected: Status filter dropdown becomes visible/enabled
  - Expected: All orders (not just active) now display

- [ ] **Test 4**: Pagination works with default filter
  - Expected: Previous/Next buttons show correct page numbers
  - Expected: Filter parameters persist when clicking pagination

- [ ] **Test 5**: Clear Filters button works
  - Action: Click "Clear Filters" button
  - Expected: Status reset to "active" (default)
  - Expected: "Show All Orders" unchecked
  - Expected: Only active orders display again

### Debug Notes
- Check URL parameters: `&show_all=1` should appear/disappear when toggling
- Filter logic in class-exedotcom-order-manager.php line 27-32

---

## Phase 4: Free Tier Package Enrollment (UAT → STG)

**Goal**: Verify free tier auto-enrollment works correctly

### Setup
1. Have STG orders page open in one browser tab (refresh before starting)
2. Open UAT settings in another browser tab

### Tests
- [ ] **Test 1**: Baseline - no pre-existing orders on STG
  - Navigate to STG orders page
  - Filter/search for domain: uat.immigration-consultants.net
  - Expected: No orders visible for this domain

- [ ] **Test 2**: Settings page loads on UAT
  - Navigate to: https://uat.immigration-consultants.net/wp-admin/admin.php?page=aistma-settings&tab=ai_writer
  - Expected: Settings page loads without errors
  - Expected: Packages display from gateway

- [ ] **Test 3**: Free package enrollment triggered
  - Action: Scroll to packages section on UAT settings
  - Expected: Free package is visible and clickable
  - Expected: Page interacts with gateway API (check network tab)

- [ ] **Test 4**: New order appears on STG
  - Navigate back to STG orders page
  - Refresh the page (F5)
  - Search/filter for domain: uat.immigration-consultants.net
  - Expected: NEW order appears for the domain
  - Expected: Order status is "active"
  - Expected: Order shows free package details

### Debug Notes
- Enrollment triggered by: visiting UAT settings page and accessing packages
- Orders created in gateway database visible on STG admin page
- Check network tab in DevTools for API calls to /exaig/v1/* endpoints

---

## Phase 5: Free Package Properties (STG Gateway)

**Goal**: Verify free package has correct limits and properties

### Setup
1. Navigate to STG gateway package settings
2. Find the "Free" package in the list

### Tests
- [ ] **Test 1**: Free package displays correct name
  - Expected: Package named "Free"
  - Expected: Price shows "$0.00"
  - Expected: Description shows "Free tier with 5 monthly credits"

- [ ] **Test 2**: Enhancements limited to 1 per post
  - Expected: Shows "1 enhancement per post" (NOT "Unlimited enhancements per post")
  - Check line item in package details

- [ ] **Test 3**: Free package edit form loads
  - Action: Click "Edit" button on Free package
  - Expected: Edit form opens
  - Expected: NO "The link you followed has expired" error

- [ ] **Test 4**: Nonce validation works
  - Expected: Form contains `_wpnonce` field with valid token
  - Expected: Form can be submitted without nonce expiration

- [ ] **Test 5**: Free package can be saved
  - Action: Click Save button on edit form
  - Expected: Package saves successfully
  - Expected: Redirect back to package list
  - Expected: Changes persist on reload

---

## Phase 6: Complete Wizard Flow (UAT)

**Goal**: Verify end-to-end story generation with free tier credits

### Setup
1. Login to UAT as test user with free credits
2. Make sure user has just been enrolled (has 5 free startup credits)

### Tests
- [ ] **Test 1**: Wizard loads on dashboard
  - Expected: "Create Story with AI" widget shows
  - Expected: "Create a Story Now" button is clickable

- [ ] **Test 2**: Wizard modal opens
  - Action: Click "Create a Story Now"
  - Expected: Modal opens without errors
  - Expected: Prompts display correctly

- [ ] **Test 3**: Prompt selection triggers generation
  - Action: Select a prompt from the wizard
  - Expected: Loading indicator appears
  - Expected: Story generation AJAX call fires (check network tab)
  - Expected: NO "user has no credits" errors

- [ ] **Test 4**: Story generates successfully
  - Expected: Preview modal appears with generated story
  - Expected: Title, excerpt, and image display
  - Expected: Credits remaining shown

- [ ] **Test 5**: Save story from preview
  - Action: Click "Save" button in preview modal
  - Expected: Story saves to WordPress
  - Expected: Redirect to posts page or show success message
  - Expected: Credits deducted (should show 4 remaining)

### Debug Notes
- Check browser network tab for API calls to wizard generate/save endpoints
- Check WordPress error logs for any PHP errors
- Verify in gateway that user's credits were deducted

---

## Phase 7: Weekly Scheduler (STG)

**Goal**: Verify scheduled story generation works (if applicable)

### Tests (if weekly feature is enabled)
- [ ] **Test 1**: Weekly scheduler runs without errors
  - Check: WP-Cron logs or server logs
  - Expected: No fatal errors related to story generation

- [ ] **Test 2**: Scheduled stories generate with correct package
  - Expected: New posts created on schedule
  - Expected: Credits deducted from user account

---

## Phase 8: PHP Error Verification

**Goal**: Confirm no PHP parse errors after recent fixes

### Tests
- [ ] **Test 1**: No parse errors on STG dashboard
  - Navigate: https://stg.exedotcom.ca/wp-admin/
  - Expected: Page loads without fatal errors
  - Expected: No "Parse error" messages

- [ ] **Test 2**: No parse errors on UAT dashboard
  - Navigate: https://uat.immigration-consultants.net/wp-admin/
  - Expected: Page loads normally
  - Expected: Admin menus display correctly

- [ ] **Test 3**: No parse errors in gateway
  - Navigate: STG admin menu
  - Expected: "Tools → Orders Management" loads
  - Expected: "Settings → API Gateway Settings" loads

---

## Phase 9: Final Verification

### Before Marking as Complete
- [ ] All Phase tests have been executed
- [ ] All test results documented (✓ or issue description)
- [ ] No P0 or P1 issues found
- [ ] Console shows no JavaScript errors
- [ ] No PHP errors in WordPress error logs
- [ ] Wizard to story generation works end-to-end
- [ ] Free tier enrollment works end-to-end
- [ ] Orders page filtering works correctly

### Sign-off
- **Tester Name**: ___________________________
- **Date**: ___________________________
- **Status**: ☐ READY FOR PRODUCTION | ☐ NEEDS FIXES

---

## Issue Reporting Template

If you find an issue, please document it here:

**Issue #1**: [Description]
- **Phase/Test**: [Which phase and test]
- **Steps to Reproduce**: [Exact steps]
- **Expected**: [What should happen]
- **Actual**: [What actually happened]
- **Console Output**: [Any error messages]
- **Screenshot**: [Attach if relevant]
- **Severity**: [P0/P1/P2/P3]

---

## Quick Test Commands

For terminal-based testing (if needed):

```bash
# Check for PHP parse errors
cd /home/dh_v7gsk9/stg.exedotcom.ca/wp-content/plugins
php -l ai-story-maker/ai-story-maker.php
php -l exedotcom-api-gateway/exedotcom-api-gateway.php

# View recent errors
tail -50 /home/dh_v7gsk9/stg.exedotcom.ca/wp-content/debug.log
```

---

**Last Updated**: May 15, 2026
**Code Version**: v2.3.0 with free tier auto-enrollment
**Commits Included**: 
- 86b3109: PHP parse error fix
- 86a780f: Orders page filtering
- dee02ca: Package edit nonce fix
- 4082c6e: Enhancements limit
- e07ce79: Free enrollment after prompt selection
- 1983307: Credit granting in enrollment
- 3bf4731, ed6d723: Widget debugging
