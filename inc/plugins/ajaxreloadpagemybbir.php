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

$plugins->add_hook("showthread_start", "ajaxreloadpage_showthread");

function ajaxreloadpagemybbir_info()
{
	return array(
		"name"			=> "بروزرسانی موضوعات به صورت خودکار و آژاکس",
		"description"	=> "با استفاده از این پلاگین ارسال ها بدون رفرش صفحه بروزرسانی می شوند!",
		"website"		=> "http://my-bb.ir",
		"author"		=> "AliReza_Tofighi",
		"authorsite"	=> "http://my-bb.ir",
		"version"		=> "1.51",
		"guid" 			=> "",
		"compatibility" => "*"
	);
}


function ajaxreloadpagemybbir_activate()
{
	global $db, $mybb;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'head\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_top\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_bottom\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('</head>')."#i", '{$ajaxreloadpage[\'head\']}</head>');
	find_replace_templatesets("showthread", "#".preg_quote('{$pollbox}')."#i", '{$pollbox}{$ajaxreloadpage[\'message_top\']}');
	find_replace_templatesets("showthread", "#".preg_quote('{$quickreply}')."#i", '{$ajaxreloadpage[\'message_bottom\']}{$quickreply}');
		// DELETE ALL SETTINGS TO AVOID DUPLICATES
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN(
		'ajaxreloadpageswitch',
		'ajaxreloadpagetime',
		'ajaxreloadpageviewbar',
		'ajaxreloadpageviewspinner',
		'ajaxreloadpagemessage',
		'ajaxreloadpageactiveforum'
	)");
	$db->delete_query("settinggroups", "name = 'ajaxreloadpage'");
	
	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");
	
	$insertarray = array(
		'name' => 'ajaxreloadpage',
		'title' => 'تنظیمات پلاگین بروزرسانی خودکار موضوع',
		'description' => 'نوشته شده توسط: علیرضا توفیقی',
		'disporder' => $rows+1,
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertarray);
	$mybb->ajaxreloadpage_insert_gid = $group['gid'];
	
	$insertarray = array(
		'name' => 'ajaxreloadpageswitch',
		'title' => 'فعال باشد؟',
		'description' => '',
		'optionscode' => 'onoff',
		'value' => 1,
		'disporder' => 0,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'ajaxreloadpagetime',
		'title' => 'زمان بروزرسانی',
		'description' => 'در صورتی که بر روی صفر قرار دهید بروزرسانی نخواهد شد.',
		'optionscode' => 'text',
		'value' => '120000',
		'disporder' => 2,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'ajaxreloadpageviewbar',
		'title' => 'نمایش نوار بروزرسانی',
		'description' => '',
		'optionscode' => 'select
0=نمایش داده نشود
1=در بالا و پائین موضوع نمایش داده شود
2=تنها در بالای موضوع نمایش داده شود
3=تنها در پائین موضوع نمایش داده شود',
		'value' => '1',
		'disporder' => 3,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);
	
	$insertarray = array(
		'name' => 'ajaxreloadpageviewspinner',
		'title' => 'نمایش قسمت درحال بارگذاری',
		'description' => '',
		'optionscode' => 'onoff',
		'value' => 1,
		'disporder' => 5,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);
	
	$insertarray = array(
		'name' => 'ajaxreloadpagemessage',
		'title' => 'متن نوار بروزرسانی',
		'description' => '',
		'optionscode' => 'textarea',
		'value' => 'بروزرسانی موضوع
		<sapn style="font-weight:normal">(<timeout->secs> ثانیه یک بار به صورت خودکار بروزرسانی می شود)</span>',
		'disporder' => 4,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);
	
	$insertarray = array(
		'name' => 'ajaxreloadpageactiveforum',
		'title' => 'انجمن های فعال',
		'description' => 'ID انجمن هایی که می خواهید بروزرسانی خودکار برای آنها کارکند را وارد نمائید:<br />با کاما (,) از هم جدا کنید<br />در صورتی که صفر وارد کنید برای همه انجمن ها کار می کند',
		'optionscode' => 'textarea',
		'value' => 0,
		'disporder' => 6,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);
	
	rebuild_settings();
}

function ajaxreloadpagemybbir_deactivate()
{
	global $db, $mybb;
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_top\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$ajaxreloadpage[\'message_bottom\']}')."#i", '', 0);
		// DELETE ALL SETTINGS TO AVOID DUPLICATES
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN(
		'ajaxreloadpageswitch',
		'ajaxreloadpagetime',
		'ajaxreloadpageviewbar',
		'ajaxreloadpageviewspinner',
		'ajaxreloadpagemessage',
		'ajaxreloadpageactiveforum'
	)");
	$db->delete_query("settinggroups", "name = 'ajaxreloadpage'");
}

function ajaxreloadpage_showthread()
{
	global $db, $mybb, $thread, $thread_page, $ajaxreloadpage;
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
	$seces = $mybb->settings['ajaxreloadpagetime']/1000;
	if ($mybb->settings['ajaxreloadpageswitch'] == 1) {
		if((in_array($thread['fid'], explode(",", $mybb->settings['ajaxreloadpageactiveforum']))) Or ($mybb->settings['ajaxreloadpageactiveforum'] == 0))
		{
			$ajaxreloadpage['head'] = "<script type=\"text/javascript\">
				function get_mybbir_ajax_answes(tid, page) {";
				if($mybb->settings['ajaxreloadpageviewspinner'] == 1) {
					$ajaxreloadpage['head'] .= "Thread.spinner = new ActivityIndicator(\"body\", {image: imagepath + \"/spinner_big.gif\"});";
				}
				$ajaxreloadpage['head'] .="	new Ajax.Request('ajax_answers_mybbir.php?tid='+tid".$pagepid.", {
						method: 'GET', postBody: null, onComplete: function(request) {
							$('posts').innerHTML=request.responseText;
							var scripts = request.responseText.extractScripts();
							scripts.each(function(script)
							{
								eval(script);
							});";
				if($mybb->settings['ajaxreloadpageviewspinner'] == 1) {
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
				$ajaxreloadpagemessage = str_replace("<timeout->secs>", $seces, $mybb->settings['ajaxreloadpagemessage']);
				if ($mybb->settings['ajaxreloadpagetime'] > 0) {
					$ajaxreloadpage['head'] .= "setInterval(\"get_mybbir_ajax_answes('{$mybb->input['tid']}','{$page}')\", {$mybb->settings['ajaxreloadpagetime']});";
				}
				$ajaxreloadpage['head'] .= "\n</script>\n";
			if($mybb->settings['ajaxreloadpageviewbar'] >0) {
				$ajaxreloadpage['message'] = "<div style=\"background:#fcf2b7;font-size:11px;font-weight:bold;text-align:center;border-top:2px solid #f2c71e;border-bottom:2px solid #f2c71e;padding:5px;margin-top:3px;margin-bottom:3px;cursor: pointer;\" onclick=\"get_mybbir_ajax_answes('{$mybb->input['tid']}','{$page}');\">
						{$ajaxreloadpagemessage}<a href=\"http://my-bb.ir\" title=\"My-BB.Ir\" target=\"_blank\" style=\"color:#fcf2b7;font-size:0px;\">My-BB.Ir</a>
					</div>";
				if($mybb->settings['ajaxreloadpageviewbar'] != 3) {
					$ajaxreloadpage['message_top'] = $ajaxreloadpage['message'];
				}
				if($mybb->settings['ajaxreloadpageviewbar'] != 2) {
					$ajaxreloadpage['message_bottom'] = $ajaxreloadpage['message'];
				}
			}
		}
	}
}


?>