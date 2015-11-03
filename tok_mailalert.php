<?php

// {{{ plugin setup

$plugin['allow_html_help'] = 0;
$plugin['version'] = '0.1';
$plugin['author'] = 'Torsten Krueger';
$plugin['author_uri'] = 'http://www.kryger.de/';
$plugin['description'] = 'Automatically send a mail on new posting';
$plugin['order'] = 5;
$plugin['type'] = 4;
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001);
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);
$plugin['flags'] = PLUGIN_HAS_PREFS | PLUGIN_LIFECYCLE_NOTIFY;

// }}}

// {{{ language setup

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
tok_mailalert_boxtitle_plugin_error => »tok_mailalert« plugin error
tok_mailalert_boxtxt_not_yet_setup => The plugin »tok_mailalert« is not yet setup
tok_mailalert_conf_val_mailsubject => A new posting was published
tok_mailalert_conf_val_mailbody => Hi!\r\rPlease visit <txp_siteurl> as soon as possible\r\rSincerely, <txp_user>
#@language de-de
tok_mailalert_boxtitle_plugin_error => »tok_mailalert« Plugin Fehler
tok_mailalert_boxtxt_not_yet_setup => Das Plugin »tok_mailalert« ist noch nicht konfiguriert
tok_mailalert_conf_val_mailsubject => Ein neues Posting wurde veröffentlicht
tok_mailalert_conf_val_mailbody => Hallo!\r\rBitte besuche alsbald\r\r<txp_siteurl>\r\rViele Grüße\r<txp_user>
EOT;

// }}}

// Torsten Krueger <torsten@kryger.de>,maren.krueger@gmx.de,mathias@aett-brom.de,Christian.WEISE@ec.europa.eu,bernd-doerrie@arcor.de,kruegers@gmx.li,Heidrun.Achtzehn@web.de,lovis@kryger.de,luzie@kryger.de,barbara@kryger.de

if (!defined('txpinterface'))
  @include_once('zem_tpl.php');

if (0) {
  ?>
# --- BEGIN PLUGIN HELP ---

h1. tok_mailalert

p. This plugin adds a checkbox to the Write tab. If the checkmark is set,
  an email will be sent to a preconfigured list of email addresses
  when the article is saved the next time.

p. The result of the mail sending attempt will be show in the same place.
  
p. Recipients list and mail body as well as the appearance of the checkbox
  are configurable on the extensions tab.

p. Version: 0.1

h2. Table of contents

# "Plugin requirements":#help-section02
# "Installation":#help-section03
# "Configuration":#help-section04
# "Uninstallation":#help-section05


h2(#help-section02). Plugin requirements

p. This plugin has only been tested with Textpattern 4.5 but may run
    on other/previous versions.

h2(#help-section03). Installation

p. Right after installation the plugin will not work;
   the list of email recipients must first be configured on the extensions tab.

p. Other settings have default values and may be changed according to your needs.

p. These values are stored in textpatterns prefs table.

h2(#help-section04). Configuration

p. Things on the extension tab should not be to hard to understand.
     But you may use variables in the mail body. They are:

|_. variable    |_. result                |
| <txp_siteurl> | Site URL                |
| <txp_user>    | name of logged in user  |
     
h2(#help-section05). Uninstallation

p. The plugins deinstallation routine will remove all previously inserted
     records of the table "txp_prefs".


# --- END PLUGIN HELP ---
     <?php
     }

# --- BEGIN PLUGIN CODE ---

/*
 * Setup
 * -----------------------------------------------------------------------------
 */
if (@txpinterface == 'admin') {

  // Variables used
  global $tok_mailalert_recipientlist,
    $tok_mailalert_subject, $tok_mailalert_mailfrom, $tok_mailalert_mailbody,
    $tok_mailalert_element_txt_title,$tok_mailalert_element_txt_label,
    $tok_mailalert_element_txt_fail, $tok_mailalert_element_txt_ok,
    $tok_mailalert_element_txt_hint;

  // plugin registration
  add_privs( 'tok_mailalert', '1' ); // Publishers only
  register_callback( 'tok_mailalert', 'article_ui', 'status' );

  // plugins configuration
  add_privs( 'tok_mailalert_configuration', '1' );
  add_privs( 'plugin_prefs.tok_mailalert', '1,2' );
  register_tab( 'extensions', 'tok_mailalert_configuration', 'tok_mailalert' );
  register_callback( 'tok_mailalert_configuration', 'tok_mailalert_configuration' );
  register_callback( 'tok_mailalert_configuration', 'plugin_prefs.tok_mailalert' );

  // Lifecycle stuff
  register_callback('tok_mailalert_installation_routine',
		    'plugin_lifecycle.tok_mailalert', 'installed');
  register_callback('tok_mailalert_deletion_routine',
		    'plugin_lifecycle.tok_mailalert', 'deleted');
}

/*
 * helper functions
 * -----------------------------------------------------------------------------
 */

// allow variables in mail body
function tok_mail_replace_vars($text, $rs) {
  // setup vars
  $userId = $rs['AuthorID'];
  $articleId = $rs['ID'];
  $userName = safe_field('RealName', 'txp_users', "name = '".doSlash($userId)."'");
  $article = safe_row ("ID, Status, Title, Body_html, AuthorID", "textpattern", "ID=$articleId");
  $postingTitle = $rs['Title'];
  $postingExcerpt = $rs['Excerpt'];
  $postingSection = $rs['Section'];
  /* $postingBody = $article['Body']; */
  include_once txpath.'/lib/txplib_publish.php';
  $postingBody = parse($article['Body_html']);
  
  /* $postingBody = $GLOBALS['thisarticle']['body']; */
  /* $postingBody = safe_field('user_html','txp_page',"name='".doSlash($articleId)."'"); */
  /* $postingBody = $textile->TextileThis( $article['Body_html']); */
  /* $postingBody = TXP_Wrapper::format_field($postingBody,$postingBody,$textile); */
 
  
  // replace
  $text = str_replace( '<%userId%>', $userId, $text );
  $text = str_replace( '<%userName%>', $userName, $text );
  $text = str_replace( '<%siteurl%>', $GLOBALS['prefs']['siteurl'] , $text );
  $text = str_replace( '<%postingTitle%>', $postingTitle, $text );
  $text = str_replace( '<%postingExcerpt%>', $postingExcerpt, $text );
  $text = str_replace( '<%postingBody%>', $postingBody, $text );
  $text = str_replace( '<%postingSection%>', $postingSection, $text );
  return( $text ); }


/*
 * main function
 * -----------------------------------------------------------------------------
 */

function tok_mailalert( $event, $step, $data, $rs ) {

  global $tok_mailalert_recipientlist,
    $tok_mailalert_subject, $tok_mailalert_mailfrom, $tok_mailalert_mailbody,
    $tok_mailalert_element_txt_title,$tok_mailalert_element_txt_label,
    $tok_mailalert_element_txt_fail, $tok_mailalert_element_txt_ok,
    $tok_mailalert_element_txt_hint,
    $app_mode;
    
  // in async mode remove tok_mailalert box from page
  if ($app_mode == 'async') {
    send_script_response('$("#tok_mailalert_box").remove();'); }

  $arraydata = print_r( $rs , true );

  
  // check if plugin was setup already
  if ((! isset( $tok_mailalert_recipientlist )) or
      (! isset( $tok_mailalert_subject )) or
      (! isset( $tok_mailalert_mailfrom )) or
      (! isset( $tok_mailalert_mailbody )) or
      (! isset( $tok_mailalert_element_txt_label )) or
      (! isset( $tok_mailalert_element_txt_fail )) or
      (! isset( $tok_mailalert_element_txt_ok )) or
      (! $tok_mailalert_recipientlist )) {
    return( $data.n.'<fieldset id="tok_mailalert_box">'.
	    n.'<legend>'.gTxt( "tok_mailalert_boxtitle_plugin_error" ).'</legend>'.
	    n.'<p class="alert-block error"><strong class="error">'.
	    gTxt( "tok_mailalert_boxtxt_not_yet_setup" ).'</strong></p>'.
	    n.'</fieldset>' ); }
  
  /*
   * if checkbox is checked, send mail
   */
  if ( isset( $_POST["tok_mailalert_send_action"] )) {
    // get configured list of recipients
    $recipients = explode( ",", $tok_mailalert_recipientlist );
    // setup parts of mail
    $mail_subject = tok_mail_replace_vars( $tok_mailalert_subject, $rs );
    $mail_body = tok_mail_replace_vars( $tok_mailalert_mailbody, $rs );
    // if sending mail was requested
    $error_num = 0;
    foreach( $recipients as $recipient ) {
      $success = mail( $recipient,
  		       $mail_subject,
  		       chunk_split(base64_encode($mail_body)),
  		       "From: " . $tok_mailalert_mailfrom . "\r\n" .
  		       "Content-Type: text/plain; charset=utf-8" . "\r\n" .
  		       "Content-Transfer-Encoding: base64" . "\r\n" );
      if (! $success ) { $error_num += 1; }
    } // recipient list loop ends
    if ( $error_num > 0 ) { // inform about mail errors …
      return( $data.n.'<fieldset id="tok_mailalert_box">'.
	      n.'<legend>'.gTxt( $tok_mailalert_element_txt_title ).'</legend>'.
	      n.'<p class="alert-block error"><strong>'.
	      sprintf( $tok_mailalert_element_txt_fail,  $error_num ).
	      '</strong></p>'.n.'</fieldset>');
    }
    else {
      return( $data.n.'<fieldset id="tok_mailalert_box">'.
	      n.'<legend>'.gTxt( $tok_mailalert_element_txt_title ).'</legend>'.
	      n.'<p class="alert-block success" style="margin:0;">'.$tok_mailalert_element_txt_ok.
	      '</p>'.n.'</fieldset>');
    }
  }
  return( $data.n.'<fieldset id="tok_mailalert_box">'.
	  n.'<legend>'.gTxt( $tok_mailalert_element_txt_title ).'</legend>'.
	  n.graf(checkbox('tok_mailalert_send_action', '1',
			  $tok_mailalert_element_txt_label, '',
			  'tok_mailalert_send_check').
		 '<label for="tok_mailalert_send_check">'.gTxt($tok_mailalert_element_txt_label).
		 '</label>', ' title="' . $tok_mailalert_element_txt_hint . '"').
	  n.'</fieldset>');
}


/*
 * Configuration
 * -----------------------------------------------------------------------------
 */
function tok_mailalert_configuration($event, $step) {

  global $tok_mailalert_recipientlist,
    $tok_mailalert_subject, $tok_mailalert_mailfrom, $tok_mailalert_mailbody,
    $tok_mailalert_element_txt_title,$tok_mailalert_element_txt_label,
    $tok_mailalert_element_txt_fail, $tok_mailalert_element_txt_ok,
    $tok_mailalert_element_txt_hint;

  include( txpath . '/include/txp_prefs.php' );
  
  if ( ps( "save" )) {
    prefs_save();
    header( "Location: index.php?event=tok_mailalert_configuration" ); }
  
  pagetop( "Mail Alert Preferences" );

  echo '<h1>Mail Alert Preferences</h1>';
  
  echo form( startTable( '', '', 'txp-list' ).
	     tr( tdcs( hed( 'List of email recipients', 3), 2 ), ' class="pref-heading"').
	     tr( tda( gTxt( "Comma separated list<br />of e mail addresses" )).
		 td( '<textarea id="tok_mailalert_recipientlist" name="tok_mailalert_recipientlist" ' .
		     'rows="12" cols="60">'.htmlspecialchars($tok_mailalert_recipientlist).'</textarea>')).
	     tr( tdcs( hed( 'Mail Appearance', 3 ), 2 ), ' class="pref-heading"').
	     tr( tda( gTxt( "mail from header" )).
		 td( text_input( "tok_mailalert_mailfrom", $tok_mailalert_mailfrom, 60 ))).
	     tr( tda( gTxt( "mail subject" )).
		 td( text_input( "tok_mailalert_subject", $tok_mailalert_subject, 60 ))).
	     tr( tda( gTxt( "mail body<br />" .
			    "(You may use these variables:<br />" .
			    "&lt;txp_siteurl&gt: Site URL<br />" .
			    '&lt;txp_user&gt: name of logged in user)')).
		 td( '<textarea id="tok_mailalert_mailbody" name="tok_mailalert_mailbody" ' .
		     'rows="12" cols="60">'.htmlspecialchars($tok_mailalert_mailbody).'</textarea>')).
	     tr( tdcs( hed( 'Element Texts', 3 ), 2 ), ' class="pref-heading"').
	     tr( tda( gTxt( "element title" )).
		 td( text_input( "tok_mailalert_element_txt_title", $tok_mailalert_element_txt_title, 60 ))).
	     tr( tda( gTxt( "label text" )).
		 td( text_input( "tok_mailalert_element_txt_label", $tok_mailalert_element_txt_label, 60 ))).
	     tr( tda( gTxt( "label hint" )).
		 td( text_input( "tok_mailalert_element_txt_hint", $tok_mailalert_element_txt_hint, 60 ))).
	     tr( tda( gTxt( "message mailing ok" )).
		 td( text_input( "tok_mailalert_element_txt_ok", $tok_mailalert_element_txt_ok, 60 ))).
	     tr( tda( gTxt( "message mailing failed" )).
		 td( text_input( "tok_mailalert_element_txt_fail", $tok_mailalert_element_txt_fail, 60 ))).
	     tr( tda( fInput( "submit", "save", gTxt( "save_button" ), "publish" ).
		      eInput( "tok_mailalert_configuration" ).
		      sInput( 'saveprefs' ), " colspan=\"2\" class=\"noline\"" )).
	     endTable()); }




/*
 * Plugin Installation
 * -----------------------------------------------------------------------------
 */

function tok_mailalert_installation_routine() {

  global $tok_mailalert_recipientlist,
    $tok_mailalert_subject, $tok_mailalert_mailfrom, $tok_mailalert_mailbody,
    $tok_mailalert_element_txt_title,$tok_mailalert_element_txt_label,
    $tok_mailalert_element_txt_fail, $tok_mailalert_element_txt_ok,
    $tok_mailalert_element_txt_hint;

  // mail variables
  if (! isset( $tok_mailalert_recipientlist )) {
    $tok_mailalert_recipientlist = "";
    safe_insert('txp_prefs',
		"name='tok_mailalert_recipientlist', val='$tok_mailalert_recipientlist', prefs_id='1'"); }
  
  if (! isset( $tok_mailalert_mailfrom )) {
    $tok_mailalert_mailfrom = "";
    safe_insert('txp_prefs',
		"name='tok_mailalert_mailfrom', val='$tok_mailalert_mailfrom', prefs_id='1'"); }

  if (! isset( $tok_mailalert_subject )) {
    $tok_mailalert_subject = gTxt( "tok_mailalert_conf_val_mailsubject" );
    safe_insert('txp_prefs',
		"name='tok_mailalert_subject', val='$tok_mailalert_subject', prefs_id='1'"); }

  if (! isset( $tok_mailalert_mailbody )) {
    $tok_mailalert_mailbody = gTxt( "tok_mailalert_conf_val_mailbody" );
    safe_insert('txp_prefs',
		"name='tok_mailalert_mailbody', val='$tok_mailalert_mailbody', prefs_id='1'"); }

  // element appearance
  if (! isset( $tok_mailalert_element_txt_title )) {
    $tok_mailalert_element_txt_title = "Mail Alert";
    safe_insert('txp_prefs',
		"name='tok_mailalert_element_txt_title', val='$tok_mailalert_element_txt_title', prefs_id='1'"); }

  if (! isset( $tok_mailalert_element_txt_label )) {
    $tok_mailalert_element_txt_label = "Send Infomail";
    safe_insert('txp_prefs',
		"name='tok_mailalert_element_txt_label', val='$tok_mailalert_element_txt_label', prefs_id='1'"); }

  if (! isset( $tok_mailalert_element_txt_hint )) {
    $tok_mailalert_element_txt_hint = "To have friends of the site informed about a new posts via mail check here";
    safe_insert('txp_prefs',
		"name='tok_mailalert_element_txt_hint', val='$tok_mailalert_element_txt_hint', prefs_id='1'"); }

  if (! isset( $tok_mailalert_element_txt_ok )) {
    $tok_mailalert_element_txt_ok = "Mails have successfully been sent";
    safe_insert('txp_prefs',
		"name='tok_mailalert_element_txt_ok', val='$tok_mailalert_element_txt_ok', prefs_id='1'"); }

  if (! isset( $tok_mailalert_element_txt_fail )) {
    $tok_mailalert_element_txt_fail = "Sending of %s mails failed";
    safe_insert('txp_prefs',
		"name='tok_mailalert_element_txt_fail', val='$tok_mailalert_element_txt_fail', prefs_id='1'"); }
}

/*
 * Plugin Deletion
 * -----------------------------------------------------------------------------
 */

function tok_mailalert_deletion_routine() {
  safe_delete("txp_prefs","name='tok_mailalert_recipientlist'");
  safe_delete("txp_prefs","name='tok_mailalert_subject'");
  safe_delete("txp_prefs","name='tok_mailalert_mailfrom'");
  safe_delete("txp_prefs","name='tok_mailalert_mailbody'");
  safe_delete("txp_prefs","name='tok_mailalert_element_txt_title'");
  safe_delete("txp_prefs","name='tok_mailalert_element_txt_label'");
  safe_delete("txp_prefs","name='tok_mailalert_element_txt_label'");
  safe_delete("txp_prefs","name='tok_mailalert_element_txt_fail'");
  safe_delete("txp_prefs","name='tok_mailalert_element_txt_ok'");
  safe_delete("txp_prefs","name='tok_mailalert_element_txt_hint'");
}


# --- END PLUGIN CODE ---

?>


/// Local variables:
/// folded-file: t
/// End:
