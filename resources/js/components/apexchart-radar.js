/**
 * Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
 * Author: FoxPixel
 * Module/App: Apex Radar Charts
 */

//
// BASIC RADAR CHART
//

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

const basicRadarColors = ["#7f56da"];
const basicRadarOptions = {
  chart: {
    toolbar: {
      show: false,
    },
    height: 350,
    type: "radar",
  },
  series: [
    {
      name: "Series 1",
      data: [80, 50, 30, 40, 100, 20],
    },
  ],
  colors: basicRadarColors,
  labels: ["January", "February", "March", "April", "May", "June"],
};
const basicRadarChart = new ApexCharts(document.querySelector("#basic-radar"), basicRadarOptions);
basicRadarChart.render();

//
// RADAR WITH POLYGON-FILL
//
const radarPolygonColors = ["#f95c5c"];
const radarPolygonOptions = {
  chart: {
    height: 350,
    type: "radar",
    toolbar: {
      show: false,
    },
  },
  series: [
    {
      name: "Series 1",
      data: [20, 100, 40, 30, 50, 80, 33],
    },
  ],
  labels: [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ],
  plotOptions: {
    radar: {
      size: 140,
    },
  },
  colors: radarPolygonColors,
  markers: {
    size: 4,
    colors: ["#fff"],
    strokeColor: radarPolygonColors,
    strokeWidth: 2,
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val;
      },
    },
  },
  yaxis: {
    tickAmount: 7,
    labels: {
      formatter: function (val, i) {
        if (i % 2 === 0) {
          return val;
        } else {
          return "";
        }
      },
    },
  },
};
const radarPolygonChart = new ApexCharts(document.querySelector("#radar-polygon"), radarPolygonOptions);
radarPolygonChart.render();

//
// RADAR – MULTIPLE SERIES
//
const radarMultipleSeriesColors = ["#1989df", "#f95c5c", "#1bb394"];
const radarMultipleSeriesOptions = {
  chart: {
    height: 350,
    type: "radar",
    toolbar: {
      show: false,
    },
  },
  series: [
    {
      name: "Series 1",
      data: [80, 50, 30, 40, 100, 20],
    },
    {
      name: "Series 2",
      data: [20, 30, 40, 80, 20, 80],
    },
    {
      name: "Series 3",
      data: [44, 76, 78, 13, 43, 10],
    },
  ],
  stroke: {
    width: 0,
  },
  fill: {
    opacity: 0.4,
  },
  markers: {
    size: 0,
  },
  legend: {
    offsetY: -10,
  },
  colors: radarMultipleSeriesColors,
  labels: ["2011", "2012", "2013", "2014", "2015", "2016"],
};
const radarMultipleSeriesChart = new ApexCharts(document.querySelector("#radar-multiple-series"), radarMultipleSeriesOptions);
radarMultipleSeriesChart.render();

function update() {
  function randomSeries() {
    let arr = [];
    for (let i = 0; i < 6; i++) {
      arr.push(Math.floor(Math.random() * 100));
    }

    return arr;
  }

  chart.updateSeries([
    {
      name: "Series 1",
      data: randomSeries(),
    },
    {
      name: "Series 2",
      data: randomSeries(),
    },
    {
      name: "Series 3",
      data: randomSeries(),
    },
  ]);
}