# Phase 3: Quick Reference Guide

## For Developers

### Using the Rating Request Class

```php
use exedotcom\aistorymaker\AISTMA_Rating_Request;

// Get current generation count
$count = AISTMA_Rating_Request::get_generation_count($user_id);

// Increment count (call after story save)
AISTMA_Rating_Request::increment_generation_count($user_id);

// Check if should show rating modal
if (AISTMA_Rating_Request::should_show_rating($user_id)) {
    // Show modal to user
}

// Get ready-to-use modal data
$modal_data = AISTMA_Rating_Request::get_modal_data($user_id);
if ($modal_data) {
    // Render modal with data
}

// Mark as shown (start 7-day cooldown)
AISTMA_Rating_Request::mark_rating_shown($user_id);

// Mark never show again
AISTMA_Rating_Request::mark_never_show($user_id);

// Reset for testing
AISTMA_Rating_Request::reset_for_testing($user_id);
```

## For Testing

### Manual Test Scenario

1. **Reset user for testing:**
```php
// In WordPress admin, run via plugin console or WP-CLI:
$user_id = get_current_user_id();
AISTMA_Rating_Request::reset_for_testing($user_id);
```

2. **Simulate 4 generations:**
```php
for ($i = 0; $i < 4; $i++) {
    AISTMA_Rating_Request::increment_generation_count($user_id);
}
// Modal should NOT show yet
```

3. **Trigger 5th generation:**
```php
AISTMA_Rating_Request::increment_generation_count($user_id);
// Modal SHOULD show now
```

4. **Test 7-day cooldown:**
```php
// After user dismisses, check this:
$should_show = AISTMA_Rating_Request::should_show_rating($user_id);
// Returns false (within 7 days)

// Jump ahead 8 days in database:
$last_shown = get_user_meta($user_id, 'aistma_rating_last_shown', true);
$eight_days_ago = $last_shown - (8 * DAY_IN_SECONDS);
update_user_meta($user_id, 'aistma_rating_last_shown', $eight_days_ago);

// Now should show again:
$should_show = AISTMA_Rating_Request::should_show_rating($user_id);
// Returns true
```

5. **Test "Never ask again":**
```php
AISTMA_Rating_Request::mark_never_show($user_id);
$should_show = AISTMA_Rating_Request::should_show_rating($user_id);
// Returns false forever
```

## Browser Developer Console Tests

### Check if AistmaRating is loaded
```javascript
// Open browser console (F12)
typeof AistmaRating
// Should return "object" if loaded

AistmaRating.show()  // Manually show modal
AistmaRating.close() // Manually close modal
```

### Test AJAX handlers
```javascript
// Submit rating
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'aistma_submit_rating',
        nonce: aistmaRatingL10n.submitNonce,
        rating: 5,
        never_ask: 0
    },
    success: console.log,
    error: console.error
});

// Dismiss rating
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'aistma_dismiss_rating',
        nonce: aistmaRatingL10n.dismissNonce
    },
    success: console.log,
    error: console.error
});

// Never show
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'aistma_never_show_rating',
        nonce: aistmaRatingL10n.dismissNonce
    },
    success: console.log,
    error: console.error
});
```

## Database Queries

### Check user's rating state
```sql
SELECT meta_key, meta_value 
FROM wp_usermeta 
WHERE user_id = 123 
AND meta_key LIKE 'aistma_rating%';
```

### Check generation count
```sql
SELECT meta_value 
FROM wp_usermeta 
WHERE user_id = 123 
AND meta_key = 'aistma_generation_count';
```

### Find users who rated
```sql
SELECT * FROM wp_usermeta 
WHERE meta_key = 'aistma_rating_last_shown' 
LIMIT 10;
```

### Find users who opted out
```sql
SELECT * FROM wp_usermeta 
WHERE meta_key = 'aistma_rating_never_show' 
AND meta_value = 1;
```

## Common Issues & Fixes

### Modal not showing on 5th generation

**Check 1:** Verify generation count incremented
```php
$count = AISTMA_Rating_Request::get_generation_count($user_id);
// Should be 5 or more
```

**Check 2:** Verify should_show_rating logic
```php
$should = AISTMA_Rating_Request::should_show_rating($user_id);
// Should be true
```

**Check 3:** Verify JavaScript is loaded
```javascript
// In browser console:
typeof AistmaRating
// Should be "object"
```

**Check 4:** Verify CSS is loaded
```javascript
// In browser console:
jQuery('#aistma-rating-modal').length
// Should be 1
```

### AJAX errors

**Check nonces:**
```php
// In browser console, check if nonces exist:
aistmaRatingL10n.submitNonce
aistmaRatingL10n.dismissNonce
// Should show nonce strings
```

**Check permissions:**
```php
$user = wp_get_current_user();
// Should have 'edit_posts' capability
$user->has_cap('edit_posts')
```

## Performance Testing

### Measure modal load time
```javascript
console.time('rating-modal-show');
AistmaRating.show();
console.timeEnd('rating-modal-show');
// Should be <50ms
```

### Check AJAX response time
```javascript
console.time('rating-submit-ajax');
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: { /* ... */ },
    complete: function() {
        console.timeEnd('rating-submit-ajax');
    }
});
// Should be <200ms
```

### Monitor user meta queries
```php
// Enable query logging:
define('SAVEQUERIES', true);

// Check queries made:
global $wpdb;
echo count($wpdb->queries); // Should be minimal
```

## Style Customization

### Change star color
```css
/* In admin/css/rating-modal.css */
.aistma-star.active {
    color: #ff9900;  /* Change from #ffd700 */
}
```

### Change modal width
```css
.aistma-rating-content {
    width: 600px;  /* Change from 500px */
}
```

### Change button color
```css
.aistma-rating-submit {
    background-color: #0073aa;  /* WordPress blue */
}
```

## Event Logging

### View logged events (if gateway logger available)
```php
// Check exedotcom Gateway logger dashboard
// Look for events:
// - aistma_rating_submitted
// - aistma_rating_dismissed  
// - aistma_rating_never

// Can also check local logs:
AISTMA_Log_Manager::get_logs('rating');
```

## Hooks & Filters (For Phase 4)

```php
// Future hooks that could be added:

// Before rating modal shows
do_action('aistma_before_rating_modal_show', $user_id);

// After rating submitted
do_action('aistma_rating_submitted', $user_id, $rating);

// After rating dismissed
do_action('aistma_rating_dismissed', $user_id);

// After never ask set
do_action('aistma_rating_never_ask', $user_id);

// Filter generation threshold
apply_filters('aistma_rating_generation_threshold', 5);

// Filter cooldown days
apply_filters('aistma_rating_cooldown_days', 7);
```

## Debugging Workflow

```
1. Reset user for testing:
   AISTMA_Rating_Request::reset_for_testing($user_id);

2. Increment to 4 generations:
   for ($i = 0; $i < 4; $i++) {
       AISTMA_Rating_Request::increment_generation_count($user_id);
   }

3. Verify count:
   echo AISTMA_Rating_Request::get_generation_count($user_id);

4. One more for 5th:
   AISTMA_Rating_Request::increment_generation_count($user_id);

5. Check if should show:
   var_dump(AISTMA_Rating_Request::should_show_rating($user_id));

6. Refresh admin page - modal should appear

7. Test interactions:
   - Click stars (should highlight)
   - Click "Maybe later" (should close)
   - Or test "Don't ask again" checkbox

8. Verify logging:
   - Check browser console for AJAX responses
   - Check database for meta updates
   - Check gateway logger for events
```

## Quick Command Reference

```bash
# SSH to server and check PHP syntax
php -l /path/to/ai-story-maker/includes/class-aistma-rating-request.php

# Check for console errors in JS
grep -n "console\." /path/to/ai-story-maker/admin/js/rating-modal.js

# Find all rating-related files
find /path/to/ai-story-maker -name "*rating*"

# Check file sizes
wc -l /path/to/ai-story-maker/includes/class-aistma-rating-request.php

# Backup before changes
cp -r /path/to/ai-story-maker /path/to/ai-story-maker.backup
```

## Reporting Issues

When reporting issues, include:

1. WordPress version
2. PHP version
3. User role/capabilities
4. Browser type and version
5. Generation count for affected user
6. Steps to reproduce
7. Error messages (console, logs)
8. Expected vs actual behavior

## Success Indicators

✅ **Rating modal shows after 5th generation**
✅ **Stars highlight on hover**
✅ **Stars change color on click**
✅ **"Rate on WordPress.org" button opens external link**
✅ **"Maybe later" dismisses and reminds in 7 days**
✅ **"Don't ask again" checkbox prevents future modals**
✅ **AJAX requests succeed (check Network tab)**
✅ **Events logged to gateway/local logs**
✅ **User meta updated correctly**
✅ **Responsive on mobile and desktop**

---

**For questions or issues, refer to:**
- PHASE-3-RATING-REQUEST.md (full documentation)
- PHASE-3-IMPLEMENTATION-SUMMARY.md (technical overview)
- admin/class-aistma-admin.php (AJAX handlers)
- includes/class-aistma-rating-request.php (core logic)
