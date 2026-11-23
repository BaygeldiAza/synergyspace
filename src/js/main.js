document.addEventListener('DOMContentLoaded', function () {
    var dashboardSearch = document.getElementById('dashboardSearch');
    var searchableCards = document.querySelectorAll('.searchable-card');
    var searchEmpty = document.getElementById('dashboardSearchEmpty');

    if (dashboardSearch) {
        dashboardSearch.addEventListener('input', function () {
            var query = dashboardSearch.value.trim().toLowerCase();
            var visibleCount = 0;

            searchableCards.forEach(function (card) {
                var haystack = (
                    card.getAttribute('data-search') + ' ' + card.textContent
                ).toLowerCase();
                var matches = query === '' || haystack.includes(query);
                card.hidden = !matches;
                if (matches) {
                    visibleCount += 1;
                }
            });

            if (searchEmpty) {
                searchEmpty.hidden = query === '' || visibleCount > 0;
            }
        });
    }

    var notificationButton = document.querySelector('.notification-button');
    if (notificationButton) {
        notificationButton.addEventListener('click', function () {
            notificationButton.classList.toggle('has-read');
            var dot = notificationButton.querySelector('.notification-dot');
            if (dot) {
                dot.hidden = notificationButton.classList.contains('has-read');
            }
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (event) {
            var target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    var bio = document.getElementById('bio');
    var bioCount = document.getElementById('bioCount');

    if (bio && bioCount) {
        var updateBioCount = function () {
            bioCount.textContent = String(bio.value.length);
        };

        bio.addEventListener('input', updateBioCount);
        updateBioCount();
    }

    var photoInput = document.getElementById('profile_image');
    var photoPreview = document.querySelector('#profilePhotoPreview');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files && photoInput.files[0];
            if (!file || !file.type.match(/^image\/(jpeg|png|webp)$/)) {
                return;
            }

            var reader = new FileReader();
            reader.addEventListener('load', function () {
                var image = document.createElement('img');
                image.src = reader.result;
                image.alt = '';
                image.className = 'profile-photo profile-photo-preview';
                photoPreview.replaceChildren(image);
            });
            reader.readAsDataURL(file);
        });
    }
});