<?php
/**
 * Kills and/or modifies cruft in the database
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Kill Cruft</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class killCruft
{
	var $mode;
	var $reason_user_id;
		
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->remove_site_to_minisite_template_allowable_relationship();
		$this->remove_allowable_relationships_with_missing_types();
		$this->remove_orphaned_relationships();
		$this->update_whats_new_in_reason_blurb();
	}
	
	function remove_site_to_minisite_template_allowable_relationship()
	{
		echo'<hr/>';
		if (reason_relationship_name_exists('site_to_minisite_template'))
		{
			$rel_id = relationship_id_of('site_to_minisite_template', false);
			$q = 'DELETE from allowable_relationship WHERE id='.$rel_id;
			if ($rel_id && ($this->mode == 'run') )
			{
				db_query($q);
				echo '<p>Deleted site_to_minisite_template relationship using this query:</p>';
				echo '<p>'.$q.'</p>';
			}
			elseif ($rel_id && ($this->mode == 'test'))
			{
				echo '<p>Would delete site_to_minisite_template relationship using this query:</p>';
				echo '<p>'.$q.'</p>';
			}
		}
		else
		{
			echo '<p>The relationship name site_to_minisite_template does not exist in your database</p>';
		}
	}
	
	function remove_allowable_relationships_with_missing_types()
	{
		echo '<hr/>';
		$ids = get_allowable_relationships_with_missing_types();
		if ($this->mode == 'run')
		{
			if (count($ids) > 0)
			{
				$deleted_count = remove_allowable_relationships_with_missing_types();
				echo '<p>Removed ' . $deleted_count . ' allowable relationships with missing types.</p>';
			}
			else
			{
				echo '<p>Nothing to delete there are no allowable relationships with missing types.</p>';
			}
		}
		else
		{
			echo '<p>Would delete ' . count($ids) . ' allowable relationships with missing types.</p>';
		}
	}
	
	/**
	 * If the number to delete is huge we'll do it in chunks
	 */
	function remove_orphaned_relationships()
	{
		echo '<hr/>';
		$orphans = get_orphaned_relationship_ids();
		if ($this->mode == 'run')
		{
			if (count($orphans) > 0)
			{
				$deleted_count = remove_orphaned_relationships();
				echo '<p>Removed ' . $deleted_count . ' orphaned relationships.</p>';
			}
			else
			{
				echo '<p>Nothing to delete there are no orphaned relationships.</p>';
			}
		}
		else
		{
			echo '<p>Would delete ' . count($orphans) . ' orphaned relationships (this may be inaccurate if the previous steps would delete allowable relationships).</p>';
		}
	}
	
	function update_whats_new_in_reason_blurb()
	{
		echo '<hr/>';
		if (reason_unique_name_exists('whats_new_in_reason_blurb'))
		{
			$id = id_of('whats_new_in_reason_blurb');
			$e = new entity($id);
			$name = $e->get_value('name');
			if (trim($name) == 'Welcome to Reason 4 Beta 4')
			{
				if ($this->mode == 'run')
				{
					reason_update_entity($id, $this->reason_user_id, array('name' => 'Welcome to Reason'));
					echo "<p>Updated the blurb with unique_name 'whats_new_in_reason_blurb' to remove the version number reference.</p>";
				}
				else
				{
					echo "<p>Would update the blurb with unique_name 'whats_new_in_reason_blurb' to remove the version number reference.</p>";
				}
			}
			else
			{
				echo "<p>The blurb with unique_name 'whats_new_in_reason_blurb' does not need updating.</p>";
			}
		}
		else
		{
			echo "<p>The blurb with unique_name 'whats_new_in_reason_blurb' does not exist in this instance.</p>";
		}
	}
}

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have Reason upgrade rights to run this script');
}

?>
<h2>Reason: Kill Cruft</h2>
<p>As Reason changes, sometimes old crufty relationships and entities persist past their useful lifespan. This script zaps cruft.</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Deletes the site_to_minisite_template allowable relationship.</li>
<li>Removes any allowable relationships that reference missing types.</li>
<li>Delete orphaned relationships (those that do not correspond to a valid allowable relationship).</li>
<li>If the blurb with unique name whats_new_in_reason_blurb is titled "Welcome to Reason 4 Beta 4" we update it to remove the version reference.</li>
</ul>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new killCruft();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>