<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaDB extends MnoSoaBaseDB {
    /**
    * Update identifier map table
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    * @param	string	mno_id                  Maestrano entity identifier
    * @param	string	mno_entity_name         Maestrano entity name
    *
    * @return 	boolean Record inserted
    */
    
    public static function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) {
        MnoSoaLogger::debug("start");
	$query = "INSERT INTO mno_id_map (mno_entity_guid, mno_entity_name, app_entity_id, app_entity_name, db_timestamp) VALUES "
                            ."('$mno_id', '".strtoupper($mno_entity_name)."', '$local_id', '".strtoupper($local_entity_name)."', UTC_TIMESTAMP)";
        db_query_bound($query);
        $id = db_insert_id("mno_id_map");
        
        return (!empty($id)) ? $id : false;
    }
    
    /**
    * Get Maestrano GUID when provided with a local identifier
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    *
    * @return 	boolean Record found	
    */
    public static function getMnoIdByLocalId($local_id, $local_entity_name, $mno_entity_name)
    {       
        $mno_entity = null;
        
	// Fetch record
	$query = "SELECT mno_entity_guid, mno_entity_name, deleted_flag from mno_id_map WHERE "
                . "app_entity_id='$local_id' and app_entity_name='".strtoupper($local_entity_name).
                "' and mno_entity_name='".strtoupper($mno_entity_name)."'";
        $result = db_query_bound($query);
        $row = db_fetch_array($result);
        
	// Return id value
	if ($row) {
            $mno_entity_guid = trim($row["mno_entity_guid"]);
            $mno_entity_name = trim($row["mno_entity_name"]);
            $deleted_flag = trim($row["deleted_flag"]);
            
            if (!empty($mno_entity_guid) && !empty($mno_entity_name)) {
                $mno_entity = (object) array (
                    "_id" => $mno_entity_guid,
                    "_entity" => $mno_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
        
        MnoSoaLogger::debug("returning mno_entity = ".json_encode($mno_entity));
	return $mno_entity;
    }
    
    public static function getLocalIdByMnoId($mno_id, $mno_entity_name, $local_entity_name)
    {
	$local_entity = null;
        
        $mno_entity_name_format = strtoupper($mno_entity_name);
        $local_entity_name_format = strtoupper($local_entity_name);
        
	// Fetch record
	$query = "  SELECT  app_entity_id, app_entity_name, deleted_flag
                    FROM    mno_id_map 
                    WHERE   mno_entity_guid='{$mno_id}'
                            AND mno_entity_name='{$mno_entity_name_format}' 
                            AND app_entity_name='{$local_entity_name_format}'";
        $result = db_query_bound($query);
        $row = db_fetch_array($result);
        
	// Return id value
	if ($row) {
            $app_entity_id = trim($row["app_entity_id"]);
            $app_entity_name = trim($row["app_entity_name"]);
            $deleted_flag = trim($row["deleted_flag"]);
            
            if (!empty($app_entity_id) && !empty($app_entity_name)) {
                $local_entity = (object) array (
                    "_id" => $app_entity_id,
                    "_entity" => $app_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
	
        MnoSoaLogger::debug("returning local_entity=".json_encode($local_entity));
	return $local_entity;
    }
    
    public static function getLocalUserIdByMnoUserId($mno_user_id)
    {
        $user_query = "SELECT id FROM mantis_user_table WHERE mno_uid = '$mno_user_id'";
        $result = db_query_bound($user_query);
        $row = db_fetch_array($result);
        
        MnoSoaLogger::debug("mno_id={$mno_user_id} local_id={$row['id']}");
        
        return (!empty($row['id'])) ? $row['id'] : null;
    }
    
    public static function getMnoUserIdByLocalUserId($local_user_id)
    {
        $user_query = "SELECT mno_uid FROM mantis_user_table WHERE id = '$local_user_id'";
        $result = db_query_bound($user_query);
        $row = db_fetch_array($result);
        
        MnoSoaLogger::debug(" row[mno_uid] = ".json_encode($row['mno_uid']));
        
        return (!empty($row['mno_uid'])) ? $row['mno_uid'] : null;
    } 
    
    public static function deleteIdMapEntry($local_id, $local_entity_name) 
    {        
        MnoSoaLogger::debug(" start");
        // Logically delete record
        $query = "UPDATE mno_id_map SET deleted_flag=1 WHERE app_entity_id='$local_id'"
                ." and app_entity_name='".strtoupper($local_entity_name)."'";
        db_query_bound($query);
        
        return true;
    }
}

?>