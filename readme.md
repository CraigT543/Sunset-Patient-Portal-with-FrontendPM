<h1>Modifications of the Sunset-Patient-Portal to work with the Frontend PM messaging system in OpenEMR</h1>
<p>I am making modifications to the OpenEMR <a href="https://github.com/openemr/sunset-patient-portal">Sunset-Patient-Portal</a> webserve.php file to replace Cartpauj PM with <a href="https://www.shamimsplugins.com/products/front-end-pm-pro/">Front End PM Pro</a>.  Why Front End PM Pro? It is built on more modern HTML principles, it has many more features that make it much more usable. The FrontendPMmods.php file is just a series of code snippets you can use to modify your webserve.php file. I did not just add a modified webserve.php file here because I am in the process of completely rewriting the webserve.php file and this is just part of what I need to do. If you are using the original webserve.php file, just paste the code in place of the code at the respective headings and you should be good to go.</p>
<p>This is designed to work with Frontend PM Pro, not the Front End PM free version.  The free version is missing necessary features. The free version does not restrict communication with admin only and this is necessary for clinical purposes. And the $29 price tag is very reasonable.  The author is very responsive.  It is worth every penny.</p>
<h2>A necessary extra plugin to add</h2>
<p>If you want to see the client, first/last name and with an email address afterward (as I do) you need to set up the display name to look like that in wordpress. I used the <a href="https://wordpress.org/plugins/force-first-last/">Force First Last plugin</a> to change all the display names to be first last name plus email. I had to modify this to include the email address in the display name with the patient’s name. So within the plugin file, force-first-last.php on line 40 I changed it to this:</p>
<code>
$display_name = trim($_POST['first_name'] . " " . $_POST['last_name'] . "--" .  $_POST['user_email']);
</code>
<p>On line 60 I changed it to this:</p>
<code>
$display_name = trim($info->first_name . ' ' . $info->last_name . '--' . $info->user_email);
</code>
<p>This sets it up to display and search patient first, last name or email</p>
<h2>Necessary Function to Add</h2>
<p>I have added a function to my theme's functions.php file.  This is necessary for the time being because as Front End PM is set up now, if one participant of a thread deletes them selves from the thread, they are disconnected from all future posts to the thread.  This is fine in some situations but not in clinical situations.  I have talked to the author about this and he will be adding an option to revive the thread in the future but for now, the following function will revive all participants if one participant remains active and posts to the thread.</p>
<code>
//For Front End PM to undelete a participant from a thread if the other active participant(s) post following deletion of one participant.  This will maintain the all participants in a thread until all delete themselves from the thread. </code>

<code>add_action('save_post', 'undelete_thread');</code><br>
<code>function undelete_thread($post_id) {</code><br>
<code>	$post = get_post($post_id);</code><br>
<code>	if ($post->post_type = 'fep_message' && $post->post_parent != 0){</code><br>
<code>		$participants = fep_get_participants( $post->post_parent );</code><br>
<code>		foreach( $participants as $participant )</code><br>		
<code>		{</code>
<code>			delete_post_meta($post->post_parent,'_fep_delete_by_'. $participant );	</code><br>
<code>		}</code><br>
<code>	}</code><br>
<code>}</code><br>

<h2>Front End PM Settings</h2>
I have made several changes to the settings to make it work like Cartpauj PM.  I am limitting all communication to be between the therapist and one client only.  I am also making my email messages only contain enough information to guide the client back to my site to read the message.  No message content should be in the email to keep true to HIPPA principles.
<h3>GENERAL SETTINGS</h3>
<ul>
	<li>Front End Page: PM</li>
	<li>Messages to show per page: 15</li>
	<li>Max user per page in directory: 50</li>
	<li>Time delay: 5</li>
	<li>Custom CSS:</li>
</ul>
<code>
div.fep-message-content.fep-message-content-2258.fep-hide-if-js{
    display: block !important;
}

.fep-message-title-heading.participants {
    font-size: 10px;
}
</code>
<ul>
	<li>Editor Type: WP Editor</li>
	<li>Parent Message Status: Publish</li>
	<li>Reply Message Status: Publish</li>
	<li>Allow Attachment: X</li>
	<li>Max Size of attachment: 4MB</li>
	<li>Maximum Number of attachment: 4</li>	
	<li>Hide Directory from front end: X</li>	
	<li>Hide site wide notification:</li>	
	<li>Hide Branding Fotter:</li>	
	<li>Remove Data on Uninstall?</li>	
</ul>
<h3>RECEPIANT</h3>
<ul>
	<li>Can send to users:</li>
	<li>Can admin send to users: x</li>
	<li>Max recipiants: 1</li>
	<li>Message type: same message</li>
	<li>Read Receipt: x</li>
	<li>Can send to admin: x</li>
	<li>Admins: Administrator [myadmin name]</li>
	<li>Show in frontend as: Select</li>
</ul>
<h3>MESSAGE</h3>
Message view: thread

<h3>ANNOUNCEMENT</h3>
Send email? uncheck

<h3>EMAILS</h3>
<ul>
	<li>Email content Type: Html</li>
	<li>From Name: [My practice name here]</li>
	<li>From Email: [My practice email here]</li>
	<li>Enable piping: check </li>
	<li>Piping Email: [My practice email here]</li>
	<li>Clean reply quote: check</li>
	<li>New message email template: Default</li>
	<li>New message subject: 	{{site_title}} - New message</li>
	<li>New message content:</li>
</ul>
<code>Hi {{receiver}},</code>
<code>You have received a new message at the {{site_title}} portal. Please view the message at this link: <a href="{{message_url}}">My Message</a></code>
<code>{{site_title}}</code>
<ul>
	<li>Send Attachments: uncheck  (I do not want to accidentally send out any paitient information in an attachment)</li>
	<li>Replay message email template: default</li>
	<li>Reply subject: {{site_title}} - New reply</li>
	<li>Reply content:</li>
</ul>
<code>
<code>Hi {{receiver}},</code>
<code>You have received a new reply of your message at the {{site_title}} portal. Please view the message at this link: <a href="{{message_url}}">My Message</a></code>
<code>{{site_title}}</code>
</code>
<ul>
	<li>Send Attachments: uncheck  (I do not want to accidentally send out any paitient information in an attachment)</li>
	<li>Sending Interval: 60</li>
	<li>Emails send per interval: 100</li>
	<li>Announcement email template: default</li>
</ul>
<code>
<code>Hi {{receiver}},</code>
<code>You have received a new reply of your message at the {{site_title}} portal. Please view the message at this link: <a href="{{message_url}}">My Message</a></code>
<code>{{site_title}}</code>
</code>
<ul>
	<li>Send Attachments: uncheck  (I do not want to accidentally send out any paitient information in an attachment)</li>
</ul>
<h3>SECURITY:</h3>
<p>Who can access message system?</p>
<ul>
	<li>Administrator</li>
	<li>Client [Or Patient if you are set up that way]</li>
</ul>
<p>Who can send new message?</p>
<ul>
	<li>Administrator</li>
	<li>Client [Or Patient if you are set up that way]</li>
</ul>
<p>Who can send reply?</p>	
<ul>
	<li>Administrator</li>
	<li>Client [Or Patient if you are set up that way]</li>
</ul>

<ul>
	<li>Whitelist Username: blank</li>
	<li>Blacklist Username: blank</li>
</ul>
<h3>LICENCES</h3>
<p>Frontend PM PRO License</p>
<ul>
	<li>[My licence Key]</li>
</ul>


