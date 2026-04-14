<script>
    document.addEventListener('DOMContentLoaded', function () {
        function setupDepartmentPicker(searchSelector, listSelector) {
            const searchInput = document.querySelector(searchSelector);
            const list = document.querySelector(listSelector);

            if (!searchInput || !list) {
                return;
            }

            const options = Array.from(list.querySelectorAll('.department-option'));
            const emptyState = list.querySelector('.department-picker__empty');

            function filterOptions() {
                const term = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                options.forEach(function (option) {
                    const matches = term === '' || option.dataset.departmentName.includes(term);
                    option.classList.toggle('d-none', !matches);

                    if (matches) {
                        visibleCount += 1;
                    }
                });

                if (emptyState) {
                    emptyState.classList.toggle('d-none', visibleCount > 0);
                }
            }

            filterOptions();
            searchInput.addEventListener('input', filterOptions);
        }

        document.querySelectorAll('[data-select-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                const list = document.querySelector(button.getAttribute('data-select-all'));

                if (!list) {
                    return;
                }

                list.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                    checkbox.checked = true;
                });
            });
        });

        document.querySelectorAll('[data-clear-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                const list = document.querySelector(button.getAttribute('data-clear-all'));

                if (!list) {
                    return;
                }

                list.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                    checkbox.checked = false;
                });
            });
        });

        setupDepartmentPicker('#department-search-create', '#department-list-create');
        setupDepartmentPicker('#department-search-edit', '#department-list-edit');
    });
</script>
