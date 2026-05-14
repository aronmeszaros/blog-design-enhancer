(function () {
    // Enables smooth scrolling for generated TOC links.
    var tocLinks = document.querySelectorAll('.bde-toc a[href^="#"]');

    if (tocLinks.length > 0) {
        tocLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var targetId = this.getAttribute('href');
                if (!targetId) {
                    return;
                }

                var target = document.querySelector(targetId);
                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }
})();
