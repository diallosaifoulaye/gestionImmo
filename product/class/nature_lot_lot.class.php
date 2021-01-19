<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *    Put here description of your class
 */
class Nature_lot extends CommonObject
{
    var $db;                            //!< To store db handler
    var $error;                            //!< To return error code (or message)
    var $errors = array();                //!< To return several error codes (or messages)
    var $element = 'journal';            //!< Id that identify managed objects
    var $table_element = 'journal';        //!< Name of table without prefix where object is stored

    var $id;

    var $label;
    var $typeJournal;
   /* var $code;
    var $periode;*/
    var $account_root;
    var $userCreation;
   /* var $entite;
    var $numcompte;*/
    var $table_Nature = array();

    /**
     *  Constructor
     *
     * @param    DoliDb $db Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Create object into database
     *
     * @param    User $user User that creates
     * @param  int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->label)) $this->label = trim($this->label);
        if (isset($this->code)) $this->code = trim($this->code);
        if (isset($this->numcompte)) $this->numcompte = trim($this->numcompte);
        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "journal(";

        $sql .= "label,";
        $sql .= "typeJournal,";
        $sql .= "code,";
        $sql .= "periode,";
        $sql .= "account_root,";
        $sql .= "userCreation,";
        $sql .= "entity";
        $sql .= ",numcompte";

        $sql .= ") VALUES (";
        $sql .= "" . (!isset($this->label) ? 'NULL' : "'" . $this->db->escape($this->label) . "'") . ",";
        $sql .= "" . (!isset($this->typeJournal) ? 'NULL' : "'" . $this->db->escape($this->typeJournal) . "'") . ",";
        $sql .= "" . (!isset($this->code) ? 'NULL' : "'" . $this->db->escape($this->code) . "'") . ",";
        $sql .= "" . (!isset($this->periode) ? 'NULL' : "'" . $this->db->escape($this->periode) . "'") . ",";
        $sql .= " " . (!isset($this->account_root) ? 0 : "'" . $this->account_root . "'") . ",";
        $sql .= "" . (!isset($this->userCreation) ? 'NULL' : "" . $this->db->escape($this->userCreation) . "") . ",";
        $sql .= "  " . $conf->entity . ",";
		$sql .= "" . (!isset($this->numcompte) ? 'NULL' : "" . $this->db->escape($this->numcompte) . "") . "";
      //  if (isset($this->numcompte)) $sql .= "  " . $this->numcompte . " ";

        $sql .= ")";
  //   print $sql;die;
        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "journal");
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }


    /**
     *  Load object in memory from the database
     *
     * @param    int $id Id object
     * @return int            <0 if KO, >0 if OK
     */
    function fetch($id)
    {
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.typeJournal,";
        $sql .= " t.label,";
        $sql .= " t.code,";
        $sql .= " t.periode,";

        $sql .= " FROM " . MAIN_DB_PREFIX . "journal as t";
        $sql .= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->rowid = $obj->rowid;

                $this->label = $obj->label;
                $this->typeJournal = $obj->typeJournal;
                $this->code = $obj->code;
                $this->account_root = $obj->account_root;


            }
            $this->db->free($resql);

            return 1;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *  Load object in memory from the database
     *
     * @param    int $param Id object
     * @return int            <0 if KO, >0 if OK
     */
//    function getJournalWithEtat($param,$arg = null)
//    {
//        global $langs;
//
//        $sql = "SELECT  c.label ,c.code, c.periode, c.rowid, SUM(g.debit) as sumDebit, SUM(g.credit) as sumCredit";
//        $sql .= " FROM " . MAIN_DB_PREFIX . "journal as c";
//        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "grand_livre as g ON c.rowid=g.type_operation";
//        $sql .= " WHERE c.rowid = " . $param. " OR c.periode = ". $param. " OR MID(c.periode,1,2) = ". $param. " OR MID(c.periode,4,4) = ". $param;
//        $sql .= ($arg == "rowid") ? " GROUP BY c.$arg" : " GROUP BY c.code";
//
//        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
//        $resql = $this->db->query($sql);
//        if ($resql) {
//            $num = $this->db->num_rows($resql);
//            $i = 0;
//            while ($i < $num) {
//                $obj = $this->db->fetch_object($resql);
//                $this->table_Journal = array_merge($this->table_Journal,[$obj]);
//            }
//            echo"<pre>";var_dump($this->table_Journal);exit();
//            return 1;
//        } else {
//            $this->error = "Error " . $this->db->lasterror();
//            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
//            return -1;
//        }
//    }


    /**
     *  Update object into database
     *
     * @param    User $user User that modifies
     * @param  int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, >0 if OK
     */
    function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;
        $label = $this->label;
        $typejournal = $this->typeJournal;
        $code = $this->code;
        $periode = $this->periode;
        $idjournal = $this->rowid;
        $account_root = $this->account_root;

        // Clean parameters
        if (isset($label)) $this->label = trim($label);
        if (isset($typejournal)) $this->code = trim($typejournal);
        if (isset($code)) $this->code = trim($code);
        if (isset($periode)) $this->periode = trim($periode);
        // Check parameters
        // Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "journal SET";

        $sql .= " label='" . $label . "',";
        $sql .= " typeJournal='" . $typeJournal . "',";
        $sql .= " code='" . $code . "',";
        $sql .= " periode='" . $periode . "',";
        $sql .= " account_root='" . $account_root . "'";

        $sql .= " WHERE rowid=" . $idjournal;

        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }


    /**
     *  Delete object in database
     *
     * @param  User $user User that deletes
     * @param  int $notrigger 0=launch triggers after, 1=disable triggers
     * @return    int                     <0 if KO, >0 if OK
     */
    function delete($user, $ligne, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "journal";
            $sql .= " WHERE rowid=" . $ligne;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }


    /**
     *  retourne les si le jour a des oprations
     *
     * @param  User $user User that deletes
     * @param  int $notrigger 0=launch triggers after, 1=disable triggers
     * @return    int                     <0 if KO, >0 if OK
     */

    /**
     *    Load an object from its id and create a new one in database
     *
     * @param    int $fromid Id of object to clone
     * @return    int                    New id of clone
     */
    function createFromClone($fromid)
    {
        global $user, $langs;

        $error = 0;

        $object = new Nature_lot($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;

        // Clear fields
        // ...

        // Create clone
        $result = $object->create($user);

        // Other options
        if ($result < 0) {
            $this->error = $object->error;
            $error++;
        }

        if (!$error) {


        }

        // End
        if (!$error) {
            $this->db->commit();
            return $object->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *    Initialise object with example values
     *    Id must be 0 if object instance is a specimen
     *
     * @return    void
     */
    function initAsSpecimen()
    {
        $this->id = 0;

        $this->label = '';
    }

    /**
     *    Initialise object with example values
     *    Id must be 0 if object instance is a specimen
     *
     * @return    void
     */
    function getPeriodeLettre($periode)
    {
        $tab = explode("-", $periode);
        $mm = $tab[0];
        //mois en lettre
        switch ($mm) {

            case "1":

                $mm = "Jan";

                break;

            case "2":

                $mm = "Fev";

                break;

            case "3":

                $mm = "Mar";

                break;

            case "4":

                $mm = "Avr";

                break;

            case "5":

                $mm = "Mai";

                break;

            case "6":

                $mm = "Juin";

                break;

            case "7":

                $mm = "Juil";

                break;

            case "08":

                $mm = "Ao&ucirc;t";

                break;

            case "09":

                $mm = "Sept";

                break;

            case "10":

                $mm = "Oct";

                break;

            case "11":

                $mm = "Nov";

                break;

            case "12":

                $mm = "Dec";

                break;

        }
        $periode = $mm . "." . $tab[1];
        return $periode;
    }

}
