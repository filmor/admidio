<?php
/******************************************************************************
 * Class manages access to database table adm_auto_login
 *
 * Copyright    : (c) 2004 - 2012 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Diese Klasse dient dazu ein Autologinobjekt zu erstellen.
 * Das Autologin kann ueber diese Klasse in der Datenbank verwaltet werden.
 *
 * Beside the methods of the parent class there are the following additional methods:
 *
 * tableCleanup()       - loescht Datensaetze aus der AutoLogin-Tabelle die nicht
 *                        mehr gebraucht werden
 *
 *****************************************************************************/

require_once(SERVER_PATH. '/adm_program/system/classes/table_access.php');

class TableAutoLogin extends TableAccess
{
	/** Constuctor that will create an object of a recordset of the table adm_auto_login. 
	 *  If the id is set than the specific auto login will be loaded.
	 *  @param $db Object of the class database. This should be the default object $gDb.
	 *  @param $session The recordset of the auto login with this session will be loaded. If session isn't set than an empty object of the table is created.
	 */
    public function __construct(&$db, $session = 0)
    {
        parent::__construct($db, TBL_AUTO_LOGIN, 'atl');

		// if not numeric than the session id is commited
		if(is_numeric($session))
		{
			$this->readDataById($session);
		}
		else
		{
			$this->readDataByColumns(array('atl_session_id' => $session));
		}

    }

    // interne Methode, die Defaultdaten fur Insert und Update vorbelegt
    public function save($updateFingerPrint = true)
    {
        if($this->new_record)
        {
            // Insert
            global $gCurrentOrganization;
            $this->setValue('atl_org_id', $gCurrentOrganization->getValue('org_id'));
            $this->setValue('atl_last_login', DATETIME_NOW);
            $this->setValue('atl_ip_address', $_SERVER['REMOTE_ADDR']);
            
            // Tabelle aufraeumen, wenn ein neuer Datensatz geschrieben wird
            $this->tableCleanup();
        }
        else
        {
            // Update
            $this->setValue('atl_last_login', DATETIME_NOW);
            $this->setValue('atl_ip_address', $_SERVER['REMOTE_ADDR']);
        }
        parent::save($updateFingerPrint);
    }  
    
    // diese Methode loescht Datensaetze aus der AutoLogin-Tabelle die nicht mehr gebraucht werden
    public function tableCleanup()
    {
        // Zeitpunkt bestimmen, ab dem die Auto-Logins geloescht werden, mind. 1 Jahr alt
        $date_session_delete = time() - 60*60*24*365;
            
        $sql    = 'DELETE FROM '. TBL_AUTO_LOGIN. ' 
                    WHERE atl_last_login < \''. date('Y.m.d H:i:s', $date_session_delete). '\'';
        $this->db->query($sql);
    }    
}
?>