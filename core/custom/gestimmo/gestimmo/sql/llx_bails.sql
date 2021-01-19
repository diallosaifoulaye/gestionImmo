
CREATE TABLE llx_immo_bails (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_prop integer NOT NULL,
  fk_loc integer NOT NULL,
  fk_logement INTEGER  NOT NULL  ,
  fk_mandat INTEGER  NULL  ,
  Type VARCHAR(25)  NULL  ,
  Date_location date  NULL  ,
  Depot_garantie VARCHAR(25)  NULL  ,
  date_fin date  NULL   ,
tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
entity integer NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE llx_immo_bails_det (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_bails integer not null,
  date_quitance date null,
  date_debut date null,
  date_fin date null,
  montant integer not null,
  fk_payement integer not null,
 entity integer not null
) ENGINE=InnoDB;

