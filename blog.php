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
	
	//CONECT TO DATABASE
	if(!($connect=mysql_connect("localhost", "debian-sys-maint", "AHqoc3BKjYxHaVK5"))) {
		echo "Mysql connect failed";
		exit;
	}
	//CHOOSE ESPECIFIC DATABASE
	mysql_select_db("elgg2") or die(mysql_error());
	
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

function create_objects_entity($id){
	$title = "Titulo de blog " . $id;
	$description = "contenido del blog " . $id;
	$query = "INSERT into elgg_objects_entity ( guid, title, description) values ( $id, '$title', '$description')";

	$result = mysql_query($query);

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
function create_metadata_puco($count, $value_type, $owner_guid, $time, $access_id){
	begin_profiling();
	$query = "INSERT into elgg_metadata"
         . " (entity_guid, name_id, value_id, value_type, owner_guid, time_created, access_id)"
         . " VALUES ($count, '10','11','$value_type', $owner_guid, $time, $access_id)";
	
        $result = mysql_query($query);
	analyse();

	begin_profiling();
	$query = "INSERT into elgg_metadata"
         . " (entity_guid, name_id, value_id, value_type, owner_guid, time_created, access_id)"
         . " VALUES ($count, '12','13','$value_type', $owner_guid, $time, $access_id)";

        $result = mysql_query($query);
	analyse();

}
//Hay que estudiar el caso de allowmultiple
//Tambien hay que añadir 10 11 y 12 13 para que salga publicado y los comentarios on
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

	GLOBAL $data;
	$data2 = "";
	$connect = connect_db();
	$count = 0;
	$FileName = "dataFull";
	
	$FileHandler = open_file($FileName);	

	while($count < 1000){
	//CREAR BLOG CREA ENTIDAD LUEGO CREA METRASTRINGS Y LUEGO CREA METADATA
		$data = 0;
		create_entity();
		$id = get_last_entity();
		//echo "\nID " . $id;
		$metadataid = get_last_metadata();
		//echo "\nMETADATAID " . $metadataid;
		$metastrinid = get_last_metastring();
		//echo "\nMETASTRINID " . $metastrinid . "\n";

		create_metadata($metadataid, $metastrinid);

		create_objects_entity($id);	
	
		$data2 = $count . " " . $data;
		write_to_file($FileHandler, $data2);
		
		//$result = mysql_query("SHOW profiles");
		//$num = mysql_num_rows($result);
		//echo("NUMERO " . $num . "\n");
		//Cogemos la última
		//mysql_data_seek($result, $num-1);
		//podemos hacerlo con el puntero en show profiles pero no podemos sacar la suma o con la consulta esta poniendo order by query_id desc
			//printf("Query_ID: %s Duration %s\n",$row["Query_ID"],$row["Duration"]);
		$count++;
		
	}
	fclose($FileHandler);
	mysql_close($connect);

?>	
