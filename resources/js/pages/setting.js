// Profile Foreground Img

import flatpickr from "flatpickr";

if (document.querySelector("#profile-img-file-input")) {
  document
    .querySelector("#profile-img-file-input")
    .addEventListener("change", function () {
      var preview = document.querySelector(".user-profile-image");
      var file = document.querySelector(".profile-img-file-input").files[0];
      var reader = new FileReader();
      reader.addEventListener(
        "load",
        function () {
          preview.src = reader.result;
        },
        false
      );
      if (file) {
        reader.readAsDataURL(file);
      }
    });
}

// Profile Foreground Img
if (document.querySelector("#profile-img-file-input1")) {
  document
    .querySelector("#profile-img-file-input1")
    .addEventListener("change", function () {
      var preview = document.querySelector(".user-profile-image1");
      var file = document.querySelector(".profile-img-file-input1").files[0];
      var reader = new FileReader();
      reader.addEventListener(
        "load",
        function () {
          preview.src = reader.result;
        },
        false
      );
      if (file) {
        reader.readAsDataURL(file);
      }
    });
}

class FlatpickrDemo {
  init() {
    document.getElementById("preloading-timepicker").flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: "H:i",
      defaultDate: "07:45",
    });
    document.getElementById("preloading-timepicker2").flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: "H:i",
      defaultDate: "11:45",
    });
  }
}
document.addEventListener("DOMContentLoaded", function (e) {
  new FlatpickrDemo().init();
});
