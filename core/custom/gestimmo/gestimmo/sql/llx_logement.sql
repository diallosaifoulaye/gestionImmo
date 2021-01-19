CREATE TABLE llx_logement (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  ref   varchar(128)  NOT NULL,
  adresse VARCHAR(255)  NULL  ,
  town                     varchar(50),                         		
  fk_departement           integer        DEFAULT 0,            		
  fk_pays                  integer        DEFAULT 0,
  fk_mandat  integer        DEFAULT 0,
  datec date,
  nb_piece INTEGER  NULL  ,
  descriptif VARCHAR(255)  NULL  ,
  superficie INTEGER  NULL  ,
  dpe VARCHAR(255)  NULL  ,
  loyer double(24,8) DEFAULT 0, 
  charges double(24,8) DEFAULT 0 ,
  caution double(24,8) DEFAULT 0 ,
  Honoraire INTEGER  NULL  ,
  Assurance INTEGER  NULL    ,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  entity integer NOT NULL DEFAULT 1
) ENGINE=InnoDB;


