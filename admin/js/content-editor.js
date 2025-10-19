/**
 * AI Content Editor JavaScript
 *
 * Handles text selection, content improvement requests, and editor integration
 * for both Classic Editor (TinyMCE) and Block Editor (Gutenberg).
 */

(function($) {
    'use strict';

    let selectedText = '';
    let selectedRange = null;
    let improvedContent = '';
    let currentOperation = 'text_improve';
    let isProcessing = false;

    // Initialize when document is ready
    $(document).ready(function() {
        initContentEditor();
    });

    /**
     * Initialize the content editor
     */
    function initContentEditor() {
        console.log('AI Content Editor: Initializing for', aistmaContentEditor.editorType, 'editor');

        if (aistmaContentEditor.editorType === 'classic') {
            initClassicEditor();
        } else {
            initBlockEditor();
        }

        bindEvents();
    }

    /**
     * Initialize Classic Editor (TinyMCE) integration
     */
    function initClassicEditor() {
        // Wait for TinyMCE to be ready
        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            setupTinyMCEIntegration();
        } else {
            // Wait for TinyMCE to load
            $(document).on('tinymce-editor-init', function(event, editor) {
                if (editor.id === 'content') {
                    setupTinyMCEIntegration();
                }
            });
        }
    }

    /**
     * Setup TinyMCE integration
     */
    function setupTinyMCEIntegration() {
        const editor = tinymce.get('content');
        if (!editor) return;

        // Listen for selection changes
        editor.on('selectionchange', function() {
            handleTextSelection();
        });

        // Listen for click events
        editor.on('click', function() {
            setTimeout(handleTextSelection, 100);
        });

        console.log('AI Content Editor: TinyMCE integration setup complete');
    }

    /**
     * Initialize Block Editor (Gutenberg) integration
     */
    function initBlockEditor() {
        // Block editor integration is handled by block-editor-integration.js
        console.log('AI Content Editor: Block editor integration will be handled by dedicated script');
    }

    /**
     * Handle text selection in editor
     */
    function handleTextSelection() {
        if (isProcessing) return;

        let text = '';
        let range = null;

        if (aistmaContentEditor.editorType === 'classic') {
            const editor = tinymce.get('content');
            if (editor && editor.selection) {
                text = editor.selection.getContent({ format: 'text' });
                range = editor.selection.getRng();
            }
        } else {
            // Block editor selection is handled by block-editor-integration.js
            return;
        }

        // Update selected text
        selectedText = text.trim();
        selectedRange = range;

        // Show/hide editor panel based on selection
        if (selectedText && selectedText.length > 10) {
            showEditorPanel();
            updateSelectedTextPreview();
        } else {
            hideEditorPanel();
        }
    }

    /**
     * Show the editor panel
     */
    function showEditorPanel() {
        $('.aistma-editor-selection').show();
        $('.aistma-editor-status').hide();
    }

    /**
     * Hide the editor panel
     */
    function hideEditorPanel() {
        $('.aistma-editor-selection').hide();
        $('.aistma-editor-status').show();
    }

    /**
     * Update the selected text preview
     */
    function updateSelectedTextPreview() {
        const preview = selectedText.length > 200 
            ? selectedText.substring(0, 200) + '...' 
            : selectedText;
        
        $('.selected-text-preview').text(preview);
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Improve content button
        $('#aistma-improve-content').on('click', function() {
            improveContent();
        });

        // Clear selection button
        $('#aistma-clear-selection').on('click', function() {
            clearSelection();
        });

        // Apply result button
        $('#aistma-apply-result').on('click', function() {
            applyImprovedContent();
        });

        // Discard result button
        $('#aistma-discard-result').on('click', function() {
            discardResult();
        });

        // Dismiss error button
        $('#aistma-dismiss-error').on('click', function() {
            hideError();
        });

        // Operation type change
        $('input[name="operation_type"]').on('change', function() {
            currentOperation = $(this).val();
            updateOperationUI();
        });

        // Enter key in prompt textarea
        $('#aistma-improvement-prompt').on('keydown', function(e) {
            if (e.ctrlKey && e.keyCode === 13) { // Ctrl+Enter
                improveContent();
            }
        });
    }

    /**
     * Improve content using AI
     */
    function improveContent() {
        if (isProcessing || !selectedText) return;

        const prompt = $('#aistma-improvement-prompt').val().trim();
        if (!prompt) {
            showError('Please enter improvement instructions.');
            return;
        }

        isProcessing = true;
        showLoading();

        // Prepare request data
        const requestData = {
            action: 'aistma_improve_content',
            nonce: aistmaContentEditor.nonce,
            selected_text: selectedText,
            user_prompt: prompt,
            operation_type: currentOperation,
            editor_type: aistmaContentEditor.editorType
        };

        // Make AJAX request
        $.post(aistmaContentEditor.ajaxurl, requestData)
            .done(function(response) {
                if (response.success) {
                    handleSuccessResponse(response.data);
                } else {
                    showError(response.data || 'An error occurred while improving content.');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AI Content Editor Error:', error);
                showError('Network error. Please try again.');
            })
            .always(function() {
                isProcessing = false;
                hideLoading();
            });
    }

    /**
     * Handle successful API response
     */
    function handleSuccessResponse(data) {
        improvedContent = data.content;
        
        // Show result panel
        $('.aistma-editor-result').show();
        $('.aistma-editor-selection').hide();
        
        // Update result preview
        const preview = improvedContent.length > 300 
            ? improvedContent.substring(0, 300) + '...' 
            : improvedContent;
        
        $('.result-preview').html(preview);
        
        // Show usage info if available
        if (data.usage_info) {
            showUsageInfo(data.usage_info);
        }
    }

    /**
     * Apply improved content to editor
     */
    function applyImprovedContent() {
        if (!improvedContent || !selectedRange) return;

        if (aistmaContentEditor.editorType === 'classic') {
            applyToClassicEditor();
        } else {
            applyToBlockEditor();
        }

        // Clear selection and hide panels
        clearSelection();
        hideResultPanel();
    }

    /**
     * Apply content to Classic Editor
     */
    function applyToClassicEditor() {
        const editor = tinymce.get('content');
        if (!editor) return;

        // Restore selection
        editor.selection.setRng(selectedRange);
        
        // Insert improved content
        if (currentOperation === 'text_improve') {
            editor.selection.setContent(improvedContent);
        } else {
            // For image operations, insert the HTML directly
            editor.selection.setContent(improvedContent);
        }
        
        // Trigger change event
        editor.fire('change');
    }

    /**
     * Apply content to Block Editor
     */
    function applyToBlockEditor() {
        // Block editor integration is handled by block-editor-integration.js
        console.log('Block editor apply will be handled by dedicated script');
    }

    /**
     * Clear current selection
     */
    function clearSelection() {
        selectedText = '';
        selectedRange = null;
        improvedContent = '';
        
        // Clear UI
        $('#aistma-improvement-prompt').val('');
        $('.selected-text-preview').text('');
        
        // Hide panels
        hideEditorPanel();
        hideResultPanel();
        
        // Clear editor selection
        if (aistmaContentEditor.editorType === 'classic') {
            const editor = tinymce.get('content');
            if (editor && editor.selection) {
                editor.selection.collapse();
            }
        }
    }

    /**
     * Discard the improved result
     */
    function discardResult() {
        improvedContent = '';
        hideResultPanel();
        showEditorPanel();
    }

    /**
     * Show loading state
     */
    function showLoading() {
        $('#aistma-improve-content .button-text').text('Processing...');
        $('#aistma-improve-content .spinner').show();
        $('#aistma-improve-content').prop('disabled', true);
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        $('#aistma-improve-content .button-text').text('Improve Content');
        $('#aistma-improve-content .spinner').hide();
        $('#aistma-improve-content').prop('disabled', false);
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('.aistma-editor-error .error-message').text(message);
        $('.aistma-editor-error').show();
        
        // Auto-hide after 5 seconds
        setTimeout(hideError, 5000);
    }

    /**
     * Hide error message
     */
    function hideError() {
        $('.aistma-editor-error').hide();
    }

    /**
     * Hide result panel
     */
    function hideResultPanel() {
        $('.aistma-editor-result').hide();
    }

    /**
     * Update UI based on operation type
     */
    function updateOperationUI() {
        const button = $('#aistma-improve-content .button-text');
        
        switch (currentOperation) {
            case 'text_improve':
                button.text('Improve Text');
                break;
            case 'image_insert':
                button.text('Add Image');
                break;
            case 'image_replace':
                button.text('Replace with Image');
                break;
        }
    }

    /**
     * Show usage information
     */
    function showUsageInfo(usageInfo) {
        if (usageInfo.daily_limit > 0) {
            const message = `Usage: ${usageInfo.used_today}/${usageInfo.daily_limit} operations today`;
            console.log('AI Content Editor:', message);
        }
    }

    // Expose functions for block editor integration
    window.aistmaContentEditor = window.aistmaContentEditor || {};
    window.aistmaContentEditor.classic = {
        handleTextSelection: handleTextSelection,
        applyImprovedContent: applyImprovedContent,
        clearSelection: clearSelection,
        showError: showError,
        hideError: hideError
    };

})(jQuery);
