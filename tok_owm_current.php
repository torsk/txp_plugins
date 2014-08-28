// -*- mode: PHP; mode: folding; folded-file: t; -*-
<?php

// {{{ plugin setup

$plugin['version'] = '0.2';
$plugin['author'] = 'Torsten Krüger';
$plugin['author_uri'] = 'http://kryger.de/';
$plugin['description'] = 'Shows data from OpenWeatherMap widely configurable';

$plugin['type'] = 0;

if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);
$plugin['flags'] = PLUGIN_LIFECYCLE_NOTIFY;


@include_once('zem_tpl.php');

if (0) {
?>
// }}}

// {{{ plugin help

# --- BEGIN PLUGIN HELP ---

h1. tok_owm_current

… displays current weather conditions using the "OpenWeatherMap API":http://openweathermap.org/api

The output may be widely configured by setting query parameters and using variables via the plugins attributes.


h2. Attributes of this plugin

* apikey

Necessary attribute. Please provide your "OpenWeatherMap APPID":http://openweathermap.org/appid here.

* cityids

Another necessary attribute: The id number (or numbers) of the desired city (or cites) as defined in the OpenWeatherMap database. Use the "search form":http://openweathermap.org/find or try the "maps":http://openweathermap.org/maps to find your city. You'll find the appropriate number in the address bar of your browser. If you want to request data of more than one city, please seperate the id numbers by a comma sign ",".
  
* lang

This attribute is your interface to the "OpenWeaterMap multilingual support":http://openweathermap.org/current#multi. Defaults to "en".

* na

What to show, if a requested value is not available. This may depend on language or personal preferences; so you may set it here. Default is a question mark "?".

* cache_secs

Time in seconds to cache a requests result. The same request within the given time will be answered from the cached data without sending another request to OpenWeatherMap. Please note, that the output is cached -- so if you are fiddeling around with the display attribute, reduce this value for the time of testing. Default is 600 (10 minutes).

* cache_mark

Text to be attached to the output, if cached data was used.

* units

OpenWeatherMap supports two metric systems: "metric" or "imperial" – choose one! Defaults to "metric"

* display

Define the look of the plugin. Provide a simple html sequence here. Defaults to show the name of city, an icon showing current weather condition and the temperature. If you use this @tok_owm_current@ as a container tag, the display attribute is ignored and the tags content is taken instead. Special variables are used for the weather data. Please see the next section for details:


h3. Display variables

You may use the following variables in the display attribute (the double square brackets belong to the variable name, please see the _example_ section). The variable names follow the "OpenWeatherMap parameters":http://openweathermap.org/weather-data#current
  
* @[[cityname]]@

Name of city as provided by OpenWeatherMap

* @[[city_owm_url]]@

URL of the selected city´s page at OpenWeatherMap

* @[[lat]]@
* @[[lon]]@

Coordinates of the city as provided by OpenWeatherMap

* @[[temp]]@

Current temperature with (at this moment) two decimal places

* @[[temp_int]]@

Current temperature rounded to an integer

* @[[temp_min]]@

Minimum temperature at the moment

* @[[temp_max]]@

Maximum temperature at the moment

* @[[humidity]]@

Humidity

* @[[pressure]]@

Atmospheric pressure

* @[[speed]]@

Wind speed

* @[[deg]]@

Wind direction

* @[[clouds]]@

Cloudiness

* @[[condition]]@

Weather "condition code":http://openweathermap.org/weather-conditions as used by OpenWeatherMap

* @[[main]]@

Group of weather parameters

* @[[description]]@

Description of current weather condition 

* @[[icon_url]]@

URL of weather icon

* @[[rain1]]@

Precipitation volume for last 1 hour

* @[[rain3]]@

Precipitation volume for last 3 hours


h2. Examples

Please note, that you have to provide a valid APPID to get the examples to work.

h3. A short textual weather report

bc. <txp:tok_owm_current
  cityids = "2147714"
  apikey = "0123456789abcdef0123456789abcdef"
  display = "<a href='[[city_owm_url]]' target='_blank'>[[cityname]]</a>: [[temp_int]]°C, [[description]]" /></li>


h3. Nicer output, some eyecandy too, used as container tag

This example looks scaring, mostly because of the built-in CSS code. But in that way I am allowed to provide an example which is ready to use by copy and paste. In your textpattern environment the CSS code should be better integrated.

Some very special technology is used here (CSS 3, Unicode characters), so please use an up-to-date browser for this one.

bc. <txp:tok_owm_current cityids = "1796236,3369157,3435910" apikey = "0123456789abcdef0123456789abcdef">
<div style='border:1px solid #ccc;border-radius:5px;display:inline-block;
            font-size:0.8em;margin-right:1em;padding:6px;text-align:center;'>
  <strong style='font-size:1.2em;'>[[cityname]]</strong><br />
  <img src='[[icon_url]]' alt='[[description]]' title='[[description]]' /><br />
  <span style='background-color: #777;border-radius:3px;color:#fff;display:inline-block;
               font-weight:bold;margin-bottom:1ex;padding:3px;vertical-align: baseline;'
               title='Current temperature'>[[temp]]°C</span><br />
  <span title='Cloudiness'>☁  [[clouds]] %</span><br />
  <span title='Precipitation volume for last hour'>☔ [[rain1]] mm</span></p>
  <p title='Wind direction and speed'>
    %{display:inline-block;margin-top:1ex;transform:rotate([[deg]]deg);}↓%<br />
    [[speed]] m/s</p>
  <p title='Humidity and pressure'>[[humidity]] %<br />
    [[pressure]] hpa</p>
</div></txp:tok_owm_current> 
				
  
h2. Development

If you have suggestions for additional features of this plugin, please "let me know":https://github.com/torsk/txp_plugins/issues.
					 


# --- END PLUGIN HELP ---
<?php
}

// }}}

// {{{ plugin code
# --- BEGIN PLUGIN CODE ---

  // {{{ lifecycle callbacks and functions

    // {{{ Lifecycle callback
if (@txpinterface == 'admin') {
  register_callback('tok_owm_current_installation_routine',
		    'plugin_lifecycle.tok_owm_current', 'installed');
  register_callback('tok_owm_current_deletion_routine',
		    'plugin_lifecycle.tok_owm_current', 'deleted');
}
// }}}

    // {{{ installation routine
function tok_owm_current_installation_routine() {

  $create_table ="CREATE TABLE IF NOT EXISTS `".PFX."tok_owm_current_requests_cache` ("; 
  $create_table .= <<<EOD
    `id` int(15) NOT NULL AUTO_INCREMENT,
    `request` varchar(32) NOT NULL,
    `timestamp` int(11) NOT NULL,
    `data` mediumtext NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `request` (`request`)
    ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
EOD;

  safe_query($create_table); 
}
// }}}

    // {{{ deletion routine
function tok_owm_current_deletion_routine() {
  $drop_table = "DROP TABLE IF EXISTS `".PFX."tok_owm_current_requests_cache`;";
  safe_query($drop_table); 
}
// }}}

// }}}

  // {{{ main function
function tok_owm_current($atts, $thing = null ) {

  // {{{ get and check Attributes
  extract( lAtts( array(
			'apikey'  => '',
			'lang'  => 'en',
			'na'  => '?',
			'cache_secs'  => '600',
			'cache_mark'  => '',
			'cityids'  => '',
			'units'   => 'metric',
			'display' => '<p><strong>[[cityname]]</strong><br /><img src="[[icon_url]]" '.
			'alt="[[description]]" /><br />[[temp_int]]°C</p>'
			), $atts ));

  $err_format = '<span style="color:#d12;" title="%s">█</span>';

  // check
  $error = '';
  if ( empty ( $cityids )) {
    $error .= sprintf( $err_format, 'City ID is missing!' );
  }
  if ( empty ( $apikey )) {
    $error .= sprintf( $err_format, 'No API key given!' );
  }
  if ( $units != 'metric' and $units != 'imperial' ) {
    $error .= sprintf( $err_format, 'Invalid units system! ('.
  		       '»metric« and »imperial« are allowed, but »'.
  		       $units.'« was given');
  }
  if ( $error ) {
    return( $error );
  }
  // }}}


  if ($thing !== null) {
    $display = parse($thing);
  }

  // {{{ return cached data, if there is some
  $now = time();
  $index = md5( $cityids.'_'.$lang.'_'.$units );
  $cached_data = safe_row("timestamp, data",
			  "tok_owm_current_requests_cache",
			  "request='$index'");

  if( $cached_data ){
    extract( $cached_data );
    if ( $now < $timestamp + $cache_secs ) {
      return( $data.$cache_mark );
    }
  };
  // }}}
  
  // {{{ else request data from OpenWeatherMap

  // request depends on number of requested cities
  $request_find = ( strpos( $cityids, ',') ) ? "group" : "weather";

  // send request
  $response_json = file_get_contents
    (sprintf
     ( "http://api.openweathermap.org/data/2.5/%s?id=%s&units=%s&appid=%s&lang=%s",
       $request_find,
       $cityids,
       $units,
       $apikey,
       $lang
       )
     );
  $owm_response = json_decode( $response_json, true );
  // }}}

  // {{{ assemble plugins output, save to database and return to txp
  $output = "";

  // single city
  if ( $request_find === "weather" ) {
    $weather = tok_owm_current_import_data( $owm_response, $na );
    $output = strtr( $display, $weather );
  }
  // group of cities
  else {
    foreach ( $owm_response[ "list" ] as $current_city ) {
      $weather = tok_owm_current_import_data( $current_city, $na );
      $output .= strtr( $display, $weather );
    }
  }

  // save output to database
  if( $cached_data ){
    safe_update( "tok_owm_current_requests_cache",
		 "timestamp='".$now."',".
		 "data='".mysql_real_escape_string( $output )."'",
		 "request='$index';");
  }
  else {
    safe_insert( "tok_owm_current_requests_cache",
		 "timestamp='".$now."',".
		 "data='".mysql_real_escape_string( $output )."',".
		 "request='".$index."';");
  }

  return ( $output );
  // }}}
}


/* write data */
/* safe_update("aks_cache", "ttl=$ttl2, data='$data2', infos='$id2|$diff|".strlen($data)."'", "hid='$hash'");  */
/* safe_insert("aks_cache", "hid='$hash', ttl=$ttl2, data='$data2', infos='$id2|$diff|".strlen($data)."'");  */




// }}}

  // {{{ function to import data
// convert response array to plugins variables
function tok_owm_current_import_data( $owm_data, $na ) {

  // basic data
  $variable_array = array("[[id]]" => $owm_data['id'],
			  "[[rx_timestamp_epoch]]" => $owm_data["dt"],
			  "[[cityname]]" => $owm_data["name"],
			  "[[lat]]" => $owm_data["coord"]["lat"],
			  "[[lon]]" => $owm_data["coord"]["lon"],
			  "[[country]]" => $owm_data["sys"]["country"],
			  "[[sunrise]]" => $owm_data["sys"]["sunrise"],
			  "[[sunset]]" => $owm_data["sys"]["sunset"],
			  "[[temp]]" => $owm_data["main"]["temp"],
			  "[[humidity]]" => $owm_data["main"]["humidity"],
			  "[[temp_min]]" => $owm_data["main"]["temp_min"],
			  "[[temp_max]]" => $owm_data["main"]["temp_max"],
			  "[[pressure]]" => $owm_data["main"]["pressure"],
			  "[[speed]]" => $owm_data["wind"]["speed"],
			  "[[deg]]" => $owm_data["wind"]["deg"],
			  "[[clouds]]" => $owm_data["clouds"]["all"],
			  "[[condition_id]]" => $owm_data["weather"][0]["id"],
			  "[[main]]" => $owm_data["weather"][0]["main"],
			  "[[description]]" => $owm_data["weather"][0]["description"],
			  "[[icon]]" => $owm_data["weather"][0]["icon"],
			  // calculated values
			  "[[temp_int]]" => round( $owm_data["main"]["temp"] ),
			  "[[icon_url]]" => 'http://openweathermap.org/img/w/'.$owm_data["weather"][0]["icon"].'.png',
			  "[[city_owm_url]]" => 'http://www.openweathermap.org/city/'.$owm_data['id']
			  );

  // volatile data
  $variable_array[ "[[rain3]]" ] = ( isset( $owm_data["rain"]["3h"] ))
    ? $owm_data["rain"]["3h"] : "0";
  $variable_array[ "[[rain1]]" ] = ( isset( $owm_data["rain"]["1h"] ))
    ? $owm_data["rain"]["1h"] : "0";
  $variable_array[ "[[snow3]]" ] = ( isset( $owm_data["snow"]["3h"] ))
    ? $owm_data["snow"]["3h"] : "0";
  $variable_array[ "[[snow1]]" ] = ( isset( $owm_data["snow"]["1h"] ))
    ? $owm_data["snow"]["1h"] : "0";
  $variable_array[ "[[gust]]" ] = ( isset( $owm_data["wind"]["gust"] ))
    ? $owm_data["wind"]["gust"] : $na;
  $variable_array[ "[[pressure_sea_level]]" ] = ( isset( $owm_data["main"]["sea_level"] ))
    ? $owm_data["main"]["sea_level"] : $na;
  $variable_array[ "pressure_grnd_level" ] = ( isset( $owm_data["main"]["grnd_level"] ))
    ? $owm_data["main"]["grnd_level"] : $na;

  return ( $variable_array);
}
// }}}

# --- END PLUGIN CODE ---
// }}}

?>

