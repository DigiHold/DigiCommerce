(() => {
  // resources/js/front/modal.js
  document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("terms-modal");
    if (!modal)
      return;
    const overlay = modal.querySelector(".modal-overlay");
    const container = modal.querySelector(".modal-container");
    const showModal = () => {
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
      setTimeout(() => {
        overlay.classList.remove("opacity-0");
        setTimeout(() => {
          container.classList.remove("opacity-0", "translate-y-[-20px]");
        }, 150);
      }, 10);
    };
    const hideModal = () => {
      overlay.classList.add("opacity-0");
      container.classList.add("opacity-0", "translate-y-[-20px]");
      setTimeout(() => {
        modal.classList.add("hidden");
        document.body.style.overflow = "";
      }, 300);
    };
    document.querySelectorAll("a.modal").forEach((link) => {
      link.addEventListener("click", function(e) {
        e.preventDefault();
        showModal();
      });
    });
    modal.querySelectorAll(".close-modal").forEach((button) => {
      button.addEventListener("click", hideModal);
    });
    overlay.addEventListener("click", hideModal);
    document.addEventListener("keydown", function(e) {
      if (e.key === "Escape" && !modal.classList.contains("hidden")) {
        hideModal();
      }
    });
  });
})();
