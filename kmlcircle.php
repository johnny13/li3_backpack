<?php

/*
#!/usr/bin/php

#The MIT License
#
# Copyright (c) 2007 Nick Galbreath
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.
#

#
# Version 2 - 12-Sept-2007 Simplified XML output
#                          Added commandline interface
# Version 1 - 10-Sept-2007 Initial release
#

#
# Convert (x,y,z) on unit sphere
# back to (long, lat)
#
# p is vector of three elements
# 
*/

function toEarth( $p ) {
	if( $p[0] == 0.0 ) {
		$longitude = pi() / 2.0;
	} else {
		$longitude = atan( $p[1] / $p[0] );
	}
	$colatitude = acos( $p[2] );
	$latitude = ( (pi() / 2.0) - $colatitude );

	# select correct branch of arctan
	if( $p[0] < 0.0 ) {
		if( $p[1] <= 0.0 ) {
			$longitude = -( pi() - $longitude );
		} else {
			$longitude = pi() + $longitude;
		}
	}
	$DEG = 180.0 / pi();
	return array( round($longitude * $DEG, 10), round($latitude * $DEG, 10) );
}

#c
# convert long, lat IN RADIANS to (x,y,z)
#
function toCart( $longitude, $latitude ) {
	$theta = $longitude;
	# spherical coordinate use "co-latitude", not "lattitude"
	# lattiude = [-90, 90] with 0 at equator
	# co-latitude = [0, 180] with 0 at north pole
	$phi = pi() / 2.0 - $latitude;
	return array( cos($theta) * sin($phi), sin($theta) * sin($phi), cos($phi) );
}

# spoints -- get raw list of points in long,lat format
#
# meters: radius of polygon
# n: number of sides
# offset: rotate polygon by number of degrees
#
# Returns a list of points comprising the object
#
function spoints( $long, $lat, $meters, $n, $offset = 0 ) {
	# constant to convert to radians
	$RAD = pi() / 180.0;
	# Mean Radius of Earth, meters
	$MR = 6378.1 * 1000.0;
	$offsetRadians = $offset * $RAD;
	# compute longitude degrees (in radians) at given latitude
	$r = ( $meters / ($MR * cos($lat * $RAD)) );

	$vec = toCart( $long * $RAD, $lat * $RAD );
	$pt = toCart( $long * $RAD + $r, $lat * $RAD );
	$pts = array();

	for( $i = 0; $i < $n; $i++ ) {
		$pts[] = toEarth( rotPoint($vec, $pt, $offsetRadians + (2.0 * pi() / $n) * $i) );
	}

	# connect to starting point exactly
	# not sure if required, but seems to help when
	# the polygon is not filled
	$pts[] = $pts[0];
	return $pts;
}

#
# rotate point pt, around unit vector vec by phi radians
# http://blog.modp.com/2007/09/rotating-point-around-vector.html
#
function rotPoint( $vec, $pt, $phi ) {
	# remap vector for sanity
	list( $u, $v, $w, $x, $y, $z ) = array( $vec[0], $vec[1], $vec[2], $pt[0], $pt[1],
		$pt[2] );

	$a = $u * $x + $v * $y + $w * $z;
	$d = cos( $phi );
	$e = sin( $phi );

	return array( ($a * $u + ($x - $a * $u) * $d + ($v * $z - $w * $y) * $e), ($a *
		$v + ($y - $a * $v) * $d + ($w * $x - $u * $z) * $e), ($a * $w + ($z - $a * $w) *
		$d + ($u * $y - $v * $x) * $e) );
}

#
# Regular polygon
# (longitude, latitude) in decimal degrees
# meters is radius in meters
# segments is number of sides, > 20 looks like a circle
# offset, rotate polygon by a number of degrees
#
# returns a string suitable for adding to a KML file.
#
# You may want to
#  edit this function to change "extrude" and other XML nodes.
#
function kml_regular_polygon( $long, $lat, $meters, $segments = 30, $offset = 0 ) {
	$s = "<Polygon>\n";
	$s .= "  <outerBoundaryIs><LinearRing><coordinates>\n";
	foreach( spoints($long, $lat, $meters, $segments, $offset) as $p ) {
		$s .= "    ".( float )$p[0].",".( float )$p[1]."\n";
	}
	$s .= "  </coordinates></LinearRing></outerBoundaryIs>\n";
	$s .= "</Polygon>\n";
	return $s;
}

#
# Make a "star" or "burst" pattern
#
# (long, lat) = center point
# outer = radius in meters
# inner = radius in meters, typically < outer
# segments = number of "points" on the star
# offset = rotate by degrees
#
# Returns a XML snippet suitable for adding into a KML file
#
function kml_star( $long, $lat, $outer, $inner, $segments = 10, $offset = 0 ) {
	$opts = spoints( $long, $lat, $outer, $segments, $offset );
	$ipts = spoints( $long, $lat, $inner, $segments, $offset + 180.0 / $segments );

	# interweave the outer and inner points
	# I'm sure there is a better way
	$pts = array();
	for( $i = 0; $i < count($opts); $i++ ) {
		$pts[] = $opts[$i];
		$pts[] = $ipts[$i];
	}
	$s = "<Polygon>\n";
	$s .= "  <outerBoundaryIs><LinearRing><coordinates>\n";
	foreach( $pts as $p ) {
		$s .= "    ".$p[0].",".$p[1]."\n";
	}
	$s .= "  </coordinates></LinearRing></outerBoundaryIs>\n";
	$s .= "</Polygon>\n";
	return $s;
}

#   $s .= "  <extrude>1</extrude>\n";
#   $s .= "  <altitudeMode>clampToGround</altitudeMode>\n";

# example:
//echo kml_regular_polygon( 81, 14.2, 10000, 4 );
//echo kml_star( 81, 14.2, 10000, 8000, 5 );

?>