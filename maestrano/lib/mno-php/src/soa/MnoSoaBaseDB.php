<?php

/**
 * Mno DB Map Interface
 */
class MnoSoaBaseDB {
    protected static $_db;
    
    public static function initialize($db=null)
    {
        static::$_db = $db;
    }

    public static function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function getMnoIdByLocalId($local_id, $local_entity_name, $mno_entity_name)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function getLocalIdByMnoId($mno_id, $mno_entity_name, $local_entity_name)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    protected static function getLocalUserIdByMnoUserId($mno_user_id)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    protected static function getMnoUserIdByLocalUserId($local_user_id)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function deleteIdMapEntry($local_id, $local_entity_name)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoDB class!');
    }
    
    public static function isValidIdentifier($id_obj) 
    {
        MnoSoaLogger::debug("in is valid identifier");
        return !empty($id_obj) && (!empty($id_obj->_id) || (array_key_exists('_id',$id_obj) && $id_obj->_id == 0)) && 
                array_key_exists('_deleted_flag',$id_obj) && $id_obj->_deleted_flag == 0;
    }

    public static function isDeletedIdentifier($id_obj) 
    {
        MnoSoaLogger::debug("in is deleted identifier");
        return !empty($id_obj) && (!empty($id_obj->_id) || (array_key_exists('_id',$id_obj) && $id_obj->_id == 0)) && 
                array_key_exists('_deleted_flag',$id_obj) && $id_obj->_deleted_flag == 1;
    }
    
    public static function isNewIdentifier($id_obj)
    {
        MnoSoaLogger::debug("in is new identifier");
        return !static::isValidIdentifier($id_obj) && !static::isDeletedIdentifier($id_obj);
    }
    
    public static function getOrCreateMnoId($local_id, $local_entity_name, $mno_entity_name)
    {
        $mno_entity = MnoSoaDB::getMnoIdByLocalId($local_id, $local_entity_name, $mno_entity_name);
        if (empty($mno_entity)) { 
            $mno_id = MnoSOADB::createGUID();
            MnoSoaDB::addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name);
            if (!empty($mno_id)) {
                $mno_entity = (object) array (
                    "_id" => $mno_id,
                    "_entity" => strtoupper($mno_entity_name),
                    "_deleted_flag" => 0
                );
            }
        }
        return $mno_entity;
    }
    
    public static function createGUID()
    {
        $charid = strtolower(md5(uniqid(rand(), true)));
        error_log("charid=".$charid);
        $hyphen = chr(45);// "-"
        $guid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $guid;
    }
}

?>