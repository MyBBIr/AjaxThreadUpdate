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
	global $lang, $plugins_cache;
	$info = array(
		"name"			=> "بروزرسانی موضوعات به صورت خودکار و آژاکس",
		"description"	=> "با استفاده از این پلاگین ارسال ها بدون رفرش صفحه بروزرسانی می شوند!",
		"website"		=> "http://my-bb.ir",
		"author"		=> "AliReza_Tofighi",
		"authorsite"	=> "http://my-bb.ir",
		"version"		=> "1.51",
		"guid" 			=> "",
		"compatibility" => "*"
	);
	if($plugins_cache['active']['ajaxreloadpagemybbir'])
	{
		global $PL;
		$PL or require_once PLUGINLIBRARY;
		$info["description"] .= "<br /><a href=\"index.php?module=config/settings&action=change&search=ajaxreloadpage_\">ویرایش تنظیمات</a>.";
	}
	return $info;
}


function ajaxreloadpagemybbir_activate()
{
	global $db, $mybb, $PL;
	if(!file_exists(PLUGINLIBRARY))
	{
		flash_message('برای استفاده از این پلاگین نیاز به پلاگین لایبری است.', "error");
		admin_redirect("index.php?module=config-plugins");
	}
	$PL or require_once PLUGINLIBRARY;
	if($PL->version < 12)
	{
		flash_message('نسخه‌ی پلاگین لایبری شما قدیمی است.', "error");
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
				  'تنظیمات پلاگین بروزرسانی خودکار موضوع',
				  'نوشته‌ی My-BB.Ir Group',
				  array(
					"active" => array(
						'title' => 'فعال باشد؟',
						'description' => '',
						'value' => 1
					),
					"time" => array(
						'title' => 'زمان بروزرسانی',
						'description' => 'در صورتی که بر روی صفر قرار دهید بروزرسانی انجام نخواهد شد',
						'optionscode' => 'text',
						'value' => 120
					),
					"viewbar" => array(
						"title" => 'نمایش نوار بروزرسانی',
						'description' => '',
						'optionscode' => 'select
0=نمایش داده نشود
1=در بالا و پائین موضوع نمایش داده شود
2=تنها در بالای موضوع نمایش داده شود
3=تنها در پائین موضوع نمایش داده شود',
						'value' => '1'
					),
					"showspinner" => array(
						'title' => 'نمایش قسمت درحال بارگذاری',
						'description' => '',
						'optionscode' => 'onoff',
						'value' => 1
					),
					"message" => array (
						'title' => 'متن نوار بروزرسانی',
						'description' => '',
						'optionscode' => 'textarea',
						'value' => 'بروزرسانی موضوع
						<sapn style="font-weight:normal">(<timeout->secs> ثانیه یک بار به صورت خودکار بروزرسانی می شود)</span>'
					),
					"activeforums" => array (
						'title' => 'انجمن های فعال',
						'description' => 'ID انجمن هایی که می خواهید بروزرسانی خودکار برای آنها کارکند را وارد نمائید:<br />با کاما (,) از هم جدا کنید<br />در صورتی که صفر وارد کنید برای همه انجمن ها کار می کند',
						'optionscode' => 'textarea',
						'value' => ''
					)
				  )
				);
	
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
	$seces = $mybb->settings['ajaxreloadpage_time'];
	if ($mybb->settings['ajaxreloadpage_active'] == 1) {
		if((in_array($thread['fid'], explode(",", $mybb->settings['ajaxreloadpage_activeforums']))) Or ($mybb->settings['ajaxreloadpage_activeforums'] == 0))
		{
			$ajaxreloadpage['head'] = "<script type=\"text/javascript\">
				function get_mybbir_ajax_answes(tid, page) {";
				if($mybb->settings['ajaxreloadpage_showspinner'] == 1) {
					$ajaxreloadpage['head'] .= "Thread.spinner = new ActivityIndicator(\"body\", {image: imagepath + \"/spinner_big.gif\"});";
				}
				$ajaxreloadpage['head'] .="	new Ajax.Request('ajax_answers_mybbir.php?tid='+tid".$pagepid.", {
						method: 'GET', postBody: null, onComplete: function(request) {
							if(request.status == 200) {
								$('posts').innerHTML=request.responseText;
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
				$ajaxreloadpagemessage = str_replace("<timeout->secs>", $seces, $mybb->settings['ajaxreloadpage_message']);
				if ($mybb->settings['ajaxreloadpage_time'] > 0) {
					$ajaxreloadpage['head'] .= "setInterval(\"get_mybbir_ajax_answes('{$mybb->input['tid']}','{$page}')\", ".($mybb->settings['ajaxreloadpage_time']*1000).");";
				}
				$ajaxreloadpage['head'] .= "\n</script>\n";
			if($mybb->settings['ajaxreloadpage_viewbar'] >0) {
				$ajaxreloadpage['message'] = "<div style=\"background:#fcf2b7;font-size:11px;font-weight:bold;text-align:center;border-top:2px solid #f2c71e;border-bottom:2px solid #f2c71e;padding:5px;margin-top:3px;margin-bottom:3px;cursor: pointer;\" onclick=\"get_mybbir_ajax_answes('{$mybb->input['tid']}','{$page}');\">
						{$ajaxreloadpagemessage}<a href=\"http://my-bb.ir\" title=\"My-BB.Ir\" target=\"_blank\" style=\"color:#fcf2b7;font-size:0px;\">My-BB.Ir</a>
					</div>";
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