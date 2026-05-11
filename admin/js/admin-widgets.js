/**
 * Admin Widgets Scripts
 * JavaScript for dashboard widgets
 *
 * @package AI_Story_Maker
 * @since 2.3.0
 */

(function ($) {
	'use strict';

	/**
	 * Handle heatmap square hover tooltips
	 */
	$(document).on('mouseenter', '.aistma-story-calendar-widget .heatmap-square', function () {
		const count = $(this).data('count') || 0;
		const date = $(this).data('date') || 'Unknown';
		const title = count + ' story/ies on ' + date;
		$(this).attr('title', title);
	});

	/**
	 * Handle posts activity table interactions
	 */
	$(document).ready(function () {
		// Add click handlers for post links if needed
		$('.aistma-posts-activity-widget tbody tr').on('click', function () {
			const postId = $(this).data('post-id');
			if (postId) {
				window.location.href = '/wp-admin/post.php?post=' + postId + '&action=edit';
			}
		});
	});

	/**
	 * Animate data cards on page load
	 */
	$(window).on('load', function () {
		const cards = $('.aistma-data-card');
		cards.each(function (index) {
			$(this).delay(index * 100).fadeIn(300);
		});
	});

})(jQuery);
