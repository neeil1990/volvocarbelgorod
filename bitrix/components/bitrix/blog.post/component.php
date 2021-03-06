<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}

$arParams["SOCNET_GROUP_ID"] = IntVal($arParams["SOCNET_GROUP_ID"]);
$bSoNet = false;
$bGroupMode = false;
if (CModule::IncludeModule("socialnetwork") && (IntVal($arParams["SOCNET_GROUP_ID"]) > 0 || IntVal($arParams["USER_ID"]) > 0))
{
	$bSoNet = true;

	if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
		$bGroupMode = true;

	if (array_key_exists("USE_SHARE", $arParams) && $arParams["USE_SHARE"] == "Y")
		if (!CSocNetFeaturesPerms::CanPerformOperation(false, ($bGroupMode ? SONET_ENTITY_GROUP : SONET_ENTITY_USER), ($bGroupMode ? $arParams["SOCNET_GROUP_ID"] : $arParams["USER_ID"]), "blog", "view_post"))
			$arParams["USE_SHARE"] = "N";
					
	if($bGroupMode)
	{
		if(!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog"))
		{
			ShowError(GetMessage("BLOG_SONET_MODULE_NOT_AVAIBLE"));
			return;
		}
	}
	else
	{
		if (!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $arParams["USER_ID"], "blog"))
		{
			ShowError(GetMessage("BLOG_SONET_MODULE_NOT_AVAIBLE"));
			return;
		}
	}
}

$arParams["ID"] = intval($arParams["~ID"]);
if($arParams["ID"] > 0 && $arParams["ID"]."" != $arParams["~ID"])
{
	ShowError(GetMessage("B_B_MES_NO_POST"));
	@define("ERROR_404", "Y");
	CHTTP::SetStatus("404 Not Found");
	return;
}

$arParams["BLOG_URL"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["BLOG_URL"]));

if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(IntVal($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";
	
$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_BLOG_CATEGORY"] = trim($arParams["PATH_TO_BLOG_CATEGORY"]);
if(strlen($arParams["PATH_TO_BLOG_CATEGORY"])<=0)
	$arParams["PATH_TO_BLOG_CATEGORY"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#"."&category=#category_id#");
	
$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=post_edit&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");
	
$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);

if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (!array_key_exists("PATH_TO_MESSAGES_CHAT", $arParams))
	$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
if (!array_key_exists("PATH_TO_VIDEO_CALL", $arParams))
	$arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#user_id#/";

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = GetMessage('B_B_MES_NAME_TEMPLATE_DEFAULT');
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";	

if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
else
	$arParams["CACHE_TIME"] = 0;	
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);
$arParams["SHOW_RATING"] = ($arParams["SHOW_RATING"] == "Y" ? "Y" : "N");

$arPost = CBlogPost::GetByID($arParams["ID"]);
if(!empty($arPost) && $arPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH) 
	unset($arPost);
if(!empty($arPost))
{
	CBlogPost::CounterInc($arParams["ID"]);
	//BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
	//BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
	//BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");

	$arPost = CBlogTools::htmlspecialcharsExArray($arPost);
	$arResult["Post"] = $arPost;
	
	$user_id = $USER->GetID();
	$arResult["USER_ID"] = $user_id;
	
	if($bSoNet)
	{

		$arFilterblg = Array(
		    "ACTIVE" => "Y",
			"GROUP_ID" => $arParams["GROUP_ID"],
			"GROUP_SITE_ID" => SITE_ID,
			"USE_SOCNET" => "Y",
			);

		$arResult["PostPerm"] = BLOG_PERMS_DENY;
		if($bGroupMode)
		{
			$arGroupTmp = CSocNetGroup::GetByID($arParams["SOCNET_GROUP_ID"]);

			if ($arGroupTmp["CLOSED"] == "Y" && COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
				$arResult["HideArchiveLinks"] = true;
			$arFilterblg["SOCNET_GROUP_ID"] = $arParams["SOCNET_GROUP_ID"];
			
			$arResult["PostPerm"] = BLOG_PERMS_DENY;
			if (!$arResult["HideArchiveLinks"] && CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin()))
				$arResult["PostPerm"] = BLOG_PERMS_FULL;
			elseif (!$arResult["HideArchiveLinks"] && CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "moderate_post"))
				$arResult["PostPerm"] = BLOG_PERMS_MODERATE;
			elseif (!$arResult["HideArchiveLinks"] && CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "write_post"))
				$arResult["PostPerm"] = BLOG_PERMS_WRITE;
			elseif (!$arResult["HideArchiveLinks"] && CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "premoderate_post"))
				$arResult["PostPerm"] = BLOG_PERMS_PREMODERATE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "view_post"))
				$arResult["PostPerm"] = BLOG_PERMS_READ;
		}
		else
		{
			$arFilterblg["OWNER_ID"] = $arParams["USER_ID"];
			$arResult["PostPerm"] = BLOG_PERMS_DENY;
			if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin()) || $APPLICATION->GetGroupRight("blog") >= "W" || $arParams["USER_ID"] == $user_id)
				$arResult["PostPerm"] = BLOG_PERMS_FULL;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "moderate_post"))
				$arResult["PostPerm"] = BLOG_PERMS_MODERATE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "write_post"))
				$arResult["PostPerm"] = BLOG_PERMS_WRITE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "premoderate_post"))
				$arResult["PostPerm"] = BLOG_PERMS_PREMODERATE;
			elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "view_post"))
				$arResult["PostPerm"] = BLOG_PERMS_READ;
		}
		$dbBl = CBlog::GetList(Array(), $arFilterblg);
		$arBlog = $dbBl ->Fetch();
	}
	else
	{
		$arResult["PostPerm"] = CBlogPost::GetBlogUserPostPerms($arParams["ID"], $arResult["USER_ID"]);
		$arBlog = CBlog::GetByUrl($arParams["BLOG_URL"], $arParams["GROUP_ID"]);
	}

	$arBlog = CBlogTools::htmlspecialcharsExArray($arBlog);
	$arResult["Blog"] = $arBlog;
	$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
	if(!empty($arBlog) && $arBlog["ID"] == $arPost["BLOG_ID"] && $arBlog["ACTIVE"] == "Y" && $arGroup["SITE_ID"] == SITE_ID)
	{
		if($bSoNet)			
			$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array( "user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
		elseif($arPost["AUTHOR_ID"] == $arBlog["OWNER_ID"])
			$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
		else
		{
			$arOwnerBlog = CBlog::GetByOwnerID($arPost["AUTHOR_ID"], $arParams["GROUP_ID"]);
			$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arOwnerBlog["URL"], "user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
		}

		$arResult["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("blog" => ($arPost["AUTHOR_ID"] == $arBlog["OWNER_ID"] ? $arBlog["URL"] : $arOwnerBlog["URL"]), "post_id"=>$arResult["Post"]["ID"], "user_id" => $arPost["AUTHOR_ID"]));

		if($_GET["become_friend"]=="Y"  && $arResult["PostPerm"]<BLOG_PERMS_READ && !$bSoNet)
		{
			if($USER->IsAuthorized())
			{
				$dbCandidate = CBlogCandidate::GetList(Array(), Array("BLOG_ID"=>$arBlog["ID"], "USER_ID"=>$arResult["USER_ID"]));
				if($arCandidate = $dbCandidate->Fetch())
				{
					$arResult["MESSAGE"] = GetMessage("B_B_MES_REQUEST_ALREADY")."<br />";
				}
				else
				{
					if(CBlogCandidate::Add(Array("BLOG_ID"=>$arBlog["ID"], "USER_ID"=>$arResult["USER_ID"])))
					{
						$arResult["MESSAGE"] = GetMessage("B_B_MES_REQUEST_ADDED")."<br />";
						
						$BlogUser = CBlogUser::GetByID($user_id, BLOG_BY_USER_ID); 
						$BlogUser = CBlogTools::htmlspecialcharsExArray($BlogUser);
						
						$dbUser = CUser::GetByID($user_id);
						$arUser = $dbUser->GetNext();
						$AuthorName = CBlogUser::GetUserName($BlogUser["ALIAS"], $arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"]);
						$dbUser = CUser::GetByID($arResult["BLOG"]["OWNER_ID"]);
						$arUserBlog = $dbUser->GetNext();
						if (strlen($serverName) <=0)
						{
							if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
								$serverName = SITE_SERVER_NAME;
							else
								$serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
						}

						$arMailFields = Array(
								"BLOG_ID" => $arBlog["ID"],
								"BLOG_NAME" => $arBlog["NAME"],
								"BLOG_URL" => $arBlog["URL"],
								"BLOG_ADR" => "http://".$serverName.CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_BLOG"]), array("blog" => $arBlog["URL"])),
								"USER_ID" => $user_id,
								"USER" => $AuthorName,
								"USER_URL" => "http://".$serverName.CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_USER"]), array("user_id" => $user_id)),
								"EMAIL_FROM" => COption::GetOptionString("main","email_from", "nobody@nobody.com"),
							);
						$arF1 = $arF2 = $arMailFields;
						$arF1["EMAIL_TO"] = $arUser["EMAIL"];
						$arF2["EMAIL_TO"] = $arUserBlog["EMAIL"];
						CEvent::Send("BLOG_YOU_TO_BLOG", SITE_ID, $arF1);							
						CEvent::Send("BLOG_USER_TO_YOUR_BLOG", SITE_ID, $arF2);

					}
					else
						$arResult["ERROR_MESSAGE"] = GetMessage("B_B_MES_REQUEST_ERROR")."<br />";
				}
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("B_B_MES_REQUEST_AUTH")."<br />";
		}

		if ($_GET["delete"]=="Y")
		{
			if (check_bitrix_sessid() && (!$bSoNet && CBlogPost::CanUserDeletePost(IntVal($arParams["ID"]), ($USER->IsAuthorized() ? $arResult["USER_ID"] : 0 )) || ($bSoNet && CBlogSoNetPost::CanUserDeletePost(IntVal($arParams["ID"]), $user_id, $arParams["USER_ID"], $arParams["SOCNET_GROUP_ID"]))))
			{
				if (CBlogPost::Delete($arParams["ID"]))
				{
					//$okMessage = GetMessage("BLOG_BLOG_BLOG_MES_DELED");

					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/pages/");
					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/first_page/");
					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/calendar/");
					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$arParams["ID"]."/");
					BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
					BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
					BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
					BXClearCache(True, "/".SITE_ID."/blog/last_comments/");
					BXClearCache(True, "/".SITE_ID."/blog/groups/".$arBlog["GROUP_ID"]."/");
					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/trackback/".$arParams["ID"]."/");
					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_out/");
					BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_all/");
					BXClearCache(True, "/".SITE_ID."/blog/rss_sonet/");
					BXClearCache(True, "/".SITE_ID."/blog/rss_all/");
					BXClearCache(True, "/".SITE_ID."/blog/last_messages_list_extranet/");
					$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arBlog["SOCNET_GROUP_ID"]));
					if(strpos("?", $url) === false)
						$url .= "?";
					else
						$url .= "&";
					$url .= "del_id=".$arParams["ID"]."&success=Y";
					
					LocalRedirect($url);
				}
				else
					$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR").'<br />';
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_NO_RIGHTS").'<br />';
		}
		if ($_GET["hide"]=="Y")
		{
			if (check_bitrix_sessid())
			{
				if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
				{
					if(CBlogPost::Update($arParams["ID"], Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_READY)))
					{					
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/first_page/");
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/pages/");
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/calendar/");
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$arParams["ID"]."/");
						BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
						BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
						BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
						BXClearCache(True, "/".SITE_ID."/blog/last_comments/");
						BXClearCache(True, "/".SITE_ID."/blog/groups/".$arBlog["GROUP_ID"]."/");
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/trackback/".$arParams["ID"]."/");
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_out/");
						BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_all/");
						BXClearCache(True, "/".SITE_ID."/blog/rss_sonet/");
						BXClearCache(True, "/".SITE_ID."/blog/rss_all/");
						BXClearCache(True, "/".SITE_ID."/blog/last_messages_list_extranet/");
						
						$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arBlog["SOCNET_GROUP_ID"]));
						if(strpos("?", $url) === false)
							$url .= "?";
						else
							$url .= "&";
						$url .= "hide_id=".$arParams["ID"]."&success=Y";
						
						LocalRedirect($url);
					}
					else
						$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_HIDE_ERROR").'<br />';
				}
				else
					$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_HIDE_NO_RIGHTS").'<br />';
			}
		}

		if($arResult["PostPerm"] > BLOG_PERMS_DENY)
		{
			if(!empty($arPost) && $arPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_DRAFT)
			{
				if($arPost["PUBLISH_STATUS"] == "P" || $arResult["PostPerm"] == BLOG_PERMS_FULL || $arPost["AUTHOR_ID"] == $arResult["USER_ID"])
				{
/*				
					if($bGroupMode)
						$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));;
*/
					if($arParams["SET_TITLE"]=="Y")
						$APPLICATION->SetTitle($arPost["TITLE"]);

					if($arParams["SET_NAV_CHAIN"]=="Y")
						$APPLICATION->AddChainItem($arBlog["NAME"], CComponentEngine::MakePathFromTemplate(htmlspecialcharsback($arParams["PATH_TO_BLOG"]), array("blog" => $arBlog["URL"], "user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"])));

					$cache = new CPHPCache;
					$cache_id = "blog_message_".serialize($arParams)."_".$arResult["PostPerm"];
					$cache_path = "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$arPost["ID"]."/";

					if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
					{
						$APPLICATION->AddHeadString('<script type="text/javascript" src="/bitrix/components/bitrix/player/wmvplayer/wmvscript.js"></script>', true);
						$APPLICATION->AddHeadString('<script type="text/javascript" src="/bitrix/components/bitrix/player/wmvplayer/silverlight.js"></script>', true); 
						$APPLICATION->AddHeadString('<script type="text/javascript" src="/bitrix/components/bitrix/player/wmvplayer/wmvplayer.js"></script>', true);
						$APPLICATION->AddHeadString('<script type="text/javascript" src="/bitrix/components/bitrix/player/mediaplayer/flvscript.js"></script>', true);
						$Vars = $cache->GetVars();
						foreach($Vars["arResult"] as $k=>$v)
							$arResult[$k] = $v;
						CBitrixComponentTemplate::ApplyCachedData($Vars["templateCachedData"]);	
						$cache->Output();
					}
					else
					{
						if ($arParams["CACHE_TIME"] > 0)
							$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);

						$arResult["urlToAuthor"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));

						$p = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
						
						$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost['ID'], "BLOG_ID"=>$arBlog['ID']));
						while ($arImage = $res->Fetch())
							$arImages[$arImage['ID']] = $arImage['FILE_ID'];

						if($arPost["DETAIL_TEXT_TYPE"] == "html" && COption::GetOptionString("blog","allow_html", "N") == "Y")
						{
							$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "QUOTE" => "Y", "CODE" => "Y");
							if(COption::GetOptionString("blog","allow_video", "Y") != "Y")
								$arAllow["VIDEO"] = "N";
						
							$arResult["Post"]["textFormated"] = $p->convert($arPost["~DETAIL_TEXT"], false, $arImages, $arAllow);
						}
						else
						{
							$arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y");
							if(COption::GetOptionString("blog","allow_video", "Y") != "Y")
								$arAllow["VIDEO"] = "N";
						
							$arResult["Post"]["textFormated"] = $p->convert($arPost["~DETAIL_TEXT"], false, $arImages, $arAllow);
						}
						$arResult["Post"]["DATE_PUBLISH_FORMATED"] = date($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($arResult["Post"]["DATE_PUBLISH"], CSite::GetDateFormat("FULL")));
						$arResult["Post"]["DATE_PUBLISH_DATE"] = ConvertDateTime($arResult["Post"]["DATE_PUBLISH"], FORMAT_DATE);
						$arResult["Post"]["DATE_PUBLISH_TIME"] = ConvertDateTime($arResult["Post"]["DATE_PUBLISH"], "HH:MI");
						$arResult["Post"]["DATE_PUBLISH_D"] = ConvertDateTime($arResult["Post"]["DATE_PUBLISH"], "DD");
						$arResult["Post"]["DATE_PUBLISH_M"] = ConvertDateTime($arResult["Post"]["DATE_PUBLISH"], "MM");
						$arResult["Post"]["DATE_PUBLISH_Y"] = ConvertDateTime($arResult["Post"]["DATE_PUBLISH"], "YYYY");

						
						$arResult["BlogUser"] = CBlogUser::GetByID($arPost["AUTHOR_ID"], BLOG_BY_USER_ID); 
						$arResult["BlogUser"] = CBlogTools::htmlspecialcharsExArray($arResult["BlogUser"]);
						$dbUser = CUser::GetByID($arPost["AUTHOR_ID"]);
						$arResult["arUser"] = $dbUser->GetNext();
						$arResult["AuthorName"] = CBlogUser::GetUserName($arResult["BlogUser"]["ALIAS"], $arResult["arUser"]["NAME"], $arResult["arUser"]["LAST_NAME"], $arResult["arUser"]["LOGIN"]);
						
						if($arResult["PostPerm"] > BLOG_PERMS_MODERATE || ($arResult["PostPerm"]>=BLOG_PERMS_WRITE && $arPost["AUTHOR_ID"] == $arResult["USER_ID"]))
							$arResult["urlToEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("blog" => $arBlog["URL"], "post_id"=>$arPost["ID"], "user_id" => $arParams["USER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
						if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
							$arResult["urlToHide"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("hide=Y", Array("del_id", "sessid", "success", "hide", "delete")));
						if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
							$arResult["urlToDelete"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("delete=Y", Array("sessid", "delete", "hide")));
						
						$arResult["BlogUser"]["AVATAR_file"] = CFile::GetFileArray($arResult["BlogUser"]["AVATAR"]);
						if ($arResult["BlogUser"]["AVATAR_file"] !== false)
							$arResult["BlogUser"]["AVATAR_img"] = CFile::ShowImage($arResult["BlogUser"]["AVATAR_file"]["SRC"], 150, 150, "border=0 align='right'");

						if(strlen($arPost["CATEGORY_ID"])>0)
						{
							$arCategory = explode(",",$arPost["CATEGORY_ID"]);
							foreach($arCategory as $v)
							{
								if(IntVal($v)>0)
								{
									$arCatTmp = CBlogTools::htmlspecialcharsExArray(CBlogCategory::GetByID($v));
									$arCatTmp["urlToCategory"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG_CATEGORY"], array("blog" => $arBlog["URL"], "category_id" => $v, "group_id" => $arParams["SOCNET_GROUP_ID"], "user_id" => $arParams["USER_ID"]));
									$arResult["Category"][] = $arCatTmp;
								}
							}
						}
						
						$arResult["POST_PROPERTIES"] = array("SHOW" => "N");
			
						if (!empty($arParams["POST_PROPERTY"]))
						{
							$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_POST", $arPost["ID"], LANGUAGE_ID);
			
							if (count($arParams["POST_PROPERTY"]) > 0)
							{
								foreach ($arPostFields as $FIELD_NAME => $arPostField)
								{
									if (!in_array($FIELD_NAME, $arParams["POST_PROPERTY"]))
										continue;
									$arPostField["EDIT_FORM_LABEL"] = strLen($arPostField["EDIT_FORM_LABEL"]) > 0 ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"];
									$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["EDIT_FORM_LABEL"]);
									$arPostField["~EDIT_FORM_LABEL"] = $arPostField["EDIT_FORM_LABEL"];
									$arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;
								}
							}
							if (!empty($arResult["POST_PROPERTIES"]["DATA"]))
								$arResult["POST_PROPERTIES"]["SHOW"] = "Y";
						}
						
						
						if ($arParams["CACHE_TIME"] > 0)
							$cache->EndDataCache(array("templateCachedData"=>$this-> GetTemplateCachedData(), "arResult"=>$arResult));
					}
					if($arParams["SHOW_RATING"] == "Y" && !empty($arResult["Post"]))
						$arResult['RATING'] = CRatings::GetRatingVoteResult('BLOG_POST', $arResult["Post"]["ID"]);
				}
				else
					$arResult["ERROR_MESSAGE"] .= GetMessage("B_B_MES_NO_RIGHTS")."<br />";
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("B_B_MES_NO_MES")."<br />";
		}
		elseif($_GET["become_friend"]!="Y" && !$bSoNet)
		{
			$arResult["NOTE_MESSAGE"] .= GetMessage("B_B_MES_FR_ONLY").'<br />';
			if($USER->IsAuthorized())
				$arResult["NOTE_MESSAGE"] .= GetMessage("B_B_MES_U_CAN").' <a href="'.$APPLICATION->GetCurPageParam("become_friend=Y", Array("become_friend")).'">'.GetMessage("B_B_MES_U_CAN1").'</a> '.GetMessage("B_B_MES_U_CAN2").'</br />';
			else
				$arResult["NOTE_MESSAGE"] .= GetMessage("B_B_MES_U_AUTH").'<br />';
		}
		else
			$arResult["FATAL_MESSAGE"] .= GetMessage("B_B_MES_NO_RIGHTS")."<br />";
	}
	else
	{
		$arResult["FATAL_MESSAGE"] = GetMessage("B_B_MES_NO_BLOG");
		CHTTP::SetStatus("404 Not Found");
	}
}
else
{
	$arResult["FATAL_MESSAGE"] = GetMessage("B_B_MES_NO_POST");
	CHTTP::SetStatus("404 Not Found");
}
	
$this->IncludeComponentTemplate();
?>