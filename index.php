<?php
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
$PROJECT = 'geophotoreq';
require_once('commonphp/template.php');
templatetop("Geotagged pages needing images", array( 'coord.css' ), array( 'jquery.js', 'coord.js' ), '',
"<script src=\"//geoiplookup.wikimedia.org\" type=\"text/javascript\"></script>"
);
require_once('commonphp/GlobalFunctions.php');
?>
			<div id="no-js-warn">You need a browser with JavaScript enabled to use this tool<br/>
			If your browser has JavaScript enabled, but you still see this message, please <a href="http://en.wikipedia.org/wiki/User_talk:Mr.Z-man">let me know</a>,
			and include information about the browser you are using, and details from the JavaScript error console if available.</div>
			<div id="main">
			<fieldset>
			<legend>Search for pages on the English Wikipedia needing images near a given coordinate</legend>
				<form id="mainform" action="get">
				<div class="geo-container">
					<div id="form-opts">
						<span class="form-opts-left">
							<label for="nearme">Near your present location: </label><input type="radio" name="inputtype" id="nearme" value="nearme" checked="checked" />
						</span><span class="form-opts-right">
							<label for="decimal">Decimal coordinates: </label><input type="radio" name="inputtype" id="decimal" value="dec" />
						</span><br /><span class="form-opts-left">
							<label for="near">Near another location: </label><input type="radio" name="inputtype" id="near" value="near" />
						</span><span class="form-opts-right">
							<label for="dms">Deg/min/sec: </label><input type="radio" name="inputtype" id="dms" value="dms" />
						</span><br />
					</div>
					<div class="opt-form" id="dec-form" style="display:none">
						<span class="geo-label"><label for="lat">Latitude: </label></span>
						<span class="geo-input"><input name="lat" size="30" value="0" id="lat" /></span>
						<br />
						<span class="geo-label"><label for="long">Longitude: </label></span>
						<span class="geo-input"><input name="long" size="30" value="0" id="long" /></span>
					</div>
					<div class="opt-form" id="dms-form" style="display:none">
						<span class="geo-label-4"><label>Latitude: </label></span>
						<span class="geo-input-4"><input name="latd" size="5" value="0" id="latd" />°</span>
						<span class="geo-input-4"><input name="latm" size="5" value="0" id="latm" />'</span>
						<span class="geo-input-4"><input name="lats" size="5" value="0" id="lats" />″</span>
						<span class="geo-input-4"><input name="latdir" size="1" maxlength="1" value="" id="latdir" />(N/S)</span>
						<br />
						<span class="geo-label-4"><label>Longitude: </label></span>
						<span class="geo-input-4"><input name="longd" size="5" value="0" id="longd" />°</span>
						<span class="geo-input-4"><input name="longm" size="5" value="0" id="longm" />'</span>
						<span class="geo-input-4"><input name="longs" size="5" value="0" id="longs" />″</span>
						<span class="geo-input-4"><input name="longdir" size="1" maxlength="1" value="" id="longdir" />(E/W)</span>
					</div>
					<div class="opt-form" id="near-form" style="display:none">
						<span class="geo-label-3"><label for="page">Search for pages near the coordinates on another article: </label></span>
						<span class="geo-input-3"><input name="page" size="45" value="" id="page" /></span>
						<span id="note" style="display:none"></span>
					</div>
					<div class="opt-form" id="nearme-form">
						<span class="geo-label-3"><label for="geoapi">Use your browser's geolocation: </label></span>
						<span class="geo-input"><input name="geolocate" type="radio" value="geoapi" id="geoapi" /></span>
						<br />
						<div id="geoapi-extend"><span class="geo-label-3" style="padding: 2% 0 2% 5%;"><label for="extend-rad">Extend search radius based on geolocation accuracy: </label></span>
						<span class="geo-input"><input name="extend-rad" type="checkbox" value="1" id="extend-rad" /></span><br /></div>
						<span id="geoip" style="display:none">
						<span class="geo-label-3"><label for="geoapi">Use an IP address-based geolcation database: </label></span>
						<span class="geo-input"><input name="geolocate" type="radio" value="tsdb" id="tsdb" /></span><br />
						</span>
					</div>
					<div id="extra-opts">
						<div id="extra-checks">
						<span class="geo-input-2"><input type="checkbox" name="noimg" id="noimg" /></span>
						<span class="geo-label-3"><label for="noimg">Show pages with no images</label></span>
						<br />
						<span class="geo-input-2"><input type="checkbox" name="nojpg" id="nojpg" /></span>
						<span class="geo-label-3"><label for="nojpg">Show pages with no JPGs</label></span>
						<br />
						<span class="geo-input-2"><input type="checkbox" name="reqphoto" id="reqphoto" checked="checked" /></span>
						<span class="geo-label-3"><label for="reqphoto">Show pages with photo requests</label></span>
						<br />
						<div id="andor" style="padding:0.5em">
							<span class="geo-input-2"><input type="radio" name="andor" id="useor" checked="checked" value="useor" /></span>
							<span class="geo-label-3"><label for="useor">Show pages that meet <em>any</em> of the options</label></span><br />
							<span class="geo-input-2"><input type="radio" name="andor" id="useand" checked="checked" value="useand" /></span>
							<span class="geo-label-3"><label for="useand">Show pages that meet <em>all</em> of the options</label></span>
						</div>
						</div>
						<span class="geo-label-2"><label for="dist">Within </label></span>
						<span class="geo-input-2"><input name="dist" size="5" value="10" id="dist" /></span>
						<select id="dist-type">
							<option value="km">km</option>
							<option value="mi">mi</option>
						</select><br />
						<span class="geo-label-2"><label for="limit">Limit </label></span>
						<span class="geo-input-2"><input name="limit" maxlength="3" size="3" value="20" id="limit" /></span>
					</div>
					<br />
					<input type="submit" id="submitbutton" value="Submit" /><br />
					<span id="warning" class="result notice" style="display:none" ></span>
				</div>
				</form>
			</fieldset>
		</div>
		<div id="results" style="display:none">
		<fieldset>
		<legend>Results</legend>
			<div id="search-coords" style="display:none; padding-bottom:1%">
			</div>
			<div id="results-inner">
			</div>
			<ul id="bottom-stuff" style="display:none">
			<li><a href="#" id="kmlgen">Generate KML</a></li>
			<li id="kmlinfo" style="display:none"></li>
			<li><a href="#" id="googleview">View in Google Maps</a></li>
			</ul>
		</fieldset>
		</div>
<?php
templatebottom();
