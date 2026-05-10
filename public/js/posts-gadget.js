/**
 * Posts Gadget JavaScript for AI Story Maker
 *
 * @package AI_Story_Maker
 * @since 2.0.1
 */

(function($) {
    'use strict';

    // Main Posts Gadget Class
    class PostsGadget {
        constructor(element) {
            this.$element = $(element);
            this.config = this.$element.data('config') || {};
            this.currentPage = 1;
            this.isLoading = false;
            this.searchTimeout = null;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initSearch();
            this.initFilters();
        }

        bindEvents() {
            // Load more button
            this.$element.on('click', '.aistma-load-more-btn', (e) => {
                e.preventDefault();
                this.loadMorePosts();
            });

            // Search functionality
            this.$element.on('input', '.aistma-posts-search', (e) => {
                this.handleSearch(e.target.value);
            });

            this.$element.on('click', '.aistma-search-btn', (e) => {
                e.preventDefault();
                const searchTerm = this.$element.find('.aistma-posts-search').val();
                this.handleSearch(searchTerm);
            });

            // Filter buttons
            this.$element.on('click', '.aistma-filter-btn', (e) => {
                e.preventDefault();
                this.handleFilter(e.currentTarget);
            });

            // Keyboard navigation
            this.$element.on('keydown', '.aistma-posts-search', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.handleSearch(e.target.value);
                }
            });
        }

        initSearch() {
            // Debounced search
            this.$element.on('input', '.aistma-posts-search', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.handleSearch(e.target.value);
                }, 300);
            });
        }

        initFilters() {
            // Initialize active filter
            const activeFilter = this.$element.find('.aistma-filter-btn.active').data('filter');
            if (activeFilter) {
                this.applyFilter(activeFilter);
            }
        }

        handleSearch(searchTerm) {
            if (this.isLoading) return;

            this.showLoading();
            this.hideError();
            this.hideEmpty();

            const data = {
                action: 'aistma_posts_gadget_search',
                nonce: aistmaPostsGadget.nonce,
                search: searchTerm.trim(),
                config: JSON.stringify(this.config)
            };

            $.post(aistmaPostsGadget.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.updatePosts(response.posts);
                        this.updateLoadMoreButton(response.has_more, 1);
                        this.hideLoading();
                    } else {
                        this.showError();
                    }
                })
                .fail(() => {
                    this.showError();
                })
                .always(() => {
                    this.isLoading = false;
                });
        }

        handleFilter(button) {
            if (this.isLoading) return;

            // Update active state
            this.$element.find('.aistma-filter-btn').removeClass('active');
            $(button).addClass('active');

            const filter = $(button).data('filter');
            this.applyFilter(filter);
        }

        applyFilter(filter) {
            if (this.isLoading) return;

            this.showLoading();
            this.hideError();
            this.hideEmpty();

            // Clear search when filtering
            this.$element.find('.aistma-posts-search').val('');

            let config = { ...this.config };
            let searchTerm = '';

            // Apply filter-specific configurations
            switch (filter) {
                case 'new':
                    config.highlight_new = true;
                    config.date_range = 'week';
                    break;
                case 'popular':
                    config.sort_by = 'popular';
                    config.sort_order = 'DESC';
                    break;
                case 'recent':
                    config.sort_by = 'date';
                    config.sort_order = 'DESC';
                    config.date_range = '';
                    break;
                default: // 'all'
                    config.highlight_new = false;
                    config.date_range = '';
                    break;
            }

            const data = {
                action: 'aistma_posts_gadget_search',
                nonce: aistmaPostsGadget.nonce,
                search: searchTerm,
                config: JSON.stringify(config)
            };

            $.post(aistmaPostsGadget.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.updatePosts(response.posts);
                        this.updateLoadMoreButton(response.has_more, 1);
                        this.hideLoading();
                        this.currentPage = 1;
                    } else {
                        this.showError();
                    }
                })
                .fail(() => {
                    this.showError();
                })
                .always(() => {
                    this.isLoading = false;
                });
        }

        loadMorePosts() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.showLoading();
            this.hideError();

            const nextPage = this.currentPage + 1;
            const activeFilter = this.$element.find('.aistma-filter-btn.active').data('filter');
            const searchTerm = this.$element.find('.aistma-posts-search').val().trim();

            let config = { ...this.config };

            // Apply current filter settings
            if (activeFilter === 'new') {
                config.highlight_new = true;
                config.date_range = 'week';
            } else if (activeFilter === 'popular') {
                config.sort_by = 'popular';
                config.sort_order = 'DESC';
            } else if (activeFilter === 'recent') {
                config.sort_by = 'date';
                config.sort_order = 'DESC';
                config.date_range = '';
            }

            const data = {
                action: 'aistma_posts_gadget_load',
                nonce: aistmaPostsGadget.nonce,
                page: nextPage,
                config: JSON.stringify(config)
            };

            $.post(aistmaPostsGadget.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.appendPosts(response.posts);
                        this.updateLoadMoreButton(response.has_more, response.next_page);
                        this.currentPage = nextPage;
                    } else {
                        this.showError();
                    }
                })
                .fail(() => {
                    this.showError();
                })
                .always(() => {
                    this.isLoading = false;
                    this.hideLoading();
                });
        }

        updatePosts(postsHtml) {
            const $postsGrid = this.$element.find('.aistma-posts-grid');
            $postsGrid.html(postsHtml);

            // Trigger animation for new posts
            this.animateNewPosts();

            // Check if no posts found
            if ($postsGrid.find('.aistma-no-posts').length > 0) {
                this.showEmpty();
            }
        }

        appendPosts(postsHtml) {
            const $postsGrid = this.$element.find('.aistma-posts-grid');
            $postsGrid.append(postsHtml);

            // Trigger animation for new posts
            this.animateNewPosts();
        }

        animateNewPosts() {
            const $newPosts = this.$element.find('.aistma-post-item').not('.aistma-animated');
            
            $newPosts.each((index, element) => {
                $(element).css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                });

                setTimeout(() => {
                    $(element).addClass('aistma-animated').css({
                        opacity: 1,
                        transform: 'translateY(0)',
                        transition: 'opacity 0.5s ease, transform 0.5s ease'
                    });
                }, index * 100);
            });
        }

        updateLoadMoreButton(hasMore, nextPage) {
            const $loadMoreBtn = this.$element.find('.aistma-load-more-btn');
            const $pagination = this.$element.find('.aistma-posts-pagination');

            if (hasMore) {
                $loadMoreBtn.data('page', nextPage);
                $pagination.show();
            } else {
                $pagination.hide();
            }
        }

        showLoading() {
            this.$element.find('.aistma-posts-loading').show();
            this.$element.find('.aistma-posts-pagination').hide();
        }

        hideLoading() {
            this.$element.find('.aistma-posts-loading').hide();
        }

        showError() {
            this.$element.find('.aistma-posts-error').show();
            this.hideLoading();
        }

        hideError() {
            this.$element.find('.aistma-posts-error').hide();
        }

        showEmpty() {
            this.$element.find('.aistma-posts-empty').show();
            this.hideLoading();
        }

        hideEmpty() {
            this.$element.find('.aistma-posts-empty').hide();
        }
    }

    // Initialize all posts gadgets when document is ready
    $(document).ready(function() {
        $('.aistma-posts-gadget').each(function() {
            new PostsGadget(this);
        });
    });

    // Re-initialize gadgets after AJAX content loads (for dynamic content)
    $(document).on('aistma-gadget-ready', function() {
        $('.aistma-posts-gadget').each(function() {
            if (!$(this).data('posts-gadget-initialized')) {
                $(this).data('posts-gadget-initialized', true);
                new PostsGadget(this);
            }
        });
    });

    // Utility functions
    window.AistmaPostsGadget = {
        // Public API for external use
        refresh: function(gadgetId) {
            const $gadget = $(gadgetId);
            if ($gadget.length) {
                $gadget.trigger('aistma-gadget-ready');
            }
        },

        // Search function for external use
        search: function(gadgetId, searchTerm) {
            const $gadget = $(gadgetId);
            if ($gadget.length) {
                $gadget.find('.aistma-posts-search').val(searchTerm);
                $gadget.find('.aistma-search-btn').trigger('click');
            }
        },

        // Filter function for external use
        filter: function(gadgetId, filter) {
            const $gadget = $(gadgetId);
            if ($gadget.length) {
                const $filterBtn = $gadget.find(`[data-filter="${filter}"]`);
                if ($filterBtn.length) {
                    $filterBtn.trigger('click');
                }
            }
        }
    };

    // Accessibility enhancements
    $(document).ready(function() {
        // Add ARIA labels
        $('.aistma-posts-search').attr('aria-label', aistmaPostsGadget.strings.search_placeholder);
        $('.aistma-load-more-btn').attr('aria-label', 'Load more posts');
        
        // Add role attributes
        $('.aistma-posts-grid').attr('role', 'list');
        $('.aistma-post-item').attr('role', 'listitem');
        
        // Keyboard navigation for filter buttons
        $('.aistma-filter-btn').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
        });
    });

    // Performance optimization: Intersection Observer for lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        // Observe all post images
        $(document).ready(function() {
            $('.aistma-post-thumbnail').each(function() {
                imageObserver.observe(this);
            });
        });
    }

    // Error handling
    window.addEventListener('error', function(e) {
        console.error('Posts Gadget Error:', e.error);
    });

})(jQuery);
