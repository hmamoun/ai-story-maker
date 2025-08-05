# Recent Posts Duplication Fix Summary

## Issue Identified
The user reported that consecutive generated posts had the same title, indicating that the recent posts functionality to avoid duplication was not working properly.

## Root Cause Analysis
After investigating the code, I found that:

1. **Local Story Generator**: ✅ **Working correctly**
   - Retrieves recent posts using `aistma_get_recent_posts(20, $prompt['category'])`
   - Includes recent posts in system content via `aistma_get_master_instructions($recent_posts)`
   - Passes recent posts to both master API and direct OpenAI calls

2. **Generate Story via subscription**: ❌ **Missing recent posts integration**
   - Received `$recent_posts` parameter but didn't use it
   - System content was not being enhanced with recent posts titles
   - This caused the AI to generate similar content without awareness of existing posts

## Fix Implemented

### 1. Updated Master API Story Generation
**File**: `API Gateway/modules/aistma/class-exaig-aistma-subscription-management.php`

**Changes**:
- Added recent posts to system content in `generate_story_with_openai()` method
- Enhanced system content with recent post titles to avoid duplication
- Added logging for debugging recent posts functionality

```php
// Add recent posts to system content to avoid duplication
if ( ! empty( $recent_posts ) && is_array( $recent_posts ) ) {
    $system_content .= "\nExclude references to the following recent posts:";
    foreach ( $recent_posts as $post ) {
        if ( isset( $post['title'] ) && ! empty( $post['title'] ) ) {
            $system_content .= "\nTitle: " . $post['title'];
        }
    }
    
    // Log the recent posts for debugging
    error_log( sprintf(
        'Master API - Recent posts for domain %s: %s',
        $domain ?? 'unknown',
        json_encode( array_column( $recent_posts, 'title' ) )
    ) );
}
```

### 2. Enhanced Local Logging
**File**: `ai-story-maker/ai-story-maker/includes/class-aistma-story-generator.php`

**Changes**:
- Added logging to track recent posts being retrieved for each category
- Helps with debugging and monitoring recent posts functionality

```php
// Log recent posts for debugging
$this->aistma_log_manager::log( 'info', sprintf(
    'Recent posts for category "%s": %s',
    $prompt['category'],
    json_encode( array_column( $recent_posts, 'title' ) )
) );
```

## How It Works Now

### For Subscriptions (Master API)
1. Local site retrieves recent posts for the category
2. Recent posts are sent to master API in request data
3. Master API includes recent post titles in system content
4. OpenAI receives enhanced system content with recent posts to avoid
5. AI generates content that avoids duplicating existing titles

### For Direct OpenAI Calls (Fallback)
1. Local site retrieves recent posts for the category
2. Recent posts are included in system content via `aistma_get_master_instructions()`
3. System content is passed to OpenAI API
4. AI generates content that avoids duplicating existing titles

## Testing Recommendations

1. **Check WordPress Error Logs**: Look for recent posts logging messages
2. **Verify Category**: Ensure the category has published posts
3. **Test Generation**: Generate stories and check for duplicate titles
4. **Monitor Master API Logs**: Check if recent posts are being received

## Expected Behavior

- ✅ Recent posts titles are included in system content
- ✅ AI is instructed to avoid duplicating existing titles
- ✅ Generated stories should have unique titles
- ✅ Logging shows recent posts being processed

## Files Modified

1. `API Gateway/modules/aistma/class-exaig-aistma-subscription-management.php`
   - Added recent posts to system content
   - Added logging for debugging

2. `ai-story-maker/ai-story-maker/includes/class-aistma-story-generator.php`
   - Added logging for recent posts retrieval

## Next Steps

1. Test story generation to verify the fix works
2. Monitor logs to ensure recent posts are being processed
3. Check for any remaining duplicate titles
4. Consider adding more sophisticated duplicate detection if needed

---

**Extra Pro Debugging Tip**: Use the WordPress debug log to monitor recent posts processing. Add this to your `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Then check the debug log at `/wp-content/debug.log` for recent posts logging messages. 