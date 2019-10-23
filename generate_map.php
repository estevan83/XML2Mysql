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
            'annuncio' => array(
                'idannuncio' => array('unique'),
                'nazione' => array('field','Nazione'),
                'regione' => array('field','Regione'),
                'provincia' => array('field','Provincia'),
                'comune' => array('field','Comune'),
                'idxml' => array('field','AnnuncioId'),
            )
     
        )
);


file_put_contents("proprieta.json", json_encode($map));

