(() => {
  // resources/js/front/lightbox.js
  document.addEventListener("DOMContentLoaded", () => {
    var lightbox = new PhotoSwipeLightbox({
      gallery: ".product-gallery",
      children: "a",
      pswpModule: PhotoSwipe
    });
    lightbox.init();
  });
})();
