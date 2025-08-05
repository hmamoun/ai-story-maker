# Duplicate Free Subscriptions Issue - Analysis and Fix

## Problem Description

You reported seeing 2 related records in the orders table after subscribing to a free subscription. This is a bug where free subscriptions were being created in **two different places** in the codebase, causing duplicate database entries.

## Root Cause Analysis

### **Two Independent Free Subscription Creation Points**

1. **`class-exaig-aistma-payment-endpoint.php`** (lines 35-60)
   - Handles free subscriptions via `admin_post_exaig_handle_purchase` action
   - Creates records with `session_id` like `free_timestamp_random`

2. **`class-exaig-aistma-subscription-management.php`** (lines 432-470)
   - Handles free subscriptions via `aistma_handle_package_selection` action
   - Creates records with empty `session_id`

### **Why This Happened**

- Both endpoints were processing free subscriptions independently
- No duplicate prevention logic existed
- Different session ID formats made it harder to detect duplicates
- Both were triggered by the same user action (subscribing to free plan)

## Files Modified

### **1. Enhanced Subscription Management Class**
**File**: `API Gateway/modules/aistma/class-exaig-aistma-subscription-management.php`

#### **Changes Made**:
```php
// Before: Direct insertion without duplicate check
$wpdb->insert( $table, [
    'domain'        => $domain,
    'user_email'    => $user_email,
    'package_id'    => $package_id,
    'package_name'  => $package_name,
    'session_id'    => '',
    'credits_total' => $credits_total,
    'credits_used'  => 0,
    'meta'          => null,
    'created_at'    => current_time( 'mysql' ),
    'updated_at'    => current_time( 'mysql' ),
] );

// After: Duplicate prevention with proper error handling
// Check for existing free subscription for this domain and package
$existing_free_subscription = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM $table WHERE domain = %s AND package_id = %s AND package_name = %s AND status = 'active' LIMIT 1",
    $domain,
    $package_id,
    $package_name
) );

if ( $existing_free_subscription ) {
    error_log( 'AISTMA: Free subscription already exists for domain ' . $domain . ' and package ' . $package_name );
    wp_safe_redirect( add_query_arg( 'order', 'success', wp_get_referer() ?: home_url() ) );
    exit;
}

$result = $wpdb->insert( $table, [
    'domain'        => $domain,
    'user_email'    => $user_email,
    'package_id'    => $package_id,
    'package_name'  => $package_name,
    'session_id'    => 'free_' . time() . '_' . rand(1000, 9999),
    'credits_total' => $credits_total,
    'credits_used'  => 0,
    'status'        => 'active',
    'meta'          => null,
    'created_at'    => current_time( 'mysql' ),
    'updated_at'    => current_time( 'mysql' ),
] );

if ( $result === false ) {
    error_log( 'AISTMA: Failed to create free subscription: ' . $wpdb->last_error );
    wp_die( 'Failed to create free subscription. Please try again.' );
}

error_log( 'AISTMA: Free subscription created successfully for domain ' . $domain . ' and package ' . $package_name );
```

### **2. Enhanced Payment Endpoint Class**
**File**: `API Gateway/modules/aistma/class-exaig-aistma-payment-endpoint.php`

#### **Changes Made**:
```php
// Before: Direct insertion without duplicate check
$wpdb->insert( $table, [
    'domain'        => $domain,
    'package_id'    => $index,
    'package_name'  => $package['name'],
    'session_id'    => 'free_' . time() . '_' . rand(1000, 9999),
    'user_email'    => '',
    'credits_total' => $package['credits'],
    'credits_used'  => 0,
    'status'        => 'active',
    'created_at'    => current_time( 'mysql' ),
    'updated_at'    => current_time( 'mysql' ),
] );

// After: Duplicate prevention with proper error handling
// Check for existing free subscription for this domain and package
$existing_free_subscription = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM $table WHERE domain = %s AND package_id = %s AND package_name = %s AND status = 'active' LIMIT 1",
    $domain,
    $index,
    $package['name']
) );

if ( $existing_free_subscription ) {
    error_log( 'AISTMA: Free subscription already exists for domain ' . $domain . ' and package ' . $package['name'] );
    wp_safe_redirect( add_query_arg( 'order', 'success', wp_get_referer() ) );
    exit;
}

$result = $wpdb->insert( $table, [
    'domain'        => $domain,
    'package_id'    => $index,
    'package_name'  => $package['name'],
    'session_id'    => 'free_' . time() . '_' . rand(1000, 9999),
    'user_email'    => '',
    'credits_total' => $package['credits'],
    'credits_used'  => 0,
    'status'        => 'active',
    'created_at'    => current_time( 'mysql' ),
    'updated_at'    => current_time( 'mysql' ),
] );

if ( $result === false ) {
    error_log( 'AISTMA: Failed to create free subscription: ' . $wpdb->last_error );
    wp_die( 'Failed to create free subscription. Please try again.' );
}

error_log( 'AISTMA: Free subscription created successfully for domain ' . $domain . ' and package ' . $package['name'] );
```

### **3. Cleanup Tool**
**File**: `API Gateway/tools/cleanup-duplicate-free-subscriptions.php`

#### **Features**:
- Identifies duplicate free subscription records
- Shows detailed information about duplicates
- Provides safe cleanup functionality
- Keeps the oldest record and deletes newer duplicates
- Verification after cleanup
- Prevention measures documentation

## Prevention Measures Implemented

### **1. Database Duplicate Check**
```php
// Check for existing free subscription for this domain and package
$existing_free_subscription = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM $table WHERE domain = %s AND package_id = %s AND package_name = %s AND status = 'active' LIMIT 1",
    $domain,
    $package_id,
    $package_name
) );

if ( $existing_free_subscription ) {
    // Redirect to success page without creating duplicate
    wp_safe_redirect( add_query_arg( 'order', 'success', wp_get_referer() ) );
    exit;
}
```

### **2. Unique Session IDs**
```php
// Generate unique session ID for free subscriptions
'session_id' => 'free_' . time() . '_' . rand(1000, 9999),
```

### **3. Error Handling**
```php
if ( $result === false ) {
    error_log( 'AISTMA: Failed to create free subscription: ' . $wpdb->last_error );
    wp_die( 'Failed to create free subscription. Please try again.' );
}
```

### **4. Comprehensive Logging**
```php
error_log( 'AISTMA: Free subscription created successfully for domain ' . $domain . ' and package ' . $package_name );
```

## How to Clean Up Existing Duplicates

### **Option 1: Use the Cleanup Tool**
1. Access the cleanup tool at: `your-site.com/wp-admin/admin.php?page=cleanup-duplicates`
2. Review the identified duplicates
3. Click "Cleanup Duplicates" to remove them
4. Verify the cleanup was successful

### **Option 2: Manual SQL Cleanup**
```sql
-- Find duplicates
SELECT 
    domain,
    package_id,
    package_name,
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY created_at) as order_ids
FROM wp_exaig_orders 
WHERE session_id LIKE 'free_%' OR session_id = ''
GROUP BY domain, package_id, package_name
HAVING COUNT(*) > 1;

-- Keep oldest record, delete others (replace with actual IDs)
DELETE FROM wp_exaig_orders 
WHERE id IN (duplicate_ids_here) 
AND id NOT IN (oldest_id_here);
```

## Monitoring for Future Duplicates

### **SQL Query to Monitor**
```sql
SELECT 
    domain,
    package_id,
    package_name,
    COUNT(*) as count
FROM wp_exaig_orders 
WHERE session_id LIKE 'free_%' OR session_id = ''
GROUP BY domain, package_id, package_name
HAVING COUNT(*) > 1;
```

### **Extra Pro Debugging Tip**
```php
// Add this to your theme's functions.php or a custom plugin
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        global $wpdb;
        $duplicates = $wpdb->get_results("
            SELECT domain, package_name, COUNT(*) as count
            FROM {$wpdb->prefix}exaig_orders 
            WHERE session_id LIKE 'free_%' OR session_id = ''
            GROUP BY domain, package_name
            HAVING COUNT(*) > 1
        ");
        
        if (!empty($duplicates)) {
            echo '<div style="background: #ff6b6b; color: white; padding: 10px; margin: 10px; border-radius: 5px;">';
            echo '<strong>⚠️ Duplicate Free Subscriptions Detected:</strong><br>';
            foreach ($duplicates as $dup) {
                echo "- {$dup->domain}: {$dup->package_name} ({$dup->count} records)<br>";
            }
            echo '</div>';
        }
    }
});
```

## Testing the Fix

### **Test Scenarios**
1. **New Free Subscription**: Should create only one record
2. **Duplicate Attempt**: Should redirect to success page without creating duplicate
3. **Error Handling**: Should show proper error message if database insert fails
4. **Logging**: Should log successful creation and any errors

### **Verification Steps**
1. Subscribe to a free plan
2. Check the orders table for only one record
3. Try to subscribe again - should redirect without creating duplicate
4. Check error logs for proper logging

## Related Topics to Learn

- **Database Duplicate Prevention**: Unique constraints and application-level checks
- **WordPress Error Handling**: Proper error logging and user feedback
- **Session Management**: Unique identifier generation for tracking
- **Database Transactions**: Ensuring data consistency
- **WordPress Hooks**: Understanding action and filter priorities
- **Debugging Techniques**: Logging and monitoring for production issues

## Implementation Status

✅ **Completed**: Duplicate prevention logic in subscription management
✅ **Completed**: Duplicate prevention logic in payment endpoint
✅ **Completed**: Enhanced error handling and logging
✅ **Completed**: Unique session ID generation
✅ **Completed**: Cleanup tool for existing duplicates
✅ **Completed**: Monitoring and verification tools

The duplicate free subscription issue has been resolved with comprehensive prevention measures and cleanup tools. 