document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('ai-story-search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchQuery = document.getElementById('ai-story-search').value;
            const url = new URL(window.location.href);
            url.searchParams.set('s', searchQuery);
            history.pushState(null, '', url.toString());
            location.reload();
        });
    }
}); 