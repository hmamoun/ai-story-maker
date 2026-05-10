/**
 * AI Content Editor Block Editor Integration
 *
 * Integrates AI content editing capabilities with the WordPress Block Editor (Gutenberg).
 * Provides a sidebar plugin for text improvement and image insertion.
 */

(function() {
    'use strict';

    // Check if required WordPress dependencies are available
    if (typeof wp === 'undefined' || !wp.plugins || !wp.editPost || !wp.components || !wp.element || !wp.data || !wp.i18n) {
        console.error('AI Content Editor: Required WordPress dependencies not available');
        return;
    }

    const { registerPlugin } = wp.plugins;
    // Use the new API if available, fallback to deprecated one
    const PluginDocumentSettingPanel = wp.editor?.PluginDocumentSettingPanel || wp.editPost?.PluginDocumentSettingPanel;
    const { PanelBody, PanelRow, TextareaControl, RadioControl, Button, Spinner } = wp.components;
    const { useState, useEffect } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { __ } = wp.i18n;
    const { createElement: el } = wp.element;
    
    // Check if wp.blocks is available, if not, use a fallback
    const createBlock = wp.blocks ? wp.blocks.createBlock : function(type, attributes) {
        console.warn('AI Content Editor: wp.blocks not available, using fallback');
        return { type, attributes };
    };

    let selectedText = '';
    let improvedContent = '';
    let currentOperation = 'text_improve';
    let isProcessing = false;

    /**
     * AI Content Editor Panel Component
     */
    function AIContentEditorPanel() {
        try {
            const [prompt, setPrompt] = useState('');
            const [showResult, setShowResult] = useState(false);
            const [error, setError] = useState('');
            const [loading, setLoading] = useState(false);

            // Get block editor data with correct method names
            const blockEditorData = useSelect((select) => {
                const blockEditor = select('core/block-editor');
                return {
                    selectedBlocks: blockEditor.getMultiSelectedBlocks ? blockEditor.getMultiSelectedBlocks() : [],
                    selectedBlock: blockEditor.getSelectedBlock ? blockEditor.getSelectedBlock() : null,
                    getBlocks: blockEditor.getBlocks ? blockEditor.getBlocks() : []
                };
            }, []);
            
            const { selectedBlocks, selectedBlock } = blockEditorData;
            const { insertBlocks, replaceBlock } = useDispatch('core/block-editor');

        useEffect(() => {
            // Update selected text when selection changes
            updateSelectedText();
        }, [selectedBlocks]);

        /**
         * Update selected text from current block selection
         */
        function updateSelectedText() {
            if (selectedBlocks.length === 1) {
                const block = selectedBlocks[0];
                if (block && block.attributes && block.attributes.content) {
                    selectedText = block.attributes.content;
                } else if (block && block.innerBlocks) {
                    // For blocks with inner content
                    selectedText = extractTextFromBlocks(block.innerBlocks);
                }
            } else {
                selectedText = '';
            }
        }

        /**
         * Extract text content from blocks
         */
        function extractTextFromBlocks(blocks) {
            let text = '';
            blocks.forEach(block => {
                if (block.attributes && block.attributes.content) {
                    text += block.attributes.content + ' ';
                }
                if (block.innerBlocks) {
                    text += extractTextFromBlocks(block.innerBlocks);
                }
            });
            return text.trim();
        }

        /**
         * Handle content improvement
         */
        function handleImproveContent() {
            if (isProcessing || !selectedText || !prompt.trim()) {
                setError('Please select text and enter improvement instructions.');
                return;
            }

            isProcessing = true;
            setLoading(true);
            setError('');

            const requestData = {
                action: 'aistma_improve_content',
                nonce: aistmaBlockEditor.nonce,
                selected_text: selectedText,
                user_prompt: prompt.trim(),
                operation_type: currentOperation,
                editor_type: 'block'
            };

            // Make AJAX request
            fetch(aistmaBlockEditor.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    improvedContent = data.content;
                    setShowResult(true);
                } else {
                    setError(data.data || 'An error occurred while improving content.');
                }
            })
            .catch(error => {
                console.error('AI Content Editor Error:', error);
                setError('Network error. Please try again.');
            })
            .finally(() => {
                isProcessing = false;
                setLoading(false);
            });
        }

        /**
         * Apply improved content to editor
         */
        function applyImprovedContent() {
            if (!improvedContent || !selectedBlock) return;

            if (currentOperation === 'text_improve') {
                // Replace block content
                const newBlock = {
                    ...selectedBlock,
                    attributes: {
                        ...selectedBlock.attributes,
                        content: improvedContent
                    }
                };
                replaceBlock(selectedBlock.clientId, newBlock);
            } else {
                // Insert image block
                const imageBlock = createBlock('core/image', {
                    url: extractImageUrl(improvedContent),
                    alt: prompt
                });
                insertBlocks([imageBlock], selectedBlocks.length, selectedBlock.clientId);
            }

            // Reset state
            setShowResult(false);
            setPrompt('');
            improvedContent = '';
        }

        /**
         * Extract image URL from HTML content
         */
        function extractImageUrl(htmlContent) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlContent, 'text/html');
            const img = doc.querySelector('img');
            return img ? img.src : '';
        }

        /**
         * Discard result
         */
        function discardResult() {
            setShowResult(false);
            improvedContent = '';
        }

        /**
         * Clear selection
         */
        function clearSelection() {
            setPrompt('');
            setShowResult(false);
            setError('');
            selectedText = '';
        }

        return el(PluginDocumentSettingPanel, {
            name: 'aistma-content-editor',
            title: __('AI Content Editor', 'ai-story-maker'),
            className: 'aistma-content-editor-panel'
        },
            el(PanelBody, { 
                title: __('Improve Content', 'ai-story-maker'),
                initialOpen: true 
            },
                // Selection status
                selectedText ? 
                    el('div', { className: 'aistma-selection-status' },
                        el('p', { className: 'selection-info' }, 
                            __('Selected text:', 'ai-story-maker'),
                            el('em', {}, ` "${selectedText.substring(0, 50)}${selectedText.length > 50 ? '...' : ''}"`)
                        )
                    ) :
                    el('p', { className: 'no-selection' }, 
                        __('Select text in a block to start improving with AI', 'ai-story-maker')
                    ),

                // Improvement prompt
                selectedText && el(TextareaControl, {
                    label: __('Improvement Instructions', 'ai-story-maker'),
                    value: prompt,
                    onChange: setPrompt,
                    placeholder: __('Describe how you want to improve this text...', 'ai-story-maker'),
                    rows: 3
                }),

                // Operation type selection
                selectedText && el(RadioControl, {
                    label: __('Operation Type', 'ai-story-maker'),
                    selected: currentOperation,
                    options: [
                        { label: __('Improve Text', 'ai-story-maker'), value: 'text_improve' },
                        { label: __('Add Image', 'ai-story-maker'), value: 'image_insert' },
                        { label: __('Replace with Image', 'ai-story-maker'), value: 'image_replace' }
                    ],
                    onChange: (value) => { currentOperation = value; }
                }),

                // Action buttons
                selectedText && prompt.trim() && el('div', { className: 'aistma-editor-actions' },
                    el(Button, {
                        variant: 'primary',
                        onClick: handleImproveContent,
                        disabled: loading || isProcessing,
                        className: 'improve-button'
                    },
                        loading ? el(Spinner) : __('Improve Content', 'ai-story-maker')
                    ),
                    el(Button, {
                        variant: 'secondary',
                        onClick: clearSelection,
                        disabled: loading || isProcessing
                    }, __('Clear', 'ai-story-maker'))
                ),

                // Error display
                error && el('div', { className: 'aistma-error notice notice-error' },
                    el('p', {}, error),
                    el(Button, {
                        variant: 'link',
                        onClick: () => setError('')
                    }, __('Dismiss', 'ai-story-maker'))
                ),

                // Result display
                showResult && improvedContent && el('div', { className: 'aistma-result' },
                    el('h4', {}, __('Improved Content:', 'ai-story-maker')),
                    el('div', { 
                        className: 'result-preview',
                        dangerouslySetInnerHTML: { __html: improvedContent }
                    }),
                    el('div', { className: 'result-actions' },
                        el(Button, {
                            variant: 'primary',
                            onClick: applyImprovedContent
                        }, __('Apply Changes', 'ai-story-maker')),
                        el(Button, {
                            variant: 'secondary',
                            onClick: discardResult
                        }, __('Discard', 'ai-story-maker'))
                    )
                )
            )
        );
        } catch (error) {
            console.error('AI Content Editor: Error in AIContentEditorPanel:', error);
            return el('div', { className: 'aistma-error' },
                el('p', {}, 'AI Content Editor encountered an error. Please refresh the page.')
            );
        }
    }

    // Register the plugin with error handling
    try {
        registerPlugin('aistma-content-editor', {
            render: AIContentEditorPanel,
            icon: 'edit',
        });
        console.log('AI Content Editor: Plugin registered successfully');
    } catch (error) {
        console.error('AI Content Editor: Failed to register plugin:', error);
    }

    // Expose functions for external access
    window.aistmaContentEditor = window.aistmaContentEditor || {};
    window.aistmaContentEditor.block = {
        selectedText: () => selectedText,
        improvedContent: () => improvedContent,
        currentOperation: () => currentOperation
    };

})();
