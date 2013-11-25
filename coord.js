/*
	Copyright 2013 Alex Zaddach. (mrzmanwiki@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

var styleelem = $('<style></style>').attr('type', 'text/css').html(
"#dms-form,"+
"#near-form,"+
"#results,"+
"#bottom-stuff {"+
"	display:none;"+
"}"+
"#no-js-warn {"+
"	display:none;"+
"}")
$('head').append(styleelem);

var request = {};
var resspinnerid = 0;
var kmlspinnerid = 1;
var kmlfile = '';
var loading = 0; // This is a hack for a bug I can't find where getResults() gets called twice in some situations

$(document).ready(function(){

	$('#mainform').submit( function() {
		resspinnerid += 2;
		$('#warning').html('')
		injectSpinner($('#submitbutton'), resspinnerid);
		$('#results-inner').hide('slow', function() {
			$('#results-inner').html(''); 
				continue1(); 
			});	
		return false;
	});
	
	if (typeof Geo != "undefined") {
		$('#geoip').show('fast')
	}
	
	$("#form-opts").change(function(){
		changeOptions();
	});
	//$("#form-opts").click(function(){
	//	if ($.browser.msie) { // change() is broken for radio buttons on IE
	//		changeOptions();
	//	}
	//});
	
	if (!navigator.geolocation) {
		$('#geoapi').prop('disabled', "disabled");
		$('#tsdb').prop('checked', 'checked');
	} else {
		$('#geoapi').prop('checked', 'checked');
		$('#geoapi-extend').show('fast');
	}
	
	$("#nearme-form").change(function() {
		if ($("#geoapi").prop('checked')) {
			$('#geoapi-extend').show('fast');
		} else {
			$('#geoapi-extend').hide('fast');
		}
	});
	
});

function continue1() {
	$('#warning').hide('fast');
	$('#note').hide('fast');
	$('#bottom-stuff').hide('fast', function() {
		$('#kmlinfo').html(''); 
		continue2(); 
	});
}

function continue2() {
	$('#search-coords').hide('fast', function() {
		$('#search-coords').html(''); 
		continue3();
	});
}

function continue3() {
	$('#submitbutton').prop('disabled', "disabled");
	kmlfile = ''
	var coords = validateFields();
	if (!coords) {	
		$('#submitbutton').prop('disabled', "");
		removeSpinner(resspinnerid);
	}
}
	
function changeOptions() {
	var selected = '#'+ $("#form-opts>span").contents("input:checked").val() + "-form";
	$(".opt-form").hide("normal");
	$(selected).show("normal");
}

function getResults(coords) {
	loading += 1
	if (loading > 1) {
		loading -= 1;
		return false;
	}
	var lat = coords[0];
	var lng = coords[1];
	var lim = parseInt($("#limit").val());
	var acc = coords[2];
	var shownoimg = $("#noimg").prop('checked').toString();
	var showreqphoto = $("#reqphoto").prop('checked').toString();
	var shownojpg = $("#nojpg").prop('checked').toString();
	
	var andor = $("#andor").find("input:checked").val();
	var useor = 'false';
	if (andor == "useor") {
		useor = 'true';
	}
	
	if (!lim || lim <= 0) {
		addWarn("Limit must be a positive integer");
		return false;
	}
	var dist = parseFloat($("#dist").val());
	if (!dist || dist <= 0) {
		addWarn("Distance must be a positive number");
		return false;
	}
	if ($("#dist-type").val() == "km") {
		dist = km2mi(dist);
	}
	request = { latitude: lat, 
		longitude: lng, 
		distance: dist, 
		limit: lim, 
		units: $("#dist-type").val(), 
		noimg: shownoimg, 
		nojpg: shownojpg, 
		reqphoto: showreqphoto, 
		useorquery:useor,
		accuracy:acc  };
	if ($("#page").val()) {
		request.loc = $("#page").val();
	}
	$.post("results.php",
		{ latitude: lat, longitude: lng, distance: dist, limit: lim, action: 'results', noimg:shownoimg, nojpg: shownojpg, reqphoto:showreqphoto, useorquery:useor },
		function(data) {
			makeTable(data);
		},
		"json"
	);
}

function makeTable(data) {
	$('#results-inner').show();
	var tbaseodd = '<span class="geo-res-t res-odd" style="display:none">$1</span>';
	var nbaseodd = '<span class="geo-res-n res-odd" style="display:none">$1</span>';
	var tbaseeven = '<span class="geo-res-t res-even" style="display:none">$1</span>';
	var nbaseeven = '<span class="geo-res-n res-even" style="display:none">$1</span>';
	var coordoutput = '<strong>Lat / Long:</strong> '+ request.latitude.toFixed(4).toString() + ' / ' + request.longitude.toFixed(4).toString();
	$('#results').show('normal');
	$('#search-coords').html(coordoutput).show('fast');
	if (request.accuracy != 0) {
		accval = request.accuracy.toFixed(4).toString();
		if (request.accuracy > 1) {
			accval = request.accuracy.toFixed(1).toString()
		} 
		var accoutput = '<br />Accuracy: '+accval+' '+$("#dist-type").val()
		$('#search-coords').append(accoutput);
	}
	var header = "<span style='font-weight: bold;'>"+
		tbaseodd.replace('$1', "Title")+
		nbaseodd.replace('$1', "Distance ("+$("#dist-type").val()+")")+
		nbaseodd.replace('$1', "Latitude")+
		nbaseodd.replace('$1', "Longitude")+
		"</span><br />";
	$('#results-inner').append(header);
	$('.geo-res-t').fadeIn('slow');
	$('.geo-res-n').fadeIn('slow');
	for (var i=0; i<data.length; i++) {
		var link = '<a href="https://en.wikipedia.org/wiki/'+data[i].encoded+'" title="'+data[i].title+'">'+data[i].title+'</a>';
		var lat = data[i].latitude.toFixed(4);
		var lng = data[i].longitude.toFixed(4);
		if ($("#dist-type").val() == 'km') {
			var dist = mi2km(data[i].distance).toFixed(2)
		} else {
			var dist = data[i].distance.toFixed(2);
		}
		tbase = i%2 == 0 ? tbaseeven : tbaseodd;
		nbase = i%2 == 0 ? nbaseeven : nbaseodd;
		row = tbase.replace('$1', link)+nbase.replace('$1', dist)+nbase.replace('$1', lat)+nbase.replace('$1', lng)+'<br />';
		$("#results-inner").append(row);
		$('.geo-res-t').fadeIn('slow');
		$('.geo-res-n').fadeIn('slow');
	}
	if (data.length > 0) {
		$("#bottom-stuff").slideDown('fast');
	}
	$('#submitbutton').prop('disabled', "");
	removeSpinner(resspinnerid);
	loading -= 1;
}

$(document).on("click", "#kmlgen", function(){
	if ( kmlfile ) {
		$('#kmlinfo').html('KML file generated, available at <a href="//tools.wmflabs.org/geophotoreq/kml/'+
			kmlfile+
			'.kml">/kml/'+
			kmlfile+
			'.kml</a>. Please save the file to your computer, it will be deleted from the server in 24 hours.');
		$('#kmlinfo').show('normal');
		return false;
	}
	kmlspinnerid+=2;
	injectSpinner($('#kmlgen'), kmlspinnerid);
	var details = { latitude: request.latitude, 
		longitude: request.longitude, 
		distance: request.distance, 
		limit: request.limit, 
		units: request.units, 
		reqphoto:request.reqphoto, 
		noimg:request.noimg, 
		nojpg:request.nojpg, 
		useorquery:request.useorquery };
	if (request.loc) {
		details.locname = request.loc;
	}
	$.post("makekml.php",
		details,
		function(data) {
			kmlres(data, false);
		},
		"text"
	);
	return false;
});
$(document).on("click", "#googleview", function(){
	if ( kmlfile ) {
		window.open("https://maps.google.com/maps?q=https://tools.wmflabs.org/geophotoreq/kml/"+kmlfile+".kml");
		return false;
	}
	kmlspinnerid+=2;
	injectSpinner($('#googleview'), kmlspinnerid);
	var details = { latitude: request.latitude, 
		longitude: request.longitude, 
		distance: request.distance, 
		limit: request.limit, 
		units: request.units, 
		reqphoto:request.reqphoto, 
		noimg:request.noimg, 
		nojpg:request.nojpg, 
		useorquery:request.useorquery };
	if (request.loc) {
		details.locname = request.loc;
	}
	$.post("makekml.php",
		details,
		function(data) {
			kmlres(data, true);
		},
		"text"
	);
	return false;
});

function kmlres(data, google) {
	removeSpinner(kmlspinnerid);
	if (parseInt(data)) {
		if (!google) {
			$('#kmlinfo').html('KML file generated, available at <a href="//tools.wmflabs.org/geophotoreq/kml/'+
                        data+
                        '.kml">/kml/'+
                        data+
                        '.kml</a>. Please save the file to your computer, it will be deleted from the server in 24 hours.');
			$('#kmlinfo').show('normal');
		} else {
			window.open("https://maps.google.com/maps?q=https://tools.wmflabs.org/geophotoreq/kml/"+data+".kml");
		}
		kmlfile = data;
	} else {
		$('#kmlinfo').html('KML generation failed :-(');
		$('#kmlinfo').show('normal');
		kmlfile = '';
	}
	return false;
}

function addWarn(warning) {
	if ($('#warning').text()) {
		warning = '<br />'+warning;
	}
	$('#warning').append(warning);
	$('#warning').show('fast');
	return;
}

function getDec(type) {
	deg = parseFloat($('#'+type+'d').val());
	min = parseFloat($('#'+type+'m').val());
	sec = parseFloat($('#'+type+'s').val());
	if (isNaN(deg) || isNaN(min) || isNaN(sec)) {
		addWarn("Bad input for latitude or longitude");
		return false;
	}
	var total = deg + min/60.0 + sec/3600.0;
	var dir = $('#'+type+'dir').val().toUpperCase();
	if (dir) {
		if ( (dir != "N" && dir != "S" && type == 'lat') || (dir != "E" && dir != "W" && type == 'long') ) {
			addWarn("Bad input");
			return false;
		}
		if ((dir == "S" && type == "lat") || (dir == "W" && type == "long")) {
			total = total * -1.0;
		}
	}
	return total;
}

function km2mi(km) {
	return 0.621371192*km;
}
function mi2km(mi) {
	return 1.609344*mi;
}

function validateFields() {
	var lat;
	var lng;
	var selected = $("#form-opts>span").contents("input:checked").val();
	switch(selected) {
	case "dec":
		lat = parseFloat($("#lat").val());
		lng = parseFloat($("#long").val());
		if (isNaN(lat) || isNaN(lng)) {
			addWarn("Bad input for latitude or longitude.");
			return false;
		}
		if (Math.abs(lat) > 90 || Math.abs(lng) > 180) {
			addWarn("Latitude or longitude out of valid range.")
			return false;
		}
		getResults([lat, lng, 0]);
		break;
	case "dms":
		lat = getDec('lat');
		if (lat === false) {
			return false;
		}
		lng = getDec('long');
		if (lng === false) {
			return false;
		}
		if (Math.abs(lat) > 90 || Math.abs(lng) > 180) {
			addWarn("Latitude or longitude out of valid range.")
			return false;
		}
		getResults([lat, lng, 0]);
		break;
	case "near":
		var t = $("#page").val();
		var fail = false
		$.ajax({
			async: false,
			type: "POST",
			url: "results.php",
			data: 'action=exists&title='+encodeURIComponent(t),
			success: function(data) {
				if (data == "0") {
					addWarn("Page doesn't exist");
					fail = true;
				} else if (data == "r") {
					$.ajax({
						async: false,
						type: "POST",
						url: "results.php",
						data: 'action=redir&title='+encodeURIComponent(t),
						success: function(data) {
							if (data == "0") {
								addWarn("Redirect to a non-existent page");
								fail = true;
							} else {
								$('#page').val(data);
							}
						}				
					});					
				}
			}	
		})
		t = $("#page").val();
		$.ajax({
			async: true,
			type: "POST",
			url: "results.php",
			data: 'action=getcoords&title='+encodeURIComponent(t),
			success: function(data) {
				if (data.error) {
					addWarn(data.error);
					fail = true;
				} else {
					lat = data.lat;
					lng = data.lng;
					getResults([lat, lng, 0]);
				}
			},
			dataType: 'json'
		});
		break;
	case "nearme":
		if ($("#geoapi").prop('checked')) {
			navigator.geolocation.getCurrentPosition(geolocateAPI, null, {enableHighAccuracy: true});
		} else {
			lat = parseFloat(Geo.lat);
			lng = parseFloat(Geo.lon);
			getResults([lat, lng, 0]);
		}
	}
	if (fail) {
		return false;
	}
	return true;
}

function geolocateAPI(pos) {
	var lat = pos.coords.latitude;
	var lng = pos.coords.longitude;
	var accuracy = parseFloat(pos.coords.accuracy)/1000;
	var acc = accuracy;
	if ($("#dist-type").val() == "mi") {
		acc = km2mi(acc);
	}
	if ( $('#extend-rad').prop('checked') ) {
		var radius = parseFloat($("#dist").val());
		if ($("#dist-type").val() == "mi") {
			radius = mi2km(radius);
		}
		if (accuracy > radius) {
			if ($("#dist-type").val() == "mi") {
				$("#dist").val( km2mi(accuracy).toFixed(3) );
			} else {
				$("#dist").val(accuracy.toFixed(3) );
			}
		}
	}
	getResults([lat, lng, acc]);	
}

function injectSpinner( element, id ) {
	var spinner = $('<img></img>').attr('id', "spinner-" + id)
	.attr('src', "spinner.gif")
	.attr('alt', "...").attr('title', "...")
	$(element).after(spinner);
}
function removeSpinner( id ) {
	$("#spinner-" + id).remove()
}
