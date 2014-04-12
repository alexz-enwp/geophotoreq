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
require_once('commonphp/mysql.php');
require_once('commonphp/GlobalFunctions.php');
switch($_POST['action']) {
case "results":
	header( "Content-Type: application/json; charset=utf-8" );

	$lat = (float)$_POST['latitude'];
	$long = (float)$_POST['longitude'];
	$dist = (float)$_POST['distance'];
	$limit = (int)$_POST['limit'] >= 1000 ? 1000 : (int)$_POST['limit'];
	$limit += 10;
	$whereconds = array();
	$noimg = $_POST['noimg'];
	if ( $noimg == 'true' ) {
		$whereconds[] = 'noimg = 1';
	}
	$nojpg = $_POST['nojpg'];
	if ( $nojpg == 'true' ) {
		$whereconds[] = 'nojpg = 1';
	}
	$reqphoto = $_POST['reqphoto'];
	if ( $reqphoto == 'true' ) {
		$whereconds[] = 'reqphoto = 1';
	}
	$andor = " AND ";
	$useorquery = $_POST['useorquery'];
	if ($useorquery == 'true') {
		$andor = ' OR ';
	}
	$where = '';
	if (!empty($whereconds)) {
		$where = 'AND (' . implode($andor, $whereconds) . ')';
	}
	$db = mysql_connect( 'tools-db', $my_user, $my_pass );
	mysql_select_db( 's51422__geophotoreq', $db );

	$const = 0.00020943241720614; // Based on the radius of Earth or something

	$lowerlat = -1.0*(sqrt( $const * $dist*$dist ) - $lat);
	$upperlat = sqrt( $const * $dist*$dist ) + $lat;
	$lowerlong = (cos( ($lat * M_PI) / 180 ) * $long - sqrt( $const * $dist*$dist ) ) / ( cos( ($lat * M_PI) / 180 ) );
	$upperlong = (cos( ($lat * M_PI) / 180 ) * $long + sqrt( $const * $dist*$dist ) ) / ( cos( ($lat * M_PI) / 180 ) );

	// Assume 1% error
	$lowerlat -= $lowerlat * 0.01;
	$upperlat += $upperlat * 0.01;
	$lowerlong -= $lowerlong * 0.01;
	$upperlong += $upperlong * 0.01;

	$qstring = "SELECT title, X(coordinate) AS latitude, Y(coordinate) AS longitude,
        3963.1676*ACOS(SIN($lat*PI()/180.0)*SIN(X(coordinate)*PI()/180.0)+COS($lat*PI()/180.0)*COS(X(coordinate)*PI()/180.0)*COS( Y(coordinate)*PI()/180.0-$long*PI()/180.0)) AS distance
        FROM photocoords WHERE
        Contains(
                Polygon(LineString(
                        Point($lowerlat,$upperlong),
                        Point($lowerlat,$lowerlong),
                        Point($upperlat,$lowerlong),
                        Point($upperlat,$upperlong),
                        Point($lowerlat,$upperlong)
        )), coordinate)
        $where
        ORDER BY GLength(LineString(Point($lat, $long), coordinate)) ASC LIMIT $limit;";
	file_put_contents('/data/project/geophotoreq/public_html/query.txt', $qstring);

	$res = mysql_query("SELECT title, X(coordinate) AS latitude, Y(coordinate) AS longitude,
	3963.1676*ACOS(SIN($lat*PI()/180.0)*SIN(X(coordinate)*PI()/180.0)+COS($lat*PI()/180.0)*COS(X(coordinate)*PI()/180.0)*COS( Y(coordinate)*PI()/180.0-$long*PI()/180.0)) AS distance
	FROM photocoords WHERE
	Contains(
		Polygon(LineString(
			Point($lowerlat,$upperlong),
			Point($lowerlat,$lowerlong),
			Point($upperlat,$lowerlong),
			Point($upperlat,$upperlong),
			Point($lowerlat,$upperlong)
	)), coordinate)
	$where 
	ORDER BY GLength(LineString(Point($lat, $long), coordinate)) ASC LIMIT $limit;", $db);

	$retval = array();

	while ($row = mysql_fetch_assoc($res)) {
		if( (float)$row['distance'] > $dist )
			continue;
		$title = htmlspecialchars( str_replace('_', ' ', $row['title']), ENT_QUOTES );
		$urltitle = wfUrlencode( $row['title'] );
		$latitude = (float)$row["latitude"];
		$longitude = (float)$row["longitude"];
		$distance = (float)$row['distance'];
		$retval[] = array(
			'title' => $title,
			'encoded' => $urltitle,
			'latitude' => $latitude,
			'longitude' => $longitude,
			'distance' => $distance,
		);
	}

	function cmpdist($a, $b) {
		return (int)ceil($a['distance']-$b['distance']);
	}

	usort( $retval, 'cmpdist' );
	$retval = array_slice( $retval, 0, $limit-10, true );
	$retval = json_encode($retval);
	echo($retval);
	break;
case "getcoords":
	header( "Content-Type: application/json; charset=utf-8" );
	$db = mysql_connect( 'enwiki.labsdb', $my_user, $my_pass );
	mysql_select_db( 'enwiki_p', $db );
	$title = ucfirst(str_replace(' ', '_', mysql_real_escape_string($_POST['title'])));
	$result = mysql_query( "SELECT gt_lat, gt_lon FROM geo_tags
	JOIN page ON page_id=gt_page_id
	WHERE page_namespace=0 AND page_title='$title' ORDER BY gt_primary DESC LIMIT 1", $db );
	$res = array();
	$err = mysql_error( $db );
	if ( $err ) {
		$res['error'] = $err;
		echo( json_encode($res) );
	} elseif ( mysql_num_rows( $result ) === 0 ) {
		$res['error'] = 'Unable to determine coordinates';
		echo( json_encode($res) );
	} else {
		$row = mysql_fetch_assoc($result);
		$res['lat'] = (float)$row['gt_lat'];
		$res['lng'] = (float)$row['gt_lon'];
		echo( json_encode($res) );
	}
	break;
case "redir":
	$db = mysql_connect( 'enwiki.labsdb', $my_user, $my_pass );
	mysql_select_db( 'enwiki_p', $db );
	$title = ucfirst(str_replace(' ', '_', mysql_real_escape_string($_POST['title'])));
	$res = mysql_query( "SELECT rd_title FROM redirect JOIN page ON rd_from=page.page_id JOIN page AS page2 ON page2.page_title=rd_title AND page2.page_namespace=rd_namespace WHERE rd_namespace=0 AND page.page_namespace=0 AND page.page_title='$title' LIMIT 1;", $db );
	$row = mysql_fetch_assoc($res);
	if ($row) {
		echo str_replace('_', ' ', $row['rd_title']);
	} else {
		echo "0";
	}
	break;
case "exists":
	$db = mysql_connect( 'enwiki.labsdb', $my_user, $my_pass );
	mysql_select_db( 'enwiki_p', $db );
	$title = ucfirst(str_replace(' ', '_', mysql_real_escape_string($_POST['title'])));
	$res = mysql_query( "SELECT page_id,page_is_redirect FROM page WHERE page_namespace=0 AND page_title='$title';", $db );
	$row = mysql_fetch_assoc($res);
	if ($row && $row['page_is_redirect']) {
		echo "r";
	} elseif ($row) {
		echo "1";
	} else {
		echo "0";
	}
	break;
}
