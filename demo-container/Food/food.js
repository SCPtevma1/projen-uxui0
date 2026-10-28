document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const filterButtons = document.querySelectorAll("#filter-buttons .btn-filter");
    const filterableCards = document.querySelectorAll("#filterable-cards .card");
    const searchInput = document.getElementById("foodSearchInput");
    const modal = document.getElementById("detailModal");
    const modalImg = document.getElementById("modalImage");
    const modalTitle = document.getElementById("modalTitle");
    const modalPrice = document.getElementById("modalPrice");
    const modalCal = document.getElementById("modalCal");
    const modalDesc = document.getElementById("modalDesc");

    let currentFilter = 'all';
    function applyFilters() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        filterableCards.forEach(card => {
            const cardCategory = card.dataset.name || '';
            const cardTitle = (card.dataset.title || '').toLowerCase();
            const cardDesc = (card.dataset.desc || '').toLowerCase();

            const matchesCategory = (currentFilter === 'all' || cardCategory === currentFilter);
            const matchesSearch = (!query || cardTitle.includes(query) || cardDesc.includes(query));

            if (matchesCategory && matchesSearch) {
                card.classList.remove("hide");
                card.classList.add("show");
            } else {
                card.classList.add("hide");
                card.classList.remove("show");
            }
        });
    }
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener("click", (e) => {
                const clickedBtn = e.currentTarget;
                document.querySelectorAll("#filter-buttons .btn-filter").forEach(b => b.classList.remove("active"));
                clickedBtn.classList.add("active");

                currentFilter = clickedBtn.dataset.filter || 'all';
                applyFilters();

                document.querySelectorAll('.sub-link[data-nav-filter]').forEach(navLink => {
                    if (navLink.dataset.navFilter === currentFilter) {
                        navLink.classList.add('active');
                    } else {
                        navLink.classList.remove('active');
                    }
                });
            });
        });
    }
    if (searchInput) {
        searchInput.addEventListener("input", () => {
            applyFilters();
        });
    }

    window.filterFromNav = function (category) {
        currentFilter = category;

        const targetBtn = document.querySelector(`#filter-buttons button[data-filter="${category}"]`);
        if (targetBtn) {
            document.querySelectorAll("#filter-buttons .btn-filter").forEach(b => b.classList.remove("active"));
            targetBtn.classList.add("active");
        }
        document.querySelectorAll('.sub-link[data-nav-filter]').forEach(navLink => {
            if (navLink.dataset.navFilter === category) {
                navLink.classList.add('active');
            } else {
                navLink.classList.remove('active');
            }
        });

        applyFilters();

        const catalogSection = document.getElementById("menu-catalog") || document.querySelector(".header-section");
        if (catalogSection) {
            const offset = 64 + 48 + 16;
            const top = catalogSection.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: "smooth" });
        }
    };
    window.openDetailModal = function (button) {
        const card = button.closest(".card");
        if (!card || !modal) return;

        if (modalImg) modalImg.src = card.dataset.img || 'https://placehold.co/600x400/111827/f8fafc?text=Food+Image';
        if (modalTitle) modalTitle.textContent = card.dataset.title || 'ชื่อเมนูอาหาร';
        if (modalPrice) modalPrice.textContent = card.dataset.price || '฿0';
        if (modalCal) modalCal.innerHTML = `<i class="fa-solid fa-fire"></i> พลังงาน: ${card.dataset.cal || '350 Kcal'}`;
        if (modalDesc) modalDesc.textContent = card.dataset.desc || 'ไม่มีข้อมูลรายละเอียดเมนูอาหารนี้';

        modal.classList.add("active");
        document.body.style.overflow = "hidden";
    };

    window.closeDetailModal = function () {
        if (!modal) return;
        modal.classList.remove("active");
        document.body.style.overflow = "";
    };
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                closeDetailModal();
            }
        });
    }

    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal && modal.classList.contains("active")) {
            closeDetailModal();
        }
    });
    if (typeof Swiper !== 'undefined' && document.querySelector('.card-wrapper')) {
        new Swiper('.card-wrapper', {
            loop: true,
            speed: 700,
            spaceBetween: 24,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                0: {
                    slidesPerView: 1,
                    spaceBetween: 16
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 24
                }
            }
        });
    }
    document.querySelectorAll('.sub-link[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            const href = link.getAttribute('href');
            if (href && href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offset = 64 + 48 + 16;
                    const top = target.getBoundingClientRect().top + window.scrollY - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            }
        });
    });

    document.querySelectorAll('.back-to-top').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
});

