<?php

$plugin['version'] = '0.1';
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

h1. <tok_next_weekly />

… calculates the upcoming occurence of a weekly recurring date. The output is widely configurable through strftime compatibility. 


h2. Attributes of the tag

- dow := Number of day of week in ISO 8601 format (from 1 = monday through 7 = sunday)
- week := In which weeks does the date repeat? Allowed values are:
  @every@ to a weekly repeating event (which is the default)
  @odd@ for events that repeat every odd week
  @even@ for events that repeat every even week
- skip_at := Set the time at which a todays date changes from today to the next occurence.
               Please provide the time in 24-hour format, immediately (without any separating character) followed by the two digits of the minute. So @09:34 PM@ becomes @2134@. Default is "1200" for events in the morning
- format := the date output in strftime format. Default is @%x@ – the "preferred date representation based on locale".
- todays_label := Append a special label to the output if the date is today. Defaults to an exclamation mark in parentheses
- alt_locale := As names in calendars are language dependent, a locale setting may be forced with this attributed (default: not set). Please note that this function depends massively on which locales are available on the server system


h2. Examples


h3. The most simple one

The upcoming wednesday

bc. <txp:tok_next_weekly dow="3" />


h3. A bit broader about todays occurence

You'll see this only, if you set the appropriate day of week. Or just wait …

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

  // some preparation
  $dows = array( '', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
  if ($alt_locale) { setlocale( LC_ALL, $alt_locale ); }

  // get today
  $today = date( "N:W:Gi" ) . "\n";
  list( $dow_today, $week_today_no, $time_now ) = explode(':', $today);
  $week_today = ( $week_today_no & 1 ? "odd" : "even" );

  // calculate the day we are looking for
  $looking_for = '';

  // let's see, if we're looking for today ...
  if (( $week_today == $week || $week == 'every' ) &&
      $dow_today == $dow &&
      0 + $time_now < 0 + $skip_at ) {
    return( strftime( $format, strtotime( 'today' )) . "$todays_label" ); }

  // ... or a forthcoming day in the current week ...
  elseif (( $week_today == $week || $week == 'every' ) &&
	  $dow_today < $dow ) {
    $looking_for = '+' . $dow - $dow_today . ' day'; }

  // ... if not, ...
  else {
    // ... it's in a future week: next one  ...
    if ( $week == 'every' || $week_today != $week ) {
      $looking_for = 'next ' . $dows[ $dow ]; }
    // ... or after next week
    else {
      $looking_for = 'second '.$dows[ $dow ]; }
  }

  if ( $looking_for ) {
    return( strftime( $format, strtotime( $looking_for )) ); }

  return( sprintf( $err_format, 'date unknown' ));
}

# --- END PLUGIN CODE ---

?>
