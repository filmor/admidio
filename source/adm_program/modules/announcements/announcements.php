<?php
/******************************************************************************
 * Show a list of all announcements
 *
 * Copyright    : (c) 2004 - 2012 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Parameters:
 *
 * start     - Angabe, ab welchem Datensatz Ankuendigungen angezeigt werden sollen
 * headline  - Ueberschrift, die ueber den Ankuendigungen steht
 *             (Default) Ankuendigungen
 * id        - Nur eine einzige Annkuendigung anzeigen lassen.
 * date      - Alle Ankuendigungen zu einem Datum werden aufgelistet
 *             Uebergabeformat: YYYYMMDD
 *
 *****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/classes/table_announcement.php');
require_once('../../system/classes/module_announcements.php');
require_once('../../system/classes/module_menu.php');
unset($_SESSION['announcements_request']);

// pruefen ob das Modul ueberhaupt aktiviert ist
if ($gPreferences['enable_announcements_module'] == 0)
{
    // das Modul ist deaktiviert
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}
elseif($gPreferences['enable_announcements_module'] == 2)
{
    // nur eingeloggte Benutzer duerfen auf das Modul zugreifen
    require('../../system/login_valid.php');
}

// Initialize and check the parameters
$getStart    = admFuncVariableIsValid($_GET, 'start', 'numeric', 0);
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string', $gL10n->get('ANN_ANNOUNCEMENTS'));
$getAnnId    = admFuncVariableIsValid($_GET, 'id', 'numeric', 0);
$getDate     = admFuncVariableIsValid($_GET, 'date', 'numeric');

if(strlen($getDate) > 0)
{
	$getDate = substr($getDate,0,4). '-'. substr($getDate,4,2). '-'. substr($getDate,6,2);
}

// Navigation faengt hier im Modul an
$_SESSION['navigation']->clear();
$_SESSION['navigation']->addUrl(CURRENT_URL);

// Html-Kopf ausgeben
$gLayout['title']  = $getHeadline;
$gLayout['header'] = '
    <script type="text/javascript"><!--
        $(document).ready(function() 
        {
            $("a[rel=\'lnkDelete\']").colorbox({rel:\'nofollow\', scrolling:false, onComplete:function(){$("#admButtonNo").focus();}});
        }); 
    //--></script>';

if($gPreferences['enable_rss'] == 1)
{
    $gLayout['header'] .= '<link rel="alternate" type="application/rss+xml" title="'.$gL10n->get('SYS_RSS_FEED_FOR_VAR', $gCurrentOrganization->getValue('org_longname').' - '.$getHeadline).'"
        href="'.$g_root_path.'/adm_program/modules/announcements/rss_announcements.php?headline='.$getHeadline.'" />';
};

require(SERVER_PATH. '/adm_program/system/overall_header.php');

// show headline of module
echo '<h1 class="moduleHeadline">'.$getHeadline.'</h1>';

// create objects to manage the selected announcements
$announcements = new Announcements($getAnnId, $getDate);

// create module menu
$announcementsMenu = new ModuleMenu('admMenuAnnouncements');

if($gCurrentUser->editAnnouncements())
{
	// show link to create new announcement
	$announcementsMenu->addItem('admMenuItemNewAnnouncement', $g_root_path.'/adm_program/modules/announcements/announcements_new.php?headline='.$getHeadline, 
								$gL10n->get('SYS_CREATE_VAR', $getHeadline), 'add.png');
}

if($gCurrentUser->isWebmaster())
{
	// show link to system preferences of announcements
	$announcementsMenu->addItem('admMenuItemPreferencesAnnouncements', $g_root_path.'/adm_program/administration/organization/organization.php?show_option=ANN_ANNOUNCEMENTS', 
								$gL10n->get('SYS_MODULE_PREFERENCES'), 'options.png');
}

$announcementsMenu->show();

if($announcements->getAnnouncementsCount() == 0)
{
    // Keine Ankuendigungen gefunden
    if($getAnnId > 0)
    {
        echo '<p>'.$gL10n->get('SYS_NO_ENTRY').'</p>';
    }
    else
    {
        echo '<p>'.$gL10n->get('SYS_NO_ENTRIES').'</p>';
    }
}
else
{
    $getAnnouncements = $announcements->getAnnouncements($getStart);    
    $announcement = new TableAnnouncement($gDb);

    // Ankuendigungen auflisten
    foreach($getAnnouncements['announcements'] as $row)
    {
        $announcement->clear();
        $announcement->setArray($row);
        echo '
        <div class="boxLayout" id="ann_'.$announcement->getValue("ann_id").'">
            <div class="boxHead">
                <div class="boxHeadLeft">
                    <img src="'. THEME_PATH. '/icons/announcements.png" alt="'. $announcement->getValue("ann_headline"). '" />'.
                    $announcement->getValue("ann_headline"). '
                </div>
                <div class="boxHeadRight">'.$announcement->getValue("ann_timestamp_create", $gPreferences['system_date']).'&nbsp;';
                    
                    // aendern & loeschen duerfen nur User mit den gesetzten Rechten
                    if($gCurrentUser->editAnnouncements())
                    {
                        if($announcement->editRight() == true)
                        {
                            echo '
                            <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/announcements/announcements_new.php?ann_id='. $announcement->getValue('ann_id'). '&amp;headline='.$getHeadline.'"><img 
                                src="'. THEME_PATH. '/icons/edit.png" alt="'.$gL10n->get('SYS_EDIT').'" title="'.$gL10n->get('SYS_EDIT').'" /></a>';
                        }

                        // Loeschen darf man nur Ankuendigungen der eigenen Gliedgemeinschaft
                        if($announcement->getValue('ann_org_shortname') == $gCurrentOrganization->getValue('org_shortname'))
                        {
                            echo '
                            <a class="iconLink" rel="lnkDelete" href="'.$g_root_path.'/adm_program/system/popup_message.php?type=ann&amp;element_id=ann_'.
                                $announcement->getValue('ann_id').'&amp;name='.urlencode($announcement->getValue('ann_headline')).'&amp;database_id='.$announcement->getValue('ann_id').'"><img 
                                src="'. THEME_PATH. '/icons/delete.png" alt="'.$gL10n->get('SYS_DELETE').'" title="'.$gL10n->get('SYS_DELETE').'" /></a>';
                        }    
                    }
                    echo '</div>
            </div>

            <div class="boxBody">'.
                $announcement->getValue('ann_description').'
                <div class="editInformation">'.
                    $gL10n->get('SYS_CREATED_BY', $row['create_firstname']. ' '. $row['create_surname'],  $announcement->getValue('ann_timestamp_create'));

                    if($announcement->getValue('ann_usr_id_change') > 0)
                    {
                        echo '<br />'.$gL10n->get('SYS_LAST_EDITED_BY', $row['change_firstname']. ' '. $row['change_surname'],  $announcement->getValue('ann_timestamp_change'));
                    }
                echo '</div>
            </div>
        </div>';
    }  // Ende While-Schleife
    
    // Navigation mit Vor- und Zurueck-Buttons
    $base_url = $g_root_path.'/adm_program/modules/announcements/announcements.php?headline='.$getHeadline;
    echo admFuncGeneratePagination($base_url, $getAnnouncements['totalCount'], $getAnnouncements['limit'], $getStart, TRUE);
}


        
require(SERVER_PATH. '/adm_program/system/overall_footer.php');

?>