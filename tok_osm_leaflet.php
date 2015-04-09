<?php

$plugin['version'] = '0.3';
$plugin['author'] = 'Torsten Krüger';
$plugin['author_uri'] = 'http://www.kryger.de/';
$plugin['description'] = 'Displays a small OpenStreetMap (with a marker in it if requested) using the leaflet library';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and non-AJAX admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the non-AJAX admin side
// 4 = admin+ajax   : only on admin side
// 5 = public+admin+ajax   : on both the public and admin side
$plugin['type'] = 1;

// Flags
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001);
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);
// $plugin['flags'] = PLUGIN_HAS_PREFS | PLUGIN_LIFECYCLE_NOTIFY;
$plugin['flags'] = PLUGIN_LIFECYCLE_NOTIFY;

if (!defined('txpinterface')) {
  @include_once('zem_tpl.php');}

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

If you do not provide width and height, more or less senseful default values will be applied.

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


h2. Preference of this plugin

h3. Local leaflet installation

By default @tok_osm_leaflet@ loads code and style (including images) from the leaflet content delivering network. In case you have downloaded the packaged files from the leaflet site, saved them on your server (and maybe have adjusted them a bit) you may advise the plugin to use that files. Just enter the path to the base directory of your leaflet stuff in the "admin" section of the "Advanced Preferences" page. Lookout for _Leaflet directory path_.

Assuming you unpacked the leaflet zip file in a directory called "leaflet" in your textpattern base directory, the following values were allowed for _Leaflet directory path_:

* @http://www.yourdomain.tld/leaflet@
* @$site_url/leaflet@
* @/leaflet@

If you forgot to remove a trailing slash in the path delaration, the plugin will do it for you.

An empty value for _Leaflet directory path_ will cause the files to be loaded from the leaflet content delivery network.


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

global $tok_osm_leaflet_mapcounter;
if ( $tok_osm_leaflet_mapcounter == NULL) {
  $tok_osm_leaflet_mapcounter = 0; }


// register admin stuff of plugin
if ( @txpinterface === 'admin' ) {
  /* tok_osm_leaflet_install(); */
  register_callback( 'tok_osm_leaflet_prefs', 'prefs', '', 1 );
  register_callback( 'tok_osm_leaflet_lifecycle', 'plugin_lifecycle.tok_osm_leaflet' );
}

// cleanup database after removing plugin
function tok_osm_leaflet_lifecycle( $event = '', $step = '' ) {
  if( $step == 'deleted' ) {
    safe_delete( 'txp_prefs',
		 "name like 'tok_osm_leaflet_%'"
		 );
    return;
  }
}
		
// add pref to appropriate panel
function tok_osm_leaflet_prefs() {
  global $textarray;
  $textarray['tok_osm_leaflet_leafletpath'] = 'Leaflet directory path';
  
  if ( ! safe_field ( 'name', 'txp_prefs', "name='tok_osm_leaflet_leafletpath'" )) {
    safe_insert( 'txp_prefs',
		 "prefs_id=1, name='tok_osm_leaflet_leafletpath', val='', " .
		 "type=1, event='admin', html='text_input', position=20");
  }
}

// main part of plugin
function tok_osm_leaflet( $atts ) {

  global $tok_osm_leaflet_mapcounter, $tok_osm_leaflet_leafletpath;
  $tok_osm_leaflet_mapcounter++;

  // setup path for leaflet stuff
  if ( empty ( $tok_osm_leaflet_leafletpath )) {
    $tok_osm_leaflet_leafletpath = 'http://cdn.leafletjs.com/leaflet-0.7.3';}
  $leafletpath = rtrim( $tok_osm_leaflet_leafletpath, '/' );
  $leafletpath = str_replace( '$site_url', rtrim( site_url(), '/' ), $leafletpath );

  // Get Attributes
  extract( lAtts( array(
			'width'    => '600px',
			'height'   => '400px',
			'zoom'     => '16',
			'clat'     => '',
			'clon'     => '',
			'mlat'     => '',
			'mlon'     => '',
			'mcomment' => '',
                        'tokcounter' => 0
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

  // build map ID
  $map_id = 'tok_osm_leaflet_map_' . $tok_osm_leaflet_mapcounter;

  // assemble html part
  $map_part = '';

  // load leaflet stuff only once
  if ( $tok_osm_leaflet_mapcounter == 1 ) {
    $map_part .= '<script src="' . $leafletpath . '/leaflet.js"></script>' . "\n" .
    '<link rel="stylesheet" href="' . $leafletpath . '/leaflet.css" />' . "\n"; }

  $map_part .=  '<div id="' . $map_id . '" style="width:' . $width .
    ';height:' . $height . '; border: 1px solid #8a7364;"></div>' . "\n" .
    '<script type="text/javascript">' . "\n" .
    'window.addEventListener("load", build_' . $map_id . ', false);' . "\n" .
    'function build_' . $map_id . '() {'. "\n" .
    'var ' . $map_id . ' = L.map("' . $map_id . '").setView([' . $clat . ',' . $clon . '],' . $zoom . ');' .
    "\n" . 'L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {' . "\n" .
    'attribution: \'&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors\'}).addTo(' . $map_id . ');';

  // add marker if one was requestes
  if (( !empty( $mlon )) && ( !empty( $mlat )) ) {
    $map_part .= 'L.marker([' . $mlat . ',' . $mlon . ']).addTo(' . $map_id . ')';
    if ( !empty( $mcomment )) {
      $map_part .= '.bindPopup("'.$mcomment.'")';}
  }

  // finish
  $map_part .= '}</script>';
 
  return ( $map_part );
}

# --- END PLUGIN CODE ---

?>
