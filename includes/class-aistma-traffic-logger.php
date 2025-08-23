<?php
/**
 * Traffic Logger for AI Story Maker
 *
 * Creates and writes to the aistma_traffic_info table to track post views.
 *
 * @package AI_Story_Maker
 */

// phpcs:disable WordPress.Files.FileName.NotClassName
// phpcs:disable WordPress.Files.FileName.NotClass

namespace exedotcom\aistorymaker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISTMA_Traffic_Logger {
    /**
     * Ensure the traffic table exists.
     */
    public static function ensure_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table_name       = $wpdb->prefix . 'aistma_traffic_info';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            viewed_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_post_id (post_id),
            KEY idx_viewed_at (viewed_at)
        ) {$charset_collate};";

        dbDelta( $sql );

        // Ensure we don't keep any previously created personal data columns.
        self::migrate_table_to_minimal_schema();
    }

    /**
     * Log a view for a specific post.
     */
    public static function log_post_view( int $post_id ): void {
        if ( $post_id <= 0 || ! is_single() ) {
            return;
        }
        global $wpdb;

        $table = $wpdb->prefix . 'aistma_traffic_info';
        $wpdb->insert(
            $table,
            array(
                'post_id'   => $post_id,
                'viewed_at' => current_time( 'mysql', true ), // GMT time
            ),
            array( '%d', '%s' )
        );
    }

    /**
     * Hook helper to log on template_redirect before headers are sent.
     */
    public static function maybe_log_current_view(): void {
        if ( ! is_single() ) {
            return;
        }
        $post_id = get_queried_object_id();
        // Restrict to standard posts to avoid attachments or custom types if undesired.
        if ( get_post_type( $post_id ) !== 'post' ) {
            return;
        }
        self::log_post_view( (int) $post_id );
    }

    /**
     * Migrate existing traffic table to remove personal data columns if present.
     */
    private static function migrate_table_to_minimal_schema(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'aistma_traffic_info';

        // Helper to drop a column if it exists.
        $columns_to_drop = array( 'ip_address', 'cookie_id', 'is_returning', 'user_agent' );
        foreach ( $columns_to_drop as $col ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s',
                $table,
                $col
            ) );
            if ( (int) $exists > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query( "ALTER TABLE `{$table}` DROP COLUMN `{$col}`" );
            }
        }
    }
}


