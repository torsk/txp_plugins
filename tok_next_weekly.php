<?php

$plugin['version'] = '0.3';
$plugin['author'] = 'Torsten Krüger';
$plugin['author_uri'] = 'http://kryger.de/';
$plugin['description'] = 'Shows the next date of a weekly recurring event.';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0;

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h1. tok_next_weekly

… calculates the upcoming occurence of a weekly recurring date. The output is widely configurable through strftime compatibility. 


h2. Attributes of the tag

- dow := Number of day of week in ISO 8601 format (from 1 = monday through 7 = sunday)
- week := In which weeks does the date repeat? Allowed values are:
  @every@ to a weekly repeating event (which is the default)
  @odd@ for events that repeat every odd week
  @even@ for events that repeat every even week
- skip_at := Set the time at which a todays date changes from today to the next occurence.
Please provide the time in 24-hour format, immediately (without any separating character) followed by the two digits of the minute. So @09:34 PM@ becomes @2134@. Defaults to "1200" for events in the morning.=:
- format := the date output in strftime format. Default is @%x@ – the "preferred date representation based on locale".
- todays_label := Append a special label to the output if the date is today. Defaults to an exclamation mark in parentheses
- alt_locale := As names in calendars are language dependent, a locale setting may be forced with this attributed (default: not set). Please note that this function depends massively on which locales are available on the server system


h2. Examples


h3. The most simple one

The upcoming wednesday

bc. <txp:tok_next_weekly dow="3" />


h3. A bit broader about todays occurence

You will see this only, if you set the appropriate day of week. Or just wait …

bc. <txp:tok_next_weekly dow="1" todays_label=" *Which is today. So hurry up!*" />


h3. Nicer date format

A typical german date mark with full day and month names, leading zeros stripped from day number 

bc. <txp:tok_next_weekly dow="6" format="%A, %-d. %B" />


h3. With manipulated locale

This makes sense only when running under an exotic locale

bc. <txp:tok_next_weekly format="%x, %A" dow="4" alt_locale="C" />

						  
# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---

function tok_next_weekly( $atts ) {

  // Get Attributes
  extract( lAtts( array(
			'dow'          => '',
			'week'         => 'every',
			'skip_at'      => '1200',
			'format'       => '%x',
			'alt_locale'   => '',
			'todays_label' => '(!)'
			), $atts ));

  $err_format = '<span style="color:#d12;" title="%s">█</span>';

  // check mandatory attribute
  if ( empty ( $dow )) {
    return( sprintf( $err_format, 'No day of week given' )); }

  $ret_val = sprintf( $err_format, 'date unknown' );

  if ($alt_locale) {
    $regular_locale = setlocale( LC_TIME, 0 );
    setlocale( LC_TIME, $alt_locale ); }

  // get day and time of now
  $today = safe_strftime( "%u:%V:%H%M" );
  list( $dow_today, $week_today_no, $time_now ) = explode(':', $today);
  $week_today = ( $week_today_no & 1 ? "odd" : "even" );

  // calculate the day we are looking for
  $date_offset = 0;

  // let's see, if we're looking for today ...
  if (( $week_today == $week || $week == 'every' ) &&
      $dow_today == $dow &&
      0 + $time_now < 0 + $skip_at ) {
    $ret_val = ( strftime( $format, strtotime( 'today' )) . "$todays_label" ); }

  else { // so, it's not today, ...

    // ... but maybe a forthcoming day in the current week ...
    if (( $week_today == $week || $week == 'every' ) &&
	$dow_today < $dow ) {
      $date_offset = $dow - $dow_today; }

    // ... if not, it's in a future week:
    else {
      // either next one  ...
      if ( $week_today != $week || $week == 'every' ) {
	$date_offset = 7 + $dow - $dow_today; }
      // ... or after next week
      else {
	$date_offset = 14 + $dow - $dow_today; }
    }

    // put value into variable
    $ret_val = ( strftime( $format, strtotime( '+' . $date_offset . ' day' )) );
  }

  // reset locale
  if ($alt_locale) {
    setlocale( LC_TIME, $regular_locale );
  }

  // finish
  return( $ret_val );
}

# --- END PLUGIN CODE ---

?>
