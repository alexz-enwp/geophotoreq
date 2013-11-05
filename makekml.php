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
date_default_timezone_set('UTC');
$_POST['action'] = 'results';

$units = $_POST['units'];

$center = '';
$link = '';
if (isset($_POST['locname'])) {
	$center = htmlspecialchars($_POST['locname']);
	$linktitle = urlencode(str_replace(' ', '_', $_POST['locname']));
	$link = "<a href='https://en.wikipedia.org/wiki/$linktitle' title='$center'>$center</a>";
} else {
	$la = number_format((float)$_POST['latitude'], 7, '.', '');
	$lo = number_format((float)$_POST['longitude'], 7, '.', '');
	$center = $la . ', ' . $lo;
}
ob_start();
@require('results.php');
$res = ob_get_contents();
ob_end_clean();

$data = json_decode($res, true);
$writer = new XMLWriter;
$writer->openMemory();
$writer->startDocument('1.0', 'UTF-8');
$writer->startElement('kml');
$writer->writeAttribute('xmlns', "http://www.opengis.net/kml/2.2");
$writer->text("\n");
$writer->startElement('Document');
# Add the center point
$writer->text("\n");
$writer->startElement('Placemark');
$writer->text("\n");
$writer->writeElement('name', $center);
$writer->text("\n");
if ($link) {
	$writer->writeElement('description', $link);
	$writer->text("\n");
}
$writer->startElement('Point');
$writer->text("\n");
$writer->writeElement('coordinates', $_POST['longitude'] . ',' . $_POST['latitude']. ',0');
$writer->text("\n");
$writer->endElement();
$writer->text("\n");
$writer->endElement();
$writer->text("\n");

# Add each point
foreach ($data as $entry) {
	$writer->startElement('Placemark');
	$writer->text("\n");
	$writer->writeElement('name', $entry['title']);
	$writer->text("\n");
	$desc = "<a href='http://en.wikipedia.org/wiki/".$entry['encoded']."' title='".$entry['title']."'>".$entry['title']."</a>, ";
	$desc .= '<br />Distance: ';
	$dist = (float)$entry['distance'];
	if ($units == 'km') {
		$dist = number_format(mi2km($dist), 3, '.', '');
	} else {
		$dist = number_format($dist, 3, '.', '');
	}
	$desc .= $dist . ' ' . $units;
	$writer->writeElement('description', $desc);
	$writer->text("\n");
	$writer->startElement('Point');
	$writer->text("\n");
	$writer->writeElement('coordinates', $entry['longitude'] . ',' . $entry['latitude']. ',0');
	$writer->text("\n");
	$writer->endElement();
	$writer->text("\n");
	$writer->endElement();
	$writer->text("\n");
}	
$writer->endElement();
$writer->text("\n");
$writer->endElement(); # Close <kml>
$writer->endDocument();

$filename = date( "YmdHis" );

if (file_put_contents("/data/project/geophotoreq/public_html/kml/$filename.kml", $writer->outputMemory() )) {
	echo $filename;
} else {
	echo 0;
}

function mi2km( $mi ) {
	return 1.609344*$mi;
}
