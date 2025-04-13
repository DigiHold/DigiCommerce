(() => {
  // resources/js/admin/orders-admin.js
  document.addEventListener("DOMContentLoaded", function() {
    const updateBtn = document.getElementById("update-order-btn");
    if (updateBtn) {
      updateBtn.addEventListener("click", function(e) {
        const spinner = this.parentElement.querySelector(".spinner");
        spinner.classList.add("is-active");
      });
    }
    const searchSelects = document.querySelectorAll(".digicommerce__search");
    searchSelects.forEach((select) => {
      const choices = new Choices(select, {
        searchEnabled: true,
        searchPlaceholderValue: select.dataset.placeholder,
        searchResultLimit: -1
      });
    });
  });
})();
