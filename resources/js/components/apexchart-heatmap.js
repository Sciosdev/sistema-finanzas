/**
 * Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
 * Author: FoxPixel
 * Module/App: Apex Area Charts
 */

//
// BASIC HEATMAP - SINGLE SERIES
//

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

function generateData(count, yrange) {
  const series = [];
  for (let i = 0; i < count; i++) {
    const x = (i + 1).toString();
    const y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
    series.push({
      x: x,
      y: y,
    });
  }
  return series;
}

//
// BASIC HEATMAP - SINGLE SERIES
//
const basicHeatmapColors = ["#1989df"];
const basicHeatmapOptions = {
  chart: {
    toolbar: { show: false },
    height: 380,
    type: "heatmap",
  },
  dataLabels: { enabled: false },
  colors: basicHeatmapColors,
  series: [
    { name: "Metric 1", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 2", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 3", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 4", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 5", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 6", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 7", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 8", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 9", data: generateData(20, { min: 0, max: 90 }) },
  ],
  xaxis: { type: "category" },
};
const basicHeatmapChart = new ApexCharts(document.querySelector("#basic-heatmap"), basicHeatmapOptions);
basicHeatmapChart.render();

//
// HEATMAP - MULTIPLE SERIES
//
const seriesHeatmapColors = [
  "#1989df", "#53389f", "#7f56da", "#ff86c8", "#f95c5c",
  "#f95c5c", "#f9b931", "#1bb394", "#1bb394",
];
const seriesHeatmapOptions = {
  chart: {
    toolbar: { show: false },
    height: 380,
    type: "heatmap",
  },
  dataLabels: { enabled: false },
  colors: seriesHeatmapColors,
  series: [
    { name: "Metric 1", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 2", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 3", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 4", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 5", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 6", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 7", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 8", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric 9", data: generateData(20, { min: 0, max: 90 }) },
  ],
  xaxis: { type: "category" },
};
const seriesHeatmapChart = new ApexCharts(document.querySelector("#multiple-series-heatmap"), seriesHeatmapOptions);
seriesHeatmapChart.render();

//
// HEATMAP - COLOR RANGE
//
const rangeHeatmapColors = ["#1989df", "#f95c5c", "#f9b931", "#1bb394"];
const colorRangeHeatmapOptions = {
  chart: {
    toolbar: { show: false },
    height: 380,
    type: "heatmap",
  },
  plotOptions: {
    heatmap: {
      shadeIntensity: 0.5,
      colorScale: {
        ranges: [
          { from: -30, to: 5, name: "Low", color: rangeHeatmapColors[0] },
          { from: 6, to: 20, name: "Medium", color: rangeHeatmapColors[1] },
          { from: 21, to: 45, name: "High", color: rangeHeatmapColors[2] },
          { from: 46, to: 55, name: "Extreme", color: rangeHeatmapColors[3] },
        ],
      },
    },
  },
  dataLabels: { enabled: false },
  series: [
    { name: "Jan", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Feb", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Mar", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Apr", data: generateData(20, { min: -30, max: 55 }) },
    { name: "May", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Jun", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Jul", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Aug", data: generateData(20, { min: -30, max: 55 }) },
    { name: "Sep", data: generateData(20, { min: -30, max: 55 }) },
  ],
};
const colorRangeHeatmapChart = new ApexCharts(document.querySelector("#color-range-heatmap"), colorRangeHeatmapOptions);
colorRangeHeatmapChart.render();

//
// HEATMAP - RANGE WITHOUT SHADES
//
const roundedHeatmapColors = ["#1989df", "#1bb394"];
const roundedHeatmapOptions = {
  chart: {
    toolbar: { show: false },
    height: 380,
    type: "heatmap",
  },
  stroke: { width: 0 },
  plotOptions: {
    heatmap: {
      radius: 30,
      enableShades: false,
      colorScale: {
        ranges: [
          { from: 0, to: 50, color: roundedHeatmapColors[0] },
          { from: 51, to: 100, color: roundedHeatmapColors[1] },
        ],
      },
    },
  },
  dataLabels: {
    enabled: true,
    style: { colors: ["#fff"] },
  },
  series: [
    { name: "Metric1", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric2", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric3", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric4", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric5", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric6", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric7", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric8", data: generateData(20, { min: 0, max: 90 }) },
    { name: "Metric9", data: generateData(20, { min: 0, max: 90 }) },
  ],
  xaxis: { type: "category" },
  grid: { borderColor: "#f1f3fa" },
};
const roundedHeatmapChart = new ApexCharts(document.querySelector("#rounded-heatmap"), roundedHeatmapOptions);
roundedHeatmapChart.render();