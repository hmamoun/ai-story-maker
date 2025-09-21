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
            const lastRow = promptList.querySelector("tr:last-child");
            if (lastRow) {
                const newRow = lastRow.cloneNode(true);
                // Remove the deleted-prompt class from the new row
                newRow.classList.remove("marked-for-deletion");
                // Clear the changed attribute from the new row
                newRow.querySelectorAll("[data-changed]").forEach(el => {
                    delete el.dataset.changed;
                });
                // Add class unsaved-prompt to the new row, overriding the default color
                newRow.classList.add("new-prompt-row");

                // Reset editable text field to default content
                const textEl = newRow.querySelector("[data-field='text']");
                if (textEl) {
                    textEl.innerText = "New Prompt";
                    delete textEl.dataset.changed;
                }
                // Reset category dropdown to its first option
                const categorySelect = newRow.querySelector("[data-field='category'] select");
                if (categorySelect) {
                    categorySelect.selectedIndex = 0;
                }
                // Reset photos dropdown to its first option
                const photosSelect = newRow.querySelector("[data-field='photos'] select");
                if (photosSelect) {
                    photosSelect.selectedIndex = 0;
                }
                // Uncheck active checkbox and clear changed attribute
                const checkbox = newRow.querySelector("[data-field='active'] .toggle-active, [data-field='active'] input");
                if (checkbox) {
                    checkbox.checked = false;
                    delete checkbox.dataset.changed;
                }
                const promptIdEl = newRow.querySelector("[data-field='prompt_id']");
                if (promptIdEl) {
                    promptIdEl.value = "";
                }
                const auto_publish = newRow.querySelector("[data-field='auto_publish'] input");
                if (auto_publish) {
                    auto_publish.value = "";
                }

                promptList.appendChild(newRow);
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

            promptsData.value = JSON.stringify(settings).replace(/\\"/g, '"');

            // Allow the form to submit normally
            prompt_form.submit();
        });
    }

    // === AI Story Maker Instant Settings Save ===
        const aistmaSettingsMessage = document.getElementById("aistma-settings-message");
        const aistmaNonce = window.aistmaSettings ? window.aistmaSettings.nonce : '';
        const aistmaAjaxUrl = window.aistmaSettings ? window.aistmaSettings.ajaxUrl : '';

    // Debounce utility
        function aistma_debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

            // Enhanced message display with animations
    function aistma_show_message(msg, success = true) {
        if (!aistmaSettingsMessage) return;
        
        aistmaSettingsMessage.textContent = msg;
        aistmaSettingsMessage.style.color = success ? '#28a745' : '#dc3545';
        aistmaSettingsMessage.style.backgroundColor = success ? '#d4edda' : '#f8d7da';
        aistmaSettingsMessage.style.border = success ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
        aistmaSettingsMessage.style.margin = '15px 0';
        aistmaSettingsMessage.style.opacity = '0';
        aistmaSettingsMessage.style.transform = 'translateY(-10px)';
        aistmaSettingsMessage.style.transition = 'all 0.3s ease';
        
        // Animate in
        setTimeout(() => {
            aistmaSettingsMessage.style.opacity = '1';
            aistmaSettingsMessage.style.transform = 'translateY(0)';
        }, 10);
        
        // Auto-hide after 4 seconds
        setTimeout(() => {
            aistmaSettingsMessage.style.opacity = '0';
            aistmaSettingsMessage.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                aistmaSettingsMessage.textContent = '';
            }, 300);
        }, 4000);
    }

            // Enhanced settings saving with loading states
    function aistma_save_setting(setting, value) {
        const control = document.querySelector(`[data-setting="${setting}"]`);
        if (control) {
            // Add loading state
            control.style.opacity = '0.6';
            control.disabled = true;
        }

        const data = new FormData();
        data.append('action', 'aistma_save_setting');
        data.append('aistma_security', aistmaNonce);
        data.append('setting_name', setting);
        data.append('setting_value', value);
        
        fetch(aistmaAjaxUrl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                aistma_show_message(data.data.message, true);
            } else {
                aistma_show_message(data.data.message || 'Error saving setting.', false);
            }
        })
        .catch((error) => {
            console.error('Settings save error:', error);
            aistma_show_message('Network error. Please try again.', false);
        })
        .finally(() => {
            if (control) {
                control.style.opacity = '1';
                control.disabled = false;
            }
        });
    }

    // Attach listeners to all controls with data-setting
        document.querySelectorAll('[data-setting]').forEach(function(control) {
            const setting = control.getAttribute('data-setting');
            if (control.type === 'checkbox') {
                control.addEventListener('change', function() {
                    aistma_save_setting(setting, control.checked ? 1 : 0);
                });
            } else if (control.tagName === 'SELECT') {
                control.addEventListener('change', function() {
                    aistma_save_setting(setting, control.value);
                });
            } else if (control.type === 'text') {
                control.addEventListener('input', aistma_debounce(function() {
                    aistma_save_setting(setting, control.value);
            }, 800)); // Increased debounce time for better UX
            }
        });
    });

    // check if the button exists before adding the event listener
    if (document.getElementById("aistma-generate-stories-button"))
        document.getElementById("aistma-generate-stories-button").addEventListener("click", function(e) {
            e.preventDefault();
            $originalCaption = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating... do not leave or close the page';

            const nonce = document.getElementById("generate-story-nonce").value;
            const showNotice = (message, type) => {
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
            };
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
                    this.disabled = false;
                    this.innerHTML = $originalCaption;
                });
        });

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
    // Get current domain with port if it exists
    const currentDomain = window.location.hostname + (window.location.port ? ':' + window.location.port : '');

    // Get master URL from WordPress constant
    const masterUrl = window.aistmaSettings ? window.aistmaSettings.masterUrl : '';
    
    if (!masterUrl) {
        console.error('AISTMA_MASTER_URL not defined');
        return;
    }
    
    // Make API call to master server to check subscription status
    fetch(`${masterUrl}wp-json/exaig/v1/verify-subscription?domain=${encodeURIComponent(currentDomain)}`)
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                // Hide any old notice if present
                const oldStatus = document.getElementById('aistma-subscription-status');
                if (oldStatus) oldStatus.remove();

                // Helper: format yyyy-MMM-dd
                const formatDateYYYYMMMDD = (input) => {
                    if (!input) return 'N/A';
                    let d = null;
                    if (typeof input === 'string' || typeof input === 'number') {
                        d = new Date(input);
                    } else if (typeof input === 'object') {
                        const raw = input.raw_date || input.date || input.formatted_date || input.next_refill_date || input;
                        d = new Date(raw);
                    }
                    if (!d || isNaN(d.getTime())) return (typeof input === 'object' && input.formatted_date) ? input.formatted_date : 'N/A';
                    const yyyy = d.getFullYear();
                    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    const mmm = monthNames[d.getMonth()];
                    const dd = String(d.getDate()).padStart(2, '0');
                    return `${yyyy}-${mmm}-${dd}`;
                };

                // Locate matching plan card by name or id with safe fallback
                const safeQueryByData = (attr, value) => {
                    if (!value) return null;
                    try {
                        if (window.CSS && typeof CSS.escape === 'function') {
                            return document.querySelector(`.aistma-package-box[${attr}="${CSS.escape(String(value))}"]`);
                        }
                    } catch (_) { /* ignore */ }
                    const boxes = document.querySelectorAll('.aistma-package-box');
                    for (const el of boxes) {
                        if (el.getAttribute(attr) === String(value)) return el;
                    }
                    return null;
                };

                let card = safeQueryByData('data-package-id', data.package_id);
                if (!card) card = safeQueryByData('data-package-name', data.package_name);

                // Compute concise line
                const nextBilling = formatDateYYYYMMMDD(data.next_billing_date);
                // days remaining is the difference in days between today and next billing date
                const remainingDays = nextBilling ? Math.ceil((new Date(nextBilling) - new Date()) / (1000 * 60 * 60 * 24)) : 'N/A';
                const creditsUsed = typeof data.credits_used !== 'undefined' ? data.credits_used : 'N/A';
                
                // storiesRemaining is the total credits - credits used
                const storiesRemaining = typeof data.credits_total !== 'undefined' ? data.credits_total - data.credits_used : 'N/A';
                //  const line = `Your current plan, stories generated during this cycle: (${creditsUsed}) next billing (${nextBilling}), remaining days (${remainingDays}), stories remaining (${data.credits_total})`;
                const line = `This cycle: ${creditsUsed} stories created.  ${storiesRemaining} stories remaining. Next billing: ${nextBilling}. ${remainingDays} days left.`;

                if (card) {
                    // Remove any existing highlight first
                    document.querySelectorAll('.aistma-package-box.aistma-current-package').forEach(el => {
                        el.classList.remove('aistma-current-package');
                        el.removeAttribute('aria-current');
                    });
                    // Add highlight and ARIA marker
                    card.classList.add('aistma-current-package');
                    card.setAttribute('aria-current', 'true');

                    const lineEl = card.querySelector('.aistma-current-plan-line');
                    if (lineEl) {
                        lineEl.textContent = line;
                        lineEl.style.display = 'block';
                        lineEl.style.marginTop = '8px';
                        lineEl.style.fontWeight = '600';
                        lineEl.style.color = '#0073aa';
                    }
                    // Optional: brief focus animation and ensure visibility
                    try {
                        card.animate([
                            { transform: 'scale(1.0)' },
                            { transform: 'scale(1.02)' },
                            { transform: 'scale(1.0)' }
                        ], { duration: 400 });
                    } catch (_) { /* no-op if Web Animations API not available */ }
                    // Scroll into view if off-screen
                    if (typeof card.scrollIntoView === 'function') {
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                } else {
                    // Fallback: inject concise line above packages
                    let fallback = document.getElementById('aistma-subscription-status');
                    if (!fallback) {
                        fallback = document.createElement('div');
                        fallback.id = 'aistma-subscription-status';
                        const packagesContainer = document.querySelector('.aistma-packages-container');
                        if (packagesContainer) {
                            packagesContainer.parentNode.insertBefore(fallback, packagesContainer);
                        }
                    }
                    fallback.className = 'notice notice-success';
                    fallback.textContent = line;
                }
            } else {
                // Remove concise line if previously set
                document.querySelectorAll('.aistma-current-plan-line').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                // Remove highlight if present
                document.querySelectorAll('.aistma-package-box.aistma-current-package').forEach(el => {
                    el.classList.remove('aistma-current-package');
                    el.removeAttribute('aria-current');
                });
                const statusElement = document.getElementById('aistma-subscription-status');
                if (statusElement) statusElement.remove();
            }
        })
        .catch(error => {
            console.error('Error checking subscription status:', error);
            
            // Show error message
            let statusElement = document.getElementById('aistma-subscription-status');
            if (!statusElement) {
                statusElement = document.createElement('div');
                statusElement.id = 'aistma-subscription-status';
                statusElement.className = 'notice notice-error';
                statusElement.style.margin = '10px 0';
                
                const packagesContainer = document.querySelector('.aistma-packages-container');
                if (packagesContainer) {
                    packagesContainer.parentNode.insertBefore(statusElement, packagesContainer);
                }
            }
            
            statusElement.innerHTML = '<strong>Error:</strong> Could not check subscription status. Please try again later.';
        });
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