<?php

$plugin['version'] = '0.1';
$plugin['author'] = 'Torsten Krüger';
$plugin['author_uri'] = 'http://kryger.de/';
$plugin['description'] = 'Shows data from OpenWeatherMap widely configurabel';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0;

@include_once('zem_tpl.php');

if (0) {
?>
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

* units

OpenWeatherMap supports two metric systems: "metric" or "imperial" – choose one! Defaults to "metric"

* display

  Define the look of the plugin. Provide a simple html sequence here. Defaults to show the name of city, an icon showing current weather condition and the temperature. Special variables are used for the weather data. Please see the next section for details:
  

h3. Display variables

  You may use the following variables in the display attribute (the double curly brackets belong to the variable name, please see the _example_ section). The variable names follow the "OpenWeatherMap parameters":http://openweathermap.org/weather-data#current
  
* @{{cityname}}@

Name of city as provided by OpenWeatherMap

* @{{city_owm_url}}@

URL of the selected city´s page at OpenWeatherMap

* @{{lat}}@
* @{{lon}}@

Coordinates of the city as provided by OpenWeatherMap

* @{{temp}}@

Current temperature with (at this moment) two decimal places

* @{{temp_int}}@

Current temperature rounded to an integer

* @{{temp_min}}@

Minimum temperature at the moment

* @{{temp_max}}@

Maximum temperature at the moment

* @{{humidity}}@

Humidity

* @{{pressure}}@

Atmospheric pressure

* @{{speed}}@

Wind speed

* @{{deg}}@

Wind direction

* @{{clouds}}@

Cloudiness

* @{{condition}}@

Weather "condition code":http://openweathermap.org/weather-conditions as used by OpenWeatherMap

* @{{main}}@

Group of weather parameters

* @{{description}}@

Description of current weather condition 

* @{{icon_url}}@

URL of weather icon

* @{{rain1}}@

Precipitation volume for last 1 hour

* @{{rain3}}@

Precipitation volume for last 3 hours


h2. Examples

Please note, that you have to provide a valid APPID to get the examples to work.

h3. A short textual weather report

bc. <txp:tok_owm_current
  cityids = "2147714"
  apikey = "0123456789abcdef0123456789abcdef"
  display = "<a href='{{city_owm_url}}' target='_blank'>{{cityname}}</a>: {{temp_int}}°C, {{description}}" /></li>


h3. Nicer output, some eyecandy too

This example looks scaring, mostly because of the built-in CSS code. But in that way I am allowed to provide an example which is ready to use by copy and paste. In your textpattern environment the CSS code should be better integrated.

Some brandnew technology is used here (HTML5, CSS 3), so please use an up-to-date browser to test.

bc. <txp:tok_owm_current
  cityids = "1796236,3369157,3435910"
  apikey = "0123456789abcdef0123456789abcdef"
  display = "<div style='border:1px solid #ccc;border-radius:5px;
                          display:inline-block;font-size:0.8em;
                          margin-right:1em;padding:6px;text-align:center;'>
               <strong style='font-size:1.2em;'>{{cityname}}</strong><br />
               <img src='{{icon_url}}' alt='{{description}}' title='{{description}}' /><br />
               <span style='background-color: #777;border-radius:3px;
                            color:#fff;display:inline-block;
                            font-weight:bold;margin-bottom:1ex;
                            padding:3px;vertical-align: baseline;' 
                     title='Current temperature'>{{temp}}°C</span><br />
               <span title='Cloudiness'>☁  {{clouds}} %</span><br />
               <span title='Precipitation volume for last hour'>☔ {{rain1}} mm</span><br />
               <p title='Wind direction and speed'>
                 <span style='display:inline-block;margin-top:1ex;
                              transform:rotate({{deg}}deg);'>↓</span><br />
                 {{speed}} m/s</p>
               <p title='Humidity and pressure'>{{humidity}} %<br />
                 {{pressure}} hpa</p>
             </div>"
/>
 
				
  
h2. Development

If you have suggestions for additional features of this plugin, please "let me know":https://github.com/torsk/txp_plugins/issues.
					 


# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---

function tok_owm_current($atts) {

  // Get Attributes
  extract( lAtts( array(
			'apikey'  => '',
			'lang'  => 'en',
			'na'  => '?',
			'cityids'  => '',
			'units'   => 'metric',
			'display' => '<p><strong>{{cityname}}</strong><br /><img src="{{icon_url}}" '.
			'alt="{{description}}" /><br />{{temp_int}}°C</p>'
			), $atts ));

  $err_format = '<span style="color:#d12;" title="%s">█</span>';

  // check neccessary attributes
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

  // get data from OpenWeatherMap

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

  // finish single city
  if ( $request_find != "group" ) {
    $weather = tok_owm_current_import_data( $owm_response );
    return ( strtr( $display, $weather ) );
  }

  
  // if some more cities have been requested
  $weatherstring = "";
  foreach ( $owm_response[ "list" ] as $current_city ) {
    $weather = tok_owm_current_import_data( $current_city );
    $weatherstring .= strtr( $display, $weather );
  }
  return ( $weatherstring );
}

// convert response array to plugins variables
function tok_owm_current_import_data( $owm_data ) {

  // basic data
  $variable_array = array("{{id}}" => $owm_data['id'],
			  "{{rx_timestamp_epoch}}" => $owm_data["dt"],
			  "{{cityname}}" => $owm_data["name"],
			  "{{lat}}" => $owm_data["coord"]["lat"],
			  "{{lon}}" => $owm_data["coord"]["lon"],
			  "{{country}}" => $owm_data["sys"]["country"],
			  "{{sunrise}}" => $owm_data["sys"]["sunrise"],
			  "{{sunset}}" => $owm_data["sys"]["sunset"],
			  "{{temp}}" => $owm_data["main"]["temp"],
			  "{{humidity}}" => $owm_data["main"]["humidity"],
			  "{{temp_min}}" => $owm_data["main"]["temp_min"],
			  "{{temp_max}}" => $owm_data["main"]["temp_max"],
			  "{{pressure}}" => $owm_data["main"]["pressure"],
			  "{{speed}}" => $owm_data["wind"]["speed"],
			  "{{deg}}" => $owm_data["wind"]["deg"],
			  "{{clouds}}" => $owm_data["clouds"]["all"],
			  "{{condition_id}}" => $owm_data["weather"][0]["id"],
			  "{{main}}" => $owm_data["weather"][0]["main"],
			  "{{description}}" => $owm_data["weather"][0]["description"],
			  "{{icon}}" => $owm_data["weather"][0]["icon"],
			  // calculated values
			  "{{temp_int}}" => round( $owm_data["main"]["temp"] ),
			  "{{icon_url}}" => 'http://openweathermap.org/img/w/'.$owm_data["weather"][0]["icon"].'.png',
			  "{{city_owm_url}}" => 'http://www.openweathermap.org/city/'.$owm_data['id']
			  );

  // volatile data
  $variable_array[ "{{rain3}}" ] = ( isset( $owm_data["rain"]["3h"] ))
    ? $owm_data["rain"]["3h"] : "0";
  $variable_array[ "{{rain1}}" ] = ( isset( $owm_data["rain"]["1h"] ))
    ? $owm_data["rain"]["1h"] : "0";
  $variable_array[ "{{snow3}}" ] = ( isset( $owm_data["snow"]["3h"] ))
    ? $owm_data["snow"]["3h"] : "0";
  $variable_array[ "{{snow1}}" ] = ( isset( $owm_data["snow"]["1h"] ))
    ? $owm_data["snow"]["1h"] : "0";
  $variable_array[ "{{gust}}" ] = ( isset( $owm_data["wind"]["gust"] ))
    ? $owm_data["wind"]["gust"] : $na;
  $variable_array[ "{{pressure_sea_level}}" ] = ( isset( $owm_data["main"]["sea_level"] ))
    ? $owm_data["main"]["sea_level"] : $na;
  $variable_array[ "pressure_grnd_level" ] = ( isset( $owm_data["main"]["grnd_level"] ))
    ? $owm_data["main"]["grnd_level"] : $na;

  return ( $variable_array);
}




# --- END PLUGIN CODE ---

?>
