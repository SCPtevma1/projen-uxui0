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
