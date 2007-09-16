defaultOpts = {
	fillOpacity:			1.0,
	axis: {
				lineWidth:			1.0,
				lineColor:			'#000000',
				tickSize:			3.0,
				labelColor:			'#666666',
				labelFont:			'Tahoma',
				labelFontSize:		20,
				labelWidth: 		50.0
		},

	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor:		'#cccccc',
	colorScheme:			'blue'
}

showCT = function() {
	console.log('asdg');
	if($('browserchart')) {
		var bchart = new Plotr.PieChart('bchart', defaultOpts);
		bchart.addDataset(bchartdata);
		bchart.render();
		console.log('bchart rendered');
	}
	if($('trendchart')) {
		var tchart = new Plotr.LineChart('tchart', defaultOpts);
		tchart.addDataset(tchartdata);
		tchart.render();
		console.log('tchart rendered');
	}
	if($('usertable') || $('viewtable')) {
		fdTableSort.init();
		tablePaginater.init();
		console.log('table rendered');
	}
}


Event.observe(window, 'load', function() {
	var stob = $('sitetree').observeMethod('SelectionChanged', showCT());
});
