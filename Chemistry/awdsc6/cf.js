        document.addEventListener('DOMContentLoaded', () => {
            const swiper = new Swiper('.swiper', {
                slidesPerView: 1,
                spaceBetween: 24,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    }
                }
            });
        });