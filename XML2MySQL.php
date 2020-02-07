<?php
$json = file_get_contents("proprieta.json");
$map = json_decode($json,true);
global $db;
$connect = $map['datasource'];
$db =  new mysqli($connect['host'], $connect['user'], $connect['password'], $connect['database'], $connect['port']);
if ($db->connect_error) {
    die('Errore di connessione (' . $db->connect_errno . ') '. $db->connect_error);
}

$xml = file_get_contents($map['xmlFile']);

$arrayXml = xmlToArray($xml);

$posXML = 0;
foreach ($arrayXml as $valueXML){
    $firsttime = true; 
    $tablenames = array_keys($map['tablemapping']);
    foreach ($map['tablemapping'] as $tablename => $fields) {
        // Generazione di una Insert per ogni tabella
        $cnt = count($fields);
        $insert[$tablename] = 'insert into '.$tablename.' (';
        $key = array_keys($fields);
        $i=0;   
        do{
            $insert[$tablename].=$key[$i].', ';
            $i++;
        }while ($i < $cnt);
        $insert[$tablename] = substr($insert[$tablename], 0, strlen($insert[$tablename])-2);
        $insert[$tablename].=') values (';
        
        $counter = 0;
        foreach ($fields as $field){

            // die(print_r($field)); 
            if ($field[0]=='unique' && $firsttime == true){
                $id = generaId($tablename, $key[$counter]);
                $firsttime = false;
                $params[$key[$counter]] = $id;
            }
            else if ($field[0]=='unique' && $firsttime == false){
                $params[$key[$counter]] = $id;
            }
            else if($field[0]=='value'){
                $params[$key[$counter]] = $field[1];
            }

            else if($field[0]=='field'){

                $params[$key[$counter]] = getValue($valueXML, $field[1]);
            }
            else if($field[0]=='sql'){
                $params[$key[$counter]] = $field[1];// executeSql($field[1]);
            }
            $counter++;
        }
        $parameter[$tablename]=$params;
        unset($params);
        
        foreach ($parameter[$tablename] as $par){
           // die ($par);
            $insert[$tablename].="'".$par."', ";
        }
        $insert[$tablename] = substr($insert[$tablename], 0, strlen($insert[$tablename])-2); 
        $insert[$tablename].=')';  
    }
    runInsert($insert, $tablenames);
    $posXML++;
}





/**
 * Quando si imposta 'unique' estrae id+1 dalla tabella dove si scriverà 
 * @global mysqli $db
 * @param string $tablename
 * @param string $field
 * @return type
 */
function generaId($tablename, $field){
    global $db;
    $sql = "select ifnull(MAX($field),0)+1 as id from $tablename";
    // faccio select
    
    if ($stmt = $db->prepare($sql)) {

        /* execute query */
        $stmt->execute();

        /* bind result variables */
        $stmt->bind_result($id);

        /* fetch value */
        $stmt->fetch();

        /* close statement */
        $stmt->close();
    }
    
   return $id;
}


/**
 * Quando si imposta 'field' estrae valore corrispondente dall'array generato dal file Xml
 * @param array $array
 * @param string $key
 * @return string
 */
function getValue($array, $key){
   return $array[$key];
}


/**
 * ESEGUE LE INSERT NEL DATABASE
 * @global mysqli $db
 * @param array $insert
 * @param string $tabs
 */
function runInsert($insert, $tabs){
    global $db;
    foreach ($tabs as $tab){
        if(!$db->query($insert[$tab])){
            echo $insert[$tab].PHP_EOL;
            echo 'Error query  ';
            print_r($db->error);
            echo PHP_EOL;
        }
        
    }
}


/**
 * Converte un file di tipo xml in un array
 * IL FILE XML DEVE AVERE QUESTO FORMATO:
 <root>
    <sez1>
        <campo1>valore</campo1>
        <campo2>valore</campo2>
        <campoN>valore</campoN>
    </sez1>    
    <sez2>
        <campo1>valore</campo1>
        <campo2>valore</campo2>
        <campoN>valore</campoN>
    </sez2>    
    <sezN>
        <campo1>valore</campo1>
        <campo2>valore</campo2>
        <campoN>valore</campoN>
    </sezN>    
</root>
 * @param string $xml
 * @return array
 */
function xmlToArray($xml){
    $new = simplexml_load_string($xml); 
    $con = json_encode($new); 
    $newArr = json_decode($con, true); 
    foreach ($newArr as $root){
        return $root;
    }
}


/**
 * Quando si imposta 'sql' esegue del codice SQL
 * ** DA TESTARE 
 * @global mysqli $db
 * @param string $sql
 * @return un valore
 */
function executeSql($sql){
    global $db;
    
    if ($stmt = $db->prepare($sql)) {

        /* execute query */
        $stmt->execute();

        /* bind result variables */
        $stmt->bind_result($value);

        /* fetch value */
        $stmt->fetch();

        /* close statement */
        $stmt->close();
    }
    
   return $value;
}

