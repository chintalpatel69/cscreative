document.addEventListener("DOMContentLoaded", function () {
    const thumbnails = document.querySelectorAll(".thumbnail-image");
    const largeImage = document.getElementById("large-image");

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener("click", function () {
            // Update the large image source
            largeImage.src = this.src;

            // Remove active class from all thumbnails
            thumbnails.forEach((thumb) => thumb.classList.remove("active"));

            // Add active class to clicked thumbnail
            this.classList.add("active");
        });
    });
});
