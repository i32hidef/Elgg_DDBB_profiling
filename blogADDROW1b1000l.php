<?php
	/******************
	 * This script is for make full the database of elgg, in order to create benchmarks.
	 *
	 * http://www.php-trivandrum.org/open-php-myprofiler/
         *
	 * We will use the tables:
	 * 
	 * 	- Metadata, metastrings, objects_entity, groups_entity, annotations??
	 * 
	*******************/

function begin_profiling(){
        //SETTING VARIABLES TO ENABLE PROFILE           
        $result = mysql_query("set profiling=1") or die(mysql_error());
        //$result = mysql_query("set profiling_history_size=100") or die(mysql_error());
}

function stop_profiling(){
	mysql_query("set profiling=0") or die(mysql_error());
}

function connect_db(){
	GLOBAL $db;
	//CONECT TO DATABASE
	if(!($connect=mysql_connect("localhost", "debian-sys-maint", "AHqoc3BKjYxHaVK5"))) {
		echo "Mysql connect failed";
		exit;
	}
	//CHOOSE ESPECIFIC DATABASE
	mysql_select_db($db) or die(mysql_error());
	
	return $connect;	
}

function disconnect_db($connect){
	mysql_close($connect);
}

function get_last_entity(){
	$query = "select max(guid) as max from elgg_entities";
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	return $row['max'];
}

function create_entity(){
	//lo hace mysql por el constraint
	//hay que hacer una consulta que cuente las entidades y meta el valor en count
	//$count = get_last_entity();
	$type = 'object';
	$subtype_id = 4;
	$owner_guid = 34;
	$site_guid = 1;
	$container_guid = 34;
	$access_id = 2;
	$time = time();
	
	$query = "INSERT into elgg_entities
	(type, subtype, owner_guid, site_guid, container_guid,
		access_id, time_created, time_updated, last_action)
	values
	('$type',$subtype_id, $owner_guid, $site_guid, $container_guid,
		$access_id, $time, $time, $time)";
	
	$result = mysql_query($query);
	
}

function exists_table($table){
	GLOBAL $db;

	$tables = mysql_list_tables ($db); 
	while (list ($temp) = mysql_fetch_array ($tables)) {
		if ($temp == $table) {
			return TRUE;
		}
	}
	return FALSE;
}

function drop_table($table){
	echo "DROP TABLE " . $table;
	if(exists_table($table)){
		$query = "DROP table $table";
		$result = mysql_query($query);
	}
}

//Check about FULLTEXT KEY
//If the tables are created delete them, if not create them
//There are some info about plugins saved in this table.
function create_objects_table(){
	
	if(exists_table("elgg_objects_entity")){
		echo"Existe";
		drop_table("elgg_objects_entity");
		$query = "CREATE TABLE elgg_objects_entity(
			`guid` bigint(20) unsigned NOT NULL,
			`traduction_id` bigint(20) unsigned NOT NULL,
			`title` text NOT NULL,
			`description` text NOT NULL)
			ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
		$result = mysql_query($query);

	}else{
		echo "NO EXISTE";
		//Y si le quitamos primary key??
		$query = "CREATE TABLE elgg_objects_entity(
			`guid` bigint(20) unsigned NOT NULL,
			`traduction_id` bigint(20) unsigned NOT NULL,
			`title` text NOT NULL,
			`description` text NOT NULL,
			ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";
		$result = mysql_query($query);
	}
	
	if(exists_table("elgg_translation")){
		drop_table("elgg_translation");
		$query = "CREATE TABLE elgg_translation(
			 `trad_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			 `language` text NOT NULL,
			 PRIMARY KEY (`trad_id`))  ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

		$result = mysql_query($query);
	}else{		
		$query = "CREATE TABLE elgg_translation(
			 `trad_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			 `language` text NOT NULL,
			 PRIMARY KEY (`trad_id`))  ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

		$result = mysql_query($query);
	}
}

function check_language($language){
	$query = "SELECT * from elgg_translation where language='$language'";
	$result = mysql_query($query);
	if($result){
		$row = mysql_fetch_assoc($result);
		if($row['language'] == $language)
			return true;
	}else
		return false;
}	

function register_new_language($language){
	$query = "INSERT into elgg_translation (language) values ('$language')";
	$result = mysql_query($query);
}

//return language id or false if it doesnt exist
function get_language_id($language){
	$query = "SELECT * from elgg_translation where language='$language'";
        $result = mysql_query($query);
	if($result){
		$row = mysql_fetch_assoc($result);
		return $row['trad_id'];
	}
}

// ADD a new object_entity, we have to check which languages already exists
function create_objects_entity($id, $count, $language){	
	$title = "Titulo de blog " . $id;
	$description = "contenido del blog " . $id;
		

	if(check_language($language)){
		$lang_id = get_language_id($language);
	}else{
		register_new_language($language);
		$lang_id = get_language_id($language);
	}

 	$query = "INSERT into elgg_objects_entity (guid, traduction_id, title, description) values
		 ($id, $lang_id, '$title', '$description')";

	begin_profiling();
	$result = mysql_query($query);
	analyse();


/*	if($count == 0){
		begin_profiling();
		$query = "INSERT into elgg_objects_entity ( guid, title, description) values ( $id, '$title', '$description')";
		$result = mysql_query($query);
		analyse();
	}else{
		add_language($id);
		begin_profiling();
		$query = "INSERT into elgg_objects_entity ( guid, title, description_$count) values ( $id, '$title', '$description')";
		$result = mysql_query($query);
		analyse();
	}
*/

}

function get_last_metastring(){
	$query = "select max(id) as id from elgg_metastrings";
	$result = mysql_query($query);
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row['id'];
}

//call it before metadata
function create_metastring($value, $name){
	$id = get_last_metastring();
	$id++;
	begin_profiling();
	$result = mysql_query("INSERT into elgg_metastrings (id, string) values ($id,'$value')");
	analyse();
	$id++;
	begin_profiling();
	$result = mysql_query("INSERT into elgg_metastrings (id, string) values ($id,'$name')");
	analyse();

}

//Create some metadata needed for a blog
function create_metadata_puco($count, $value_type, $owner_guid, $time, $access_id){
	begin_profiling();
	//Insert status published
	$query = "INSERT into elgg_metadata"
         . " (entity_guid, name_id, value_id, value_type, owner_guid, time_created, access_id)"
         . " VALUES ($count, '10','11','$value_type', $owner_guid, $time, $access_id)";
	
        $result = mysql_query($query);
	analyse();

	begin_profiling();
	//Insert comments on
	$query = "INSERT into elgg_metadata"
         . " (entity_guid, name_id, value_id, value_type, owner_guid, time_created, access_id)"
         . " VALUES ($count, '12','13','$value_type', $owner_guid, $time, $access_id)";

        $result = mysql_query($query);
	analyse();

}

//Hay que estudiar el caso de allowmultiple
function create_metadata($count, $id){
	$time = time();
	$owner_guid = 34;
	$valuem = $id++;
	$namem = $id++;
	$value_type = 'text';
	$access_id = 2;
	$value = 'value ' . $id;
	$name = 'tag ' . $id;

	create_metastring($value, $name);
	create_metadata_puco($count, $value_type, $owner_guid, $time, $access_id);

	begin_profiling();
	$query = "INSERT into elgg_metadata"
	 . " (entity_guid, name_id, value_id, value_type, owner_guid, time_created, access_id)"
	 . " VALUES ($count, '14','$valuem','$value_type', $owner_guid, $time, $access_id)";
		
	$result = mysql_query($query);
	analyse();
	
	begin_profiling();
	$query = "INSERT into elgg_metadata"
	 . " (entity_guid, name_id, value_id, value_type, owner_guid, time_created, access_id)"
	 . " VALUES ($count, '16','$namem','$value_type', $owner_guid, $time, $access_id)";
		
	$result = mysql_query($query);
	analyse();

}

function get_last_metadata(){
	$query = "select max(id) as id from elgg_metadata";
	$result = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	return $row['id'];
}

//Look in to the profiling table to get the sum of the times of each query
function get_duration(){
	$result = mysql_query("select sum(duration) as duration from information_schema.profiling order by query_id desc") or die(mysql_error());
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	//var_dump($row);
	return $row["duration"];
}

function analyse(){
	GLOBAL $data;
	$data += get_duration();
}

function open_file($FileName){
        $FileHandler = fopen($FileName,'w') or die("Cant open file");
	return $FileHandler;
	
}

function write_to_file($FileHandler, $data){
	fwrite($FileHandler, $data . "\n");	
}

	GLOBAL $data, $db;
	$db ="elgg7";
	$data2 = "";
	$connect = connect_db();
	$count = 0;
	$FileName = "dataFull";
	
	$FileHandler = open_file($FileName);	
	
	create_objects_table();
	
	//while($count < 1000){
	
	//CREAR BLOG CREA ENTIDAD LUEGO CREA METRASTRINGS Y LUEGO CREA METADATA
	//TENEMOS que medir los tiempos de creación de elgg_objects_entity en diferentes casos.
	// Esta version no contempla tags ni el excerpt
	// Crear 1000 blogs con un lenguage diferente cada uno
	// Crear un blog y a partir de ahi meterle lenguages
	// Crear blog con nuevos lenguage
			
		$data = 0;
		create_entity();
		$id = get_last_entity();
		//echo "\nID " . $id;
		$metadataid = get_last_metadata();
		//echo "\nMETADATAID " . $metadataid;
		$metastrinid = get_last_metastring();
		//echo "\nMETASTRINID " . $metastrinid . "\n";

		create_metadata($metadataid, $metastrinid);

	while($count < 1000){
		$language = "language". $count;
		create_objects_entity($id, $count, $language);	
	
		$data2 = $count . " " . $data;
		write_to_file($FileHandler, $data2);
		
		$count++;
		
	}
	fclose($FileHandler);
	mysql_close($connect);

?>	
