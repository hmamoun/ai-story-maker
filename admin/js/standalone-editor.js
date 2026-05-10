/**
 * AI Story Maker Standalone Editor JavaScript - Rebuilt for Text Selection
 */

(function($) {
    'use strict';

    // Prevent multiple instances
    if (window.aistmaStandaloneEditorInitialized) {
        return;
    }
    window.aistmaStandaloneEditorInitialized = true;

    let currentPostId = null;
    let selectedText = '';
    let selectedRange = null;
    let currentOperation = 'text_improve';
    
    // Track original state for change detection
    let originalTitle = '';
    let originalContent = '';
    let originalTags = '';
    let originalMetaDescription = '';
    
    // Enhancement tracking
    let enhancementsUsed = 0;
    let enhancementsLimit = 0;
    let enhancementsRemaining = 0;

    // Prevent duplicate initialization
    let isInitialized = false;

    // Initialize when document is ready
    $(document).ready(function() {
        if (!isInitialized) {
            initStandaloneEditor();
            isInitialized = true;
        }
    });

    /**
     * Initialize the standalone editor
     */
    function initStandaloneEditor() {
        // Get post ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        currentPostId = urlParams.get('post_id');

        if (!currentPostId) {
            showMessage('No post ID provided.', 'error');
            return;
        }

        // Initialize enhancement tracking
        if (typeof aistmaStandaloneEditor !== 'undefined') {
            enhancementsUsed = aistmaStandaloneEditor.enhancements_used || 0;
            enhancementsLimit = aistmaStandaloneEditor.enhancements_limit || 0;
            enhancementsRemaining = aistmaStandaloneEditor.enhancements_remaining || 0;
        }

        bindEvents();
        setupTextSelection();
        checkEnhancementLimits();
        
        // Store original state after a small delay to ensure DOM is fully ready
        setTimeout(function() {
            storeOriginalState();
        }, 100);
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Popup controls
        $('.aistma-popup-close, .aistma-popup-cancel').on('click', closePopup);
        $('#improve-selected-btn').on('click', handleImproveSelected);
        
        // Enhancement history toggle
        $('.enhancement-history-toggle').on('click', function() {
            $('.enhancement-history-details').slideToggle();
            const buttonText = $('.enhancement-history-details').is(':visible') ? 'Hide Enhancements' : 'Show Enhancements';
            $(this).text(buttonText);
        });
        
        // Tags improvement
        $('#improve-tags-btn').on('click', handleImproveTags);
        
        // SEO improvement
        $('#improve-seo-btn').on('click', handleImproveSEO);
        
        // Save post
        $('#save-post-btn').on('click', handleSavePost);
        
        // Operation type is now fixed to 'text_improve' - no radio buttons needed

        // Close popup when clicking outside
        $('#aistma-improvement-popup').on('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });

        // Escape key to close popup
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#aistma-improvement-popup').is(':visible')) {
                closePopup();
            }
        });

        // Test popup button removed
        
        // Add change detection for form fields
        $('#post-title, #post-tags, #meta-description').on('input change', function() {
            checkForChanges();
        });
        
        // Add change detection for content preview (when text is improved)
        $(document).on('contentUpdated', function() {
            checkForChanges();
        });
    }

    /**
     * Setup text selection functionality
     */
    function setupTextSelection() {
        const $preview = $('#content-preview');
        
        // Handle text selection ONLY on mouse up (when user finishes selecting)
        $preview.on('mouseup', function() {
            setTimeout(function() {
                handleTextSelection();
            }, 10);
        });

        // Also handle mouseup on document for better selection detection
        $(document).on('mouseup', function(e) {
            if ($(e.target).closest('#content-preview').length > 0) {
                setTimeout(function() {
                    handleTextSelection();
                }, 10);
            }
        });
    }

    /**
     * Handle text selection
     */
    function handleTextSelection() {
        const selection = window.getSelection();
        const text = selection.toString().trim();
        
        if (text.length > 0) {
            selectedText = text;
            selectedRange = selection.getRangeAt(0);
            showSelectionInfo(text);
        } else {
            hideSelectionInfo();
        }
    }

    /**
     * Show selection info and popup
     */
    function showSelectionInfo(text) {
        // Store the selected text globally
        window.aistmaSelectedText = text;
        window.aistmaSelectedRange = selectedRange;
        
        // Show selection info in sidebar
        $('#selected-text-preview').text(text);
        $('#selection-info').show();

        // Show popup immediately on text selection (no timer delay)
        showPopup(text);
    }

    /**
     * Hide selection info
     */
    function hideSelectionInfo() {
        $('#selection-info').hide();
        selectedText = '';
        selectedRange = null;
        // Don't clear global storage here as we need it for the popup
    }

    /**
     * Show improvement popup
     */
    function showPopup(text) {
        $('#popup-selected-text').text(text);
        $('#improvement-prompt').val('');
        $('#aistma-improvement-popup').fadeIn(300);
        
        // Focus on the prompt textarea
        setTimeout(function() {
            $('#improvement-prompt').focus();
        }, 350);
    }

    /**
     * Close popup
     */
    function closePopup() {
        $('#aistma-improvement-popup').fadeOut(300);
        // Clear global storage when popup is closed
        window.aistmaSelectedText = '';
        window.aistmaSelectedRange = null;
        hideSelectionInfo();
    }

    /**
     * Check enhancement limits and disable buttons if needed
     */
    function checkEnhancementLimits() {
        // If limit is 0, it means unlimited
        if (enhancementsLimit === 0) {
            return;
        }
        
        // If no enhancements remaining, disable all improvement buttons
        if (enhancementsRemaining <= 0) {
            $('#improve-selected-btn').prop('disabled', true).addClass('disabled');
            $('#improve-tags-btn').prop('disabled', true).addClass('disabled');
            $('#improve-seo-btn').prop('disabled', true).addClass('disabled');
            
            // Show tooltip or notice
            showMessage('Enhancement limit reached for this post', 'warning');
        }
    }

    /**
     * Update enhancement display in the UI
     */
    function updateEnhancementDisplay() {
        // Update the counter display
        const limitText = enhancementsLimit > 0 ? enhancementsLimit : '∞';
        const remainingText = enhancementsLimit > 0 ? enhancementsRemaining : '∞';
        
        $('.enhancement-counter strong').text(`Enhancements: ${enhancementsUsed} of ${limitText} used`);
        
        if (enhancementsRemaining > 0 || enhancementsLimit === 0) {
            $('.enhancement-remaining').text(`${remainingText} remaining`).show();
            $('.enhancement-limit-reached').hide();
        } else {
            $('.enhancement-remaining').hide();
            $('.enhancement-limit-reached').show();
        }
        
        // Re-check limits
        checkEnhancementLimits();
    }

    /**
     * Handle improve selected text
     */
    function handleImproveSelected() {
        debugger;
        // Check enhancement limits
        if (enhancementsLimit > 0 && enhancementsRemaining <= 0) {
            showMessage('Enhancement limit reached for this post', 'error');
            return;
        }

        const prompt = $('#improvement-prompt').val().trim();
        
        // Get the selected text from global storage or current selection
        const textToImprove = window.aistmaSelectedText || selectedText || '';

        if (!textToImprove) {
            showMessage('No text selected.', 'error');
            return;
        }

        if (!prompt) {
            showMessage('Please enter improvement instructions.', 'error');
            return;
        }

        setLoading('#improve-selected-btn', true);

        const ajaxData = typeof aistmaStandaloneEditor !== 'undefined' ? aistmaStandaloneEditor : {
            ajaxurl: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: 'test-nonce'
        };
        
        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aistma_standalone_improve_content',
                nonce: ajaxData.nonce,
                content: textToImprove,
                prompt: prompt,
                operation_type: 'text_improve',
                post_id: currentPostId,
                selected_text: textToImprove,
                user_prompt: prompt
            },
            success: function(response) {
                setLoading('#improve-selected-btn', false);
                
                if (response.success) {
                    const improvedText = response.data.content;
                    replaceSelectedText(improvedText, textToImprove);
                    
                    // Update enhancement counter if available
                    if (response.data.enhancement_status) {
                        enhancementsUsed = response.data.enhancement_status.used;
                        enhancementsRemaining = response.data.enhancement_status.remaining;
                        updateEnhancementDisplay();
                    } else {
                        // Fallback: increment locally if no status from API
                        enhancementsUsed++;
                        if (enhancementsLimit > 0) {
                            enhancementsRemaining = Math.max(0, enhancementsLimit - enhancementsUsed);
                        }
                        updateEnhancementDisplay();
                    }
                    
                    // Update enhancement history immediately with current data
                    updateEnhancementHistoryImmediately();
                    
                    // Refresh enhancement data from server to get updated history
                    refreshEnhancementData();
                    
                    closePopup();
                    const successMessage = (typeof aistmaStandaloneEditor !== 'undefined' && aistmaStandaloneEditor.strings && aistmaStandaloneEditor.strings.success) 
                        ? aistmaStandaloneEditor.strings.success 
                        : 'Content improved! Enhancement usage tracked.';
                    showMessage(successMessage, 'success');
                } else {
                    showMessage(response.data || 'Failed to improve text.', 'error');
                }
            },
            error: function() {
                setLoading('#improve-selected-btn', false);
                showMessage('Network error while improving text.', 'error');
            }
        });
    }

    /**
     * Update enhancement display in the UI
     */
    function updateEnhancementDisplay() {
        
        // Update the enhancement counter
        const counterElement = document.querySelector('.enhancement-counter strong');
        if (counterElement) {
            const limitText = enhancementsLimit > 0 ? enhancementsLimit : '∞';
            counterElement.textContent = `Enhancements: ${enhancementsUsed} of ${limitText} used`;
        }
        
        // Update the remaining count
        const remainingElement = document.querySelector('.enhancement-remaining');
        const limitReachedElement = document.querySelector('.enhancement-limit-reached');
        
        if (enhancementsRemaining > 0 || enhancementsLimit === 0) {
            if (remainingElement) {
                const remainingText = enhancementsLimit > 0 ? enhancementsRemaining : '∞';
                remainingElement.textContent = `${remainingText} remaining`;
                remainingElement.style.display = 'block';
            }
            if (limitReachedElement) {
                limitReachedElement.style.display = 'none';
            }
        } else {
            if (remainingElement) {
                remainingElement.style.display = 'none';
            }
            if (limitReachedElement) {
                limitReachedElement.style.display = 'block';
            }
        }
        
        // Check if we need to disable enhancement buttons
        checkEnhancementLimits();
    }
    
    /**
     * Refresh enhancement data from server
     */
    function refreshEnhancementData() {
        if (!currentPostId) return;
        
        const ajaxData = typeof aistmaStandaloneEditor !== 'undefined' ? aistmaStandaloneEditor : {
            ajaxurl: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: 'test-nonce'
        };
        
        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aistma_get_enhancement_data',
                nonce: ajaxData.nonce,
                post_id: currentPostId
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Update the localized data
                    if (typeof aistmaStandaloneEditor !== 'undefined') {
                        aistmaStandaloneEditor.enhancements_used = response.data.enhancements_used;
                        aistmaStandaloneEditor.enhancements_limit = response.data.enhancements_limit;
                        aistmaStandaloneEditor.enhancements_remaining = response.data.enhancements_remaining;
                        aistmaStandaloneEditor.enhancements_history = response.data.enhancements_history;
                    }
                    
                    // Update local variables
                    enhancementsUsed = response.data.enhancements_used;
                    enhancementsLimit = response.data.enhancements_limit;
                    enhancementsRemaining = response.data.enhancements_remaining;
                    
                    // Update the display
                    updateEnhancementDisplay();
                    
                    // Update enhancement history with fresh data
                    updateEnhancementHistoryFromData(response.data);
                }
            },
            error: function() {
                // Failed to refresh enhancement data
            }
        });
    }

    /**
     * Update enhancement history immediately after enhancement
     */
    function updateEnhancementHistoryImmediately() {
        const historyTable = document.querySelector('.enhancement-history-details tbody');
        if (historyTable) {
            // Determine enhancement type based on context
            let enhancementType = 'content_enhancement'; // Default
            let currentPrompt = 'Improve content';
            
            // Check if this was triggered by tags improvement
            if (document.querySelector('#improve-tags-btn') && document.querySelector('#improve-tags-btn').disabled) {
                enhancementType = 'tags_enhancement';
                currentPrompt = 'Generate relevant tags for this content';
            }
            // Check if this was triggered by SEO improvement
            else if (document.querySelector('#improve-seo-btn') && document.querySelector('#improve-seo-btn').disabled) {
                enhancementType = 'seo_enhancement';
                currentPrompt = 'Generate a compelling meta description';
            }
            // Check if this was from the main improvement popup
            else if (document.querySelector('#improvement-prompt')) {
                currentPrompt = document.querySelector('#improvement-prompt').value || 'Improve content';
                // Determine type based on prompt content
                const promptLower = currentPrompt.toLowerCase();
                if (promptLower.includes('tag') || promptLower.includes('generate relevant tags')) {
                    enhancementType = 'tags_enhancement';
                } else if (promptLower.includes('seo') || promptLower.includes('meta description')) {
                    enhancementType = 'seo_enhancement';
                }
            }
            
            // Create new row for current enhancement
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <span class="enhancement-type-badge enhancement-type-${enhancementType}">
                        ${enhancementType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </span>
                </td>
                <td>${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                <td>${currentPrompt.substring(0, 100)}</td>
            `;
            
            // Add to the beginning of the table
            historyTable.insertBefore(row, historyTable.firstChild);
        }
    }

    /**
     * Update enhancement history in the UI using fresh data from AJAX response
     */
    function updateEnhancementHistoryFromData(data) {
        if (data && data.enhancements_history) {
            const historyTable = document.querySelector('.enhancement-history-details tbody');
            if (historyTable) {
                // Clear existing rows
                historyTable.innerHTML = '';
                
                // Add new rows from the fresh data
                data.enhancements_history.forEach(function(enhancement) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <span class="enhancement-type-badge enhancement-type-${enhancement.type}">
                                ${enhancement.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </span>
                        </td>
                        <td>${new Date(enhancement.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                        <td>${enhancement.prompt_snippet}</td>
                    `;
                    historyTable.appendChild(row);
                });
            }
        }
    }

    /**
     * Update enhancement history in the UI
     */
    function updateEnhancementHistory() {
        if (typeof aistmaStandaloneEditor !== 'undefined' && aistmaStandaloneEditor.enhancements_history) {
            const historyTable = document.querySelector('.enhancement-history-details tbody');
            if (historyTable) {
                // Clear existing rows
                historyTable.innerHTML = '';
                
                // Add new rows from the updated history
                aistmaStandaloneEditor.enhancements_history.forEach(function(enhancement) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <span class="enhancement-type-badge enhancement-type-${enhancement.type}">
                                ${enhancement.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </span>
                        </td>
                        <td>${new Date(enhancement.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                        <td>${enhancement.prompt_snippet}</td>
                    `;
                    historyTable.appendChild(row);
                });
            }
        }
    }

    /**
     * Replace selected text with improved version
     */
    function replaceSelectedText(improvedText, originalText) {
        
        // Try to use the stored range first
        let range = window.aistmaSelectedRange || selectedRange;
        
        if (range && originalText) {
            try {
                // Create a new range to replace the selected text
                const newRange = range.cloneRange();
                
                // Delete the selected content
                newRange.deleteContents();
                
                // Create a text node with the improved content
                const textNode = document.createTextNode(improvedText);
                
                // Insert the improved text
                newRange.insertNode(textNode);
                
                // Clear the selection
                window.getSelection().removeAllRanges();
                
                // Update the post content
                updatePostContent();
                
                // Trigger content updated event for change detection
                $(document).trigger('contentUpdated');
            } catch (error) {
                // Fallback: replace text in the preview content
                replaceTextInPreview(originalText, improvedText);
            }
        } else {
            // Fallback: replace text in the preview content
            replaceTextInPreview(originalText, improvedText);
        }
    }

    /**
     * Fallback method to replace text in preview
     */
    function replaceTextInPreview(originalText, improvedText) {
        const $preview = $('#content-preview');
        let content = $preview.html();
        
        // Replace the original text with improved text
        content = content.replace(originalText, improvedText);
        
        // Update the preview
        $preview.html(content);
        
        // Update the post content
        updatePostContent();
        
        // Trigger content updated event for change detection
        $(document).trigger('contentUpdated');
    }

    /**
     * Update the post content from the preview
     */
    function updatePostContent() {
        const $preview = $('#content-preview');
        const content = $preview.html();
        
        // Store the updated content (we'll save it when user clicks save)
        $preview.data('updated-content', content);
    }

    /**
     * Handle tags improvement
     */
    function handleImproveTags() {
        const title = $('#post-title').val();
        const content = getPreviewText();
        const currentTags = $('#post-tags').val();

        if (!title.trim() && !content.trim()) {
            showMessage('Please enter title or content to generate tags.', 'error');
            return;
        }

        setLoading('#improve-tags-btn', true);

        const prompt = `Generate relevant tags for this content. Current tags: ${currentTags}. Content: ${title} ${content.substring(0, 500)}`;

        const ajaxData = typeof aistmaStandaloneEditor !== 'undefined' ? aistmaStandaloneEditor : {
            ajaxurl: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: 'test-nonce'
        };
        
        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aistma_standalone_improve_content',
                nonce: ajaxData.nonce,
                content: currentTags || 'No tags',
                prompt: prompt,
                operation_type: 'text_improve',
                post_id: currentPostId,
                selected_text: currentTags || 'No tags',
                user_prompt: prompt
            },
            success: function(response) {
                setLoading('#improve-tags-btn', false);
                
                if (response.success) {
                    const improvedTags = response.data.content.replace(/[^\w\s,]/g, '').trim();
                    $('#post-tags').val(improvedTags);
                    
                    // Update enhancement counter if available
                    if (response.data.enhancement_status) {
                        enhancementsUsed = response.data.enhancement_status.used;
                        enhancementsRemaining = response.data.enhancement_status.remaining;
                        updateEnhancementDisplay();
                    } else {
                        // Fallback: increment locally if no status from API
                        enhancementsUsed++;
                        if (enhancementsLimit > 0) {
                            enhancementsRemaining = Math.max(0, enhancementsLimit - enhancementsUsed);
                        }
                        updateEnhancementDisplay();
                    }
                    
                    // Update enhancement history immediately with current data
                    updateEnhancementHistoryImmediately();
                    
                    // Refresh enhancement data from server to get updated history
                    refreshEnhancementData();
                    
                    showMessage('Tags improved successfully!', 'success');
                    // Trigger change detection
                    checkForChanges();
                } else {
                    showMessage(response.data || 'Failed to improve tags.', 'error');
                }
            },
            error: function() {
                setLoading('#improve-tags-btn', false);
                showMessage('Network error while improving tags.', 'error');
            }
        });
    }

    /**
     * Handle SEO improvement
     */
    function handleImproveSEO() {
        const title = $('#post-title').val();
        const content = getPreviewText();
        const currentMeta = $('#meta-description').val();

        if (!title.trim() && !content.trim()) {
            showMessage('Please enter title or content to generate meta description.', 'error');
            return;
        }

        setLoading('#improve-seo-btn', true);

        const prompt = `Generate a compelling meta description (150-160 characters) for this content. Current meta: ${currentMeta}. Content: ${title} ${content.substring(0, 500)}`;

        const ajaxData = typeof aistmaStandaloneEditor !== 'undefined' ? aistmaStandaloneEditor : {
            ajaxurl: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: 'test-nonce'
        };
        
        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aistma_standalone_improve_content',
                nonce: ajaxData.nonce,
                content: currentMeta || 'No meta description',
                prompt: prompt,
                operation_type: 'text_improve',
                post_id: currentPostId,
                selected_text: currentMeta || 'No meta description',
                user_prompt: prompt
            },
            success: function(response) {
                setLoading('#improve-seo-btn', false);
                
                if (response.success) {
                    const improvedMeta = response.data.content.trim();
                    $('#meta-description').val(improvedMeta);
                    
                    // Update enhancement counter if available
                    if (response.data.enhancement_status) {
                        enhancementsUsed = response.data.enhancement_status.used;
                        enhancementsRemaining = response.data.enhancement_status.remaining;
                        updateEnhancementDisplay();
                    } else {
                        // Fallback: increment locally if no status from API
                        enhancementsUsed++;
                        if (enhancementsLimit > 0) {
                            enhancementsRemaining = Math.max(0, enhancementsLimit - enhancementsUsed);
                        }
                        updateEnhancementDisplay();
                    }
                    
                    // Update enhancement history immediately with current data
                    updateEnhancementHistoryImmediately();
                    
                    // Refresh enhancement data from server to get updated history
                    refreshEnhancementData();
                    
                    showMessage('Meta description generated successfully!', 'success');
                    // Trigger change detection
                    checkForChanges();
                } else {
                    showMessage(response.data || 'Failed to generate meta description.', 'error');
                }
            },
            error: function() {
                setLoading('#improve-seo-btn', false);
                showMessage('Network error while generating meta description.', 'error');
            }
        });
    }

    /**
     * Handle save post
     */
    function handleSavePost() {
        const title = $('#post-title').val();
        const content = getPreviewContent();
        const tags = $('#post-tags').val();
        const metaDescription = $('#meta-description').val();

        if (!title.trim()) {
            showMessage('Please enter a post title.', 'error');
            return;
        }

        setLoading('#save-post-btn', true);

        const ajaxData = typeof aistmaStandaloneEditor !== 'undefined' ? aistmaStandaloneEditor : {
            ajaxurl: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: 'test-nonce'
        };
        
        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aistma_standalone_save_post',
                nonce: ajaxData.nonce,
                post_id: currentPostId,
                title: title,
                content: content,
                tags: tags,
                meta_description: metaDescription
            },
            success: function(response) {
                setLoading('#save-post-btn', false);
                
                if (response.success) {
                    showMessage('Post saved successfully!', 'success');
                    updateOriginalState(); // Update original state after successful save
                } else {
                    showMessage(response.data || 'Failed to save post.', 'error');
                }
            },
            error: function() {
                setLoading('#save-post-btn', false);
                showMessage('Network error while saving post.', 'error');
            }
        });
    }

    /**
     * Get preview content as HTML
     */
    function getPreviewContent() {
        const $preview = $('#content-preview');
        const updatedContent = $preview.data('updated-content');
        return updatedContent || $preview.html();
    }

    /**
     * Get preview content as text
     */
    function getPreviewText() {
        const $preview = $('#content-preview');
        return $preview.text();
    }

    /**
     * Store original state for change detection
     */
    function storeOriginalState() {
        originalTitle = $('#post-title').val();
        originalContent = getPreviewContent();
        originalTags = $('#post-tags').val();
        originalMetaDescription = $('#meta-description').val();
        
        
        // Initially disable save button since no changes yet
        $('#save-post-btn').prop('disabled', true);
    }
    
    /**
     * Check if there are any changes and enable/disable save button accordingly
     */
    function checkForChanges() {
        const currentTitle = $('#post-title').val();
        const currentContent = getPreviewContent();
        const currentTags = $('#post-tags').val();
        const currentMetaDescription = $('#meta-description').val();
        
        const hasChanges = (
            currentTitle !== originalTitle ||
            currentContent !== originalContent ||
            currentTags !== originalTags ||
            currentMetaDescription !== originalMetaDescription
        );
        
        
        // Enable save button if there are changes, disable if no changes
        const $saveBtn = $('#save-post-btn');
        if (hasChanges) {
            $saveBtn.prop('disabled', false);
        } else {
            $saveBtn.prop('disabled', true);
        }
    }
    
    /**
     * Update original state after successful save
     */
    function updateOriginalState() {
        originalTitle = $('#post-title').val();
        originalContent = getPreviewContent();
        originalTags = $('#post-tags').val();
        originalMetaDescription = $('#meta-description').val();
        
        // Disable save button after successful save
        $('#save-post-btn').prop('disabled', true);
    }

    /**
     * Set loading state for button
     */
    function setLoading(selector, loading) {
        const $btn = $(selector);
        if (loading) {
            $btn.prop('disabled', true);
            $btn.data('original-text', $btn.text());
            
            // For save button, keep "Save" text but show it's processing
            if (selector === '#save-post-btn') {
                $btn.text('Save');
            } else {
                $btn.text('Improving...');
            }
        } else {
            $btn.prop('disabled', false);
            $btn.text($btn.data('original-text') || $btn.text());
        }
    }

    /**
     * Show message
     */
    function showMessage(message, type) {
        const $message = $(`<div class="aistma-message ${type}">${message}</div>`);
        $('.wrap h1').after($message);
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $message.remove();
            });
        }, 5000);
    }

    // Image handling functions removed - Post Images section removed

})(jQuery);