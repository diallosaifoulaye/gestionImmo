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
class Type_lot extends CommonObject
{
    var $db;                            //!< To store db handler
    var $error;                            //!< To return error code (or message)
    var $errors = array();                //!< To return several error codes (or messages)
    var $element = 'Type_lot';            //!< Id that identify managed objects
    var $table_element = 'Type_lot';        //!< Name of table without prefix where object is stored

    //var $id;
    var $rowid;
    var $label;
    var $code;
    var $statut;
    var $description;

   /* var $entite;
    var $numcompte;*/
    var $table_Type = array();

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
        //if (isset($this->rowid)) $this->rowid = trim($this->rowid);
        //if (isset($this->statut)) $this->statut = trim($this->statut);
        if (isset($this->code)) $this->code = trim($this->code);
        if (isset($this->description)) $this->description = trim($this->description);

        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "type_lot(";

        $sql .= "label,";
        $sql .= "code,";
        $sql .= "description";
        $sql .= ") VALUES (";
        $sql .= "" . (!isset($this->label) ? 'NULL' : "'" . $this->db->escape($this->label) . "'") . ",";
        $sql .= "" . (!isset($this->code) ? 'NULL' : "'" . $this->db->escape($this->code) . "'"). ",";
        $sql .= "" . (!isset($this->description) ? 'NULL' : "'" . $this->db->escape($this->description) . "'") ;
        $sql .= ")";
      //var_dump($sql);die;
        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . "type_lot");
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
        $sql .= " t.label,";
        $sql .= " t.code,";
        $sql .= " t.description,";
        $sql .= " t.statut,";

        $sql .= " FROM " . MAIN_DB_PREFIX . "type_lot as t";
        $sql .= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->rowid = $obj->rowid;
                $this->label = $obj->label;
                $this->code = $obj->code;
                $this->description = $obj->description;
                $this->statut = $obj->statut;


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
        $type = $this->type;
        $rowid = $this->rowid;
       $statut=$this->statut;

        // Clean parameters
        if (isset($label)) $this->label = trim($label);
        if (isset($code)) $this->code = trim($code);
        if (isset($description)) $this->description = trim($description);
        if (isset($rowid)) $this->rowid = trim($rowid);
        if (isset($statut)) $this->statut = trim($statut);
       
        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "type_lot SET";

        $sql .= " label='" . $label . "',";
        $sql .= " code='" . $code . "',";
        $sql .= " rowid='" . $rowid . "',";
        $sql .= " description='" . $description . "',";
        $sql .= " statut='" . $statut . "',";
       
        $sql .= " WHERE rowid=" . $rowid;

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
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "nature_lot";
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

        $object = new Type_lot($this->db);

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



}
