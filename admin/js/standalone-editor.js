/**
 * AI Story Maker Standalone Editor JavaScript - Rebuilt for Text Selection
 */

(function($) {
    'use strict';

    let currentPostId = null;
    let selectedText = '';
    let selectedRange = null;
    let currentOperation = 'text_improve';
    
    // Track original state for change detection
    let originalTitle = '';
    let originalContent = '';
    let originalTags = '';
    let originalMetaDescription = '';

    // Initialize when document is ready
    $(document).ready(function() {
        initStandaloneEditor();
    });

    /**
     * Initialize the standalone editor
     */
    function initStandaloneEditor() {
        console.log('AI Standalone Editor: Initializing...');
        
        // Get post ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        currentPostId = urlParams.get('post_id');

        if (!currentPostId) {
            showMessage('No post ID provided.', 'error');
            return;
        }

        console.log('AI Standalone Editor: Post ID found:', currentPostId);
        console.log('AI Standalone Editor: Localized data:', typeof aistmaStandaloneEditor !== 'undefined' ? aistmaStandaloneEditor : 'Not available');

        bindEvents();
        setupTextSelection();
        
        // Store original state after a small delay to ensure DOM is fully ready
        setTimeout(function() {
            storeOriginalState();
        }, 100);
        
        console.log('AI Standalone Editor: Initialization complete');
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Popup controls
        $('.aistma-popup-close, .aistma-popup-cancel').on('click', closePopup);
        $('#improve-selected-btn').on('click', handleImproveSelected);
        
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
        console.log('AI Standalone Editor: Setting up text selection for element:', $preview.length);
        
        // Handle text selection ONLY on mouse up (when user finishes selecting)
        $preview.on('mouseup', function() {
            console.log('AI Standalone Editor: Mouse up event triggered');
            setTimeout(function() {
                handleTextSelection();
            }, 10);
        });

        // Also handle mouseup on document for better selection detection
        $(document).on('mouseup', function(e) {
            if ($(e.target).closest('#content-preview').length > 0) {
                console.log('AI Standalone Editor: Document mouseup in preview area');
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
        
        console.log('AI Standalone Editor: Text selection detected:', text.length > 0 ? text.substring(0, 50) + '...' : 'No text');
        
        if (text.length > 0) {
            selectedText = text;
            selectedRange = selection.getRangeAt(0);
            console.log('AI Standalone Editor: Showing selection info for:', text.substring(0, 30) + '...');
            showSelectionInfo(text);
        } else {
            hideSelectionInfo();
        }
    }

    /**
     * Show selection info and popup
     */
    function showSelectionInfo(text) {
        console.log('AI Standalone Editor: Showing selection info');
        
        // Store the selected text globally
        window.aistmaSelectedText = text;
        window.aistmaSelectedRange = selectedRange;
        
        // Show selection info in sidebar
        $('#selected-text-preview').text(text);
        $('#selection-info').show();

        // Show popup immediately on text selection (no timer delay)
        console.log('AI Standalone Editor: Showing popup for text:', text.substring(0, 30) + '...');
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
        console.log('AI Standalone Editor: Showing popup');
        
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
     * Handle improve selected text
     */
    function handleImproveSelected() {
        const prompt = $('#improvement-prompt').val().trim();
        
        // Get the selected text from global storage or current selection
        const textToImprove = window.aistmaSelectedText || selectedText || '';
        
        console.log('AI Standalone Editor: Improving text:', textToImprove.substring(0, 50) + '...');
        console.log('AI Standalone Editor: Prompt:', prompt);

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
        
        console.log('AI Standalone Editor: Making AJAX request with data:', ajaxData);
        
        $.ajax({
            url: ajaxData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aistma_standalone_improve_content',
                nonce: ajaxData.nonce,
                content: textToImprove,
                prompt: prompt,
                operation_type: currentOperation
            },
            success: function(response) {
                setLoading('#improve-selected-btn', false);
                
                if (response.success) {
                    const improvedText = response.data.content;
                    console.log('AI Standalone Editor: Received improved text:', improvedText.substring(0, 50) + '...');
                    replaceSelectedText(improvedText, textToImprove);
                    closePopup();
                    showMessage('Text improved successfully!', 'success');
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
     * Replace selected text with improved version
     */
    function replaceSelectedText(improvedText, originalText) {
        console.log('AI Standalone Editor: Replacing text:', originalText.substring(0, 30) + '...', 'with:', improvedText.substring(0, 30) + '...');
        
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
                
                console.log('AI Standalone Editor: Text replaced successfully');
            } catch (error) {
                console.error('AI Standalone Editor: Error replacing text:', error);
                // Fallback: replace text in the preview content
                replaceTextInPreview(originalText, improvedText);
            }
        } else {
            console.log('AI Standalone Editor: No range available, using fallback method');
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
        
        console.log('AI Standalone Editor: Text replaced using fallback method');
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
                operation_type: 'text_improve'
            },
            success: function(response) {
                setLoading('#improve-tags-btn', false);
                
                if (response.success) {
                    const improvedTags = response.data.content.replace(/[^\w\s,]/g, '').trim();
                    $('#post-tags').val(improvedTags);
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
                operation_type: 'text_improve'
            },
            success: function(response) {
                setLoading('#improve-seo-btn', false);
                
                if (response.success) {
                    const improvedMeta = response.data.content.trim();
                    $('#meta-description').val(improvedMeta);
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
        
        console.log('AI Standalone Editor: Storing original state:', {
            title: originalTitle,
            content: originalContent ? originalContent.substring(0, 50) + '...' : 'empty',
            tags: originalTags,
            meta: originalMetaDescription
        });
        
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
        
        console.log('AI Standalone Editor: Checking for changes:', {
            hasChanges: hasChanges,
            titleChanged: currentTitle !== originalTitle,
            contentChanged: currentContent !== originalContent,
            tagsChanged: currentTags !== originalTags,
            metaChanged: currentMetaDescription !== originalMetaDescription,
            currentTitle: currentTitle,
            originalTitle: originalTitle,
            currentTags: currentTags,
            originalTags: originalTags
        });
        
        // Enable save button if there are changes, disable if no changes
        const $saveBtn = $('#save-post-btn');
        if (hasChanges) {
            $saveBtn.prop('disabled', false);
            console.log('AI Standalone Editor: Save button enabled due to changes');
        } else {
            $saveBtn.prop('disabled', true);
            console.log('AI Standalone Editor: Save button disabled - no changes');
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