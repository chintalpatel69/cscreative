document.addEventListener('DOMContentLoaded', function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.custom-slider .slide');
    const totalSlides = slides.length;
    const slideContainer = document.querySelector('.custom-slider .slides');
    const dots = document.querySelectorAll('.custom-slider .dot');

    function showSlide(index) {
        slideContainer.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(currentSlide);
    }

    function goToSlide(index) {
        currentSlide = index;
        showSlide(currentSlide);
    }

    document.querySelector('.next-slide').addEventListener('click', nextSlide);
    document.querySelector('.prev-slide').addEventListener('click', prevSlide);

    dots.forEach((dot) => {
        dot.addEventListener('click', function() {
            goToSlide(parseInt(this.getAttribute('data-slide')));
        });
    });

    setInterval(nextSlide, 5000); // Change slide every 5 seconds
    showSlide(currentSlide);
});
