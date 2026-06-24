// /**
// * Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
// * Author: FoxPixel
// * Module/App: Theme Layout Customizer Js
// */

// class ThemeLayout {

//      constructor() {
//           this.html = document.getElementsByTagName('html')[0]
//           this.config = window.config;
//      }

//      // Main Nav
//      initVerticalMenu() {
//           const navCollapse = document.querySelectorAll('.navbar-nav li .collapse');
//           const navToggle = document.querySelectorAll(".navbar-nav li [data-bs-toggle='collapse']");

//           navToggle.forEach(toggle => {
//                toggle.addEventListener('click', function (e) {
//                     e.preventDefault();
//                });
//           });

//           // open one menu at a time only (Auto Close Menu)
//           navCollapse.forEach(collapse => {
//                collapse.addEventListener('show.bs.collapse', function (event) {
//                     const parent = event.target.closest('.collapse.show');
//                     document.querySelectorAll('.navbar-nav .collapse.show').forEach(element => {
//                          if (element !== event.target && element !== parent) {
//                               const collapseInstance = new bootstrap.Collapse(element);
//                               collapseInstance.hide();
//                          }
//                     });
//                });
//           });


//           if (document.querySelector(".navbar-nav")) {
//                // Activate the menu in left side bar based on url
//                document.querySelectorAll(".navbar-nav a").forEach(function (link) {
//                     var pageUrl = window.location.href.split(/[?#]/)[0];

//                     if (link.href === pageUrl) {
//                          link.classList.add("active");
//                          link.parentNode.classList.add("active");

//                          let parentCollapseDiv = link.closest(".collapse");
//                          while (parentCollapseDiv) {
//                               parentCollapseDiv.classList.add("show");
//                               parentCollapseDiv.parentElement.children[0].classList.add("active");
//                               parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
//                               parentCollapseDiv = parentCollapseDiv.parentElement.closest(".collapse");
//                          }
//                     }
//                });

//                setTimeout(function () {
//                     var activatedItem = document.querySelector('.nav-item li a.active');

//                     if (activatedItem != null) {
//                          var simplebarContent = document.querySelector('.main-nav .simplebar-content-wrapper');
//                          var offset = activatedItem.offsetTop - 300;
//                          if (simplebarContent && offset > 100) {
//                               scrollTo(simplebarContent, offset, 600);
//                          }
//                     }
//                }, 200);

//                // scrollTo (Left Side Bar Active Menu)
//                function easeInOutQuad(t, b, c, d) {
//                     t /= d / 2;
//                     if (t < 1) return c / 2 * t * t + b;
//                     t--;
//                     return -c / 2 * (t * (t - 2) - 1) + b;
//                }

//                function scrollTo(element, to, duration) {
//                     var start = element.scrollTop, change = to - start, currentTime = 0, increment = 20;
//                     var animateScroll = function () {
//                          currentTime += increment;
//                          var val = easeInOutQuad(currentTime, start, change, duration);
//                          element.scrollTop = val;
//                          if (currentTime < duration) {
//                               setTimeout(animateScroll, increment);
//                          }
//                     };
//                     animateScroll();
//                }
//           }
//      }

//      initConfig() {
//           this.config = JSON.parse(JSON.stringify(window.config ?? {}));

//           // Add a check to ensure `this.config.menu` exists
//           if (!this.config.menu) {
//                this.config.menu = {
//                     size: 'default' // Provide a default value
//                };
//           }
//      }
     
//      changeMenuColor(color) {
//           this.config.menu.color = color;
//           this.html.classList.add(color);
//           this.setSwitchFromConfig();
//      }

//      changeMenuSize(size, save = true) {
//           this.html.classList.add(size);
//           if (save) {
//                this.config.menu.size = size;
//           }
//      }

//      changeThemeMode(color) {
//           this.config.theme = color;
//           this.html.setAttribute('data-bs-theme', color);
//           this.setSwitchFromConfig();
//      }

//      toggleThemes() {
//           var self = this;
//           document.querySelectorAll('input[name=data-menu-color]').forEach(function (element) {
//                element.addEventListener('change', function (e) {
//                     self.changeMenuColor(element.value);
//                })
//           });

//           document.querySelectorAll('input[name=data-menu-size]').forEach(function (element) {
//                element.addEventListener('change', function (e) {
//                     self.changeMenuSize(element.value);
//                })
//           });

//           document.querySelectorAll('input[name=data-bs-theme]').forEach(function (element) {
//                element.addEventListener('change', function (e) {
//                     self.changeThemeMode(element.value);
//                })
//           });
          
//           document.querySelectorAll('input[name=data-topbar-color]').forEach(function (element) {
//                element.addEventListener('change', function (e) {
//                     self.changeTopbarColor(element.value);
//                })
//           });

//           // Topbar Light Dark Button
//           var themeColorToggle = document.getElementById('light-dark-mode');
//           if (themeColorToggle) {
//                themeColorToggle.addEventListener('click', function (e) {
//                     if (self.config.theme === 'light') {
//                          self.changeThemeMode('dark');
//                     } else {
//                          self.changeThemeMode('light');
//                     }
//                });
//           }

//           var menuToggleBtn = document.querySelector('.button-toggle-menu');
//           if (menuToggleBtn) {
//                menuToggleBtn.addEventListener('click', function () {
//                     var configSize = self.config.menu.size;
//                     var size = self.html.classList.contains(configSize);

//                     if (window.innerWidth > 1040) {
//                          self.html.classList.toggle("sidebar-hover");
//                     } else {
//                          self.html.classList.toggle('sidebar-enable');
//                          self.showBackdrop();
//                     }

//                });
//           }
//      }

//      showBackdrop() {
//           const backdrop = document.createElement('div');
//           backdrop.classList = 'offcanvas-backdrop fade show';
//           document.body.appendChild(backdrop);
//           document.body.style.overflow = "hidden";
//           if (window.innerWidth > 1040) {
//                document.body.style.paddingRight = "15px";
//           }
//           const self = this
//           backdrop.addEventListener('click', function (e) {
//                self.html.classList.remove('sidebar-enable');
//                document.body.removeChild(backdrop);
//                document.body.style.overflow = null;
//                document.body.style.paddingRight = null;
//           })
//      }

//      initWindowSize() {
//           var self = this;
//           window.addEventListener('resize', function (e) {
//                self._adjustLayout();
//           })
//      }

//      _adjustLayout() {
//           var self = this;

//           if (window.innerWidth <= 1140) {
//                self.changeMenuSize('sidebar-hidden', false);
//           } else {
//                // Add a check to ensure `self.config.menu` exists before accessing `size`
//                if (self.config.menu) {
//                     self.changeMenuSize(self.config.menu.size);
//                } else {
//                     self.changeMenuSize('default'); // Fallback to a default size
//                }
//           }
//      }

//      setSwitchFromConfig() {
     
//           sessionStorage.setItem('__THEME_CONFIG__', JSON.stringify(this.config));
     
//           document.querySelectorAll('.settings-bar input[type=radio]').forEach(function (checkbox) {
//                checkbox.checked = false;
//           })
     
//           var config = this.config;
//           if (config) {
//                var layoutColorSwitch = document.querySelector('input[type=radio][name=data-bs-theme][value=' + config.theme + ']');
//                var menuSizeSwitch = document.querySelector('input[type=radio][name=data-menu-size][value=' + config.menu.size + ']');
//                var menuColorSwitch = document.querySelector('input[type=radio][name=data-menu-color][value=' + config.menu.color + ']');
     
//                if (layoutColorSwitch) layoutColorSwitch.checked = true;
//                if (menuSizeSwitch) menuSizeSwitch.checked = true;
//                if (menuColorSwitch) menuColorSwitch.checked = true;
//           }
//      }

//      init() {
//           this.initConfig();
//           this.initVerticalMenu();
//           this.initWindowSize();
//           this._adjustLayout();
//           this.toggleThemes();
//           this.setSwitchFromConfig();
//      }
// }

// new ThemeLayout().init();









/**
* Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
* Author: FoxPixel
* Module/App: Theme Layout Customizer Js
*/

class ThemeLayout {

     constructor() {
          this.html = document.getElementsByTagName('html')[0]
          this.config = {};
          this.defaultConfig = window.config;
          this.sidebarBreakpoint = 1140;
          this.sidebarStorageKey = '__FINANCE_SIDEBAR_COLLAPSED__';
     }

     // Main Nav
     initVerticalMenu() {
          const navCollapse = document.querySelectorAll('.navbar-nav li .collapse');
          const navToggle = document.querySelectorAll(".navbar-nav li [data-bs-toggle='collapse']");

          navToggle.forEach(toggle => {
               toggle.addEventListener('click', function (e) {
                    e.preventDefault();
               });
          });

          // open one menu at a time only (Auto Close Menu)
          navCollapse.forEach(collapse => {
               collapse.addEventListener('show.bs.collapse', function (event) {
                    const parent = event.target.closest('.collapse.show');
                    document.querySelectorAll('.navbar-nav .collapse.show').forEach(element => {
                         if (element !== event.target && element !== parent) {
                              const collapseInstance = new bootstrap.Collapse(element);
                              collapseInstance.hide();
                         }
                    });
               });
          });


          if (document.querySelector(".navbar-nav")) {
               // Activate the menu in left side bar based on url
               document.querySelectorAll(".navbar-nav a").forEach(function (link) {
                    var pageUrl = window.location.href.split(/[?#]/)[0];

                    if (link.href === pageUrl) {
                         link.classList.add("active");
                         link.parentNode.classList.add("active");

                         let parentCollapseDiv = link.closest(".collapse");
                         while (parentCollapseDiv) {
                              parentCollapseDiv.classList.add("show");
                              parentCollapseDiv.parentElement.children[0].classList.add("active");
                              parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
                              parentCollapseDiv = parentCollapseDiv.parentElement.closest(".collapse");
                         }
                    }
               });

               setTimeout(function () {
                    var activatedItem = document.querySelector('.nav-item li a.active');

                    if (activatedItem != null) {
                         var simplebarContent = document.querySelector('.main-nav .simplebar-content-wrapper');
                         var offset = activatedItem.offsetTop - 300;
                         if (simplebarContent && offset > 100) {
                              scrollTo(simplebarContent, offset, 600);
                         }
                    }
               }, 200);

               // scrollTo (Left Side Bar Active Menu)
               function easeInOutQuad(t, b, c, d) {
                    t /= d / 2;
                    if (t < 1) return c / 2 * t * t + b;
                    t--;
                    return -c / 2 * (t * (t - 2) - 1) + b;
               }

               function scrollTo(element, to, duration) {
                    var start = element.scrollTop, change = to - start, currentTime = 0, increment = 20;
                    var animateScroll = function () {
                         currentTime += increment;
                         var val = easeInOutQuad(currentTime, start, change, duration);
                         element.scrollTop = val;
                         if (currentTime < duration) {
                              setTimeout(animateScroll, increment);
                         }
                    };
                    animateScroll();
               }
          }
     }

     initConfig() {
          this.defaultConfig = JSON.parse(JSON.stringify(window.defaultConfig));
          this.config = JSON.parse(JSON.stringify(window.config));
          this.setSwitchFromConfig();
     }

     changeMenuColor(color) {
          this.config.menu.color = color;
          this.html.classList.add(color);
          this.setSwitchFromConfig();
     }

     changeMenuSize(size, save = true) {
          this.html.classList.remove('sidebar-hidden');

          if (size && size !== 'default') {
               this.html.classList.add(size);
          }

          if (save) {
               this.config.menu.size = size;
               this.setSwitchFromConfig();
          }
     }

     changeThemeMode(color) {
          this.config.theme = color;
          this.html.setAttribute('data-bs-theme', color);
          this.setSwitchFromConfig();
     }

     // changeTopbarColor(color) {
     //      this.config.topbar.color = color;
     //      this.html.classList.add(color);
     //      this.setSwitchFromConfig();
     // }

     // resetTheme() {
     //      this.config = JSON.parse(JSON.stringify(window.defaultConfig));
     //      this.changeMenuColor(this.config.menu.color);
     //      this.changeMenuSize(this.config.menu.size);
     //      this.changeThemeMode(this.config.theme);
     //      this.changeTopbarColor(this.config.topbar.color);
     //      this._adjustLayout();
     // }

     initSwitchListener() {
          var self = this;
          document.querySelectorAll('input[name=data-menu-color]').forEach(function (element) {
               element.addEventListener('change', function (e) {
                    self.changeMenuColor(element.value);
               })
          });

          document.querySelectorAll('input[name=data-menu-size]').forEach(function (element) {
               element.addEventListener('change', function (e) {
                    self.changeMenuSize(element.value);
               })
          });

          document.querySelectorAll('input[name=data-bs-theme]').forEach(function (element) {
               element.addEventListener('change', function (e) {
                    self.changeThemeMode(element.value);
               })
          });

          document.querySelectorAll('input[name=data-topbar-color]').forEach(function (element) {
               element.addEventListener('change', function (e) {
                    self.changeTopbarColor(element.value);
               })
          });

          // Topbar Light Dark Button
          var themeColorToggle = document.getElementById('light-dark-mode');
          if (themeColorToggle) {
               themeColorToggle.addEventListener('click', function (e) {
                    if (self.config.theme === 'light') {
                         self.changeThemeMode('dark');
                    } else {
                         self.changeThemeMode('light');
                    }
               });
          }
          
          var menuToggleButtons = document.querySelectorAll('.button-toggle-menu');
          menuToggleButtons.forEach(function (menuToggleBtn) {
               menuToggleBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    self.toggleSidebar();
               });
          });

          document.querySelectorAll('.main-nav .menu-link').forEach(function (link) {
               link.addEventListener('click', function () {
                    if (!self.isDesktopSidebar()) {
                         self.closeMobileSidebar();
                    }
               });
          });
     }

     isDesktopSidebar() {
          return window.innerWidth > this.sidebarBreakpoint;
     }

     toggleSidebar() {
          if (this.isDesktopSidebar()) {
               this.toggleDesktopSidebar();

               return;
          }

          if (this.html.classList.contains('sidebar-enable')) {
               this.closeMobileSidebar();
          } else {
               this.openMobileSidebar();
          }
     }

     toggleDesktopSidebar() {
          const shouldCollapse = !this.html.classList.contains('sidebar-collapsed');

          this.closeMobileSidebar();
          this.html.classList.remove('sidebar-hidden', 'sidebar-hover');
          this.html.classList.toggle('sidebar-collapsed', shouldCollapse);
          sessionStorage.setItem(this.sidebarStorageKey, shouldCollapse ? 'true' : 'false');
          this.syncSidebarToggleButtons();
     }

     syncSidebarToggleButtons() {
          const isExpanded = this.isDesktopSidebar()
               ? !this.html.classList.contains('sidebar-collapsed')
               : this.html.classList.contains('sidebar-enable');

          document.querySelectorAll('.button-toggle-menu').forEach(function (button) {
               button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
               button.setAttribute(
                    'aria-label',
                    isExpanded ? 'Contraer barra lateral' : 'Mostrar barra lateral'
               );
          });
     }

     openMobileSidebar() {
          this.html.classList.remove('sidebar-collapsed');
          this.html.classList.add('sidebar-enable');
          this.syncSidebarToggleButtons();
          this.showBackdrop();
     }

     showBackdrop() {
          let backdrop = document.querySelector('.finance-sidebar-backdrop');

          if (!backdrop) {
               backdrop = document.createElement('div');
               backdrop.classList = 'offcanvas-backdrop fade show finance-sidebar-backdrop';
               document.body.appendChild(backdrop);
          }

          document.body.style.overflow = 'hidden';
          backdrop.addEventListener('click', () => this.closeMobileSidebar(), { once: true });
     }

     closeMobileSidebar() {
          this.html.classList.remove('sidebar-enable');
          document.querySelector('.finance-sidebar-backdrop')?.remove();
          document.body.style.overflow = null;
          document.body.style.paddingRight = null;
          this.syncSidebarToggleButtons();
     }

     initWindowSize() {
          var self = this;
          window.addEventListener('resize', function (e) {
               self._adjustLayout();
          })
     }

     _adjustLayout() {
          var self = this;

          if (!self.isDesktopSidebar()) {
               self.html.classList.remove('sidebar-collapsed', 'sidebar-hover');
               self.changeMenuSize('sidebar-hidden', false);
          } else {
               self.closeMobileSidebar();
               self.changeMenuSize(self.config.menu?.size || 'default', false);
               self.html.classList.toggle(
                    'sidebar-collapsed',
                    sessionStorage.getItem(self.sidebarStorageKey) === 'true'
               );
          }

          self.syncSidebarToggleButtons();
     }

     setSwitchFromConfig() {
     
          sessionStorage.setItem('__THEME_CONFIG__', JSON.stringify(this.config));
     
          document.querySelectorAll('.settings-bar input[type=radio]').forEach(function (checkbox) {
               checkbox.checked = false;
          })
     
          var config = this.config;
          if (config) {
               var layoutColorSwitch = document.querySelector('input[type=radio][name=data-bs-theme][value=' + config.theme + ']');
               var topbarColorSwitch = document.querySelector('input[type=radio][name=data-topbar-color][value=' + config.topbar.color + ']');
               var menuSizeSwitch = document.querySelector('input[type=radio][name=data-menu-size][value=' + config.menu.size + ']');
               var menuColorSwitch = document.querySelector('input[type=radio][name=data-menu-color][value=' + config.menu.color + ']');
     
               if (layoutColorSwitch) layoutColorSwitch.checked = true;
               if (topbarColorSwitch) topbarColorSwitch.checked = true;
               if (menuSizeSwitch) menuSizeSwitch.checked = true;
               if (menuColorSwitch) menuColorSwitch.checked = true;
          }
     }

     init() {
          this.initVerticalMenu();
          this.initConfig();
          this.initSwitchListener();
          this.initWindowSize();
          this._adjustLayout();
          this.setSwitchFromConfig();
     }
}

new ThemeLayout().init();
