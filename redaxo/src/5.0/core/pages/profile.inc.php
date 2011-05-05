<?php
/**
 *
 * @package redaxo5
 * @version svn:$Id$
 */

$info = '';
$warning = '';
$user_id = $REX['USER']->getValue('user_id');

// Allgemeine Infos
$userpsw       = rex_request('userpsw', 'string');
$userpsw_new_1 = rex_request('userpsw_new_1', 'string');
$userpsw_new_2 = rex_request('userpsw_new_2', 'string');

$username = rex_request('username', 'string');
$userdesc = rex_request('userdesc', 'string');

// --------------------------------- Title

rex_title(rex_i18n::msg('profile_title'),'');

// --------------------------------- BE LANG

// backend sprache
$sel_be_sprache = new rex_select;
$sel_be_sprache->setStyle('class="rex-form-select"');
$sel_be_sprache->setSize(1);
$sel_be_sprache->setName("userperm_be_sprache");
$sel_be_sprache->setId("userperm-mylang");
$sel_be_sprache->addOption("default","");

$saveLocale = rex_i18n::getLocale();
$langs = array();
foreach(rex_i18n::getLocales() as $locale)
{
	rex_i18n::setLocale($locale,FALSE); // Locale nicht neu setzen
  $sel_be_sprache->addOption(rex_i18n::msg('lang'), $locale);
  $langs[$locale] = rex_i18n::msg('lang');
}
rex_i18n::setLocale($saveLocale, false);
$userperm_be_sprache = rex_request('userperm_be_sprache', 'string');
$userperm_be_sprache_selected = '';
foreach($langs as $k => $v)
{
	if ($REX['LOGIN']->USER->hasPerm('be_lang['.$k.']'))
	{
	  $userperm_be_sprache_selected = $k;
	}
}


// --------------------------------- FUNCTIONS

if (rex_post('upd_profile_button', 'string'))
{
  $updateuser = rex_sql::factory();
  $updateuser->setTable($REX['TABLE_PREFIX'].'user');
  $updateuser->setWhere('user_id='. $user_id);
  $updateuser->setValue('name',$username);
  $updateuser->setValue('description',$userdesc);

  // set be langauage
  $userperm_be_sprache = rex_request("userperm_be_sprache","string");
  if(!isset($langs[$userperm_be_sprache]))
    $userperm_be_sprache = "default";
  $userperm_be_sprache_selected = $userperm_be_sprache;

  $rights = $REX['USER']->removePerm('be_lang');
  $rights .= 'be_lang['.$userperm_be_sprache.']#';
  $updateuser->setValue('rights',$rights);

  $updateuser->addGlobalUpdateFields();

  if($updateuser->update())
    $info = rex_i18n::msg('user_data_updated');
  else
    $warning = $updateuser->getError();
}


if (rex_post('upd_psw_button', 'string'))
{
  $updateuser = rex_sql::factory();
  $updateuser->setTable($REX['TABLE_PREFIX'].'user');
  $updateuser->setWhere('user_id='. $user_id);

  // the service side encryption of pw is only required
  // when not already encrypted by client using javascript
  if ($REX['PSWFUNC'] != '' && rex_post('javascript') == '0')
    $userpsw = call_user_func($REX['PSWFUNC'],$userpsw);

  if($userpsw != '' && $REX['USER']->getValue('psw') == $userpsw && $userpsw_new_1 != '' && $userpsw_new_1 == $userpsw_new_2)
  {
    // the service side encryption of pw is only required
    // when not already encrypted by client using javascript
    if ($REX['PSWFUNC'] != '' && rex_post('javascript') == '0')
      $userpsw_new_1 = call_user_func($REX['PSWFUNC'],$userpsw_new_1);

    $updateuser->setValue('psw',$userpsw_new_1);
    $updateuser->addGlobalUpdateFields();

    if($updateuser->update())
      $info = rex_i18n::msg('user_psw_updated');
    else
      $warning = $updateuser->getError();

  }else
  {
  	$warning = rex_i18n::msg('user_psw_error');
  }

}


$sel_be_sprache->setSelected($userperm_be_sprache_selected);



// ---------------------------------- ERR MSG

if ($info != '')
  echo rex_info($info);

if ($warning != '')
  echo rex_warning($warning);

// --------------------------------- FORMS

$sql = new rex_login_sql;
$sql->setQuery('select * from '. $REX['TABLE_PREFIX'] .'user where user_id='. $user_id);
if ($sql->getRows()!=1)
{
  echo rex_warning('You have no permission to this area!');
}
else
{
  // $userpsw = $sql->getValue($REX['TABLE_PREFIX'].'user.psw');
  $username = $sql->getValue($REX['TABLE_PREFIX'].'user.name');
  $userdesc = $sql->getValue($REX['TABLE_PREFIX'].'user.description');

  echo '
    <div class="rex-form" id="rex-form-profile">
    <form action="index.php" method="post">
      <fieldset class="rex-form-col-2">
        <legend>'.rex_i18n::msg('profile_myprofile').'</legend>

        <div class="rex-form-wrapper">
          <input type="hidden" name="page" value="profile" />

					<div class="rex-form-row">
						<p class="rex-form-col-a rex-form-read">
              <label for="userlogin">'. htmlspecialchars(rex_i18n::msg('login_name')).'</label>
              <span class="rex-form-read" id="userlogin">'. htmlspecialchars($sql->getValue($REX['TABLE_PREFIX'].'user.login')) .'</span>
						</p>

	          <p class="rex-form-col-b rex-form-select">
	            <label for="userperm-mylang">'.rex_i18n::msg('backend_language').'</label>
	            '.$sel_be_sprache->get().'
	          </p>
					</div>

					<div class="rex-form-row">
						<p class="rex-form-col-a rex-form-text">
              <label for="username">'.rex_i18n::msg('name').'</label>
              <input class="rex-form-text" type="text" id="username" name="username" value="'.htmlspecialchars($username).'" />
            </p>
						<p class="rex-form-col-b rex-form-text">
              <label for="userdesc">'.rex_i18n::msg('description').'</label>
              <input class="rex-form-text" type="text" id="userdesc" name="userdesc" value="'.htmlspecialchars($userdesc).'" />
            </p>
      		</div>

      	</div>
      </fieldset>

      <fieldset class="rex-form-col-1">
        <div class="rex-form-wrapper">
          <div class="rex-form-row">
						<p class="rex-form-col-a rex-form-submit">
            	<input class="rex-form-submit" type="submit" name="upd_profile_button" value="'.rex_i18n::msg('profile_save').'" '. rex_accesskey(rex_i18n::msg('profile_save'), $REX['ACKEY']['SAVE']) .' />
            </p>
          </div>
        </div>
      </fieldset>
    </form>
    </div>
  ';

  echo '<p>&nbsp;</p>
    <div class="rex-form" id="rex-form-profile-psw">
    <form action="index.php" method="post" id="pwformular">
      <input type="hidden" name="javascript" value="0" id="javascript" />
      <fieldset class="rex-form-col-2">
        <legend>'.rex_i18n::msg('profile_changepsw').'</legend>

        <div class="rex-form-wrapper">
          <input type="hidden" name="page" value="profile" />

					<div class="rex-form-row">
			    	<p class="rex-form-col-a rex-form-text">
              			<label for="userpsw">'.rex_i18n::msg('old_password').'</label>
							<input class="rex-form-text" type="password" id="userpsw" name="userpsw" autocomplete="off" />
						</p>
					</div>


					<div class="rex-form-row">
			    	<p class="rex-form-col-a rex-form-text">
             				 <label for="userpsw">'.rex_i18n::msg('new_password').'</label>
							<input class="rex-form-text" type="password" id="userpsw_new_1" name="userpsw_new_1" autocomplete="off" />
						</p>
			    	<p class="rex-form-col-b rex-form-text">
              				<label for="userpsw">'.rex_i18n::msg('new_password_repeat').'</label>
							<input class="rex-form-text" type="password" id="userpsw_new_2" name="userpsw_new_2" autocomplete="off" />
						</p>
					</div>

      	</div>
      </fieldset>

      <fieldset class="rex-form-col-1">
        <div class="rex-form-wrapper">
          <div class="rex-form-row">
						<p class="rex-form-col-a rex-form-submit">
            	<input class="rex-form-submit" type="submit" name="upd_psw_button" value="'.rex_i18n::msg('profile_save_psw').'" '. rex_accesskey(rex_i18n::msg('profile_save_psw'), $REX['ACKEY']['SAVE']) .' />
            </p>
          </div>
        </div>
      </fieldset>
    </form>
    </div>

    <script type="text/javascript">
       <!--
      jQuery(function($) {
        $("#username").focus();

        $("#pwformular")
          .submit(function(){
          	var pwInp0 = $("#userpsw");
          	if(pwInp0.val() != "")
          	{
            	pwInp0.val(Sha1.hash(pwInp0.val()));
          	}

          	var pwInp1 = $("#userpsw_new_1");
          	if(pwInp1.val() != "")
          	{
            	pwInp1.val(Sha1.hash(pwInp1.val()));
          	}

          	var pwInp2 = $("#userpsw_new_2");
          	if(pwInp2.val() != "")
          	{
          		pwInp2.val(Sha1.hash(pwInp2.val()));
          	}
        });

        $("#javascript").val("1");
      });
       //-->
    </script>';
}