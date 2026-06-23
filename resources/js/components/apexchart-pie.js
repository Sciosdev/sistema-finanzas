/**
 * Theme: Velok- Responsive Bootstrap 5 Admin Dashboard
 * Author: FoxPixel
 * Module/App: Apex Pie Charts
 */

//
// SIMPLE PIE CHART
//

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

const simplePieColors = ["#1989df", "#7f56da","#f95c5c", "#f9b931","#1bb394"];
const simplePieOptions = {
    chart: {
        height: 320,
        type: 'pie',
    }, 
    series: [44, 55, 41, 17, 15],
    labels: ["Series 1", "Series 2", "Series 3", "Series 4", "Series 5"],
    colors: simplePieColors,
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }]

}
const simplePieChart = new ApexCharts(document.querySelector("#simple-pie"), simplePieOptions);
simplePieChart.render();


//
// SIMPLE DONUT CHART
//
const simpleDonutColors = ["#7f56da", "#1989df","#f95c5c", "#1bb394","#f9b931"];
const simpleDonutOptions = {
    chart: {
        height: 320,
        type: 'donut',
    }, 
    series: [44, 55, 41, 17, 15],
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    labels: ["Series 1", "Series 2", "Series 3", "Series 4", "Series 5"],
    colors: simpleDonutColors,
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }]
}
const simpleDonutChart = new ApexCharts(
    document.querySelector("#simple-donut"),
    simpleDonutOptions
);
simpleDonutChart.render();


//
// MONOCHROME PIE CHART
//
const monochromePieOptions = {
    chart: {
        height: 320,
        type: 'pie',
    }, 
    series: [25, 15, 44, 55, 41, 17],
    labels: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    theme: {
        monochrome: {
            enabled: true
        }
    },
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }]
}
const monochromePieChart = new ApexCharts(
    document.querySelector("#monochrome-pie"),
    monochromePieOptions
);
monochromePieChart.render();

//
// GRADIENT DONUT CHART
//
const gradientDonutColors = ["#7f56da", "#1989df","#f95c5c", "#1bb394","#f9b931"];
const gradientDonutOptions = {
    chart: {
        height: 320,
        type: 'donut',
    }, 
    series: [44, 55, 41, 17, 15],
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    labels: ["Series 1", "Series 2", "Series 3", "Series 4", "Series 5"],
    colors: gradientDonutColors,
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }],
    fill: {
        type: 'gradient'
    }
}
const gradientDonutChart = new ApexCharts(
    document.querySelector("#gradient-donut"),
    gradientDonutOptions
);
gradientDonutChart.render();


//
// PATTERNED DONUT CHART
//
const patternedDonutColors = ["#7f56da", "#1989df","#f95c5c", "#1bb394","#f9b931"];
const patternedDonutOptions = {
    chart: {
        height: 320,
        type: 'donut',
        dropShadow: {
          enabled: true,
          color: '#111',
          top: -1,
          left: 3,
          blur: 3,
          opacity: 0.2
        }
    },
    stroke: {
        show: true,
        width: 2,
    },
    series: [44, 55, 41, 17, 15],
    colors: patternedDonutColors,
    labels: ["Comedy", "Action", "SciFi", "Drama", "Horror"],
    dataLabels: {
        dropShadow: {
            blur: 3,
            opacity: 0.8
        }
    },
    fill: {
    type: 'pattern',
      opacity: 1,
      pattern: {
        enabled: true,
        style: ['verticalLines', 'squares', 'horizontalLines', 'circles','slantedLines'], 
      },
    },
    states: {
      hover: {
        enabled: false
      }
    },
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }]
}
const patternedDonutChart = new ApexCharts(
    document.querySelector("#patterned-donut"),
    patternedDonutOptions
);
patternedDonutChart.render();


//
// PIE CHART WITH IMAGE FILL
//
const imagePieOptions = {
    chart: {
        height: 320,
        type: 'pie',
    },
    labels: ["Series 1", "Series 2", "Series 3", "Series 4"],
    colors: patternedDonutColors,
    series: [44, 33, 54, 45],
    fill: {
        type: 'image',
        opacity: 0.85,
        image: {
             src: ['/images/small/img-1.jpg', '/images/small/img-2.jpg', '/images/small/img-3.jpg', '/images/small/img-5.jpg'],
            width: 25,
            imagedHeight: 25
        },
    },
    stroke: {
        width: 4
    },
    dataLabels: {
        enabled: false
    },
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }]
}
const imagePieChart = new ApexCharts(
    document.querySelector("#image-pie"),
    imagePieOptions
);
imagePieChart.render();


//
// DONUT UPDATE
//
const updateDonutColors = ["#1989df", "#53389f", "#7f56da", "#ff86c8", "#f95c5c", "#f95c5c", "#f9b931", "#1bb394", "#040505", "#1bb394",];
const updateDonutOptions = {
    chart: {
        height: 320,
        type: 'donut',
    },
    dataLabels: {
        enabled: false
    },
    series: [44, 55, 13, 33],
    colors: updateDonutColors,
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        verticalAlign: 'middle',
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7
    },
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                height: 240
            },
            legend: {
                show: false
            },
        }
    }]
}
const updateDonutChart = new ApexCharts(
    document.querySelector("#update-donut"),
    updateDonutOptions
);
updateDonutChart.render();

function appendData() {
    var arr = chart.w.globals.series.map(function () {
        return Math.floor(Math.random() * (100 - 1 + 1)) + 1;
    });
    arr.push(Math.floor(Math.random() * (100 - 1 + 1)) + 1);
    return arr;
}

function removeData() {
    var arr = chart.w.globals.series.map(function () {
        return Math.floor(Math.random() * (100 - 1 + 1)) + 1;
    });
    arr.pop();
    return arr;
}

function randomize() {
    return chart.w.globals.series.map(function () {
        return Math.floor(Math.random() * (100 - 1 + 1)) + 1;
    });
}

function reset() {
    return options.series;
}

document.querySelector("#randomize").addEventListener("click", function () {
    chart.updateSeries(randomize());
});

document.querySelector("#add").addEventListener("click", function () {
    chart.updateSeries(appendData());
});

document.querySelector("#remove").addEventListener("click", function () {
    chart.updateSeries(removeData());
});

document.querySelector("#reset").addEventListener("click", function () {
    chart.updateSeries(reset());
});