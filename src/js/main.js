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

    var skillOptions = document.querySelectorAll('input[name="skills[]"]');
    var selectedSkillList = document.querySelector('[data-selected-skill-list]');
    var selectedSkillEmpty = document.querySelector('[data-selected-skill-empty]');

    if (skillOptions.length && selectedSkillList) {
        var updateSelectedSkills = function () {
            selectedSkillList.querySelectorAll('[data-skill-chip]').forEach(function (chip) {
                chip.remove();
            });

            skillOptions.forEach(function (option) {
                if (!option.checked) {
                    return;
                }

                var chip = document.createElement('span');
                chip.className = 'profile-tag profile-tag-skill';
                chip.dataset.skillChip = option.value;
                chip.appendChild(document.createTextNode(option.dataset.skillName));

                var removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'profile-skill-remove';
                removeButton.dataset.removeSkill = option.value;
                removeButton.setAttribute('aria-label', 'Remove ' + option.dataset.skillName + ' from selected skills');

                var removeIcon = document.createElement('i');
                removeIcon.className = 'fas fa-xmark';
                removeIcon.setAttribute('aria-hidden', 'true');
                removeButton.appendChild(removeIcon);
                chip.appendChild(removeButton);
                selectedSkillList.insertBefore(chip, selectedSkillEmpty || null);
            });

            if (selectedSkillEmpty) {
                selectedSkillEmpty.hidden = Array.from(skillOptions).some(function (option) {
                    return option.checked;
                });
            }
        };

        skillOptions.forEach(function (option) {
            option.addEventListener('change', updateSelectedSkills);
        });

        selectedSkillList.addEventListener('click', function (event) {
            var removeButton = event.target.closest('[data-remove-skill]');
            if (!removeButton) {
                return;
            }

            var option = Array.from(skillOptions).find(function (skillOption) {
                return skillOption.value === removeButton.dataset.removeSkill;
            });
            if (option) {
                option.checked = false;
                updateSelectedSkills();
            }
        });
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