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
class Echeance extends CommonObject
{
    var $db;                            //!< To store db handler
    var $error;                            //!< To return error code (or message)
    var $errors = array();                //!< To return several error codes (or messages)
    var $element = 'Echeance';            //!< Id that identify managed objects
    var $table_element = 'Echeance';        //!< Name of table without prefix where object is stored

    //var $id;
    var $rowid;
    var $etape;
    var $date_deb;
    var $date_fin;
    var $montant;
    var $date_deb_reelle;
    var $date_fin_reelle;
    var $description;
    //var $fk_categorie;
    var $fk_propal;
    var $statut;

   /* var $entite;
    var $numcompte;*/
    var $table_Echeance = array();

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

        if (isset($this->etape)) $this->etape = trim($this->etape);
        if (isset($this->date_deb)) $this->date_deb = trim($this->date_deb);
        if (isset($this->date_fin)) $this->date_fin = trim($this->date_fin);
        if (isset($this->montant)) $this->montant = trim($this->montant);
        if (isset($this->date_deb_reelle)) $this->date_deb_reelle = trim($this->date_deb_reelle);
        if (isset($this->date_fin_reelle)) $this->date_fin_reelle = trim($this->date_fin_reelle);
        if (isset($this->description)) $this->description = trim($this->description);
        //if (isset($this->fk_categorie)) $this->fk_categorie = trim($this->fk_categorie);
        if (isset($this->fk_propal)) $this->fk_propal = trim($this->fk_propal);
        if (isset($this->statut)) $this->statut = trim($this->statut);

        //var_dump(trim($this->fk_propal));die;
        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "echeance(";

        $sql .= "etape,";
        $sql .= "date_deb,";
        $sql .= "date_fin,";
        $sql .= "montant,";
        $sql .= "date_deb_reelle,";
        $sql .= "date_fin_reelle,";
        $sql .= "description,";
        $sql .= "fk_propal,";
        $sql .= "statut";

        $sql .= ") VALUES (";
        $sql .= "" . (!isset($this->etape) ? 'NULL' : "'" . $this->db->escape($this->etape) . "'") . ",";
        $sql .= "" . (!isset($this->date_deb) ? 'NULL' : "'" . $this->db->escape($this->date_deb) . "'"). ",";
        $sql .= "" . (!isset($this->date_fin) ? 'NULL' : "'" . $this->db->escape($this->date_fin) . "'") . ",";
        $sql .= "" . (!isset($this->montant) ? 'NULL' : "'" . $this->db->escape($this->montant) . "'") . ",";
        $sql .= "" . (!isset($this->date_deb_reelle) ? 'NULL' : "'" . $this->db->escape($this->date_deb_reelle) . "'"). ",";
        $sql .= "" . (!isset($this->date_fin_reelle) ? 'NULL' : "'" . $this->db->escape($this->date_fin_reelle) . "'") . ",";
        $sql .= "" . (!isset($this->description) ? 'NULL' : "'" . $this->db->escape($this->description) . "'") . ",";
        $sql .= "" . (!isset($this->fk_propal) ? 'NULL' : "'" . $this->db->escape($this->fk_propal) . "'") . ",";
        $sql .= "" . (!isset($this->statut) ? 'NULL' : "'" . $this->db->escape($this->statut) . "'");
        $sql .= ")";
   //  print $sql;die;

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . "echeance");
          //  var_dump( $this->rowid );die();
            $sql1 = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_echeance(";

            $sql1 .= "fk_categorie,";
            $sql1 .= "fk_echeance";


            $sql1 .= ") VALUES (";
            $sql1 .= "" . (!isset($this->fk_propal) ? 'NULL' : "'" . $this->db->escape($this->fk_propal) . "'") . ",";
            $sql1 .= "" . ($this->rowid) ;

            $sql1 .= ")";
           // var_dump($sql1);die();
            dol_syslog(get_class($this) . "::create sql=" . $sql1, LOG_DEBUG);
            $resql1 = $this->db->query($sql1);
            if (!$resql1) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }


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
        $sql .= " t.etape,";
        $sql .= " t.date_deb,";
        $sql .= " t.date_fin,";
        $sql .= " t.montant,";
        $sql .= " t.date_deb_reelle,";
        $sql .= " t.date_fin_reelle,";
        $sql .= " t.description,";
        $sql .= " t.fk_propal,";
        $sql .= " t.statut,";

        $sql .= " FROM " . MAIN_DB_PREFIX . "echeance as t";
        $sql .= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->rowid = $obj->rowid;

                $this->etape = $obj->etape;
                $this->date_deb = $obj->date_deb;
                $this->date_fin = $obj->date_fin;
                $this->montant = $obj->montant;
                $this->date_deb_reelle = $obj->date_deb_reelle;
                $this->date_fin_reelle = $obj->date_fin_reelle;
                $this->description = $obj->description;
                $this->fk_propal = $obj->fk_propal;
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
     *  Load object in memory from the database
     *
     * @param    int $param Id object
     * @return int            <0 if KO, >0 if OK
     */


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
        $rowid = $this->rowid;
        $etape = $this->etape;
        $date_deb = $this->date_deb;
        $date_fin = $this->date_fin;
        $montant = $this->montant;
        $date_deb_reelle = $this->date_deb_reelle;
        $date_fin_reelle = $this->date_fin_reelle;
        $description=$this->description;
       $statut=$this->statut;

        // Clean parameters
        if (isset($etape)) $this->etape = trim($etape);
        if (isset($date_deb)) $this->date_deb = trim($date_deb);
        if (isset($date_fin)) $this->date_fin = trim($date_fin);
        if (isset($montant)) $this->montant = trim($montant);
        if (isset($date_deb_reelle)) $this->date_deb_reelle = trim($date_deb_reelle);
        if (isset($date_fin_reelle)) $this->date_fin_reelle = trim($date_fin_reelle);
        if (isset($description)) $this->description = trim($description);
        if (isset($statut)) $this->statut = trim($statut);
       
        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "echeance SET";

        $sql .= " etape='" . $etape . "',";
        $sql .= " date_deb='" . $date_deb . "',";
        $sql .= " date_fin='" . $date_fin . "',";
        $sql .= " montant='" . $montant . "',";
        $sql .= " date_deb_reelle='" . $date_deb_reelle . "',";
        $sql .= " date_fin_reelle='" . $date_fin_reelle . "',";
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
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "echeance";
            $sql .= " WHERE rowid=" . $ligne;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
            $sql1 = "DELETE FROM " . MAIN_DB_PREFIX . "categorie_echeance";
            $sql1 .= " WHERE fk_echeance=" . $ligne;

            dol_syslog(get_class($this) . "::delete sql=" . $sql1);
            $resql1 = $this->db->query($sql1);
            if (!$resql1) {
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




}
