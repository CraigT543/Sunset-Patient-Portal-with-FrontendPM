<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
// Copyright 2014-2015 Rod Roark
//
// For using the WordPress API from an external program such as this, see:
// http://www.webopius.com/content/139/using-the-wordpress-api-from-pages-outside-of-wordpress
// ... including the reader comments.
//
// 5/2/2018 This is a modification of Rod's work by Craig Tucker for use the CFDB and 
// Frontend PM Pro plugins in WordPress. All references to NinjaForms have been deleted.
// NinjaForms, Gravity Forms, and CF7 can all be accessed through the CFDB plugin.
// 

define('WP_USE_THEMES', false);
require('../../../wp-load.php');

// For use of the $wpdb object to access the WordPress database, see:
// http://codex.wordpress.org/Class_Reference/wpdb

function __construct($plugin) {
	$this->plugin = $plugin;
}

$out = array('errmsg' => '');
$action = $_REQUEST['action'];

// These are the administrative settings for the Frontend PM plugin.
// We need to know who its messaging administrator is. This is likely to
// be the same as $_REQUEST['login'] but we cannot assume that.  This
// routine checks to be sure that $_REQUEST['login'] is an authorized
// admin in Frontend PM 

$adminOps = fep_get_option('oa_admins', array());
$wpuser = get_user_by( 'email', $_REQUEST['login'] );
$userlogin = $wpuser->user_login;
$count = count($adminOps)-1;

$i = 0;
$admins = array();
while($i <= $count) {
	$admins[] = $adminOps['oa_' . $i ]['username'];
	$i++;
}

if (in_array($userlogin, $admins)){
	$admin_user_login = $userlogin;
} else {
	  $out['errmsg'] = $userlogin ." is not an authorized administrator in Frontend PM Pro. Please go to Frontend PM Pro > Settings > Recipiant and add the user name to the Admins field.";
}

// While the password is sent to us as plain text, this transport interface
// should always be encrypted via SSL (HTTPS). See also:
// http://codex.wordpress.org/Function_Reference/wp_authenticate
// http://codex.wordpress.org/Class_Reference/WP_User
$user = wp_authenticate($_REQUEST['login'], $_REQUEST['password']);

if (is_wp_error($user)) {
  $out['errmsg'] = "Portal authentication failed.";
}
// Portal administrator must have one of these capabilities.
// Note manage_portal is a custom capability added via User Role Editor.
else if (!$user->has_cap('create_users') && !$user->has_cap('manage_portal')) {
  $out['errmsg'] = "This login does not have permission to administer the portal.";
}
else {
  if ('list'        == $action) action_list       ($_REQUEST['date_from'], $_REQUEST['date_to']); else
  if ('getpost'     == $action) action_getpost    ($_REQUEST['postid']                         ); else
  if ('getupload'   == $action) action_getupload  ($_REQUEST['uploadid']                       ); else
  if ('delpost'     == $action) action_delpost    ($_REQUEST['postid']                         ); else
  if ('checkptform' == $action) action_checkptform($_REQUEST['patient'], $_REQUEST['form']     ); else
  if ('getmessage'  == $action) action_getmessage ($_REQUEST['messageid']                      ); else
  if ('getmsgup'    == $action) action_getmsgup   ($_REQUEST['uploadid']                       ); else
  if ('delmessage'  == $action) action_delmessage ($_REQUEST['messageid']                      ); else
  if ('adduser'     == $action) action_adduser($_REQUEST['newlogin'], $_REQUEST['newpass'], $_REQUEST['newemail']); else
  if ('putmessage'  == $action) action_putmessage ($_REQUEST                                   ); else
  // More TBD.
  $out['errmsg'] = 'Action not recognized!';
}

// For JSON-over-HTTP we would echo json_encode($out) instead of the following.
// However serialize() works better because it supports arbitrary binary data,
// thus attachments do not have to be base64-encoded.

$tmp = serialize($out);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=cmsreply.bin');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . strlen($tmp));
ob_clean();
flush();
echo $tmp;

function get_mime_type($filename) {
	$idx = explode( '.', $filename );
	$count_explode = count($idx);
	$idx = strtolower($idx[$count_explode-1]);
 
	$mimet = array(	
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	);
 
	if (isset( $mimet[$idx] )) {
	 return $mimet[$idx];
	} else {
	 return 'application/octet-stream';
	}
 }



function convertToID($login) {
  global $wpdb;
  $result = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_login = %s", $login));
  if (!empty($result)) return $result;
  return 0;
}

// Logic to process the "list" action.
// For CF7, a row for every form submission.
//
function action_list($date_from='', $date_to='') {
  global $wpdb, $out, $admin_user_login;
  $out['list'] = array();
  $out['messages'] = array();
  
  date_default_timezone_set('UTC');
  $from_date=strtotime($date_from." 00:00:00");
  $to_date=strtotime($date_to." 23:59:59");

	$query =
	"SELECT submit_time, field_value, form_name " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits " .
	"WHERE field_order = 9999";

    $qparms = array();
    if ($date_from) {
      $query .= " AND submit_time >= %d";
      $qparms[] = "$from_date";
    }
    if ($date_to) {
      $query .= " AND submit_time <= %d";
      $qparms[] = "$to_date";
    }
    $query .= " ORDER BY submit_time";

    $query = $wpdb->prepare($query, $qparms);
    if (empty($query)) {
      $out['errmsg'] = "Internal error: wpdb prepare() failed.";
      return;
    }
	
    $rows = $wpdb->get_results($query, ARRAY_A);
    foreach ($rows as $row) {
      $out['list'][] = array(
        'postid'   => $row['submit_time'],
        'user'     => (isset($row['field_value']) ? $row['field_value'] : ''),
        'datetime' => gmdate("Y-m-d H:i:s",$row['submit_time']),
        'type'     => $row['form_name'],
      );
    }
  
  // Get list of messages also.
  // FrontendPM version
  //Current user ID 
  $curID = convertToID($admin_user_login);
  $query = "SELECT p.id, p.post_date, p.post_title, " .
	"uf.user_login AS from_login, ut.user_login AS to_login " .
	"FROM {$wpdb->prefix}posts AS p " .
		"JOIN $wpdb->postmeta pm_to " .
		"ON ((p.post_parent = pm_to.post_id AND p.post_parent <> 0) " .
		"OR (p.id = pm_to.post_id AND p.post_parent = 0)) " .
		"AND pm_to.meta_key = '_fep_participants' " .
		"AND pm_to.meta_value <> p.post_author " .
		"LEFT JOIN $wpdb->postmeta pm_delete " .
		"ON ((pm_delete.post_id = p.post_parent AND pm_delete.meta_key = '_fep_delete_by_%d' ) " .
		"OR (pm_delete.post_id = p.id AND pm_delete.meta_key = '_fep_delete_by_%d')) " .
		"LEFT JOIN $wpdb->postmeta pm_clear " .
		"ON ((pm_clear.post_id = p.post_parent AND pm_clear.meta_key = CONCAT('_oemr_fep_clear_', p.id)) " .
		"OR (pm_clear.post_id = p.id AND pm_clear.meta_key = CONCAT('_oemr_fep_clear_', p.id))) " .
	"LEFT JOIN $wpdb->users AS uf ON uf.ID = p.post_author " .
	"LEFT JOIN $wpdb->users AS ut ON ut.ID = pm_to.meta_value " .
	"WHERE (p.post_type = 'fep_message' AND uf.user_login = %s " .
	"OR p.post_type = 'fep_message' AND ut.user_login = %s) AND pm_delete.post_id IS NULL AND pm_clear.post_id IS NULL";
  $qparms = array($curID, $curID, $admin_user_login, $admin_user_login);
  if ($date_from) {
	$query .= " AND p.post_date >= %s";
	$qparms[] = "$date_from 00:00:00";
  }
  if ($date_to) {
	$query .= " AND p.post_date <= %s";
	$qparms[] = "$date_to 23:59:59";
  }
  $query .= " ORDER BY p.post_date";
  $query = $wpdb->prepare($query, $qparms);
  if (empty($query)) {
	$out['errmsg'] = "Internal error: wpdb prepare() failed.";
	return;
  }
  $rows = $wpdb->get_results($query, ARRAY_A);
  foreach ($rows as $row) {
	$out['messages'][] = array(
	  'messageid' => $row['id'],
	  'user'      => ($row['from_login'] == $admin_user_login ? $row['to_login'] : $row['from_login']),
	  'fromuser'  => $row['from_login'],
	  'touser'    => $row['to_login'],
	  'datetime'  => $row['post_date'],
	  'title'     => $row['post_title'],
	);
  }
}

// Logic to process the "getpost" action.
// The $postid argument identifies the form instance.
// For CF7 the submitted field values and names must be extracted from
// serialized globs, and each field name comes from its description text.
//
function action_getpost($postid) {
  global $wpdb, $out;
  $out['post'] = array();
  $out['uploads'] = array();

       // cf7dbplugin_submits includes a set of rows for each defined form.
    $query =
	"SELECT submit_time, form_name, field_value " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits " .
	"WHERE field_order = 9999 AND submit_time = %f";

    $queryp = $wpdb->prepare($query, $postid);
    if (empty($queryp)) {
      $out['errmsg'] = "Internal error: \"$query\" \"$postid\"";
      return;
    }

    $row = $wpdb->get_row($queryp, ARRAY_A);
    if (empty($row)) {
      $out['errmsg'] = "No rows matching: \"$postid\"";
	  echo $queryp;
      return;
    }
    // $formid = $row['submit_time'];
    $out['post'] = array(
      'postid'   => $row['submit_time'],
      'user'     => (isset($row['field_value']) ? $row['field_value'] : ''),
      'datetime' => gmdate("Y-m-d H:i:s",$row['submit_time']),
      'type'     => $row['form_name'],
    );
    $out['fields'] = array();
    $out['labels'] = array();
    // wp_cf7dbplugin_submits has one row for each defined form field.
    $query2 =	  
		"SELECT ID, submit_time, field_name, field_value, file " .
		"FROM {$wpdb->prefix}cf7dbplugin_submits " .  
		"WHERE field_order < 9999 AND submit_time= %f " . 
		"ORDER BY field_order";
    $query2p = $wpdb->prepare($query2, $postid);
    $rows = $wpdb->get_results($query2p, ARRAY_A);
	
   foreach ($rows as $fldrow) {
     // Report uploads, if any.
      if (!empty($fldrow['file'])) { 
		// Put the info into the uploads array.
		
            $out['uploads'][] = array(
              'filename' =>  $fldrow['field_value'],
              'mimetype' => get_mime_type($fldrow['field_value']),
              'id'       => $fldrow['ID'],
            );
      }
	  
      // Each field that matches with a field name in OpenEMR must have that name in
      // its description text. 
      if (is_string($fldrow['field_value'])) {
      $out['fields'][$fldrow['field_name']] = $fldrow['field_value'];
      }
      $out['labels'][$fldrow['field_name']] = $fldrow['field_name'];
    }
}

// Logic to process the "delpost" action to delete a post.
//
function action_delpost($postid) {
  global $wpdb, $out;
    // If this form instance includes any file uploads, then delete the
    // uploaded files as well as the rows in CF7.
    action_getpost($postid);
    if ($out['errmsg']) return;

    $result = $wpdb->delete("{$wpdb->prefix}cf7dbplugin_submits",
        array('submit_time' => $postid), array('%f'));
		$wpdb->delete("{$wpdb->prefix}cf7dbplugin_st",
        array('submit_time' => $postid), array('%f'));		
	if ($result) {
		$out = array('errmsg' => '');
	} else {
		$out['errmsg'] = "Delete failed for post '$postid'";
	}
}

// Logic to process the "adduser" action to create a user as a patient.
//
function action_adduser($login, $pass, $email) {
  global $wpdb, $out, $user;
  // if (!$user->has_cap('create_users')) {
  //   $out['errmsg'] = "Portal administrator does not have permission to create users.";
  //   return;
  // }
  if (empty($login)) $login = $email;
  $userid = wp_insert_user(array(
    'user_login' => $login,
    'user_pass'  => $pass,
    'user_email' => $email,
    'role'       => 'patient',
  ));
  if (is_wp_error($userid)) {
    $out['errmsg'] = "Failed to add user '$login': " . $userid->get_error_message();
  }
  else {
    $out['userid'] = $userid;
  }
}

// Logic to process the "checkptform" action to determine if a form is pending for
// the given patient login and form name.  If it is its request ID is returned.
//
function action_checkptform($patient, $form) {
  global $wpdb, $out;
  $out['list'] = array();
    $query =
	"SELECT submit_time AS ID " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits " .
	"WHERE field_order = 9999 AND " .
	"field_value = %s AND " .
	"form_name = %s " .
	"ORDER BY ID LIMIT 1 ";
    $queryp = $wpdb->prepare($query, array($patient, $form));
    if (empty($queryp)) {
      $out['errmsg'] = "Internal error: \"$query\" \"$patient\" \"$form\"";
      return;
    }
    $row = $wpdb->get_row($queryp, ARRAY_A);
    $out['postid'] = empty($row['ID']) ? '0' : $row['ID'];
}

// Logic to process the "getupload" action.
// Returns filename, mimetype, datetime and contents for the specified upload ID.
//
function action_getupload($uploadid) {
  global $wpdb, $out;
	$query = "SELECT ID, submit_time, field_name, field_value, file " .
	"FROM {$wpdb->prefix}cf7dbplugin_submits WHERE ID = %f";
	
	$row = $wpdb->get_row($wpdb->prepare($query, array($uploadid)), ARRAY_A);
	$out['filename'] = $row['field_value'];
	$out['mimetype'] = get_mime_type($row['field_value']);
	//$out['contents'] = base64_encode($row['file']);
	$out['contents'] = $row['file'];
}

// Logic to process the "getmessage" action.
// The $messageid argument identifies the message.
//FrontendPM Version
function action_getmessage($messageid) {
  global $wpdb, $out, $admin_user_login;
  $out['message'] = array();
  $out['uploads'] = array();
  //Current provider ID 
  $curID = convertToID($admin_user_login);
  $query = "SELECT p.id, p.post_date, p.post_title, p.post_content, " .
	"uf.user_login AS from_login, ut.user_login AS to_login " .
	"FROM {$wpdb->prefix}posts AS p " .
		"JOIN $wpdb->postmeta pm_to " .
		"ON ((p.post_parent = pm_to.post_id AND p.post_parent <> 0) " .
		"OR (p.id = pm_to.post_id AND p.post_parent = 0)) " .
		"AND pm_to.meta_key = '_fep_participants' " .
		"AND pm_to.meta_value <> p.post_author " .
		"LEFT JOIN $wpdb->postmeta pm_delete " .
		"ON ((pm_delete.post_id = p.post_parent AND pm_delete.meta_key = '_fep_delete_by_%d') " .
		"OR (pm_delete.post_id = p.id AND pm_delete.meta_key = '_fep_delete_by_%d')) " .
	"LEFT JOIN $wpdb->users AS uf ON uf.ID = p.post_author " .
	"LEFT JOIN $wpdb->users AS ut ON ut.ID = pm_to.meta_value " .
	"WHERE p.id = %d";
  $queryp = $wpdb->prepare($query, array($curID,$curID,$messageid));
  if (empty($queryp)) {
	$out['errmsg'] = "Internal error: \"$query\" \"$messageid\"";
	return;
  }
  $row = $wpdb->get_row($queryp, ARRAY_A);
  if (empty($row)) {
	$out['errmsg'] = "No messages matching: \"$messageid\"";
	return;
  }
  $out['message'] = array(
	'messageid' => $row['id'],
	'user'      => ($row['from_login'] == $admin_user_login ? $row['to_login'] : $row['from_login']),
	'fromuser'  => $row['from_login'],
	'touser'    => $row['to_login'],
	'datetime'  => $row['post_date'],
	'title'     => $row['post_title'],
	'contents'  => $row['post_content'],
  );
  
  $query2 = "SELECT ID, post_mime_type, guid " .
	"FROM {$wpdb->prefix}posts " .
	"WHERE post_parent = %d AND post_type = 'attachment' ORDER BY guid, ID";

  $query2p = $wpdb->prepare($query2, array($messageid));
  if (empty($query2p)) {
	$out['errmsg'] = "Internal error: \"$query2\" \"$messageid\"";
	return;
  }
 
  $msgrows = $wpdb->get_results($query2p, ARRAY_A);
  
  foreach ($msgrows as $msgrow) {
	$filepath = $msgrow['guid'];
	$filename = basename($filepath);  
  
	$out['uploads'][] = array(
	  'filename' => $filename,
	  'mimetype' => $msgrow['post_mime_type'],
	  'id'       => $msgrow['ID'],

	);
  }
}  

// Logic to process the "getmsgup" action.
// Returns filename, mimetype and contents for the specified upload ID.
// FrontendPM version  
function action_getmsgup($uploadid) {
  global $wpdb, $out;
  $query = $wpdb->prepare("SELECT ID, post_mime_type, guid FROM {$wpdb->prefix}posts WHERE ID = %d", array($uploadid));
  $rows = $wpdb->get_results($query, ARRAY_A);
	  
  foreach ($rows as $row) {
	$url = $row['guid'];
	$filename = basename($url);
	$path  = parse_url($url, PHP_URL_PATH); // just the path part of the URL
	$parts = explode('/', $path);           // all the components
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		$parts = array_slice($parts, -6);       // the last six
	}	else {
		$parts = array_slice($parts, -4);       // the last 4
	}
	$path  = implode('/', $parts);  
	$filepath = ABSPATH . $path;
	$contents = file_get_contents($filepath);
	if ($contents === false) {
	  $out['errmsg'] = "Unable to read \"$filepath\"";
	  return;
	}
	$out['filename'] = $filename;
	$out['mimetype'] = $row['post_mime_type'];
	$out['datetime'] = $row['post_date'];
	$out['contents'] = $contents;
  }
}

// Logic to process the "delmessage" action to delete a message.  It's not
// physically deleted until both sender and recipient delete it.  Note that we
// can delete (actually hide) a child message, but in WordPress that action is
// not supported; there only a parent message can be deleted.  In either case
// a physical delete also deletes all children and associated attachments.
// FrontendPM version
function action_delmessage($messageid) {
  global $wpdb, $out, $admin_user_login;
  // Get message attributes so we can figure out what to do.

  //Current provider ID 
  $curID = convertToID($admin_user_login);
  
  $query =  "SELECT uf.user_login AS from_login, ut.user_login AS to_login, pm_clear.meta_key AS clear_p, " .
		"pm_delete1.meta_key AS delete_1 , pm_delete2.meta_key AS delete_2, pm_to.post_id AS parent " .
		"FROM {$wpdb->prefix}posts AS p " .
		"JOIN $wpdb->postmeta pm_to " .
		"ON ((p.post_parent = pm_to.post_id AND p.post_parent <> 0) " .
		"OR (p.id = pm_to.post_id AND p.post_parent = 0)) " .
		"AND pm_to.meta_key = '_fep_participants' " .
		"AND pm_to.meta_value <> p.post_author " .
		"LEFT JOIN $wpdb->postmeta AS pm_delete1 " . 
		"ON ((pm_delete1.post_id = p.post_parent AND pm_delete1.meta_key = CONCAT('_fep_delete_by_', p.post_author) ) " . 
		"OR (pm_delete1.post_id = p.id AND pm_delete1.meta_key = CONCAT('_fep_delete_by_', p.post_author) )) " . 
		"LEFT JOIN $wpdb->postmeta AS pm_delete2 " . 
		"ON ((pm_delete2.post_id = p.post_parent AND pm_delete2.meta_key = CONCAT('_fep_delete_by_', pm_to.meta_value) ) " . 
		"OR (pm_delete2.post_id = p.id AND pm_delete2.meta_key = CONCAT('_fep_delete_by_', pm_to.meta_value) )) " . 
		"LEFT JOIN wp_postmeta AS pm_clear " .
		"ON ((pm_clear.post_id = p.post_parent AND pm_clear.meta_key = CONCAT('_oemr_fep_clear_', p.id)) " .
		"OR (pm_clear.post_id = p.id AND pm_clear.meta_key = CONCAT('_oemr_fep_clear_', p.id))) " .
		"LEFT JOIN $wpdb->users AS uf ON uf.ID = p.post_author " .
		"LEFT JOIN $wpdb->users AS ut ON ut.ID = pm_to.meta_value ";
	$query2 = $query;
    
	$query .= "WHERE p.id = %d";

  $row = $wpdb->get_row($wpdb->prepare($query, array($messageid)), ARRAY_A);
  if (empty($row)) {
	$out['errmsg'] = "Cannot delete, there is no message with ID $messageid.";
	return;
  }
  
  $parent = $row['parent'];
  $timestamp = idate("U"); 
  
  if ($row['from_login'] == $admin_user_login) {
	  //clear the record if admin is from
	  add_post_meta($messageid, '_oemr_fep_clear_' . $messageid, $timestamp);
  }	  
  
  else if ($row['to_login'] == $admin_user_login) {
	  //clear the record if admin is to
	  add_post_meta($messageid, '_oemr_fep_clear_' . $messageid, $timestamp);
  } 
  
  $query2 .=  "WHERE (p.id = %d OR p.post_parent = %d) AND pm_clear.meta_key IS NULL AND p.post_type <> 'attachment'";
  
  $row2 = $wpdb->get_row($wpdb->prepare($query2, array($parent, $parent)), ARRAY_A);
  
  if (empty($row2)) {
	  add_post_meta($parent, '_fep_delete_by_' . $curID, $timestamp);
  }
		
	$row3 = $wpdb->get_row($wpdb->prepare($query, array($parent)), ARRAY_A);
	
  if (($row3['delete_1'] !== NULL) && ($row3['delete_2'] !== NULL)) {
	$query4 = $wpdb->prepare("SELECT ID FROM {$wpdb->prefix}posts " .
	"WHERE (ID = %d OR post_parent = %d) and post_type = 'fep_message'", $parent, $parent);
	$row4s = $wpdb->get_results($query4, ARRAY_A);
	foreach ($row4s as $row4) {
		$query5 = $wpdb->prepare("SELECT ID, post_mime_type, guid " .
		"FROM {$wpdb->prefix}posts " .
		"WHERE post_parent = %d AND post_type = 'attachment'", $row4['ID']);
		$row5s = $wpdb->get_results($query5, ARRAY_A);
		foreach ($row5s as $row5) {
			$url = $row5['guid'];
			$filename = basename($url);
			$path  = parse_url($url, PHP_URL_PATH); // just the path part of the URL
			$parts = explode('/', $path);           // all the components
			if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
				$parts = array_slice($parts, -6);       // the last six
			}	else {
				$parts = array_slice($parts, -4);       // the last 4
			}
			$path  = implode('/', $parts);  
			$filepath = ABSPATH . $path;
			unlink($filepath); //Delete the file from the server.
			$wpdb->query($wpdb->prepare("DELETE p FROM {$wpdb->prefix}posts AS p WHERE p.id = %d",$row5['ID'])); 			
		}
		$wpdb->query($wpdb->prepare("DELETE p, pm FROM {$wpdb->prefix}posts AS p " .
		"INNER JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = p.ID " .
		"WHERE p.ID = %d OR p.post_parent = %d AND pm.post_id = %d",$row4['ID'],$row4['ID'],$row4['ID']));
	}
  }  
}

// Logic to process the "putmessage" action.
// Sends a message to the designated user with an optional attachment.
// FrontendPM version
function action_putmessage( $args ) {
	global $out, $admin_user_login;
	
	if( ! function_exists( 'fep_send_message' ) )
	return false;
	
	$sender = fep_get_userdata( $admin_user_login, 'ID', 'login' );
	
	if (!$sender) {
		$out['errmsg'] = "No such sender '$admin_user_login'";
		return false;
	}
	$recipient = fep_get_userdata( $args['user'], 'ID', 'login' );
	if (!$recipient) {
		$out['errmsg'] = "No such recipient '{$args['user']}'";
		return false;
	}
	$message = array(
		'message_title' => $args['title'],
		'message_content' => $args['message'],
		'message_to_id' => $recipient,	
	);
	$override = array(
		'post_author' => $sender,
	);
	$message_id = fep_send_message( $message, $override );
	
	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];	
	$subdir = '';
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
			$time = current_time( 'mysql' );

		$y = substr( $time, 0, 4 );
		$m = substr( $time, 5, 2 );

		$subdir = "/$y/$m";    
	}
	$upsub	= '/front-end-pm' . $subdir;
	$filename = $args['filename'];
	$newfilename = wp_unique_filename( $upload_dir, $filename );
	$pathtofile = $upload_dir . $upsub . '/' . $newfilename;
	$content = isset( $args['contents'] ) ? base64_decode($args['contents']) : '';
	
	$size_limit = (int) wp_convert_hr_to_bytes(fep_get_option('attachment_size','4MB'));
	$size = strlen( $content );
	if( $size > $size_limit )
	return false;

	$mime = isset( $args['mimetype'] ) ? $args['mimetype'] : '';
	if( !$mime || !in_array( $mime, get_allowed_mime_types() ) )
	return false;

	file_put_contents($pathtofile, $content);
	
	$attachment = array(
	 'guid' => $pathtofile,
	 'post_mime_type' => $args['mimetype'],
	 'post_title' => preg_replace('/\.[^.]+$/', '', basename($pathtofile)),
	 'post_content' => '',
	 'post_author' => $sender,
	 'post_status' => 'inherit',

	);

	$attach_id = wp_insert_attachment( $attachment, $pathtofile, $message_id );
}

