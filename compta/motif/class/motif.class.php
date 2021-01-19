<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2012      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014      Marcos García 		 <marcosgdf@gmail.com>
 * Copyright (C) 2015      Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2018      Ferran Marcet		 <fmarcet@2byte.es>
 * Copyright (C) 2018      Thibault FOUCART		 <support@ptibogxiv.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/paiement/class/motif.class.php
 *	\ingroup    facture
 *	\brief      File of class to manage payments of customers invoices
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/multicurrency/class/multicurrency.class.php';


/**
 *	Class to manage payments of customer invoices
 */
class Motif extends CommonObject
{
    /**
	 * @var string ID to identify managed object
	 */
	public $element='motif';

    /**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='motif';

    /**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'motif';

	public $rowid;
	public $label;

    public $code;

	public $description;





	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *    Load payment from database
	 *
	 *    @param	int		$id			Id of payment to get
	 *    @param	string	$ref		Ref of payment to get (currently ref = id but this may change in future)
	 *    @param	int		$fk_bank	Id of bank line associated to payment
	 *    @return   int		            <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '', $fk_bank = '')
	{
		$sql = 'SELECT m.rowid, m.label, m.code, m.statut, m.description,';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'motif as m ';
		//$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		//$sql.= ' WHERE p.entity IN (' . getEntity('invoice').')';
		/*if ($id > 0)
			$sql.= ' AND p.rowid = '.$id;
		elseif ($ref)
			$sql.= " AND p.ref = '".$ref."'";*/
//var_dump($sql);die();
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->rowid             = $obj->rowid;
				$this->label   = $obj->label;
				$this->description   = $obj->description;
				$this->code      = $obj->code;
				$this->statut         = $obj->statut;


				$this->db->free($resql);
				return 1;
			}
			else
			{
				$this->db->free($resql);
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
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
        if (isset($this->description)) $this->description = trim($this->description);
        //var_dump($this->label,$this->code,$this->description) ;die;
        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "motif(";

        $sql .= "label,";
        $sql .= "code,";
        $sql .= "description";
        //$sql .= "statut,";

        $sql .= ") VALUES (";
        $sql .= "" . (!isset($this->label) ? 'NULL' : "'" . $this->db->escape($this->label) . "'") . ",";
        $sql .= "" . (!isset($this->code) ? 'NULL' : "'" . $this->db->escape($this->code) . "'"). ",";
        $sql .= "" . (!isset($this->description) ? 'NULL' : "'" . $this->db->escape($this->description) . "'") ;
        //$sql .= "" . (!isset($this->statut) ? 'NULL' : "'" . $this->db->escape($this->statut) . "'");


        $sql .= ")";
       // var_dump($sql) ;die;
        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . "nature_lot");
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
	 *  Delete a payment and generated links into account
	 *  - Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *  - Si le paiement porte sur au moins une facture a "payee", on refuse
	 *
	 *  @param	int		$notrigger		No trigger
	 *  @return int     				<0 si ko, >0 si ok
	 */
    public function delete($notrigger = 0)
	{
		global $conf, $user, $langs;

		$error=0;

		$bank_line_id = $this->bank_line;

		$this->db->begin();

		// Verifier si paiement porte pas sur une facture classee
		// Si c'est le cas, on refuse la suppression
		$billsarray=$this->getBillsArray('fk_statut > 1');
		if (is_array($billsarray))
		{
			if (count($billsarray))
			{
				$this->error="ErrorDeletePaymentLinkedToAClosedInvoiceNotPossible";
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			return -2;
		}

		// Delete bank urls. If payment is on a conciliated line, return error.
		if ($bank_line_id > 0)
		{
			$accline = new AccountLine($this->db);

			$result=$accline->fetch($bank_line_id);
			if ($result == 0) $accline->rowid=$bank_line_id;    // If not found, we set artificially rowid to allow delete of llx_bank_url

            // Delete bank account url lines linked to payment
			$result=$accline->delete_urls($user);
            if ($result < 0)
            {
                $this->error=$accline->error;
				$this->db->rollback();
				return -3;
            }

            // Delete bank account lines linked to payment
			$result=$accline->delete($user);
			if ($result < 0)
			{
				$this->error=$accline->error;
				$this->db->rollback();
				return -4;
			}
		}

		if (! $notrigger)
		{
			// Call triggers
			$result=$this->call_trigger('PAYMENT_CUSTOMER_DELETE', $user);
			if ($result < 0)
			{
			    $this->db->rollback();
			    return -1;
            }
            // End call triggers
		}

		// Delete payment (into paiement_facture and paiement)
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement_facture';
		$sql.= ' WHERE fk_paiement = '.$this->id;
		dol_syslog($sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement';
			$sql.= ' WHERE rowid = '.$this->id;
		    dol_syslog($sql);
			$result = $this->db->query($sql);
			if (! $result)
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return -3;
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error;
			$this->db->rollback();
			return -5;
		}
	}



    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Updates the payment date
     *
     *  @param	int	$date   New date
     *  @return int					<0 if KO, 0 if OK
     */
    public function update_date($date)
    {
        // phpcs:enable
        $error=0;

        if (!empty($date) && $this->statut != 1)
        {
            $this->db->begin();

            dol_syslog(get_class($this)."::update_date with date = ".$date, LOG_DEBUG);

            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET datep = '".$this->db->idate($date)."'";
            $sql.= " WHERE rowid = ".$this->id;

            $result = $this->db->query($sql);
            if (! $result)
            {
                $error++;
                $this->error='Error -1 '.$this->db->error();
            }

            $type = $this->element;

            $sql = "UPDATE ".MAIN_DB_PREFIX.'bank';
            $sql.= " SET dateo = '".$this->db->idate($date)."', datev = '".$this->db->idate($date)."'";
            $sql.= " WHERE rowid IN (SELECT fk_bank FROM ".MAIN_DB_PREFIX."bank_url WHERE type = '".$type."' AND url_id = ".$this->id.")";
            $sql.= " AND rappro = 0";

            $result = $this->db->query($sql);
            if (! $result)
            {
                $error++;
                $this->error='Error -1 '.$this->db->error();
            }

            if (! $error)
            {

            }

            if (! $error)
            {
                $this->datepaye = $date;
                $this->date = $date;

                $this->db->commit();
                return 0;
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        return -1; //no date given or already validated
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Updates the payment number
     *
     *  @param	string	$num		New num
     *  @return int					<0 if KO, 0 if OK
     */
    public function update_num($num)
    {
        // phpcs:enable
        if(!empty($num) && $this->statut!=1) {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET num_paiement = '".$this->db->escape($num)."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::update_num", LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result)
            {
            	$this->numero = $this->db->escape($num);
                return 0;
            }
            else
            {
                $this->error='Error -1 '.$this->db->error();
                return -2;
            }
        }
        return -1; //no num given or already validated
    }

	/**
	 *    Validate payment
	 *
	 *	  @param	User	$user		User making validation
	 *    @return   int     			<0 if KO, >0 if OK
	 */
    public function valide(User $user = null)
    {
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET statut = 1 WHERE rowid = '.$this->id;

		dol_syslog(get_class($this).'::valide', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::valide '.$this->error);
			return -1;
		}
    }

	/**
	 *    Reject payment
	 *
	 *	  @param	User	$user		User making reject
	 *    @return   int     			<0 if KO, >0 if OK
	 */
    public function reject(User $user = null)
    {
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET statut = 2 WHERE rowid = '.$this->id;

		dol_syslog(get_class($this).'::reject', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::reject '.$this->error);
			return -1;
		}
    }

	/**
	 *    Information sur l'objet
	 *
	 *    @param   int     $id      id du paiement dont il faut afficher les infos
	 *    @return  void
	 */
    public function info($id)
    {
		$sql = 'SELECT p.rowid, p.datec, p.fk_user_creat, p.fk_user_modif, p.tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' WHERE p.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_creat)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_modif)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
    }

	/**
	 *  Return list of invoices the payment is related to.
	 *
	 *  @param	string		$filter         Filter
	 *  @return int|array					<0 if KO or array of invoice id
	 */
    public function getBillsArray($filter = '')
    {
		$sql = 'SELECT pf.fk_facture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'facture as f';	// We keep link on invoice to allow use of some filters on invoice
		$sql.= ' WHERE pf.fk_facture = f.rowid AND pf.fk_paiement = '.$this->id;
		if ($filter) $sql.= ' AND '.$filter;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num=$this->db->num_rows($resql);
			$billsarray=array();

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$billsarray[$i]=$obj->fk_facture;
				$i++;
			}

			return $billsarray;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::getBillsArray Error '.$this->error.' -', LOG_DEBUG);
			return -1;
		}
    }

    /**
     *  Return list of amounts of payments.
     *
     *  @return int|array					Array of amount of payments
     */
    public function getAmountsArray()
    {
    	$sql = 'SELECT pf.fk_facture, pf.amount';
    	$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
    	$sql.= ' WHERE pf.fk_paiement = '.$this->id;
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$i=0;
    		$num=$this->db->num_rows($resql);
    		$amounts = array();

    		while ($i < $num)
    		{
    			$obj = $this->db->fetch_object($resql);
    			$amounts[$obj->fk_facture]=$obj->amount;
    			$i++;
    		}

    		return $amounts;
    	}
    	else
    	{
    		$this->error=$this->db->error();
    		dol_syslog(get_class($this).'::getAmountsArray Error '.$this->error.' -', LOG_DEBUG);
    		return -1;
    	}
    }

	/**
	 *      Return next reference of customer invoice not already used (or last reference)
	 *      according to numbering module defined into constant FACTURE_ADDON
	 *
	 *      @param	   Societe		$soc		object company
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
    public function getNextNumRef($soc, $mode = 'next')
	{
		global $conf, $db, $langs;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (empty($conf->global->PAYMENT_ADDON)) $conf->global->PAYMENT_ADDON='mod_payment_cicada';
		elseif ($conf->global->PAYMENT_ADDON=='ant') $conf->global->PAYMENT_ADDON='mod_payment_ant';
		elseif ($conf->global->PAYMENT_ADDON=='cicada') $conf->global->PAYMENT_ADDON='mod_payment_cicada';

		if (! empty($conf->global->PAYMENT_ADDON))
		{
			$mybool=false;

			$file = $conf->global->PAYMENT_ADDON.".php";
			$classname = $conf->global->PAYMENT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/payment/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file))
				{
					$mybool |= include_once $dir . $file;
				}
			}

			// For compatibility
			if (! $mybool)
			{
				$file = $conf->global->PAYMENT_ADDON.".php";
				$classname = "mod_payment_".$conf->global->PAYMENT_ADDON;
				$classname = preg_replace('/\-.*$/', '', $classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot)
				{
					$dir = $dirroot."/core/modules/payment/";

					// Load file with numbering class (if found)
					if (is_file($dir.$file) && is_readable($dir.$file)) {
						$mybool |= include_once $dir . $file;
					}
				}
			}

			if (! $mybool)
			{
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc, $this);

			/**
			 * $numref can be empty in case we ask for the last value because if there is no invoice created with the
			 * set up mask.
			 */
			if ($mode != 'last' && !$numref) {
				dol_print_error($db, "Payment::getNextNumRef ".$obj->error);
				return "";
			}

			return $numref;
		}
		else
		{
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Invoice"));
			return "";
		}
	}

	/**
	 * 	get the right way of payment
	 *
	 * 	@return 	string 	'dolibarr' if standard comportment or paid in main currency, 'customer' if payment received from multicurrency inputs
	 */
    public function getWay()
	{
		global $conf;

		$way = 'dolibarr';
		if (!empty($conf->multicurrency->enabled))
		{
			foreach ($this->multicurrency_amounts as $value)
			{
				if (!empty($value)) // one value found then payment is in invoice currency
				{
					$way = 'customer';
					break;
				}
			}
		}

		return $way;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	void
	 */
    public function initAsSpecimen($option = '')
	{
		global $user,$langs,$conf;

		$now=dol_now();
		$arraynow=dol_getdate($now);
		$nownotime=dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		// Initialize parameters
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->facid = 1;
		$this->datepaye = $nownotime;
	}


	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			Sur quoi pointe le lien
	 *  @param  string  $mode           'withlistofinvoices'=Include list of invoices into tooltip
     *  @param	int  	$notooltip		1=Disable tooltip
	 *	@return	string					Chaine avec URL
	 */
    public function getNomUrl($withpicto = 0, $option = '', $mode = 'withlistofinvoices', $notooltip = 0)
	{
		global $conf, $langs;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result='';
        $label = '<u>'.$langs->trans("ShowPayment").'</u><br>';
        $label.= '<strong>'.$langs->trans("Ref").':</strong> '.$this->ref;
        if ($this->datepaye ? $this->datepaye : $this->date) $label.= '<br><strong>'.$langs->trans("Date").':</strong> '.dol_print_date($this->datepaye ? $this->datepaye : $this->date, 'dayhour');
        if ($mode == 'withlistofinvoices')
        {
            $arraybill = $this->getBillsArray();
            if (is_array($arraybill) && count($arraybill) > 0)
            {
            	include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            	$facturestatic=new Facture($this->db);
            	foreach ($arraybill as $billid)
            	{
            		$facturestatic->fetch($billid);
            		$label .='<br> '.$facturestatic->getNomUrl(1).' '.$facturestatic->getLibStatut(2, 1);
            	}
            }
        }

        $linkclose='';
        if (empty($notooltip))
        {
        	if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
        	{
        		$label=$langs->trans("ShowMyObject");
        		$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
        	}
        	$linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
        	$linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

        $url = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$this->id;

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto && $withpicto != 2) $result.= ($this->ref?$this->ref:$this->id);
		$result .= $linkend;

		return $result;
	}

	/**
	 * Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 * @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return  string				Libelle
	 */
    public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Renvoi le libelle d'un statut donne
	 *
	 * @param   int		$status     Statut
	 * @param   int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return	string  		    Libelle du statut
	 */
    public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage

		$langs->load('compta');
		/*if ($mode == 0)
		{
			if ($status == 0) return $langs->trans('ToValidate');
			if ($status == 1) return $langs->trans('Validated');
		}
		if ($mode == 1)
		{
			if ($status == 0) return $langs->trans('ToValidate');
			if ($status == 1) return $langs->trans('Validated');
		}
		if ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		}
		if ($mode == 3)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		}
		if ($mode == 5)
		{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
		}
		if ($mode == 6)
	    {
	        if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
	        if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
	    }*/
		return '';
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load the third party of object, from id into this->thirdparty
	 *
	 *	@param		int		$force_thirdparty_id	Force thirdparty id
	 *	@return		int								<0 if KO, >0 if OK
	 */
    public function fetch_thirdparty($force_thirdparty_id = 0)
	{
        // phpcs:enable
		include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

		if (empty($force_thirdparty_id))
		{
			$billsarray = $this->getBillsArray(); // From payment, the fk_soc isn't available, we should load the first supplier invoice to get him
			if (!empty($billsarray))
			{
				$invoice = new Facture($this->db);
				if ($invoice->fetch($billsarray[0]) > 0)
				{
					$force_thirdparty_id = $invoice->fk_soc;
				}
			}
		}

		return parent::fetch_thirdparty($force_thirdparty_id);
	}
}
