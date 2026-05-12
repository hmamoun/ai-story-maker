/**
 * Posts Page Button - AI Story Generation
 * Handles the "Generate AI Stories" button on the posts admin page
 * @package AI_Story_Maker
 */

document.addEventListener('DOMContentLoaded', function() {
	// Add the Generate Stories button next to "Add New" button
	const addNewButton = document.querySelector('.page-title-action');
	if (addNewButton) {
		// Create button HTML
		const buttonHtml = `
			<input type="hidden" id="aistma-posts-generate-story-nonce" value="${aistmaPostsPageData.generateNonce}">
			<input type="hidden" id="aistma-posts-validate-accounts-nonce" value="${aistmaPostsPageData.validateNonce}">
			<button id="aistma-posts-generate-stories-button" class="button button-primary aistma-posts-page-button" ${aistmaPostsPageData.buttonDisabled ? 'disabled' : ''} data-validate-accounts="true">
				${aistmaPostsPageData.buttonText}
			</button>
			<div id="aistma-posts-notice" style="display:none;"></div>
		`;

		// Insert button after the "Add New" button
		addNewButton.insertAdjacentHTML('afterend', buttonHtml);

		// Add event listener for the button
		const generateButton = document.getElementById('aistma-posts-generate-stories-button');
		if (generateButton) {
			generateButton.addEventListener('click', function(e) {
				e.preventDefault();

				// Check if button has validation enabled
				const validateAccounts = this.getAttribute('data-validate-accounts') === 'true';

				if (validateAccounts) {
					// First validate accounts before proceeding
					validateAccountsBeforeGenerationPosts(this);
				} else {
					// Proceed with generation directly
					proceedWithGenerationPosts(this);
				}
			});
		}

		function validateAccountsBeforeGenerationPosts(button) {
			const originalCaption = button.innerHTML;
			button.disabled = true;
			button.innerHTML = '<span class="spinner" style="visibility: visible; float: none; margin: 0 5px 0 0;"></span>Checking accounts...';

			const nonce = document.getElementById('aistma-posts-validate-accounts-nonce').value;

			fetch(ajaxurl, {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded"
				},
				body: new URLSearchParams({
					action: "aistma_validate_accounts",
					nonce: nonce
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Setup is valid, proceed with generation
					proceedWithGenerationPosts(button);
				} else {
					// Setup not valid, redirect to appropriate tab and show notice
					const tab = data.data.tab;
					const notice = data.data.notice;

					// Redirect to the appropriate tab first
					const redirectUrl = `admin.php?page=aistma-settings&tab=${tab}&notice=${notice}`;
					window.location.href = redirectUrl;
				}
			})
			.catch(error => {
				console.error("Account validation error:", error);
				showNotice('Error validating accounts. Please try again.', 'error');
				button.disabled = false;
				button.innerHTML = originalCaption;
			});
		}

		function proceedWithGenerationPosts(button) {
			const originalCaption = button.innerHTML;
			button.disabled = true;
			button.innerHTML = '<span class="spinner" style="visibility: visible; float: none; margin: 0 5px 0 0;"></span>Generating... do not leave or close the page';

			const nonce = document.getElementById('aistma-posts-generate-story-nonce').value;

			fetch(ajaxurl, {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded"
				},
				body: new URLSearchParams({
					action: "generate_ai_stories",
					nonce: nonce
				})
			})
			.then(response => {
				if (!response.ok) {
					return response.text().then(text => {
						throw new Error(text)
					});
				}
				return response.json();
			})
			.then(data => {
				if (data.success) {
					showNotice("Story generated successfully!", 'success');
					// Refresh the page to show new posts
					setTimeout(() => {
						window.location.reload();
					}, 2000);
				} else {
					const serverMsg = (data && data.data && (data.data.message || data.data.error)) || data.message || "Error generating stories. Please check the logs!";
					showNotice(serverMsg, 'error');
				}
			})
			.catch(error => {
				console.error("Fetch error:", error);
				const errMsg = (error && error.message) ? `Network error: ${error.message}` : 'Network error. Please try again.';
				showNotice(errMsg, 'error');
			})
			.finally(() => {
				button.disabled = false;
				button.innerHTML = originalCaption;
			});
		}

		function showNotice(message, type) {
			let messageDiv = document.getElementById('aistma-posts-notice');
			if (messageDiv) {
				messageDiv.className = `notice notice-${type} is-dismissible`;
				messageDiv.style.display = 'block';
				// Normalize and simplify common fatal error wording and strip HTML tags
				const normalized = String(message || '')
					.replace(/<[^>]*>/g, '')
					.replace(/fatal\s+error:?/ig, 'Error')
					.trim();
				messageDiv.textContent = normalized || (type === 'success' ? 'Done.' : 'Error. Please check the logs.');
			}
		}
	}
});
