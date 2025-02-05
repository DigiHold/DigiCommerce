(() => {
  // resources/js/admin/dashboard.js
  document.addEventListener("DOMContentLoaded", function() {
    const refresh = document.getElementById("digicommerce-refresh-stats");
    if (refresh) {
      let currentRotation = 0;
      refresh.addEventListener("click", function(e) {
        e.preventDefault();
        refresh.classList.add("active");
        currentRotation += 360;
        fetch(DigiCommerceData.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams({
            action: "digicommerce_refresh_sales_stats",
            nonce: DigiCommerceData.nonce
          })
        }).then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        }).then((data) => {
          if (data.success) {
            const stats = data.data;
            const dashboard = document.querySelector(".digicommerce-dashboard");
            dashboard.querySelector(".digicommerce-current-month .earnings span").textContent = stats.month.earnings_formatted;
            dashboard.querySelector(".digicommerce-current-month .sales span").textContent = stats.month.sales;
            dashboard.querySelector(".digicommerce-today .earnings span").textContent = stats.today.earnings_formatted;
            dashboard.querySelector(".digicommerce-today .sales span").textContent = stats.today.sales;
            dashboard.querySelector(".digicommerce-last-month .earnings span").textContent = stats.last_month.earnings_formatted;
            dashboard.querySelector(".digicommerce-last-month .sales span").textContent = stats.last_month.sales;
            dashboard.querySelector(".digicommerce-total .earnings span").textContent = stats.all_time.earnings_formatted;
            dashboard.querySelector(".digicommerce-total .sales span").textContent = stats.all_time.sales;
          }
        }).catch((error) => {
          console.error("There was a problem with the fetch operation:", error);
        }).finally(() => {
          setTimeout(() => {
            refresh.classList.remove("active");
          }, 1e3);
        });
      });
    }
  });
})();
