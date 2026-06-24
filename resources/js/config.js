/**
* Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
* Author: FoxPixel
* Module/App: Theme Config Js
*/

(function () {

     var themeConfigKey = "__THEME_CONFIG__";
     var themeChoiceKey = "__FINANCE_THEME_CHOICE__";
     var defaultTheme = "dark";
     var savedConfig = sessionStorage.getItem(themeConfigKey);
     var savedThemeChoice = sessionStorage.getItem(themeChoiceKey);
     var html = document.getElementsByTagName("html")[0];

     var defaultConfig = {
          theme: defaultTheme,

          topbar: {
               color: "topbar-light",
          },

          menu: {
               size: "default",
               color: "sidebar-light",
          },
     };

     function readSavedConfig(value) {
          if (value === null) {
               return null;
          }

          try {
               return JSON.parse(value);
          } catch (error) {
               sessionStorage.removeItem(themeConfigKey);

               return null;
          }
     }

     function updateThemeColor(theme) {
          var meta = document.querySelector('meta[name="theme-color"]');

          if (meta) {
               meta.setAttribute("content", theme === "light" ? "#f4f7fb" : "#0f172a");
          }
     }

     let config = JSON.parse(JSON.stringify(defaultConfig));
     window.defaultConfig = JSON.parse(JSON.stringify(config));

     var parsedConfig = readSavedConfig(savedConfig);

     if (parsedConfig !== null) {
          config = Object.assign(config, parsedConfig);
          config.topbar = Object.assign({}, defaultConfig.topbar, parsedConfig.topbar || {});
          config.menu = Object.assign({}, defaultConfig.menu, parsedConfig.menu || {});
     }

     config.theme = savedThemeChoice === "light" ? "light" : defaultTheme;
     window.config = config;

     if (config) {
          html.setAttribute("data-bs-theme", config.theme);
          html.classList.add(config.topbar.color);
          html.classList.add(config.menu.color);
          updateThemeColor(config.theme);

          if (window.innerWidth <= 1140) {
               html.classList.add("sidebar-hidden");
          } else if (sessionStorage.getItem("__FINANCE_SIDEBAR_COLLAPSED__") === "true") {
               html.classList.add("sidebar-collapsed");
          }
     }
})();
