<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}
$arParams["BLOG_URL"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["BLOG_URL"]));
$arParams["SOCNET_GROUP_ID"] = IntVal($arParams["SOCNET_GROUP_ID"]);
$bSoNet = false;
$bGroupMode = false;
if (CModule::IncludeModule("socialnetwork") && (IntVal($arParams["SOCNET_GROUP_ID"]) > 0 || IntVal($arParams["USER_ID"]) > 0))
{
	$bSoNet = true;

	if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
		$bGroupMode = true;
	
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

if($arParams["SET_TITLE"]=="Y")
	$APPLICATION->SetTitle(GetMessage('B_B_HIDE_TITLE'));
if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(IntVal($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";
	
$arParams["PATH_TO_BLOG_CATEGORY"] = trim($arParams["PATH_TO_BLOG_CATEGORY"]);
if(strlen($arParams["PATH_TO_BLOG_CATEGORY"])<=0)
	$arParams["PATH_TO_BLOG_CATEGORY"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#"."&category=#category_id#");
	
$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = htmlspecialchars($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=post_edit&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);
	
$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);
	
$arResult["OK_MESSAGE"] = Array();
$arResult["ERROR_MESSAGE"] = Array();

$user_id = IntVal($USER->GetID());

if($bSoNet)
{
	$arFilterblg = Array(
		"ACTIVE" => "Y",
		"GROUP_ID" => $arParams["GROUP_ID"],
		"GROUP_SITE_ID" => SITE_ID,
		);
			
	$arResult["PostPerm"] = BLOG_PERMS_DENY;
	if($bGroupMode)
	{
		$arFilterblg["SOCNET_GROUP_ID"] = $arParams["SOCNET_GROUP_ID"];
		$arResult["PostPerm"] = BLOG_PERMS_DENY;
		if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin()) || $APPLICATION->GetGroupRight("blog") >= "W")
			$arResult["PostPerm"] = BLOG_PERMS_FULL;
		elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "moderate_post"))
			$arResult["PostPerm"] = BLOG_PERMS_MODERATE;
		elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "write_post"))
			$arResult["PostPerm"] = BLOG_PERMS_WRITE;
		elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "premoderate_post"))
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
	$arBlog = CBlog::GetByUrl($arParams["BLOG_URL"], $arParams["GROUP_ID"]);
	$arResult["PostPerm"] = CBlog::GetBlogUserPostPerms($arBlog["ID"], $user_id);
}

if(!empty($arBlog) && $arBlog["ACTIVE"] == "Y")
{
		$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
		if($arGroup["SITE_ID"] == SITE_ID)
		{
			$arResult["BLOG"] = $arBlog;
			
			if($arParams["SET_TITLE"]=="Y")
				$APPLICATION->SetTitle(str_replace("#NAME#", $arBlog["NAME"], GetMessage("B_B_HIDE_TITLE_BLOG")));
			if($arParams["SET_NAV_CHAIN"]=="Y")
				$APPLICATION->AddChainItem($arBlog["NAME"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "group_id" => $arParams["SOCNET_GROUP_ID"])));

			if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
			{
				$errorMessage = "";
				$okMessage = "";
				if (IntVal($_GET["del_id"]) > 0)
				{
					if (check_bitrix_sessid() && 
					(!$bSoNet && CBlogPost::CanUserDeletePost(IntVal($_GET["del_id"]), $user_id) || 
						($bSoNet && CBlogSoNetPost::CanUserDeletePost(IntVal($_GET["del_id"]), $user_id, $arParams["USER_ID"], $arParams["SOCNET_GROUP_ID"])))
					)
					{
						$DEL_ID = IntVal($_GET["del_id"]);
						if (CBlogPost::Delete($DEL_ID))
						{
							$okMessage = GetMessage("B_B_HIDE_M_DEL");
						}
						else
							$errorMessage = GetMessage("B_B_HIDE_M_DEL_ERR");
					}
					else
						$errorMessage = GetMessage("B_B_HIDE_M_DEL_RIGHTS");
				}
				elseif (IntVal($_GET["show_id"]) > 0)
				{
					if($_GET["success"] == "Y") 
					{
						$okMessage = GetMessage("BLOG_BLOG_BLOG_MES_SHOWED");
					}
					else
					{
						if (check_bitrix_sessid())
						{
							$show_id = IntVal($_GET["show_id"]);
							if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
							{
								if(CBlogPost::GetByID($show_id))
								{
									if(CBlogPost::Update($show_id, Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH)))
									{
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/first_page/");
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/pages/");
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/calendar/");
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$show_id."/");
										BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
										BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
										BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
										BXClearCache(True, "/".SITE_ID."/blog/last_comments/");
										BXClearCache(True, "/".SITE_ID."/blog/groups/".$arBlog["GROUP_ID"]."/");
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/trackback/".$show_id."/");
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_out/");
										BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_all/");
										BXClearCache(True, "/".SITE_ID."/blog/rss_sonet/");
										BXClearCache(True, "/".SITE_ID."/blog/rss_all/");
										BXClearCache(True, "/".SITE_ID."/blog/last_messages_list_extranet/");
										

										LocalRedirect($APPLICATION->GetCurPageParam("show_id=".$show_id."&success=Y", Array("del_id", "sessid", "success", "show_id")));
									}
									else
										$errorMessage = GetMessage("BLOG_BLOG_BLOG_MES_SHOW_ERROR");
								}
							}
							else
								$errorMessage = GetMessage("BLOG_BLOG_BLOG_MES_SHOW_NO_RIGHTS");
						}
					}
				}


				if (StrLen($errorMessage) > 0)
					$arResult["ERROR_MESSAGE"][] = $errorMessage;
				if (StrLen($okMessage) > 0)
					$arResult["OK_MESSAGE"][] = $okMessage;			
				
				$arResult["POST"] = Array();
				$p = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arPostColl1 = Array();
				$arPostColl2 = Array();

				$dbPost = CBlogPost::GetList(
					array("DATE_PUBLISH" => "DESC"),
					Array(
							"BLOG_ID" => $arBlog["ID"],
							"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_READY
					),
					false,
					false,
					Array("ID", "BLOG_ID", "TITLE", "DATE_PUBLISH", "AUTHOR_ID", "DETAIL_TEXT", "BLOG_ACTIVE", "BLOG_URL", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "AUTHOR_ID", "BLOG_OWNER_ID", "VIEWS", "NUM_COMMENTS", "ATTACH_IMG", "BLOG_SOCNET_GROUP_ID", "DETAIL_TEXT_TYPE", "CATEGORY_ID")
				);
				while($arPost = $dbPost->Fetch())
				{
					$arPost = CBlogTools::htmlspecialcharsExArray($arPost);
					$arPost["urlToAuthor"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arPost["AUTHOR_ID"]));
					if($arPost["AUTHOR_ID"] == $arBlog["OWNER_ID"])
					{
						$arPost["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "group_id" => $arParams["SOCNET_GROUP_ID"], "user_id" => $arPost["AUTHOR_ID"]));
					}
					else
					{
						if($arOwnerBlog = CBlog::GetByOwnerID($arPost["AUTHOR_ID"], $arParams["GROUP_ID"]))
							$arPost["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arOwnerBlog["URL"], "group_id" => $arParams["SOCNET_GROUP_ID"], "user_id" => $arPost["AUTHOR_ID"]));
						if($bGroupMode)
							$arPost["urlToBlog"] = $arPost["urlToAuthor"];
					}

					$arPost["BlogUser"] = CBlogUser::GetByID($arPost["AUTHOR_ID"], BLOG_BY_USER_ID); 
					$arPost["BlogUser"] = CBlogTools::htmlspecialcharsExArray($arPost["BlogUser"]);
					$arPost["BlogUser"]["AVATAR_file"] = CFile::GetFileArray($arPost["BlogUser"]["AVATAR"]);
					if ($arPost["BlogUser"]["AVATAR_file"] !== false)
						$arPost["BlogUser"]["AVATAR_img"] = CFile::ShowImage($arPost["BlogUser"]["AVATAR_file"]["SRC"], 150, 150, "border=0 align='right'");
					
					$dbUser = CUser::GetByID($arPost["AUTHOR_ID"]);
					$arPost["arUser"] = $dbUser->GetNext();
					$arPost["AuthorName"] = CBlogUser::GetUserName($arPost["BlogUser"]["ALIAS"], $arPost["arUser"]["NAME"], $arPost["arUser"]["LAST_NAME"], $arPost["arUser"]["LOGIN"]);

					$arImages = array();
					$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost["ID"], "BLOG_ID"=>$arBlog["ID"]));
					while ($arImage = $res->Fetch())
						$arImages[$arImage['ID']] = $arImage['FILE_ID'];
						
					if($arPost["DETAIL_TEXT_TYPE"] == "html" && COption::GetOptionString("blog","allow_html", "N") == "Y")
					{
					
						$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "QUOTE" => "Y", "CODE" => "Y");
						if(COption::GetOptionString("blog","allow_video", "Y") != "Y")
							$arAllow["VIDEO"] = "N";
						$arPost["TEXT_FORMATED"] = $p->convert($arPost["~DETAIL_TEXT"], true, $arImages, $arAllow);
					}
					else
					{
						$arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y");
						if(COption::GetOptionString("blog","allow_video", "Y") != "Y")
							$arAllow["VIDEO"] = "N";
					
						$arPost["TEXT_FORMATED"] = $p->convert($arPost["~DETAIL_TEXT"], true, $arImages, $arAllow);
					}

					$arPost["IMAGES"] = $arImages;
					
					if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
						$arPost["urlToShow"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("show_id=".$arPost["ID"].'&'.bitrix_sessid_get(), Array("del_id", "sessid", "show_id", "success")));
					
					if($arResult["PostPerm"]>=BLOG_PERMS_FULL || $arPost["AUTHOR_ID"] == $user_id)
						$arPost["urlToEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("blog" => $arBlog["URL"], "post_id"=>$arPost["ID"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
						
					if($arResult["PostPerm"]>=BLOG_PERMS_FULL)
						$arPost["urlToDelete"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("del_id=".$arPost["ID"].'&'.bitrix_sessid_get(), Array("del_id", "sessid", "show_id", "success")));
					
					if(strlen($arPost["CATEGORY_ID"])>0)
					{
						$arCategory = explode(",",$arPost["CATEGORY_ID"]);
						foreach($arCategory as $v)
						{
							if(IntVal($v)>0)
							{
								$arCatTmp = CBlogTools::htmlspecialcharsExArray(CBlogCategory::GetByID($v));
								$arCatTmp["urlToCategory"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG_CATEGORY"], array("blog" => $arBlog["URL"], "category_id" => $v, "group_id" => $arParams["SOCNET_GROUP_ID"], "user_id" => $arParams["USER_ID"]));
								$arPost["CATEGORY"][] = $arCatTmp;
							}
						}
					}

					$arPost["DATE_PUBLISH_FORMATED"] = date($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($arPost["DATE_PUBLISH"], CSite::GetDateFormat("FULL")));
					$arPost["DATE_PUBLISH_DATE"] = ConvertDateTime($arPost["DATE_PUBLISH"], FORMAT_DATE);
					$arPost["DATE_PUBLISH_TIME"] = ConvertDateTime($arPost["DATE_PUBLISH"], "HH:MI");
					$arPost["DATE_PUBLISH_D"] = ConvertDateTime($arPost["DATE_PUBLISH"], "DD");
					$arPost["DATE_PUBLISH_M"] = ConvertDateTime($arPost["DATE_PUBLISH"], "MM");
					$arPost["DATE_PUBLISH_Y"] = ConvertDateTime($arPost["DATE_PUBLISH"], "YYYY");

					$arResult["POST"][] = $arPost;
				}
			}
			else
				$arResult["FATAL_ERROR"] = GetMessage("B_B_HIDE_NO_R_CR");
		}
		else
			$arResult["FATAL_ERROR"] = GetMessage("BLOG_BLOG_BLOG_NO_BLOG");
}
else
{
	$arResult["FATAL_ERROR"] = GetMessage("BLOG_BLOG_BLOG_NO_BLOG");
	CHTTP::SetStatus("404 Not Found");
}
	
$this->IncludeComponentTemplate();
?>