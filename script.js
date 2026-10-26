
document.addEventListener("DOMContentLoaded", () => {
    const root = document.documentElement;

    // =========================
    // LIGHT / DARK MODE
    // =========================

    const themeToggle = document.getElementById("themeToggle");
    const themeKey = "saran-sekolah-theme";

    function loadTheme() {
        try {
            const saved = JSON.parse(
                localStorage.getItem(themeKey) || "{}"
            );

            root.dataset.theme =
                saved.mode === "light" ? "light" : "dark";
        } catch {
            root.dataset.theme = "dark";
        }

        updateThemeButton();
    }

    function updateThemeButton() {
        if (!themeToggle) return;

        const dark = root.dataset.theme !== "light";

        themeToggle.textContent = dark ? "☀️" : "🌙";
        themeToggle.title = dark
            ? "Ganti ke mode terang"
            : "Ganti ke mode gelap";

        themeToggle.setAttribute(
            "aria-label",
            themeToggle.title
        );
    }

    function saveTheme() {
        try {
            localStorage.setItem(
                themeKey,
                JSON.stringify({
                    mode: root.dataset.theme
                })
            );
        } catch (error) {
            console.warn("Tema tidak dapat disimpan.");
        }
    }

    loadTheme();

    themeToggle?.addEventListener("click", () => {
        root.dataset.theme =
            root.dataset.theme === "light" ? "dark" : "light";

        updateThemeButton();
        saveTheme();
    });

    // =========================
    // RATING BINTANG
    // =========================

    const starPicker = document.getElementById("starPicker");
    const ratingInput = document.getElementById("rating");
    const ratingCaption = document.getElementById("ratingCaption");

    const ratingLabels = {
        1: "Sangat kurang",
        2: "Kurang",
        3: "Cukup",
        4: "Baik",
        5: "Sangat baik"
    };

    function updateStars(value) {
        const rating = Number(value) || 0;

        starPicker?.querySelectorAll(".star-button").forEach(button => {
            const current = Number(button.dataset.rating);
            const selected = current <= rating;

            button.classList.toggle("selected", selected);
            button.classList.remove("preview");

            button.setAttribute("aria-checked", String(current === rating));
            button.tabIndex = current === (rating || 1) ? 0 : -1;
        });

        if (ratingCaption) {
            ratingCaption.textContent = rating
                ? `${rating} dari 5 bintang — ${ratingLabels[rating]}`
                : "Pilih rating kamu";
        }
    }

    if (starPicker && ratingInput) {
        starPicker.querySelectorAll(".star-button").forEach(button => {
            button.addEventListener("click", () => {
                ratingInput.value = button.dataset.rating;
                updateStars(ratingInput.value);
            });

            button.addEventListener("mouseenter", () => {
                const value = Number(button.dataset.rating);

                starPicker.querySelectorAll(".star-button").forEach(star => {
                    star.classList.toggle(
                        "preview",
                        Number(star.dataset.rating) <= value
                    );
                });
            });

            button.addEventListener("focus", () => {
                const value = Number(button.dataset.rating);

                starPicker.querySelectorAll(".star-button").forEach(star => {
                    star.classList.toggle(
                        "preview",
                        Number(star.dataset.rating) <= value
                    );
                });
            });

            button.addEventListener("keydown", event => {
                let next = Number(button.dataset.rating);

                if (event.key === "ArrowRight" || event.key === "ArrowUp") {
                    next = Math.min(5, next + 1);
                } else if (
                    event.key === "ArrowLeft" ||
                    event.key === "ArrowDown"
                ) {
                    next = Math.max(1, next - 1);
                } else if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    ratingInput.value = button.dataset.rating;
                    updateStars(ratingInput.value);
                    return;
                } else {
                    return;
                }

                event.preventDefault();

                const target = starPicker.querySelector(
                    `[data-rating="${next}"]`
                );

                target?.focus();
            });
        });

        starPicker.addEventListener("mouseleave", () => {
            starPicker.querySelectorAll(".star-button").forEach(star => {
                star.classList.remove("preview");
            });
        });

        updateStars(ratingInput.value);
    }

    // =========================
    // PENGHITUNG KARAKTER
    // =========================

    const messageInput = document.getElementById("pesan");
    const charCount = document.getElementById("charCount");

    function updateCharCount() {
        if (!messageInput || !charCount) return;

        charCount.textContent =
            `${messageInput.value.length} / 3000`;

        charCount.style.color =
            messageInput.value.length >= 2800
                ? "#ff879b"
                : "";
    }

    messageInput?.addEventListener("input", updateCharCount);
    updateCharCount();

    // =========================
    // VALIDASI FORM
    // =========================

    const form = document.getElementById("feedbackForm");
    const submitButton = document.getElementById("submitButton");

    form?.addEventListener("submit", event => {
        if (!ratingInput.value) {
            event.preventDefault();

            ratingCaption.textContent =
                "Silakan pilih rating bintang terlebih dahulu.";

            starPicker?.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            return;
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.querySelector("span").textContent =
                "Mengirim aspirasi...";
        }
    });

    // =========================
    // ANIMASI MASUK
    // =========================

    const animatedItems = document.querySelectorAll(
        ".stat-card, .feedback-card, .assistant-card"
    );

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.08
        });

        animatedItems.forEach(item => {
            item.classList.add("will-animate");
            observer.observe(item);
        });
    }
});
