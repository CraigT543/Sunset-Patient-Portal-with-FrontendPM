<h1>Modifications of the Sunset-Patient-Portal to work with the Frontend PM messaging system</h1>
<p>I am making modifications to the Sunset-Patient-Portal webserve.php file to replace Cartpauj PM with Front End PM.  Why Front End PM? It is built on more modern HTML principles, it has many more features that make it much more usable. The FrontendPMmods.php file is just a series of code snippets you can use to modify your webserve.php file. I did not just add a modified webserve.php file because I am in the process of completely rewriting the webserve.php file and this is just part of what I need to do. If you are using the original webserve.php file, just paste the code in place of the code at the respective headings and you should be good to go.</p>
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
//For Front End PM to undelete a participant from a thread if the other active participant(s) post following deletion of one participant.  This will maintain the all participants in a thread until all delete themselves from the thread. 
add_action('save_post', 'undelete_thread');
function undelete_thread($post_id) {
	$post = get_post($post_id);
	if ($post->post_type = 'fep_message' && $post->post_parent != 0){
		$participants = fep_get_participants( $post->post_parent );
		foreach( $participants as $participant )		
		{
			delete_post_meta($post->post_parent,'_fep_delete_by_'. $participant );	
		}
	}
}
</code>
<h2>Front End PM Settings</h2>
I have made several changes to the settings to make it work like Cartpauj PM.  I am limitting all communication to be between the therapist and one client only.  I am also making my email messages only contain enough information to guide the client back to my site to read the message.  No message content should be in the email to keep true to HIPPA principles.
<h3>GENERAL SETTINGS</h3>
Front End Page: PM
Messages to show per page: 15
Max user per page in directory: 50
Time delay: 5
Custom CSS:
div.fep-message-content.fep-message-content-2258.fep-hide-if-js{
    display: block !important;
}

.fep-message-title-heading.participants {
    font-size: 10px;
}

Editor Type: WP Editor
Parent Message Status: Publish
Reply Message Status: Publish
Allow Attachment: X
Max Size of attachment: 4MB
Maximum Number of attachment: 4
Hide Directory from front end: X
Hide site wide notification:
Hide Branding Fotter:
Remove Data on Uninstall?

<h3>RECEPIANT</h3>
Can send to users:
Can admin send to users: x
Max recipiants: 1
Message type: same message
Read Receipt: x
Can send to admin: x
Admins: Administrator craigtuckerlcsw
Show in frontend as: Select

<h3>MESSAGE</h3>
Message view: thread

<h3>ANNOUNCEMENT</h3>
Send email? uncheck

<h3>EMAILS</h3>
Email content Type: Html
From Name: [My practice name here]
From Email: [My practice email here]
Enable piping: check 
Piping Email: [My practice email here]
Clean reply quote: check
New message email template: Default
New message subject: 	{{site_title}} - New message
New message content:

Hi {{receiver}},

You have received a new message at the {{site_title}} portal. Please view the message at this link: <a href="{{message_url}}">My Message</a>

{{site_title}}

Send Attachments: uncheck

Replay message email template: default
Reply subject: {{site_title}} - New reply
Reply content:

Hi {{receiver}},

You have received a new reply of your message at the {{site_title}} portal. Please view the message at this link: <a href="{{message_url}}">My Message</a>

{{site_title}}

Send Attachments: uncheck
Sending Interval: 60
Emails send per interval: 100

Announcement email template: default
Hi {{receiver}},

You have received a new reply of your message at the {{site_title}} portal. Please view the message at this link: <a href="{{message_url}}">My Message</a>

{{site_title}}

Send Attachments: uncheck

<h3>SECURITY:</h3>
Who can access message system?	
Administrator
Client
Client TM

Who can send new message?
Administrator
Client
Client TM

Who can send reply?	
Administrator
Client
Client TM

Whitelist Username: blank
Blacklist Username: blank

<h3>LICENCES</h3>
Frontend PM PRO License
[My licence Key]
