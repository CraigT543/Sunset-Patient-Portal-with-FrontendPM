<?php
/*
* This is a list of code snippets and replacements functions that can be used to modify the patient portal webserver.php to work with FrontendPM. 
* The following is to modify the action_list function at the point where you find the " Get list of messages also" comment to the end of the function.
*/

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
  if ($post_date_from) {
	$query .= " AND p.post_date >= %s";
	$qparms[] = "$post_date_from 00:00:00";
  }
  if ($post_date_to) {
	$query .= " AND p.post_date <= %s";
	$qparms[] = "$post_date_to 23:59:59";
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
  

/*
* The following are at the last part of the webserver.php.  
*/
  

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
	$parts = array_slice($parts, -6);       // the last six
	$path  = implode('/', $parts);  
	$filepath = ABSPATH . $path;

	// Get file contents and make a blob.
	$tmpfile = fopen($filepath, "r");
	$contents = fread($tmpfile, filesize($filepath));  
    $out['filename'] = $filename;
	$out['mimetype'] = $row['mimetype'];
	// $out['contents'] = base64_encode($contents);
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
			$parts = array_slice($parts, -6);       // the last six
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
function action_putmessage(&$args) {
  global $wpdb, $out, $admin_user_login;
  $sender = convertToID($admin_user_login);
  if (!$sender) {
    $out['errmsg'] = "No such sender '$admin_user_login'";
    return;
  }
  $recipient = convertToID($args['user']);
  if (!$recipient) {
    $out['errmsg'] = "No such recipient '{$args['user']}'";
    return;
  }

	$year = date('Y');
	$month = date('m');
	$timestamp = idate("U"); 
	$upload_dir =  ABSPATH . "wp-content/uploads/front-end-pm/" . $year . "/" . $month ;
	if (!file_exists($upload_dir)) {
		mkdir($upload_dir, 0755, true);
	}	
	$filename = $args['filename'];
	$newfilename = wp_unique_filename( $upload_dir, $filename );
	$pathtofile = $upload_dir . '/' . $newfilename;
	$metafilepath = "front-end-pm/" . $year . "/" . $month . "/". $newfilename;
	file_put_contents($pathtofile, base64_decode($args['contents']));


	
	$postarr = array(
	 'post_status' => 'publish',
	 'post_type' => 'post',
	 'post_title' => $args['title'],
	 'post_content' => $args['message'],
	 'post_author' => $sender,
	 'post_type' => 'fep_message', 
	 );
	 
	$parent_post_id = wp_insert_post($postarr);


	add_post_meta( $parent_post_id, '_fep_participants', $sender);	
	add_post_meta( $parent_post_id, '_fep_participants', $recipient);	
	add_post_meta( $parent_post_id, '_fep_parent_read_by_' .$sender , $timestamp);	
	add_post_meta( $parent_post_id, '_fep_last_reply_time', $timestamp);	
	add_post_meta( $parent_post_id, '_fep_last_reply_id', parent_post_id);
	add_post_meta( $parent_post_id, '_fep_last_reply_by', $sender);
	
	if(!$parent_post_id) {
		$out['errmsg'] = "Message insert failed";
		return;
	}

	$attachment = array(
	 'post_author' => $sender,
	 'post_mime_type' => $args['mimetype'],
	 'post_title' => preg_replace('/\.[^.]+$/', '', basename($pathtofile)),
	 'post_content' => '',
	 'post_status' => 'inherit',
	 'guid' => $pathtofile,
	);
	
	$attach_id = wp_insert_attachment( $attachment, $pathtofile, $parent_post_id );
	
	// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	// Generate the metadata for the attachment, and update the database record.
	$attach_data = wp_generate_attachment_metadata( $attach_id, $pathtofile );
	wp_update_attachment_metadata( $attach_id, $attach_data );
		
	if ($attach_id === false) {
	  $out['errmsg'] = "Attachment insert failed";
	}
}