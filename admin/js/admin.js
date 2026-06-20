/**
 * Admin JavaScript for AI Story Maker
 * Handles the dynamic behavior of the admin settings page.
 * - Inline editing of prompts
 * - Adding new prompts
 * - Deleting prompts
 * - Submitting the form with JSON data
 * - AJAX request for generating stories
 * - Handling the response from the server
 * - Displaying success or error messages
 * - Spinner animation on button click
 * - Handling the "Make Stories" button click
 * - Handling the "Add Prompt" button click
 * - Handling the "Delete Prompt" button click
 */
document.addEventListener("DOMContentLoaded", function() {

    const prompt_form = document.getElementById("prompt-form");
    const promptsData = document.getElementById("prompts-data");

    // Capture inline edits
    document.querySelectorAll(".editable").forEach(element => {
        element.addEventListener("input", function() {
            element.dataset.changed = "true";
        });
    });

    // Toggle active checkbox
    document.querySelectorAll(".toggle-active").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
            checkbox.dataset.changed = "true";
        });
    });

    document.addEventListener("click", function(e) {
        if (e.target && e.target.matches(".delete-prompt")) {
            e.target.closest("tr").classList.add("marked-for-deletion");
        }
    });

    // Add new prompt
    const addPromptBtn = document.getElementById("add-prompt");
    if (addPromptBtn) {
        addPromptBtn.addEventListener("click", function() {
            const promptList = document.getElementById("prompt-list");
            const addPromptRow = promptList.querySelector("tr:last-child"); // The "Add a new prompt" button row
            
            // Find the first actual prompt row to use as template
            const templateRow = promptList.querySelector("tr[data-index]");
            
            if (templateRow && addPromptRow) {
                // Create a new empty row based on the template
                const newRow = templateRow.cloneNode(true);
                
                // Generate a new index that's one higher than the highest existing index
                const existingRows = promptList.querySelectorAll("tr[data-index]");
                let maxIndex = -1;
                existingRows.forEach(row => {
                    const index = parseInt(row.getAttribute("data-index"));
                    if (!isNaN(index) && index > maxIndex) {
                        maxIndex = index;
                    }
                });
                const newIndex = maxIndex + 1;
                
                // Set the new row's index
                newRow.setAttribute("data-index", newIndex);
                
                // Clear all existing styles and add new prompt styling
                newRow.classList.remove("marked-for-deletion");
                newRow.classList.add("new-prompt-row");
                
                // Clear any changed attributes
                newRow.querySelectorAll("[data-changed]").forEach(el => {
                    delete el.dataset.changed;
                });

                // Reset all fields to empty/default values
                // 1. Reset the prompt text to empty
                const textEl = newRow.querySelector("[data-field='text']");
                if (textEl) {
                    textEl.innerText = "";
                    textEl.setAttribute("placeholder", "Enter your new prompt here...");
                    delete textEl.dataset.changed;
                    
                    // Add event listener to remove new-prompt-row class when user starts typing
                    textEl.addEventListener("input", function() {
                        if (textEl.innerText.trim().length > 0) {
                            newRow.classList.remove("new-prompt-row");
                            newRow.classList.add("edited-prompt-row");
                        }
                    }, { once: true }); // Only trigger once
                }
                
                // 2. Reset category dropdown to first option
                const categorySelect = newRow.querySelector("[data-field='category'] select");
                if (categorySelect) {
                    categorySelect.selectedIndex = 0;
                }
                
                // 3. Reset photos dropdown to first option (0 photos)
                const photosSelect = newRow.querySelector("[data-field='photos'] select");
                if (photosSelect) {
                    photosSelect.selectedIndex = 0;
                }
                
                // 4. Check active checkbox by default
                const activeCheckbox = newRow.querySelector("[data-field='active'] input[type='checkbox']");
                if (activeCheckbox) {
                    activeCheckbox.checked = true;
                    delete activeCheckbox.dataset.changed;
                }
                
                // 5. Uncheck auto_publish checkbox
                const autoPublishCheckbox = newRow.querySelector("[data-field='auto_publish'] input[type='checkbox']");
                if (autoPublishCheckbox) {
                    autoPublishCheckbox.checked = false;
                    delete autoPublishCheckbox.dataset.changed;
                }
                
                // 6. Generate new prompt ID
                const promptIdEl = newRow.querySelector("[data-field='prompt_id']");
                if (promptIdEl) {
                    promptIdEl.value = "prompt_" + Date.now() + "_" + Math.floor(Math.random() * 1000);
                }

                // Insert the new row directly above the "Add a new prompt" button row
                promptList.insertBefore(newRow, addPromptRow);
                
                // Focus on the text field for immediate editing
                if (textEl) {
                    textEl.focus();
                }
                
                // Add a subtle scroll to bring the new row into view
                newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Handle form submission
    if (prompt_form) {
        prompt_form.addEventListener("submit", function(event) {
            // Remove the rows with the marked-for-deletion class
            document.querySelectorAll(".marked-for-deletion").forEach(row => {
                row.remove();
            });

            let settings = {
                default_settings: {
                    model: document.getElementById("model").value
                    , system_content: document.getElementById("system_content").value
                }
                , prompts: []
            };

            document.querySelectorAll("#prompt-list tr").forEach(row => {
                const textEl = row.querySelector("[data-field='text']");
                if (textEl && textEl.innerText.trim() !== "") {
                    const categorySelect = row.querySelector("[data-field='category'] select");
                    const photosSelect = row.querySelector("[data-field='photos'] select");
                    const activeEl = row.querySelector("[data-field='active']");
                    const prompt_id = row.querySelector("[data-field='prompt_id']");
                    const auto_publish = row.querySelector("[data-field='auto_publish']");
                    settings.prompts.push({
                        text: textEl.innerText.trim()
                        , category: categorySelect ? categorySelect.value : ""
                        , photos: photosSelect ? photosSelect.value : ""
                        , active: activeEl && activeEl.checked ? 1 : 0
                        , auto_publish: auto_publish && auto_publish.checked ? 1 : 0
                        , prompt_id: prompt_id && prompt_id.value ? prompt_id.value : "prompt_" + Date.now() + "_" + Math.floor(Math.random() * 1000)
                    });
                }
            });

            promptsData.value = JSON.stringify(settings);

            // Allow the form to submit normally
            prompt_form.submit();
        });
    }

    // === Settings Save Button ===
    const aistmaSettingsMessage = document.getElementById("aistma-settings-message");
    const aistmaNonce = window.aistmaSettings ? window.aistmaSettings.nonce : '';
    const aistmaAjaxUrl = window.aistmaSettings ? window.aistmaSettings.ajaxUrl : '';
    const aistmaSaveBtn = document.getElementById("aistma-save-settings-btn");

    function aistma_show_message(msg, success) {
        if (!aistmaSettingsMessage) return;
        aistmaSettingsMessage.textContent = msg;
        aistmaSettingsMessage.style.color = success ? '#28a745' : '#dc3545';
        aistmaSettingsMessage.style.backgroundColor = success ? '#d4edda' : '#f8d7da';
        aistmaSettingsMessage.style.border = success ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
        aistmaSettingsMessage.style.padding = '8px 12px';
        aistmaSettingsMessage.style.margin = '10px 0';
        aistmaSettingsMessage.style.borderRadius = '4px';
        aistmaSettingsMessage.style.display = 'block';
        setTimeout(() => { aistmaSettingsMessage.style.display = 'none'; }, 4000);
    }

    function aistmaGetValue(el) {
        return el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
    }

    // Snapshot values on load to detect changes
    const aistmaOriginals = {};
    document.querySelectorAll('[data-setting]').forEach(function(el) {
        aistmaOriginals[el.getAttribute('data-setting')] = aistmaGetValue(el);
    });

    function aistmaHasChanges() {
        let changed = false;
        document.querySelectorAll('[data-setting]').forEach(function(el) {
            if (aistmaGetValue(el) !== aistmaOriginals[el.getAttribute('data-setting')]) {
                changed = true;
            }
        });
        return changed;
    }

    function aistmaUpdateSaveBtn() {
        if (aistmaSaveBtn) aistmaSaveBtn.disabled = !aistmaHasChanges();
    }

    document.querySelectorAll('[data-setting]').forEach(function(el) {
        el.addEventListener('change', aistmaUpdateSaveBtn);
        if (el.type === 'text' || el.type === 'url') {
            el.addEventListener('input', aistmaUpdateSaveBtn);
        }
    });

    if (aistmaSaveBtn) {
        aistmaSaveBtn.addEventListener('click', function() {
            const toSave = [];
            document.querySelectorAll('[data-setting]').forEach(function(el) {
                const key = el.getAttribute('data-setting');
                const val = aistmaGetValue(el);
                if (val !== aistmaOriginals[key]) toSave.push({ key, val });
            });
            if (!toSave.length) return;

            aistmaSaveBtn.disabled = true;
            aistmaSaveBtn.textContent = 'Saving…';

            let done = 0, errors = 0;
            toSave.forEach(function(item) {
                const fd = new FormData();
                fd.append('action', 'aistma_save_setting');
                fd.append('aistma_security', aistmaNonce);
                fd.append('setting_name', item.key);
                fd.append('setting_value', item.val);
                fetch(aistmaAjaxUrl, { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(text => {
                        try {
                            const json = JSON.parse(text);
                            if (!json.success) errors++;
                        } catch(e) {
                            console.error('aistma settings: non-JSON response', text);
                            errors++;
                        }
                    })
                    .catch(() => errors++)
                    .finally(() => {
                        done++;
                        if (done === toSave.length) {
                            aistmaSaveBtn.textContent = 'Save Settings';
                            if (errors) {
                                aistma_show_message('Some settings could not be saved.', false);
                                aistmaSaveBtn.disabled = false;
                            } else {
                                aistma_show_message('Settings saved!', true);
                                document.querySelectorAll('[data-setting]').forEach(function(el) {
                                    aistmaOriginals[el.getAttribute('data-setting')] = aistmaGetValue(el);
                                });
                                aistmaSaveBtn.disabled = true;
                            }
                        }
                    });
            });
        });
    }
    });

    // check if the button exists before adding the event listener
    if (document.getElementById("aistma-generate-stories-button"))
        document.getElementById("aistma-generate-stories-button").addEventListener("click", function(e) {
            e.preventDefault();
            
            // Check if button has validation enabled
            const validateAccounts = this.getAttribute('data-validate-accounts') === 'true';
            
            if (validateAccounts) {
                // First validate accounts before proceeding
                validateAccountsBeforeGeneration(this);
            } else {
                // Proceed with generation directly
                proceedWithGeneration(this);
            }
        });

    function validateAccountsBeforeGeneration(button) {
        const originalCaption = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking accounts...';

        const nonce = document.getElementById("validate-accounts-nonce").value;
        
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
                proceedWithGeneration(button);
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

    function proceedWithGeneration(button) {
        const originalCaption = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating... do not leave or close the page';

        const nonce = document.getElementById("generate-story-nonce").value;
        
        fetch(ajaxurl, {
                method: "POST"
                , headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                }
                , body: new URLSearchParams({
                    action: "generate_ai_stories"
                    , nonce: nonce
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
        let messageDiv = document.getElementById("aistma-notice");
        if (!messageDiv) {
            messageDiv = document.createElement('div');
            messageDiv.id = 'aistma-notice';
            const btn = document.getElementById('aistma-generate-stories-button');
            if (btn && btn.parentNode) {
                btn.insertAdjacentElement('afterend', messageDiv);
            } else {
                document.body.appendChild(messageDiv);
            }
        }
        messageDiv.className = `notice notice-${type} is-dismissible`;
        messageDiv.style.display = 'block';
        messageDiv.style.marginTop = '10px';
        // Normalize and simplify common fatal error wording and strip HTML tags
        const normalized = String(message || '')
            .replace(/<[^>]*>/g, '')
            .replace(/fatal\s+error:?/ig, 'Error')
            .trim();
        messageDiv.textContent = normalized || (type === 'success' ? 'Done.' : 'Error. Please check the logs.');
    }

// Enhanced Tab Switching Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced tab switching for subscription tabs
    const subscriptionTabs = document.querySelectorAll('#aistma-subscribe-or-api-keys-wrapper .nav-tab');
    if (subscriptionTabs.length > 0) {
        subscriptionTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedTab = this.getAttribute('data-tab');

                // Update active tab with smooth transition
                subscriptionTabs.forEach(t => {
                    t.classList.remove('nav-tab-active');
                    t.style.transition = 'all 0.3s ease';
                });
                this.classList.add('nav-tab-active');

                // Toggle content with fade effect
                const tabContents = document.querySelectorAll('.aistma-tab-content');
                tabContents.forEach(content => {
                    content.style.opacity = '0';
                    content.style.transition = 'opacity 0.3s ease';
                    content.style.display = 'none';
                });
                
                const targetContent = document.getElementById('tab-' + selectedTab);
                if (targetContent) {
                    targetContent.style.display = 'block';
                    setTimeout(() => {
                        targetContent.style.opacity = '1';
                    }, 50);
                }
            });
        });
    }
});

document.querySelectorAll('#aistma-subscribe-or-api-keys-wrapper .nav-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        const selectedTab = this.getAttribute('data-tab');

        // Update active tab
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
        this.classList.add('nav-tab-active');

        // Toggle content
        document.querySelectorAll('.aistma-tab-content').forEach(c => c.style.display = 'none');
        document.getElementById('tab-' + selectedTab).style.display = 'block';
    });
});
function aistma_get_subscription_status() {
    // Protected subscription lookups now happen server-side so we don't expose
    // gateway auth in browser JavaScript. The subscriptions template renders the
    // current status using PHP instead.
    return;
}

// Log filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const showAllLogsCheckbox = document.getElementById('aistma-show-all-logs');
    
    if (showAllLogsCheckbox) {
        showAllLogsCheckbox.addEventListener('change', function() {
            const currentUrl = new URL(window.location.href);
            
            if (this.checked) {
                currentUrl.searchParams.set('show_all_logs', '1');
            } else {
                currentUrl.searchParams.delete('show_all_logs');
            }
            
            // Redirect to the updated URL
            window.location.href = currentUrl.toString();
        });
    }
});

// Social Media Publishing functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle single account publish buttons
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('aistma-publish-single')) {
            e.preventDefault();
            
            const button = e.target;
            const postId = button.getAttribute('data-post-id');
            const accountId = button.getAttribute('data-account-id');
            const accountName = button.getAttribute('data-account-name');
            const platform = button.getAttribute('data-platform');
            
            if (!postId || !accountId) {
                alert('Missing required data for publishing');
                return;
            }
            
            // Disable button and show loading state
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Publishing...';
            button.style.opacity = '0.6';
            
            // Create nonce for security (WordPress will generate this)
            const nonce = (typeof aistmaSocialMedia !== 'undefined' && aistmaSocialMedia.nonce) || 
                         document.querySelector('#_wpnonce')?.value || 
                         document.querySelector('input[name="_wpnonce"]')?.value ||
                         wp.ajax.settings.nonce || '';
            
            const ajaxUrl = (typeof aistmaSocialMedia !== 'undefined' && aistmaSocialMedia.ajaxurl) || 
                           (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
            // Make AJAX request
            const formData = new FormData();
            formData.append('action', 'aistma_publish_to_social_media');
            formData.append('post_id', postId);
            formData.append('account_id', accountId);
            formData.append('nonce', nonce);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                timeout: 60000 // 60 second timeout
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Try to parse JSON
                return response.json().catch(jsonError => {
                    console.error('JSON parsing error:', jsonError);
                    throw new Error('Invalid response format from server');
                });
            })
            .then(data => {
                
                if (data.success) {
                    // Show success message
                    const message = data.data.message || `Successfully published to ${platform}`;
                    alert(message);
                    
                    // Optionally update button text to indicate success
                    button.textContent = '✓ Published';
                    button.style.color = '#28a745';
                } else {
                    // Show error message
                    const message = data.data?.message || 'Failed to publish to social media';
                    alert('Error: ' + message);
                    
                    // Reset button
                    button.textContent = originalText;
                    button.disabled = false;
                    button.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Publishing error details:', error);
                
                // More specific error messages
                let errorMessage = 'Network error occurred while publishing';
                if (error.message.includes('HTTP')) {
                    errorMessage = `Server error: ${error.message}`;
                } else if (error.message.includes('timeout')) {
                    errorMessage = 'Request timed out - the post may still be publishing';
                } else if (error.message.includes('Invalid response')) {
                    errorMessage = 'Server returned invalid response - check if post was published';
                }
                
                alert(errorMessage);
                
                // Reset button
                button.textContent = originalText;
                button.disabled = false;
                button.style.opacity = '1';
            });
        }
        
        // Handle multiple account menu buttons
        if (e.target && e.target.classList.contains('aistma-publish-menu')) {
            e.preventDefault();
            
            const button = e.target;
            const postId = button.getAttribute('data-post-id');
            
            // For now, show a simple alert - this could be enhanced with a proper modal
            alert('Multiple account publishing menu - feature to be enhanced. Use bulk actions for now.');
        }
    });
});