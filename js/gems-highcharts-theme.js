/* Shared Highcharts appearance for GEMS Tabler pages. */
(function () {
    if (typeof Highcharts === 'undefined') {
        return;
    }
    Highcharts.setOptions({
        chart: {
            backgroundColor: '#ffffff',
            style: { fontFamily: 'Poppins, Lato, system-ui, -apple-system, Segoe UI, sans-serif' }
        },
        colors: ['#2F7CF6', '#20B97A', '#FF9838', '#8258E8', '#F65353', '#14B8B1', '#F7B719', '#4AB7E8', '#B87540', '#94A3B8'],
        title: { style: { color: '#172033', fontWeight: '700' } },
        subtitle: { style: { color: '#94A3B8' } },
        xAxis: {
            gridLineColor: '#EDF1F5',
            lineColor: '#E3EAF2',
            labels: { style: { color: '#64748B' } },
            title: { style: { color: '#64748B' } }
        },
        yAxis: {
            gridLineColor: '#EDF1F5',
            lineColor: '#E3EAF2',
            labels: { style: { color: '#64748B' } },
            title: { style: { color: '#64748B' } }
        },
        legend: { itemStyle: { color: '#64748B', fontWeight: '500' } },
        tooltip: { borderColor: '#E3EAF2', backgroundColor: 'rgba(255,255,255,0.96)', style: { color: '#172033' } },
        exporting: {
            buttons: {
                contextButton: {
                    theme: { fill: 'transparent', padding: 4, states: { hover: { fill: '#eef1f4' }, select: { fill: '#eef1f4' } } }
                }
            },
            chartOptions: { chart: { backgroundColor: '#ffffff' } }
        },
        plotOptions: {
            series: { marker: { enabled: false, states: { hover: { enabled: true } } } },
            column: { borderRadius: 4, pointPadding: 0.05, groupPadding: 0.08 },
            bar: { borderRadius: 4, pointPadding: 0.05, groupPadding: 0.08 },
            area: { fillOpacity: 0.12 }
        },
        credits: { enabled: false }
    });
}());
