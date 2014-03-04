<?php

$plugin['version'] = '0.2';
$plugin['author'] = 'Torsten Krüger';
$plugin['author_uri'] = 'http://kryger.de/';
$plugin['description'] = 'Displays a small OpenStreetMap (with a marker in it if requested) using the leaflet library';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0;

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h1. tok_osm_leaflet

… displays a map based on OpenStreetMap data using the leaflet javascript library. Width and height of map div are configurable and of course the portion to be shown. A marker may be set on the landscape for adding a textual comment.

Required resources will be loaded from external sources, this applies obviously to the map tiles, but also to javascript, css and image files e.g. for the markers.


h2. Attributes of this plugin


h3. Map dimension

* width
* height

Are any valid CSS dimensions to set width and height of the map div. If you omit the unit, "px" is assumed.

If you don't provide width and height, more or less senseful default values will be applied.

h4. Set up the map

The visible portion of the map is determined by three attributes:

* clon
* clat
* zoom

Where _clon_ is the longitude and _clat_ is the latitude of the maps centeras GPS values. Both _clon_ and _clat_ are mandatory.

The scale of the map is set by the _zoom_ attribute.

h3. Adding a marker

The attributes for setting a marker are:

* mlon
* mlat
* mcomment

_mlon_ and _mlat_ are the GPS coordinates of the markers position, exactly as _clon_ and _clat_. A marker will be placed on the map at that position. If _mlon_ and _mlat_ are provided, _clon_ and _clat_ may be omitted; the map will be centered slightly avobe the marker in that case.

If _mcomment_ has a value, the marker will be clickable to show the given content.


h2. Examples


h3. The simplest way of showing a map

The Brandenburg Gate in Berlin

bc. <txp:tok_osm_leaflet clat="52.5163" clon="13.3778" />


h3. Showing the exact portion

The city area of Berlin.

bc. <txp:tok_osm_leaflet width="500" height="400"
     clat="52.5012" clon="13.4314" zoom="11" />


h3. A simple marker

… on the Eiffel Tower in Paris

bc. <txp:tok_osm_leaflet width="600" height="450" zoom="12"
     clat="48.8586" clon="2.3439" mlat="48.8583" mlon="2.2944" />


h3. An automatically centered marker

The Eiffel Tower again; just omit _clon_ and _clat_

bc. <txp:tok_osm_leaflet width="600" height="450" zoom="12" mlat="48.8583" mlon="2.2944" />


h3. A marker with comment

A Jazz Standard

bc. <txp:tok_osm_leaflet mlat="40.76291" mlon="-73.98285" mcomment="Birdland" />


# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---

function tok_osm_leaflet($atts) {

  // Get Attributes
  extract( lAtts( array(
			'width'    => '600px',
			'height'   => '400px',
			'zoom'     => '16',
			'clat'     => '',
			'clon'     => '',
			'mlat'     => '',
			'mlon'     => '',
			'mcomment' => ''
			), $atts ));

  $err_format = '<span style="color:#d12;" title="%s">█</span>';

  // check neccessary atttributes
  if ( empty ( $clat )) {
    if ( !empty( $mlat )) {
      $clat = number_format( $mlat + 0.0005, 5, '.', '');
    }
    else {
      return( sprintf( $err_format, 'Center latitude is missing!' ));
    }
  }
  if ( empty ( $clon )) {
    if ( !empty( $mlon )) {
      $clon = $mlon;
    }
    else {
      return( sprintf( $err_format, 'Center longitude is missing!' ));
    }
  }

  // add "px" to width and height, if no unit was given
  if ( is_numeric( $width )) { $width = $width . 'px'; }
  if ( is_numeric( $height )) { $height = $height . 'px'; }

  // assemble the html part
  $map_part = '<script src="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js"></script>' . "\n" .
    '<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css" />' . "\n" .
    '<div id="map" style="width:' . $width .
    ';height:' . $height . '; border: 1px solid #8a7364;"></div>' . "\n" .
    '<script type="text/javascript">' . "\n" .
    'window.addEventListener("load", build_map, false);' . "\n" .
    'function build_map() {'. "\n" .
    'var map = L.map("map").setView([' . $clat . ',' . $clon . '],' . $zoom . ');' . "\n" .
    'L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {' . "\n" .
    'attribution: \'&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors\'}).addTo(map);';

  // add marker if one was requestes
  if (( !empty( $mlon )) && ( !empty( $mlat ) )) {
    $map_part .= 'L.marker([' . $mlat . ',' . $mlon . ']).addTo(map)';
    if ( !empty( $mcomment )) {
      $map_part .= '.bindPopup("'.$mcomment.'")';}
  }

  // finish
  $map_part .= '}</script>';

  return ( $map_part );
}

# --- END PLUGIN CODE ---

?>
