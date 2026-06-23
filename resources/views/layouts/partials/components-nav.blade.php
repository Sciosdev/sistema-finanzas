<div class="main-nav">
     <div class="d-flex justify-content-between main-logo-box">
          <!-- Sidebar Logo -->
          <div class="logo-box">
               <a href="{{ route('second', ['dashboard', 'index'])}}" class="logo-dark">
                    <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm">
                    <img src="/images/logo-dark.png" class="logo-lg" alt="logo dark">
               </a>

               <a href="{{ route('second', ['dashboard', 'index'])}}" class="logo-light">
                    <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm">
                    <img src="/images/logo-white.png" class="logo-lg" alt="logo light">
               </a>
          </div>
          <!-- Menu Toggle Button -->
          <button type="button" class="btn btn-link d-flex button-sm-hover button-toggle-menu" aria-label="Show Full Sidebar">
               <i data-lucide="menu" class="button-sm-hover-icon"></i>
          </button>
     </div>

     <div class="h-100" data-simplebar>

          <ul class="navbar-nav" id="navbar-nav">

               <li class="menu-item pt-2">
                    <a class="menu-link" href="{{ route('second', ['dashboard', 'index'])}}">
                         <span class="nav-icon">
                              <i data-lucide="layout-dashboard"></i>
                         </span>
                         <span class="nav-text"> Resumen </span>
                         <span class="badge bg-success badge-pill text-end">9+</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarBaseUI" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarBaseUI">
                         <span class="nav-icon">
                              <i data-lucide="flame"></i>
                         </span>
                         <span class="nav-text"> Base UI </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarBaseUI">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-accordion.html">Accordion</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-alerts.html">Alerts</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-avatar.html">Avatar</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-badge.html">Badge</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-breadcrumb.html">Breadcrumb</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-buttons.html">Buttons</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-card.html">Card</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-carousel.html">Carousel</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-collapse.html">Collapse</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-dropdown.html">Dropdown</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-list-group.html">List Group</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-modal.html">Modal</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-tabs.html">Tabs</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-offcanvas.html">Offcanvas</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-pagination.html">Pagination</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-placeholders.html">Placeholders</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-popovers.html">Popovers</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-progress.html">Progress</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-scrollspy.html">Scrollspy</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-spinners.html">Spinners</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-toasts.html">Toasts</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-tooltips.html">Tooltips</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarExtendedUI" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarExtendedUI">
                         <span class="nav-icon">
                              <i data-lucide="wand"></i>
                         </span>
                         <span class="nav-text"> Advanced UI </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarExtendedUI">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="{{ route('second', ['ui', 'extended-ratings'])}}">Ratings</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-extended-sweetalert.html">Sweet Alert</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-extended-scrollbar.html">Scrollbar</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-extended-toastify.html">Toastify</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarCharts" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCharts">
                         <span class="nav-icon">
                              <i data-lucide="bar-chart-3"></i>
                         </span>
                         <span class="nav-text"> Charts </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarCharts">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-area.html">Area</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-bar.html">Bar</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-bubble.html">Bubble</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-candlestick.html">Candlestick</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-column.html">Column</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-heatmap.html">Heatmap</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-line.html">Line</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-mixed.html">Mixed</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-timeline.html">Timeline</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-boxplot.html">Boxplot</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-treemap.html">Treemap</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-pie.html">Pie</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-radar.html">Radar</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-radialbar.html">RadialBar</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-scatter.html">Scatter</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-charts-apex-polar-area.html">Polar Area</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarForms" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarForms">
                         <span class="nav-icon">
                              <i data-lucide="file-text"></i>
                         </span>
                         <span class="nav-text"> Forms </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarForms">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-basic.html">Basic Elements</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-checkbox-radio.html">Checkbox &amp; Radio</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-choices.html">Choice Select</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-clipboard.html">Clipboard</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-flatepicker.html">Flatepicker</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-validation.html">Validation</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-fileuploads.html">File Upload</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-editors.html">Editors</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-input-mask.html">Input Mask</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-forms-range-slider.html">Slider</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarTables" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTables">
                         <span class="nav-icon">
                              <i data-lucide="table"></i>
                         </span>
                         <span class="nav-text"> Tables </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarTables">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-tables-basic.html">Basic Tables</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-tables-gridjs.html">Grid Js</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarIcons" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarIcons">
                         <span class="nav-icon">
                              <i data-lucide="image"></i>
                         </span>
                         <span class="nav-text"> Icons </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarIcons">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-icons-lucid.html">Lucide</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarMaps" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMaps">
                         <span class="nav-icon">
                              <i data-lucide="map-pin"></i>
                         </span>
                         <span class="nav-text"> Maps </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarMaps">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-maps-google.html">Google Maps</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="ui-maps-vector.html">Vector Maps</a>
                              </li>
                         </ul>
                    </div>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="javascript:void(0);">
                         <span class="nav-icon">
                              <i data-lucide="volleyball"></i>
                         </span>
                         <span class="nav-text">Badge Menu</span>
                         <span class="badge bg-primary badge-pill text-end">1</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="#sidebarMultiLevelDemo" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMultiLevelDemo">
                         <span class="nav-icon">
                              <i data-lucide="share-2"></i>
                         </span>
                         <span class="nav-text"> Menu Item </span>
                         <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <div class="collapse" id="sidebarMultiLevelDemo">
                         <ul class="sub-menu-nav">
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="javascript:void(0);">Menu Item 1</a>
                              </li>
                              <li class="sub-menu-item">
                                   <a class="sub-menu-link" href="#sidebarItemDemoSubItem" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarItemDemoSubItem">
                                        <span> Menu Item 2 </span>
                                        <span class="menu-arrow"><i data-lucide="chevron-down"></i></span>
                                   </a>
                                   <div class="collapse" id="sidebarItemDemoSubItem">
                                        <ul class="sub-menu-nav">
                                             <li class="sub-menu-item">
                                                  <a class="sub-menu-link" href="javascript:void(0);">Menu Sub item</a>
                                             </li>
                                        </ul>
                                   </div>
                              </li>
                         </ul>
                    </div>
               </li>
          </ul>
     </div>
</div>
