/**
* Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
* Author: FoxPixel
* Module/App: Main Js
*/

import $ from 'jquery'

window.jQuery = window.$ = $

import bootstrap from 'bootstrap/dist/js/bootstrap.min.js'
window.bootstrap = bootstrap;

import 'simplebar'
import 'iconify-icon'
import { createIcons, icons } from "lucide";

import Inputmask from 'inputmask';
import Choices from 'choices.js';

// Components
class Components {

  initBootstrapComponents() {

    // Popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

    // Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

    // offcanvas
    const offcanvasElementList = document.querySelectorAll('.offcanvas')
    const offcanvasList = [...offcanvasElementList].map(offcanvasEl => new bootstrap.Offcanvas(offcanvasEl))

    //Toasts
    var toastPlacement = document.getElementById("toastPlacement");
    if (toastPlacement) {
      document.getElementById("selectToastPlacement").addEventListener("change", function () {
        if (!toastPlacement.dataset.originalClass) {
          toastPlacement.dataset.originalClass = toastPlacement.className;
        }
        toastPlacement.className = toastPlacement.dataset.originalClass + " " + this.value;
      });
    }

    var toastElList = [].slice.call(document.querySelectorAll('.toast'))
    var toastList = toastElList.map(function (toastEl) {
      return new bootstrap.Toast(toastEl)
    })


    const alertTrigger = document.getElementById('liveAlertBtn')
    if (alertTrigger) {
      alertTrigger.addEventListener('click', () => {
        alert('Nice, you triggered this alert message!', 'success')
      })
    }

  }

  initfullScreenListener() {
    var fullScreenBtn = document.querySelector('[data-toggle="fullscreen"]');

    if (fullScreenBtn) {
      fullScreenBtn.addEventListener('click', function (e) {
        e.preventDefault();
        document.body.classList.toggle('fullscreen-enable')
        if (!document.fullscreenElement && /* alternative standard method */ !document.mozFullScreenElement && !document.webkitFullscreenElement) {
          // current working methods
          if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
          } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
          } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
          }
        } else {
          if (document.cancelFullScreen) {
            document.cancelFullScreen();
          } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
          } else if (document.webkitCancelFullScreen) {
            document.webkitCancelFullScreen();
          }
        }
      });
    }
  }

  // Counter Number
  initCounter() {
    var counter = document.querySelectorAll(".counter-value");
    var speed = 250; // The lower the slower
    counter &&
      counter.forEach(function (counter_value) {
        function updateCount() {
          var target = +counter_value.getAttribute("data-target");
          var count = +counter_value.innerText;
          var inc = target / speed;
          if (inc < 1) {
            inc = 1;
          }
          // Check if target is reached
          if (count < target) {
            // Add inc to count and output in counter_value
            counter_value.innerText = (count + inc).toFixed(0);
            // Call function every ms
            setTimeout(updateCount, 1);
          } else {
            counter_value.innerText = numberWithCommas(target);
          }
          numberWithCommas(counter_value.innerText);
        }
        updateCount();
      });

    function numberWithCommas(x) {
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
  }

  init() {
    this.initBootstrapComponents();
    this.initfullScreenListener();
    this.initCounter();
  }
}

// Form Validation ( Bootstrap )
class FormValidation {
  initFormValidation() {
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    // Loop over them and prevent submission
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
  }

  init() {
    this.initFormValidation();
  }
}

//  Form Advanced
class FormAdvanced {

  initMask() {
    document.querySelectorAll('[data-toggle="input-mask"]').forEach(e => {
      const maskFormat = e.getAttribute('data-mask-format').toString().replaceAll('0', '9');
      e.setAttribute("data-mask-format", maskFormat);
      const im = new Inputmask(maskFormat);
      im.mask(e);
    });
  }

  // Choices Select plugin
  initFormChoices() {
    var choicesExamples = document.querySelectorAll("[data-choices]");
    choicesExamples.forEach(function (item) {
      var choiceData = {};
      var isChoicesVal = item.attributes;

      if (isChoicesVal["data-choices-groups"]) {
        choiceData.placeholderValue = "This is a placeholder set in the config";
      }
      if (isChoicesVal["data-choices-search-false"]) {
        choiceData.searchEnabled = false;
      }
      if (isChoicesVal["data-choices-search-true"]) {
        choiceData.searchEnabled = true;
      }
      if (isChoicesVal["data-choices-removeItem"]) {
        choiceData.removeItemButton = true;
      }
      if (isChoicesVal["data-choices-sorting-false"]) {
        choiceData.shouldSort = false;
      }
      if (isChoicesVal["data-choices-sorting-true"]) {
        choiceData.shouldSort = true;
      }
      if (isChoicesVal["data-choices-multiple-remove"]) {
        choiceData.removeItemButton = true;
      }

      // **FIXED:** Correctly set maxItemCount as a number
      if (isChoicesVal["data-choices-limit"]) {
        choiceData.maxItemCount = parseInt(isChoicesVal["data-choices-limit"].value);
      }

      // **FIXED:** Correctly set editItems as a boolean
      if (isChoicesVal["data-choices-editItem-true"]) {
        choiceData.editItems = true;
      }
      if (isChoicesVal["data-choices-editItem-false"]) {
        choiceData.editItems = false;
      }

      if (isChoicesVal["data-choices-text-unique-true"]) {
        choiceData.duplicateItemsAllowed = false;
        choiceData.paste = false;
      }
      if (isChoicesVal["data-choices-text-disabled-true"]) {
        choiceData.addItems = false;
      }

      const choicesInstance = new Choices(item, choiceData);

      // **FIXED:** Conditionally disable the instance after it's created
      if (isChoicesVal["data-choices-text-disabled-true"]) {
        choicesInstance.disable();
      }
    });
  }

  init() {
    this.initMask();
    this.initFormChoices();
  }

}

// Dragula (Draggable Components)
class Dragula {

  initDragula() {

    document.querySelectorAll("[data-plugin=dragula]")

      .forEach(function (element) {

        const containersIds = JSON.parse(element.getAttribute('data-containers'));
        let containers = [];
        if (containersIds) {
          for (let i = 0; i < containersIds.length; i++) {
            containers.push(document.querySelectorAll("#" + containersIds[i])[0]);
          }
        } else {
          containers = [element];
        }

        // if handle provided
        const handleClass = element.getAttribute('data-handleclass');

        // init dragula
        if (handleClass) {
          dragula(containers, {
            moves: function (el, container, handle) {
              return handle.classList.contains(handleClass);
            }
          });
        } else {
          dragula(containers);
        }

      });
  }

  init() {
    this.initDragula();
  }

}

// Toast Notification
class ToastNotification {
  initToastNotification() {

    document.querySelectorAll("[data-toast]").forEach(function (element) {
      element.addEventListener("click", function () {
        var toastData = {};
        if (element.attributes["data-toast-text"]) {
          toastData.text = element.attributes["data-toast-text"].value.toString();
        }
        if (element.attributes["data-toast-gravity"]) {
          toastData.gravity = element.attributes["data-toast-gravity"].value.toString();
        }
        if (element.attributes["data-toast-position"]) {
          toastData.position = element.attributes["data-toast-position"].value.toString();
        }
        if (element.attributes["data-toast-className"]) {
          toastData.className = element.attributes["data-toast-className"].value.toString();
        }
        if (element.attributes["data-toast-duration"]) {
          toastData.duration = element.attributes["data-toast-duration"].value.toString();
        }
        if (element.attributes["data-toast-close"]) {
          toastData.close = element.attributes["data-toast-close"].value.toString();
        }
        if (element.attributes["data-toast-style"]) {
          toastData.style = element.attributes["data-toast-style"].value.toString();
        }
        if (element.attributes["data-toast-offset"]) {
          toastData.offset = element.attributes["data-toast-offset"];
        }
        Toastify({
          newWindow: true,
          text: toastData.text,
          gravity: toastData.gravity,
          position: toastData.position,
          className: "bg-" + toastData.className,
          stopOnFocus: true,
          offset: {
            x: toastData.offset ? 50 : 0,
            y: toastData.offset ? 10 : 0, // vertical axis - can be a number or a string indicating unity. eg: '2em'
          },
          duration: toastData.duration,
          close: toastData.close == "close" ? true : false,
        }).showToast();
      });
    });
  }

  init() {
    this.initToastNotification();
  }
}

document.addEventListener('DOMContentLoaded', function (e) {
  new Components().init();
  new FormValidation().init();
  new FormAdvanced().init();
  new Dragula().init();
  new ToastNotification().init();
  createIcons({ icons })
});