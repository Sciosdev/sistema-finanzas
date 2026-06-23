// 
// Glightbox Js
// 

import GLightbox from "glightbox";
import Shuffle from 'shufflejs';

function init() {
  const Shuffle = window.Shuffle;

  class Gallery {
    constructor(element) {
      this.element = element;
      this.shuffle = new Shuffle(element, {
        itemSelector: ".picture-item",
      });
      this.activeFilters = [];
      this.addFilterButtons();
    }

    addFilterButtons() {
      const filterOptions = document.querySelector(".filter-options");
      if (filterOptions) {
        const buttons = Array.from(filterOptions.children);
        buttons.forEach((button) => {
          button.addEventListener("click", this.handleFilterClick.bind(this));
        });
      }
    }

    handleFilterClick(event) {
      const button = event.currentTarget;
      const isActive = button.classList.contains("active");
      const group = button.getAttribute("data-group");

      if (isActive) {
        button.classList.remove("active");
      } else {
        this.removeActiveClassFromChildren(button.parentNode);
        button.classList.add("active");
      }

      this.shuffle.filter(group === 'all' ? Shuffle.ALL_ITEMS : group);
    }

    removeActiveClassFromChildren(parent) {
      const children = parent.children;
      for (let i = children.length - 1; i >= 0; i--) {
        children[i].classList.remove("active");
      }
    }
  }
  
  // Initialize GLightbox
  const lightbox = GLightbox({
    selector: ".image-popup",
    title: false,
  });
}

init();