var swiper = new Swiper(".mySwiper", {
  slidesPerView: 3,
  spaceBetween: 20,
  pagination: {
    el: ".swiper-pagination",
    type: "fraction",
  },
  navigation: {
    nextEl: ".testi-button-next",
    prevEl: ".testi-button-prev",
  },
  breakpoints: {
    355: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    450: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    540: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    640: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    768: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    868: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    929: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
    1024: {
      slidesPerView: 2,
      spaceBetween: 20,
    },
    1440: {
      slidesPerView: 3,
      spaceBetween: 20,
    },
  },
});

document.addEventListener("DOMContentLoaded", function () {
  var n = document.querySelectorAll(".plus"),
    e = document.querySelectorAll(".minus");
  function t(n, e) {
    var t = 1 === e ? n.previousElementSibling : n.nextElementSibling,
      i = parseInt(t.value),
      c = parseInt(t.min),
      n = parseInt(t.max);
    c <= (e = i + e) && e <= n && (t.value = e);
  }
  n.forEach(function (n) {
    n.addEventListener("click", function () {
      t(n, 1);
    });
  }),
    e.forEach(function (n) {
      n.addEventListener("click", function () {
        t(n, -1);
      });
    });
});
