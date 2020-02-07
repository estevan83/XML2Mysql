<?php
/**
 * Genera il file json 'proprieta.json' contenente la mappatura
 */

$map = array(

   // Data  destination
    'datasource' => array(
        'driver' => 'mysqli',
        'user' => 'atalanta_tester',
        'host' => '34.90.100.132',
        'password' => 'ribotester2019',
        'database' => 'atalanta_tester',
        "port"  => '3306',
    ),
    
    'xmlFile' => 'demo.xml',
    
    'othertable' => array ( 
        
        'AMMedias'=> array(
                    'foto'=> array('field', 'AMMedias'),
                )
        
        ),
    
    
    'tablemapping'=> array(
        /*
            'person' => array(
                    'person_id' => array('unique'),
                    'fname' => array('field', 'nome'),
                    'lname' => array('value', '1'),
                ),
                
            'society' => array(
                        'person_id' => array('unique'),
                        'fname' => array('value', '1'),
                        'lname' => array('value', '1'),
                    ),
         */
                'annuncio'      => array(
                    'idannuncio'    => array('unique'),
                 /*   'agenzia'       => array('field','Agenzia'),
                    'annuncioid'    => array('field','AnnuncioId'),
                    'codice'        => array('field','Codice'),
                    'creato'        => array('field','Creato'),
                    'modificato'    => array('field','Modifica'),

                    'scadenza'      => array('field','Scadenza'),
                    'cantiereID'    => array('field','CantiereId'),
                    'nazione'       => array('field','Nazione'),
                    'regione'       => array('field','Regione'),
                    'provincia'     => array('field','Provincia'),
                    'comune'        => array('field','Comune'),

                    'comune_istat'  => array('field','Comune_Istat'),
                    'zona'          => array('field','Zona'),
                    'indirizzo'     => array('field','Indirizzo'),
                    'civico'        => array('field','Civico'),
                    'latitudine'    => array('field','Latitudine'),
                    'longitudine'   => array('field','Longitudine'),
                    'tipologia'     => array('field','Tipologia'),
                    'tipologia2'    => array('field','Tipologia2'),
                    'homepage'      => array('bool','HomePage'),*/

                    'investimento'  => array('sql', "select (case when ? = 'si' then 1 else 0 end)", 'investimento'),
                  /*  'prestigio'     => array('bool','Prestigio'),
                    'offerta'       => array('bool','Offerta'),
                    'single'        => array('bool','Single'),
                    'coppie'        => array('bool','Coppie'),
                    'famiglie'      => array('bool','Famiglie'),
                    'aste_giudiziarie' => array('bool','Aste_Giudiziarie'),
                    'affitto_riscatto'=> array('bool','Affitto_Riscatto'),
*/

                  //  'idxml' => array('field','AnnuncioId'),
            ),
            

     
        )
);


file_put_contents("proprieta.json", json_encode($map));

