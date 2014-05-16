<?php
/**
 * MyBB 1.6
 * Copyright 2012 My-BB.Ir Group, All Rights Reserved
 *
 * Website: http://my-bb.ir
 *
 * $Id: ajaxreloadpagemybbir.php AliReza_Tofighi $
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

$plugins->add_hook("showthread_start", "ajaxreloadpage_showthread");

function ajaxreloadpagemybbir_info()
{
	global $lang, $plugins_cache, $PL;
	$lang->load('ajaxreloadpage');
	$info = array(
		"name"			=> $lang->ajaxreloadpage,
		"description"	=> $lang->ajaxreloadpage_desc,
		"website"		=> "http://my-bb.ir",
		"author"		=> "AliReza_Tofighi",
		"authorsite"	=> "http://my-bb.ir",
		"version"		=> $lang->ajaxreloadpage_version,
		"guid" 			=> "",
		"compatibility" => "*"
	);
	if($plugins_cache['active']['ajaxreloadpagemybbir'])
	{
		global $PL;
		$PL or require_once PLUGINLIBRARY;
		$info["description"] .= "<br /><a href=\"index.php?module=config/settings&action=change&search=ajaxreloadpage_\">{$lang->ajaxreloadpage_changesettings}</a>.";
	}
	return $info;
}


function ajaxreloadpagemybbir_activate()
{
	global $db, $mybb, $PL, $lang;
	$lang->load('ajaxreloadpage');
	if(!file_exists(PLUGINLIBRARY))
	{
		flash_message($lang->ajaxreloadpage_misspl, "error");
		admin_redirect("index.php?module=config-plugins");
	}
	$PL or require_once PLUGINLIBRARY;
	if($PL->version < 12)
	{
		flash_message($lang->ajaxreloadpage_plistooold, "error");
		admin_redirect("index.php?module=config-plugins");
	}
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'head\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_top\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_bottom\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('</head>')."#i", '{$ajaxreloadpage[\'head\']}</head>');
	find_replace_templatesets("showthread", "#".preg_quote('{$pollbox}')."#i", '{$pollbox}{$ajaxreloadpage[\'message_top\']}');
	find_replace_templatesets("showthread", "#".preg_quote('{$quickreply}')."#i", '{$ajaxreloadpage[\'message_bottom\']}{$quickreply}');

	$PL->settings_delete('ajaxreloadpage');
	$PL->settings("ajaxreloadpage",
				  $lang->ajaxreloadpage,
				  'By My-BB.Ir Group',
				  array(
					"active" => array(
						'title' => $lang->ajaxreloadpage_active,
						'description' => '',
						'value' => 1
					),
					"time" => array(
						'title' => $lang->ajaxreloadpage_time,
						'description' => $lang->ajaxreloadpage_time_desc,
						'optionscode' => 'text',
						'value' => 120
					),
					"viewbar" => array(
						"title" => $lang->ajaxreloadpage_viewbar,
						'description' => '',
						'optionscode' => 'select
0='.$lang->ajaxreloadpage_viewbar_0.'
1='.$lang->ajaxreloadpage_viewbar_1.'
2='.$lang->ajaxreloadpage_viewbar_2.'
3='.$lang->ajaxreloadpage_viewbar_3.'',
						'value' => '1'
					),
					"showspinner" => array(
						'title' => $lang->ajaxreloadpage_showspinner,
						'description' => '',
						'optionscode' => 'onoff',
						'value' => 1
					),
					"message" => array (
						'title' => $lang->ajaxreloadpage_message,
						'description' => '',
						'optionscode' => 'textarea',
						'value' => $lang->ajaxreloadpage_message_value
					),
					"activeforums" => array (
						'title' => $lang->ajaxreloadpage_activeforums,
						'description' => $lang->ajaxreloadpage_activeforums_desc,
						'optionscode' => 'textarea',
						'value' => ''
					)
				  )
				);
	
	$PL->templates('ajaxreloadpage',
				   $lang->ajaxreloadpage,
				   array(
						'' => '<div style="background:#fcf2b7;font-size:11px;font-weight:bold;text-align:center;border-top:2px solid #f2c71e;border-bottom:2px solid #f2c71e;padding:5px;margin-top:3px;margin-bottom:3px;cursor: pointer;" onclick="{$jsclick}">
	{$message}
</div>'
				   ));
	
}

function ajaxreloadpagemybbir_deactivate()
{
	global $db, $mybb, $PL;
	$PL or require_once PLUGINLIBRARY;
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_top\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_bottom\']}')."#i", '', 0);
	$PL->settings_delete('ajaxreloadpage');
}

function ajaxreloadpage_showthread()
{
	global $db, $mybb, $thread, $templates, $thread_page, $ajaxreloadpage;
	if (isset($mybb->input['page'])) {
	$pagepid = "+'&page='+page";
	$page = $mybb->input['page'];
	}
	elseif (isset($mybb->input['pid'])) {
		$pagepid = "+'&pid='+page";
		$page = $mybb->input['pid'];
	}
	else {
		$pagepid = "";
		$page = "";
	}
	$perpage = $mybb->settings['postsperpage'];
	$postcount = intval($thread['replies'])+1;
	$pages = $postcount / $perpage;
	$pages = ceil($pages);
	//die($pages);
	if($page != $pages)
	{
		return ;
	}
	$seces = $mybb->settings['ajaxreloadpage_time'];
	if ($mybb->settings['ajaxreloadpage_active'] == 1) {
		if((in_array($thread['fid'], explode(",", $mybb->settings['ajaxreloadpage_activeforums']))) || (!$mybb->settings['ajaxreloadpage_activeforums']))
		{
			$ajaxreloadpage['head'] = "<script type=\"text/javascript\">
				function get_mybbir_ajax_answes(tid, page) {
					var elements = $$('.post_body');
					var numposts = elements.length;
					var lastpid = elements[elements.length-1].id;
					lastpid = lastpid.replace('pid_', '');
				";
				if($mybb->settings['ajaxreloadpage_showspinner'] == 1) {
					$ajaxreloadpage['head'] .= "Thread.spinner = new ActivityIndicator(\"body\", {image: imagepath + \"/spinner_big.gif\"});";
				}
				$ajaxreloadpage['head'] .="	new Ajax.Request('ajax_answers_mybbir.php?pid='+tid+'&lastid='+lastpid+'&numposts='+numposts, {
						method: 'GET', postBody: null, onComplete: function(request) {
							if(request.status == 200) {
								$$('.pagination').each(function(element){
									element.hide();
								});
								$('posts').innerHTML+=request.responseText;
								var scripts = request.responseText.extractScripts();
								scripts.each(function(script)
								{
									eval(script);
								});
							}";
				if($mybb->settings['ajaxreloadpage_showspinner'] == 1) {
					$ajaxreloadpage['head'] .="		if(Thread.spinner)
							{
								Thread.spinner.destroy();
								Thread.spinner = '';
							}";
				}
				$ajaxreloadpage['head'] .= "
						}
					});
				}
				";
				$message = str_replace("<timeout->secs>", $seces, $mybb->settings['ajaxreloadpage_message']);
				$jsclick = "get_mybbir_ajax_answes('{$mybb->input['tid']}','{$page}');";

				if ($mybb->settings['ajaxreloadpage_time'] > 0) {
					$ajaxreloadpage['head'] .= "setInterval(\"get_mybbir_ajax_answes('{$mybb->input['tid']}','{$page}')\", ".($mybb->settings['ajaxreloadpage_time']*1000).");";
				}
				$ajaxreloadpage['head'] .= "\n</script>\n";
			if($mybb->settings['ajaxreloadpage_viewbar'] >0) {
				eval("\$ajaxreloadpage['message'] .= \"".$templates->get("ajaxreloadpage")."\";");
				if($mybb->settings['ajaxreloadpage_viewbar'] != 3) {
					$ajaxreloadpage['message_top'] = $ajaxreloadpage['message'];
				}
				if($mybb->settings['ajaxreloadpage_viewbar'] != 2) {
					$ajaxreloadpage['message_bottom'] = $ajaxreloadpage['message'];
				}
			}
		}
	}
}
?>