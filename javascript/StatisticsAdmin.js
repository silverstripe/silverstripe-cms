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


overview = function() {
	$('browserchart') ? $('browserchart').hide() : null;
	$('trendchart') ? $('trendchart').hide() : null;
	$('usertable') ? $('usertable').hide() : null;
	$('viewtable') ? $('viewtable').hide() : null;
	$('oschart') ? $('oschart').hide() : null;
	$('uacchart') ? $('uacchart').hide() : null;
	$('bovs') ? $('bovs').show() : null;
}

users = function() {
	$('browserchart') ? $('browserchart').hide() : null;
	$('trendchart') ? $('trendchart').hide() : null;
	$('usertable') ? $('usertable').show() : null;
	$('viewtable') ? $('viewtable').hide() : null;
	$('oschart') ? $('oschart').hide() : null;
	$('uacchart') ? $('uacchart').show() : null;
	$('bovs') ? $('bovs').hide() : null;
}

views = function() {
	$('browserchart') ? $('browserchart').hide() : null;
	$('trendchart') ? $('trendchart').hide() : null;
	$('usertable') ? $('usertable').hide() : null;
	$('viewtable') ? $('viewtable').show() : null;
	$('oschart') ? $('oschart').hide() : null;
	$('uacchart') ? $('uacchart').hide() : null;
	$('bovs') ? $('bovs').hide() : null;
}

trends = function() {
	$('browserchart') ? $('browserchart').hide() : null;
	$('trendchart') ? $('trendchart').show() : null;
	$('usertable') ? $('usertable').hide() : null;
	$('viewtable') ? $('viewtable').hide() : null;
	$('oschart') ? $('oschart').hide() : null;
	$('uacchart') ? $('uacchart').hide() : null;
	$('bovs') ? $('bovs').hide() : null;
}

browsers = function() {
	$('browserchart') ? $('browserchart').show() : null;
	$('trendchart') ? $('trendchart').hide() : null;
	$('usertable') ? $('usertable').hide() : null;
	$('viewtable') ? $('viewtable').hide() : null;
	$('oschart') ? $('oschart').hide() : null;
	$('uacchart') ? $('uacchart').hide() : null;
	$('bovs') ? $('bovs').hide() : null;
}

os = function() {
	$('browserchart') ? $('browserchart').hide() : null;
	$('trendchart') ? $('trendchart').hide() : null;
	$('usertable') ? $('usertable').hide() : null;
	$('viewtable') ? $('viewtable').hide() : null;
	$('oschart') ? $('oschart').show() : null;
	$('uacchart') ? $('uacchart').hide() : null;
	$('bovs') ? $('bovs').hide() : null;
}


SiteTreeNode.prototype.onselect = function() {
	switch(this.id) {
		case 'statsroot' :
			break;
		case 'stoverview' :
			overview();
			break;
		case 'stusers' :
			users();
			break;
		case 'stviews' :
			views();
			break;
		case 'sttrends' :
			trends();
			break;
		case 'stbrowsers' :
			browsers();
			break;
		case 'stos' :
			os();
			break;
		default :
			console.log('Unrecognized option ' + this.id);
	}
};
