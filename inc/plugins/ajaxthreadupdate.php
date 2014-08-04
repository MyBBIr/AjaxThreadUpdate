<?php
/**
 * MyBB 1.6
 * Copyright 2012 My-BB.Ir Group, All Rights Reserved
 *
 * Website: http://my-bb.ir
 * By: AliReza_Tofighi
 *
 */
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

$plugins->add_hook("showthread_start", "ajaxthreadupdate");

function ajaxthreadupdate_info()
{
	global $lang, $plugins_cache, $PL;
	$lang->load('ajaxthreadupdate');
	$info = array(
		"name"			=> $lang->ajaxthreadupdate,
		"description"	=> $lang->ajaxthreadupdate_desc,
		"website"		=> "http://my-bb.ir",
		"author"		=> "AliReza_Tofighi",
		"authorsite"	=> "http://my-bb.ir",
		"version"		=> "2",
		"guid" 			=> "",
		"compatibility" => "16*"
	);
	if($plugins_cache['active']['ajaxthreadupdatemybbir'])
	{
		global $PL;
		$PL or require_once PLUGINLIBRARY;
		$info["description"] .= "<br /><a href=\"index.php?module=config/settings&action=change&search=ajaxthreadupdate_\">{$lang->ajaxthreadupdate_changesettings}</a>.";
	}
	return $info;
}


function ajaxthreadupdate_activate()
{
	global $db, $mybb, $PL, $lang;
	$lang->load('ajaxthreadupdate');
	if(!file_exists(PLUGINLIBRARY))
	{
		flash_message($lang->ajaxthreadupdate_misspl, "error");
		admin_redirect("index.php?module=config-plugins");
	}
	$PL or require_once PLUGINLIBRARY;
	if($PL->version < 12)
	{
		flash_message($lang->ajaxthreadupdate_plistooold, "error");
		admin_redirect("index.php?module=config-plugins");
	}
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'head\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'message\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'message_top\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'message_bottom\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('</head>')."#i", '{$ajaxthreadupdate[\'head\']}</head>');
	find_replace_templatesets("showthread", "#".preg_quote('{$pollbox}')."#i", '{$pollbox}{$ajaxthreadupdate[\'message_top\']}');
	find_replace_templatesets("showthread", "#".preg_quote('{$quickreply}')."#i", '{$ajaxthreadupdate[\'message_bottom\']}{$quickreply}');

	$PL->settings_delete('ajaxthreadupdate');
	$PL->settings("ajaxthreadupdate",
				  $lang->ajaxthreadupdate,
				  'By My-BB.Ir Group',
				  array(
					"active" => array(
						'title' => $lang->ajaxthreadupdate_active,
						'description' => '',
						'value' => 1
					),
					"time" => array(
						'title' => $lang->ajaxthreadupdate_time,
						'description' => $lang->ajaxthreadupdate_time_desc,
						'optionscode' => 'text',
						'value' => 120
					),
					"viewbar" => array(
						"title" => $lang->ajaxthreadupdate_viewbar,
						'description' => '',
						'optionscode' => 'select
0='.$lang->ajaxthreadupdate_viewbar_0.'
1='.$lang->ajaxthreadupdate_viewbar_1.'
2='.$lang->ajaxthreadupdate_viewbar_2.'
3='.$lang->ajaxthreadupdate_viewbar_3.'',
						'value' => '1'
					),
					"showspinner" => array(
						'title' => $lang->ajaxthreadupdate_showspinner,
						'description' => '',
						'optionscode' => 'onoff',
						'value' => 1
					),
					"message" => array (
						'title' => $lang->ajaxthreadupdate_message,
						'description' => '',
						'optionscode' => 'textarea',
						'value' => $lang->ajaxthreadupdate_message_value
					),
					"activeforums" => array (
						'title' => $lang->ajaxthreadupdate_activeforums,
						'description' => $lang->ajaxthreadupdate_activeforums_desc,
						'optionscode' => 'textarea',
						'value' => ''
					),
					"disablegroups" => array (
						'title' => $lang->ajaxthreadupdate_disablegroups,
						'description' => $lang->ajaxthreadupdate_disablegroups_desc,
						'optionscode' => 'textarea',
						'value' => ''
					)
				  )
				);
	
	$PL->templates('ajaxthreadupdate',
				   $lang->ajaxthreadupdate,
				   array(
						'' => '<div style="background:#fcf2b7;font-size:11px;font-weight:bold;text-align:center;border-top:2px solid #f2c71e;border-bottom:2px solid #f2c71e;padding:5px;margin-top:3px;margin-bottom:3px;cursor: pointer;" onclick="ATU.update();">
	{$message}
</div>'
				   ));
	
}

function ajaxthreadupdate_deactivate()
{
	global $db, $mybb, $PL;
	$PL or require_once PLUGINLIBRARY;
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'message\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'message_top\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxthreadupdate[\'message_bottom\']}')."#i", '', 0);
	$PL->settings_delete('ajaxthreadupdate');
}

function ajaxthreadupdate()
{
	global $db, $mybb, $thread, $templates, $thread_page, $ajaxthreadupdate, $fid, $tid;

	$disablegroups = explode(',', $mybb->settings['ajaxthreadupdate_disablegroups']);
	if(in_array($mybb->user['usergroup'], $disablegroups))
	{
		return;
	}

	$perpage = $mybb->settings['postsperpage'];
	$postcount = intval($thread['replies'])+1;
	$pages = $postcount / $perpage;
	$pages = ceil($pages);
	$page = 1;
	$perpage = $mybb->settings['postsperpage'];
	if(isset($mybb->input['page']) && $mybb->input['page'] != "last")
	{
		$page = intval($mybb->input['page']);
	}

	if(!empty($mybb->input['pid']))
	{
		
		if(is_moderator($fid))
		{
			$visible = "AND (p.visible='0' OR p.visible='1')";
		}
		else
		{
			$visible = "AND p.visible='1'";
		}

		$post = get_post($mybb->input['pid']);
		if($post)
		{
			$query = $db->query("
				SELECT COUNT(p.dateline) AS count FROM ".TABLE_PREFIX."posts p
				WHERE p.tid = '{$tid}'
				AND p.dateline <= '{$post['dateline']}'
				{$visible}
			");
			$result = $db->fetch_field($query, "count");
			if(($result % $perpage) == 0)
			{
				$page = $result / $perpage;
			}
			else
			{
				$page = intval($result / $perpage) + 1;
			}
		}
	}

	if($page != $pages)
	{
		return;
	}

	$seces = (int)$mybb->settings['ajaxthreadupdate_time'];
	if(in_array($mybb->user['usergroup'].'=1', $disablegroups))
	{
		$seces = 0;
	}

	$mybb->settings['ajaxreloadpage_showspinner'] = (bool)$mybb->settings['ajaxreloadpage_showspinner'];
	$showspinner = 'false';
	if($mybb->settings['ajaxreloadpage_showspinner'])
	{
		$showspinner = 'true';
	}
	if($mybb->settings['ajaxthreadupdate_active'] == 1)
	{
		if((in_array($thread['fid'], explode(",", $mybb->settings['ajaxthreadupdate_activeforums']))) || (!$mybb->settings['ajaxthreadupdate_activeforums']))
		{
			$ajaxthreadupdate['head'] = <<<EOT
<script type="text/javascript" src="{$mybb->settings['bburl']}/jscripts/ajaxthreadupdate.js?ver=2"></script>
<script type="text/javascript">
//<!--
	ATU.init('{$tid}', '{$seces}', {$showspinner});
// -->
</script>
EOT;
			$message = str_replace("{time}", $seces, $mybb->settings['ajaxthreadupdate_message']);

			if($mybb->settings['ajaxthreadupdate_viewbar'] > 0)
			{
				eval("\$ajaxthreadupdate['message'] = \"".$templates->get("ajaxthreadupdate")."\";");
				if($mybb->settings['ajaxthreadupdate_viewbar'] != 3)
				{
					$ajaxthreadupdate['message_top'] = $ajaxthreadupdate['message'];
				}
				if($mybb->settings['ajaxthreadupdate_viewbar'] != 2)
				{
					$ajaxthreadupdate['message_bottom'] = $ajaxthreadupdate['message'];
				}
			}
		}
	}
}
