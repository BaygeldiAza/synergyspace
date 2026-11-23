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
    var photoPreview = document.querySelector('#profileImagePreview');
    var photoMessage = document.getElementById('profileImageMessage');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files && photoInput.files[0];
            if (!file) {
                return;
            }

            var supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!supportedTypes.includes(file.type)) {
                if (photoMessage) {
                    photoMessage.textContent = 'Choose a JPG, JPEG, PNG, or WebP image.';
                }
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                if (photoMessage) {
                    photoMessage.textContent = 'The image must be no larger than 2 MB.';
                }
                return;
            }

            if (photoMessage) {
                photoMessage.textContent = file.name;
            }

            var reader = new FileReader();
            reader.addEventListener('load', function () {
                var image = document.createElement('img');
                image.src = reader.result;
                image.alt = 'Selected profile picture preview';
                image.className = 'profile-image-preview-photo';
                photoPreview.replaceChildren(image);
            });
            reader.readAsDataURL(file);
        });
    }
});