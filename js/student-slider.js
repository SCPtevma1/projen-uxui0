new Swiper(".wrapper", {
  loop: true,
  spaceBetween: 30,
  // Autoplay
  autoplay: {
    delay: 5000,
    disableOnInteraction: false,
    pauseOnMouseEnter: true,
  },
  // Pagination bullets
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
    dynamicBullets: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  breakpoints: {
    0: {
      slidesPerView: 1,
    },
    768: {
      slidesPerView: 2,
    },
    1024: {
      slidesPerView: 3,
    },
  },
});

const slides = Array.from(document.querySelectorAll('.slider-image'));
if (slides.length > 0) {
  let currentIndex = 0;

  function showSlide(index) {
    currentIndex = (index + slides.length) % slides.length;

    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === currentIndex);
    });

  }

  setInterval(() => {
    showSlide(currentIndex + 1);
  }, 4000);
}
