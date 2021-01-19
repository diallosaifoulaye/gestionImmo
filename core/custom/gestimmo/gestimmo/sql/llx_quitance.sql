-- --------------------------------------------------------

--
-- Structure de la table `llx_quitance`
--

CREATE TABLE IF NOT EXISTS `llx_quitance` (
  `rowid` int(11) NOT NULL,
  `date` date NOT NULL,
  `date_de` date NOT NULL,
  `date_fin` date NOT NULL,
  `fk_loc` int(11) NOT NULL,
  `fk_bails` int(11) NOT NULL,
  `aquiter` int(11) NOT NULL,
  `total_ht` double NOT NULL,
  `fk_payement` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `llx_quitance_det`
--

CREATE TABLE IF NOT EXISTS `llx_quitance_det` (
  `rowid` int(11) NOT NULL,
  `fk_quitance` int(11) NOT NULL,
  `ref_label` int(11) NOT NULL,
  `datec` date NOT NULL,
  `note` varchar(15) NOT NULL,
  `debit` double NOT NULL,
  `credit` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

