/* Michael Carty Bookings & Artist Management — site interactions */
(function () {
  "use strict";

  // Mobile nav toggle
  var toggle = document.querySelector(".nav-toggle");
  var links = document.querySelector(".nav-links");
  if (toggle && links) {
    toggle.addEventListener("click", function () {
      links.classList.toggle("open");
    });
    links.addEventListener("click", function (e) {
      if (e.target.tagName === "A") links.classList.remove("open");
    });
  }

  // Active nav link based on current file
  var path = window.location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".nav-links a").forEach(function (a) {
    var href = a.getAttribute("href");
    if (href === path || (path === "" && href === "index.html")) {
      a.classList.add("active");
    }
  });

  // Artist card "Read more" toggle (must not trigger the card's link nav)
  document.querySelectorAll(".read-more").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var card = btn.closest(".artist");
      if (!card) return;
      var full = card.querySelector(".bio-full");
      if (!full) return;
      var open = full.classList.toggle("open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
      btn.textContent = open ? "Read less" : "Read more";
    });
  });

  // Footer year
  var y = document.querySelector("[data-year]");
  if (y) y.textContent = new Date().getFullYear();

  // Forms now submit natively (POST -> mail.php on Titan) for real backend
  // delivery to bookings@michael-carty.com. No JS interception needed.
})();
