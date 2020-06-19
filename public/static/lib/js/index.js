var reportCountList = [0,0,0,0,1,2,0];
var reportCountList2 = [2,1,0,0,3,0,0];
var lineCompositeData = {
	labels: [ "2019-08-19", "2019-08-20", "2019-08-21", "2019-08-22", "2019-08-23",
		"2019-08-24", "2019-08-25"],

	datasets: [
		{
		"name": "订单总量",
		"values": reportCountList,
		},
		{
		"name": "已支付",
		"values": reportCountList2,
		}
	]
};


// var reportCountList2 =;

var c1 = document.querySelector("#chart-composite-1");
var c2 = document.querySelector("#chart-composite-2");

var lineCompositeChart = new Chart (c1, {
	title: "七日订单成交量",
	data: lineCompositeData,
	type: 'line',
	height: 200,
	colors: ['#1296db','#FF8544'],
	isNavigable: 1,
	valuesOverPoints: 1,

	lineOptions: {
		dotSize: 8
	},
	// yAxisMode: 'tick'
	// regionFill: 1
});


lineCompositeChart.parent.addEventListener('data-select', (e) => {
	var i = e.index;
	barCompositeChart.updateDatasets([
		fireballOver25[i], fireball_5_25[i], fireball_2_5[i]
	]);
});