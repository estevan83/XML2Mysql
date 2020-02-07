<?php


$a = new importXML();
$a->clearLog();
$a->connectToDB();


$annunci = $a->xmlToArray();

    
foreach ($annunci as $annuncio){
    $insert = $a->generateInsert($annuncio);
    $a->runInsert();
}


die();


class importXML {
    protected $db;
    protected $xmlFile;
    public $tablemapping;
    protected $logFilename;
    protected $arrayXml;
    protected $datasource;
    protected $insert;
    protected $othertable;
    
    function __construct($filename = 'proprieta.json', $logFilename = 'logImportXML'){
        $json = file_get_contents($filename);
        $map = json_decode($json,true);
        $this->xmlFile      = $map['xmlFile'];
        $this->tablemapping = $map['tablemapping'];
        $this->datasource   = $map['datasource'];
        $this->othertable = $map['othertable'];

    }
    
    // CONNESSIONE AL DB
    public function connectToDB(){
        $this->db =  new mysqli($this->datasource['host'], $this->datasource['user'], $this->datasource['password'], $this->datasource['database'], $this->datasource['port']);
        if ($this->db->connect_error) {
            $msg = 'Errore di connessione (' . $this->db->connect_errno . ') '. $this->db->connect_error;
            $this->writeLog($msg, 'FAILED');
            die();
        }
        $this->writeLog('Connected', 'INFO');
    }
    
    /**
    * Converte un file di tipo xml in un array
    * IL FILE XML DEVE AVERE QUESTO FORMATO:
    <root>
       <sez1>
           <campo1>valore</campo1>
           <campo2>valore</campo2>
           <campoN>valore</campoN>
           <campoM>
                <other>val</other>
           </campoM>
       </sez1>    
       <sez2>
           <campo1>valore</campo1>
           <campo2>valore</campo2>
           <campoN>valore</campoN>
           <campoM>
                <other>val</other>
           </campoM>
       </sez2>    
       <sezN>
           <campo1>valore</campo1>
           <campo2>valore</campo2>
           <campoN>valore</campoN>
           <campoM>
                <other>val</other>
           </campoM>
       </sezN>    
    </root>
    * @param string $xml
    * @return array
    */
    public function xmlToArray(){
        $xml =  file_get_contents($this->xmlFile);
        $new = simplexml_load_string($xml); 
        $con = json_encode($new); 
        $newArr = json_decode($con, true); 
        foreach ($newArr as $root){
            $this->arrayXml = $root;
            $this->writeLog('Array Restituito:' . print_r($root,true));
            return $root;
        }
    }

    
    public function generateInsert($valueXML){
        $firsttime = true; 
        
        foreach ($this->tablemapping as $tablename => $fields) {
 
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
                    $id = $this->generaId($tablename, $key[$counter]); 
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

                    $params[$key[$counter]] = $this->getValue($valueXML, $field[1]);
                }
                else if($field[0]=='sql'){
                    $params[$key[$counter]] = $field[1]; // $this->executeSql($field[1]);       
                }
                else if($field[0]=='bool'){
                    $params[$key[$counter]] = $this->getBoolValue($valueXML, $field[1]);
                }/*
                else if($field[0]=='multi'){
                    $this->getMultiValue($id, $valueXML, $field);
                }*/
                $counter++;
            }
            $parameter[$tablename]=$params;
            unset($params);

            foreach ($parameter[$tablename] as $par){
               // die ($par);
                $insert[$tablename].='"'.$par.'", ';
            }
            $insert[$tablename] = substr($insert[$tablename], 0, strlen($insert[$tablename])-2); 
            $insert[$tablename].=')';  
        }
        $this->insert = $insert;
        
        // per gestire tutti le insert che servono "strane"
        foreach ($this->othertable as $tablename => $fields) {
            $res = $this->handleTable($tablename);
      
            if($res===1){
                foreach ($fields as $field){
                    $this->insertFoto($id, $valueXML, $tablename, $field[1]);
                }
            }
        }
        
    }
    
    
    
    
    /**
    * Quando si imposta 'unique' estrae id+1 dalla tabella dove si scriverà 
    * @global mysqli $db
    * @param string $tablename
    * @param string $field
    * @return type
    */
    private function generaId($tablename, $field){
       $sql = "select ifnull(MAX($field),0)+1 as id from $tablename";
       // faccio select

       if ($stmt = $this->db->prepare($sql)) {

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
     * Quando si imposta 'bool' estrae valore corrispondente dall'array generato dal file Xml e se è 'no' mette 0 se è 'si' mette 1
     * @param type $array
     * @param type $key
     * @return string
     */
    private function getBoolValue($array, $key){
        $return = $array[$key];
        if ($return == 'no'){
            $return = 0;
        }
        else if ($return == 'si'){
            $return = 1;
        }
        else if (is_array($return)){
            if(empty($return)){
              //  die (print_r($array));
                $return = 'No Value';
            }
            else{
                $return = array_map('strval', $return);
            }
        }        
        else if ($return == null){
            $return = 'NULL';
        }        
        
        return $return;
    }
    
    
    private function handleTable($tablename){
        if ($tablename == 'AMMedias'){
            return 1;
        }
        else{
            return false;
        }
    }
    
    
    
    

    
    private function insertFoto($id, $valueXML,$tablename, $field){
        // unset($this->insert);
        $tmp = $valueXML[$field];
         if (!array_key_exists('AMFoto', $tmp))
        {
            return;
        }
        $tmp = $tmp['AMFoto'];
        if (empty($tmp)){
            return;
        }
        $id = '"'.$id.'"';
        foreach($tmp as $foto){
            // print_r($foto);
           if (!is_array($foto)){
                return;
            }
            if (!array_key_exists('Url', $foto))
            {
                return;
            }
            else{
                $url = '"'.$foto['Url'].'"';
            }
            if (!array_key_exists('@attributes', $foto))
            {
                return ;
            }
            else{
                
                if (!array_key_exists('Tipo', $foto['@attributes']))
                {
                    $type = "'no data'";
                }
                else{
                    $type = '"'.$foto['@attributes']['Tipo'].'"';
                }
                if (!array_key_exists('Ordine', $foto['@attributes']))
                {
                    $ordine = "'no data'";
                }
                else{
                    $ordine = '"'.$foto['@attributes']['Ordine'].'"';
                }
                
            }
            // echo ("URL: $url -> TIPO: $type -> ORDINE: $ordine".PHP_EOL);
            $this->insert[] = "insert into $tablename(idannuncio, url, tipo, sequence) values ($id, $url, $type, $ordine)";
        }
        // print_r ($this->insert);
        return;
        // die();
    }


    /**
     * Quando si imposta 'field' estrae valore corrispondente dall'array generato dal file Xml
     * @param array $array
     * @param string $key
     * @return string
     */
    private function getValue($array, $key){
        $return = $array[$key];
        if (is_array($return)){
            if(empty($return)){
              //  die (print_r($array));
                $return = 'No Value';
            }
            else{
                $return = array_map('strval', $return);
            }
        }        
        else if ($return == null){
            $return = 'NULL';
        }        
        
        return $return;
    }
    
    
    /**
     * Quando si imposta 'sql' esegue del codice SQL
     * ** DA TESTARE 
     * @global mysqli $db
     * @param string $sql
     * @return un valore
     */
    private function executeSql($sql){

        if ($stmt = $this->db->prepare($sql)) {

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
    
    
    /**
     * ESEGUE LE INSERT NEL DATABASE
     * @global mysqli $db
     * @param array $insert
     * @param string $tabs
     */
    public function runInsert(){

        foreach ($this->insert as $tmp){
            if(!$this->db->query($tmp)){
                echo $tmp.PHP_EOL;
                echo 'Error query  ';
                print_r($this->db->error);
                echo PHP_EOL;
            }
        }

    }
    
    
    
    
    public function writeLog($msg, $level = 'DEBUG'){
        return;
        $now = date("Y-m-d H:i:s");
        $res = file_put_contents($this->logFilename, "$now |  [$level] => $msg" . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($res == false){
                throw new Exception("cannot write log file");
        }
    }
    
    
    public function clearLog($clearLog = false){
        if ($clearLog == true){
            unlink($this->logFilename);
        }
    }
}