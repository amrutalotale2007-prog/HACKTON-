document.addEventListener("DOMContentLoaded", function () {

    /* =========================================
       DEMO BUTTONS
    ========================================= */

    document.querySelectorAll("[data-demo]").forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const message =
                button.dataset.demo ||
                "This feature is ready for backend integration.";

            alert(message);

        });

    });


    /* =========================================
       SKILL ASSESSMENT OPTIONS
    ========================================= */

    document.querySelectorAll(".option").forEach(function (option) {

        option.addEventListener("click", function () {

            const group = option.parentElement;

            group.querySelectorAll(".option").forEach(function (item) {

                item.style.borderColor = "";
                item.style.background = "";

            });

            option.style.borderColor = "#635bff";
            option.style.background = "#faf9ff";

        });

    });


    /* NOTE: this file previously had a block here that walked every
       element on every page and blanked out any text matching a
       hardcoded demo name ("Amruta Ashok"), and deleted any element
       with classes like .profile-name/.user-name/.student-name on
       every single page load. That's a workaround, not a fix — the
       real problem is that some template (not among the files
       reviewed here, e.g. a student dashboard) has that name hardcoded
       in its HTML/PHP instead of echoing $_SESSION['name']. Deleting
       .user-name/.profile-name globally would also wipe out the
       *real* logged-in user's name everywhere those classes are used
       for their legitimate purpose. Removed this block; fix the
       hardcoded name at its source instead. */


    /* =========================================
       SIDEBAR ACTIVE LINK
    ========================================= */

    const currentPage =
        window.location.pathname.split("/").pop();

    document.querySelectorAll(".side-link").forEach(function (link) {

        const linkPage =
            link.getAttribute("href");

        if (linkPage === currentPage) {

            link.classList.add("active");

        }

    });


    /* =========================================
       NOTIFICATION BUTTON
    ========================================= */

    document.querySelectorAll(".notification-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            alert("You have 3 new notifications.");

        });

    });


    /* =========================================
       LOGOUT
    ========================================= */

    document.querySelectorAll(".logout-link").forEach(function (button) {

        button.addEventListener("click", function (event) {

            const confirmLogout =
                confirm("Are you sure you want to logout?");

            if (!confirmLogout) {

                event.preventDefault();

            }

        });

    });


    /* =========================================
       MOBILE SIDEBAR
    ========================================= */

    const sidebar = document.querySelector(".sidebar");

    const menuButton =
        document.querySelector(".mobile-menu-btn");

    if (menuButton && sidebar) {

        menuButton.addEventListener("click", function () {

            sidebar.classList.toggle("mobile-sidebar");

        });

    }


    /* =========================================
       SEARCH FILTER
    ========================================= */

    const searchInput =
        document.querySelector(".search-input");

    if (searchInput) {

        searchInput.addEventListener("input", function () {

            const searchValue =
                searchInput.value.toLowerCase();

            document.querySelectorAll(
                ".op-card, .candidate-card, .application-row, .search-item"
            ).forEach(function (item) {

                const text =
                    item.textContent.toLowerCase();

                if (text.includes(searchValue)) {

                    item.style.display = "";

                } else {

                    item.style.display = "none";

                }

            });

        });

    }


    /* =========================================
       FILTER DROPDOWN
    ========================================= */

    document.querySelectorAll(".filter-select").forEach(function (select) {

        select.addEventListener("change", function () {

            const selected =
                select.value.toLowerCase();

            document.querySelectorAll(
                ".op-card, .candidate-card, .application-row"
            ).forEach(function (item) {

                if (
                    selected === "" ||
                    item.textContent.toLowerCase().includes(selected)
                ) {

                    item.style.display = "";

                } else {

                    item.style.display = "none";

                }

            });

        });

    });


    /* =========================================
       VIEW BUTTONS
    ========================================= */

    document.querySelectorAll(".view-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            alert(
                "Profile details will be available after backend integration."
            );

        });

    });


    /* =========================================
       APPLY BUTTONS
    ========================================= */

    document.querySelectorAll(".apply-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            const confirmation =
                confirm("Do you want to apply for this opportunity?");

            if (confirmation) {

                alert(
                    "Application submitted successfully!"
                );

                button.textContent = "Applied";
                button.disabled = true;

            }

        });

    });


    /* =========================================
       SHORTLIST BUTTONS
    ========================================= */

    document.querySelectorAll(".shortlist-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            button.textContent = "Shortlisted";
            button.classList.add("shortlisted");

            alert("Candidate shortlisted successfully!");

        });

    });


    /* =========================================
       REJECT BUTTONS
    ========================================= */

    document.querySelectorAll(".reject-btn").forEach(function (button) {

        button.addEventListener("click", function () {

            const confirmation =
                confirm("Are you sure you want to reject this candidate?");

            if (confirmation) {

                const card =
                    button.closest(
                        ".candidate-card, .candidate-row"
                    );

                if (card) {

                    card.style.opacity = "0.5";

                }

                alert("Candidate rejected.");

            }

        });

    });


    /* =========================================
       PASSWORD SHOW / HIDE
    ========================================= */

    document.querySelectorAll(".password-toggle").forEach(function (button) {

        button.addEventListener("click", function () {

            const input =
                button.parentElement.querySelector("input");

            if (!input) return;

            if (input.type === "password") {

                input.type = "text";
                button.textContent = "Hide";

            } else {

                input.type = "password";
                button.textContent = "Show";

            }

        });

    });


    /* =========================================
       FORM DEMO SUBMIT
    ========================================= */

    document.querySelectorAll("form[data-demo-submit]").forEach(function (form) {

        form.addEventListener("submit", function (event) {

            event.preventDefault();

            const message =
                form.dataset.demoSubmit ||
                "Form submitted successfully!";

            alert(message);

        });

    });


    /* =========================================
       SMOOTH SCROLL
    ========================================= */

    document.querySelectorAll('a[href^="#"]').forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId =
                link.getAttribute("href");

            if (
                targetId &&
                targetId !== "#"
            ) {

                const target =
                    document.querySelector(targetId);

                if (target) {

                    event.preventDefault();

                    target.scrollIntoView({
                        behavior: "smooth"
                    });

                }

            }

        });

    });


    /* =========================================
       CONSOLE MESSAGE
    ========================================= */

    console.log(
        "SkillBridge Frontend loaded successfully."
    );

});