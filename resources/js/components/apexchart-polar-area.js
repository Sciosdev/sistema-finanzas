/**
 * Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
 * Author: FoxPixel
 * Module/App: Apex Bar Charts
 */

//
// BASIC POLAR AREA CHART
//

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

const polarAreaColors = ['#1989df', '#53389f', '#7f56da', '#ff86c8', '#f95c5c', '#32bbe5'];
const polarAreaOptions = {
    series: [14, 23, 21, 17, 15, 10],
    chart: {
        height: 380,
        type: 'polarArea',
    },
    stroke: {
        colors: ['#fff']
    },
    fill: {
        opacity: 0.8
    },
    labels: ['Vote A', 'Vote B', 'Vote C', 'Vote D', 'Vote E', 'Vote F'],
    legend: {
        position: 'bottom'
    },
    colors: polarAreaColors,
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};
const polarAreaChart = new ApexCharts(document.querySelector("#basic-polar-area"), polarAreaOptions);
polarAreaChart.render();

//
// MONOCHROME POLAR AREA
//

const monochromePolarAreaColors = ["#1989df"];
const monochromePolarAreaOptions = {
    series: [42, 47, 52, 58, 65],
    chart: {
        height: 380,
        type: 'polarArea'
    },
    labels: ['Rose A', 'Rose B', 'Rose C', 'Rose D', 'Rose E'],
    fill: {
        opacity: 1
    },
    stroke: {
        width: 1
    },
    yaxis: {
        show: false
    },
    legend: {
        position: 'bottom'
    },
    plotOptions: {
        polarArea: {
            rings: {
                strokeWidth: 0
            },
            spokes: {
                strokeWidth: 0
            },
        }
    },
    theme: {
        monochrome: {
            enabled: true,
            shadeTo: 'light',
            color: '#727cf5',
            shadeIntensity: 0.6
        }
    }
};

const monochromePolarAreaChart = new ApexCharts(document.querySelector("#monochrome-polar-area"), monochromePolarAreaOptions);
monochromePolarAreaChart.render();