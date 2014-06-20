<?php

/**
 * Mno Organization Class
 */
class MnoSoaProject extends MnoSoaBaseProject
{
    protected $_local_entity_name = "PROJECTS";
    protected $_local_project_id = null;
    protected $_local_project_name = null;
    protected $_mno_tasklist_id = null;
    
    
    protected function pushProject() 
    {
        // FETCH PROJECT
        $project_query = "SELECT proj.id as id, proj.name as name, proj.description as description, proj.mno_status as mno_status, proj_hier.parent_id as parent
                          FROM mantis_project_table proj
                                LEFT OUTER JOIN mantis_project_hierarchy_table proj_hier ON proj.id = proj_hier.child_id
                          WHERE proj.id = " .  db_param();
        $project = db_query_bound($project_query, Array($this->_local_project_id));
        $project = db_fetch_array($project);
        
        if (!$project) { return null; }
        $project = (object) $project;
        
        // PUSH PROJECT DETAILS
        $this->_local_project_name = $this->_name = $this->push_set_or_delete_value($project->name);
        $this->_description = $this->push_set_or_delete_value($project->description);
        $this->_status = $this->push_set_or_delete_value($project->mno_status);
        
        // PUSH PROJECT PARENT
        if (!empty($project->parent)) {
            $mno_parent_project_id_obj = MnoSoaDB::getMnoIdByLocalId($project->parent, "PROJECTS", "PROJECTS");

            if (MnoSoaDB::isNewIdentifier($mno_parent_project_id_obj)) {
                push_project_to_maestrano($project->parent);
                $mno_parent_project_id_obj = MnoSoaDB::getMnoIdByLocalId($project->parent, "PROJECTS", "PROJECTS");
            }
            
            if (MnoSoaDB::isValidIdentifier($mno_parent_project_id_obj)) {
                $this->_parent = $this->push_set_or_delete_value($mno_parent_project_id_obj->_id);
            }
        }
        
        $mno_project_id_obj = MnoSoaDB::getMnoIdByLocalId($this->_local_project_id, "PROJECTS", "PROJECTS");
        $this->_id = (MnoSoaDB::isValidIdentifier($mno_project_id_obj)) ? $mno_project_id_obj->_id : null;
        
        if (empty($this->_id)) {
            $this->_start_date = ((string) time()) . '000';
            if (!empty($_SESSION['mno_uid'])) {
                $this->_project_owner = $_SESSION['mno_uid'];
            }
        }
        
        MnoSoaLogger::debug("project=".json_encode($project));
    }
    
    protected function pullProject() 
    {
        // INSTANTIATE LOCAL PROJECT OBJECT
        $local_project_id_obj = MnoSoaDB::getLocalIdByMnoId($this->_id, $this->getMnoEntityName(), $this->getLocalEntityName());
        $name = $this->pull_set_or_delete_value($this->_name);
        $status = $this->pull_set_or_delete_value($this->_status);
        $description = $this->pull_set_or_delete_value($this->_description);
              
        // PERSIST PROJECT
        if (MnoSoaDB::isValidIdentifier($local_project_id_obj)) {
            $this->_local_project_id = $local_project_id_obj->_id;
            $project_query  = "UPDATE mantis_project_table SET name=".db_param().", mno_status=".db_param().", description=".db_param()." WHERE id = " . db_param();
            MnoSoaLogger::debug("project update query={$project_query}");
            db_query_bound($project_query, Array($name,$status,$description,$this->_local_project_id));
            MnoSoaLogger::debug("after project update query");
        } else if (MnoSoaDB::isNewIdentifier($local_project_id_obj)) {
            $project_query = "  INSERT INTO mantis_project_table (name, mno_status, file_path, description) VALUES 
                                ('{$name}','{$status}', '','{$description}') ";
            MnoSoaLogger::debug("project insert query={$project_query}");
            db_query_bound($project_query);
            $this->_local_project_id = db_insert_id();
            MnoSoaDB::addIdMapEntry($this->_local_project_id, $this->getLocalEntityName(), $this->_id, $this->getMnoEntityName());
            MnoSoaLogger::debug("after project insert query");
        } else {
            return;
        }
        
        // PULL PARENT
        $mno_parent_id = $this->pull_set_or_delete_value($this->_parent);
        
        if (!empty($mno_parent_id)) {
            $notification = (object) array();
            $notification->id = $mno_parent_id;
            $notification->entity = "PROJECTS";
            $this->process_notification($notification);
            
            $local_parent_id_obj = MnoSoaDB::getLocalIdByMnoId($mno_parent_id, "PROJECTS", "PROJECTS");
            if (MnoSoaDB::isValidIdentifier($local_parent_id_obj)) {
                $local_parent_id = $local_parent_id_obj->_id;
                $project_hierarchy_select_query = "SELECT * FROM mantis_project_hierarchy_table WHERE child_id=".db_param();
                $project_hierarchy_result = db_query_bound($project_hierarchy_select_query, Array($this->_local_project_id));
                $project_hierarchy_record = db_fetch_array($project_hierarchy_result);
                
                if (!$project_hierarchy_record) {
                    $project_hierarchy_insert_query = "INSERT INTO mantis_project_hierarchy_table (`child_id`, `parent_id`, `inherit_parent`) VALUES (".db_param().",".db_param().",".db_param().")";
                    db_query_bound($project_hierarchy_insert_query, Array($this->_local_project_id,$local_parent_id,$local_parent_id));
                } else {
                    $project_hierarchy_update_query = "UPDATE mantis_project_hierarchy_table SET parent_id=".db_param()." WHERE child_id=".db_param();
                    db_query_bound($project_hierarchy_update_query, Array($local_parent_id, $this->_local_project_id));
                }
            }
        }
    }
    
    protected function pushStakeholders() 
    {
        $global_users_query = "     SELECT  user_id
                                    FROM    mantis_user_pref_table
                                    WHERE   project_id='0'";
        $global_users_query_result = db_query_bound($global_users_query);
        
        while ($global_user = db_fetch_array($global_users_query_result)) {
            $global_user_id = $global_user['user_id'];
            $select_user_query = " SELECT user_id FROM mantis_project_user_list_table WHERE user_id='{$global_user_id}' and project_id='{$this->_local_project_id}' ";
            $select_user_query_result = db_query_bound($select_user_query);
            $select_user_query_record = db_fetch_array($select_user_query_result);
            
            if (!$select_user_query_record) {
                $user_upsert_query = " INSERT INTO mantis_project_user_list_table (`user_id`, `project_id`, `mno_status`, `access_level`) VALUES ('{$global_user_id}', '{$this->_local_project_id}', 'ACTIVE', '90') ";
            } else {
                $user_upsert_query = " UPDATE mantis_project_user_list_table SET `mno_status`='ACTIVE', `access_level`='90' WHERE `user_id`='{$global_user_id}' AND `project_id`='{$this->_local_project_id}' ";
            }
            db_query_bound($user_upsert_query);
        }
        
        
        $assigned_query =   "       SELECT  pu.user_id as id, pu.mno_status as status
                                    FROM    mantis_project_user_list_table pu 
                                    WHERE   pu.project_id = ".db_param() . "
                                      
                            ";
        $local_stakeholders = db_query_bound($assigned_query, Array($this->_local_project_id));
        
        while ($local_stakeholder = db_fetch_array($local_stakeholders)) {
            MnoSoaLogger::debug("*********************************local_stakeholder=".json_encode($local_stakeholder));
            $mno_stakeholder_id = MnoSoaDB::getMnoUserIdByLocalUserId($local_stakeholder['id']);
            MnoSoaLogger::debug("*********************************mno_stakeholder_id=".json_encode($mno_stakeholder_id));
            $mno_stakholder_status = $this->pull_set_or_delete_value($local_stakeholder['status']);
            if (empty($mno_stakeholder_id) || $mno_stakholder_status === null) { continue; }
            $mno_stakeholders->{$mno_stakeholder_id} = $mno_stakholder_status;
            MnoSoaLogger::debug("*********************************mno_stakeholders=".json_encode($mno_stakeholders));
        }
        
        $this->_stakeholders = (!empty($mno_stakeholders)) ? $mno_stakeholders : null;
    }
    
    protected function pullStakeholders() 
    {    
        // UPSERT STAKEHOLDERS
        if (!empty($this->_stakeholders)) {
            foreach ($this->_stakeholders as $mno_stakeholder_id => $mno_stakeholder_status) {
                $local_stakeholder_id = MnoSoaDB::getLocalUserIdByMnoUserId($mno_stakeholder_id);
                $local_stakeholder_status = $this->pull_set_or_delete_value($mno_stakeholder_status);
                if (empty($local_stakeholder_id) || $local_stakeholder_status===null) { continue; }
                
                $project_user_list_select_query = "SELECT * FROM mantis_project_user_list_table WHERE project_id='$this->_local_project_id' and user_id='$local_stakeholder_id'";
                $project_user_list_result = db_query_bound($project_user_list_select_query);
                $project_user_list_record = db_fetch_array($project_user_list_result);
                
                if (!$project_user_list_record) {
                    $project_user_list_upsert_query = " INSERT INTO mantis_project_user_list_table (project_id, user_id, access_level, mno_status) VALUES ('{$this->_local_project_id}', '{$local_stakeholder_id}', '90', '{$local_stakeholder_status}') ";
                } else {
                    $project_user_list_upsert_query = " UPDATE mantis_project_user_list_table SET mno_status='{$local_stakeholder_status}' WHERE project_id='{$this->_local_project_id}' and user_id='{$local_stakeholder_id}' ";
                }
                MnoSoaLogger::debug("query=$project_user_list_upsert_query");
                db_query_bound($project_user_list_upsert_query);
                MnoSoaLogger::debug("after query");
            }
        }
    }
    
    protected function pushMilestones() 
    {      
        // DO NOTHING
    }
    
    protected function pullMilestones() 
    {
        // DO NOTHING
    }
    
    protected function pushTasklists() 
    {
        $mno_tasklist_id = MnoSoaDB::getLocalIdByMnoId($this->_local_project_id, "TASKLISTS", "TASKLISTS");
        if (!MnoSoaDB::isNewIdentifier($mno_tasklist_id)) { return; }
        $mno_tasklist_id = MnoSoaDB::getOrCreateMnoId($this->_local_project_id, "TASKLISTS", "TASKLISTS");
        if (!MnoSoaDB::isValidIdentifier($mno_tasklist_id)) { return; }
        $mno_tasklist_id = $mno_tasklist_id->_id;
        $mno_tasklist_name = $this->_local_project_name . " bug tasklist";
        $mno_tasklist_start_date = (string) (time() * 1000);
        
        $mno_tasklist->name = $this->push_set_or_delete_value($mno_tasklist_name);
        $mno_tasklist->description = "Auto generated bug tasklist";
        $mno_tasklist->startDate = $this->push_set_or_delete_value($mno_tasklist_start_date);
        $mno_tasklist->status = "INPROGRESS";
        
        $mno_tasklists->{$mno_tasklist_id} = $mno_tasklist;
        
        $this->_mno_tasklist_id = $mno_tasklist_id;
        
        $this->_tasklists = (!empty($mno_tasklists)) ? $mno_tasklists : null;
    }
    
    protected function pullTasklists() 
    {
        // DO NOTHING
    }
    
    protected function pushTasks() 
    {
        $tasks_query =  "SELECT bug.id as id, bug.summary as name, text.description as description, bug.mno_status as status, bug.date_submitted * 1000 as start_date, bug.status as bug_status, bug.mno_tasklist_id, "
                      . "bug.handler_id as assignedTo "
                      . "FROM mantis_bug_table bug LEFT OUTER JOIN mantis_bug_text_table text ON bug.bug_text_id = text.id "
                      . "WHERE project_id = '{$this->_local_project_id}' ";
        $local_tasks = db_query_bound($tasks_query);

        while ($local_task = db_fetch_array($local_tasks)) {
            // TRANSLATE TASK LOCAL ID TO MNO ID
            $local_task_id = $local_task['id'];
            MnoSoaLogger::debug("local_task_id=".$local_task_id);
            $mno_task_id = MnoSoaDB::getOrCreateMnoId($local_task_id, "TASKS", "TASKS");
            if (!MnoSoaDB::isValidIdentifier($mno_task_id)) { continue; }
            $mno_task_id = $mno_task_id->_id;
            MnoSoaLogger::debug("local_task->name=" . $local_task['name']);
            $mno_task = (object) array();
            $mno_task->name = $this->push_set_or_delete_value($local_task['name']);
            $mno_task->description = $this->push_set_or_delete_value($local_task['description']);
            $mno_task->status = $this->map_status_to_mno_format($local_task['status'], $local_task['bug_status']);
            $mno_task->startDate = $this->push_set_or_delete_value($local_task['start_date']);
            if (empty($local_task['mno_tasklist_id'])) {
                $this->pushTasklists();
                $mno_task->tasklist = $this->push_set_or_delete_value($this->_mno_tasklist_id);
            } else {
                $mno_task->tasklist = $this->push_set_or_delete_value($local_task['mno_tasklist_id']);
            }
            
            $task_assignees_query = "SELECT handler_id, status
                                     FROM mantis_bug_handler_table
                                     WHERE bug_id IN (SELECT id FROM mantis_bug_table WHERE project_id = '{$this->_local_project_id}')";
            $local_task_assignees = db_query_bound($task_assignees_query);

            while ($local_task_assignee = db_fetch_array($local_task_assignees)) {
                $local_task_assignee_id = $local_task_assignee['handler_id'];
                $mno_task_assignee_id = MnoSoaDB::getMnoUserIdByLocalUserId($local_task_assignee_id);
                if (!empty($mno_task_assignee_id)) {
                    $mno_task_assignees->{$mno_task_assignee_id} = $local_task_assignee['status'];
                }

                if (!empty($mno_task_assignees)) {
                    $mno_task->assignedTo = $mno_task_assignees;
                }
            }
            $mno_tasks->{$mno_task_id} = $mno_task;
        }
        
        $this->_tasks = $mno_tasks;
    }
    
    protected function map_status_to_mno_format($mno_status, $bug_status) 
    {
        $mno_status_format = $this->push_set_or_delete_value($mno_status);
        $bug_status_format = $this->push_set_or_delete_value($bug_status);
        
        if ($mno_status_format == "ABANDONED") { return "ABANDONED"; }
        
        switch ($bug_status_format) {
            case "10": return "TODO";
            case "20": return "INPROGRESS";
            case "30": return "INPROGRESS";
            case "40": return "INPROGRESS";
            case "50": return "INPROGRESS";
            case "80": return "COMPLETED";
            case "90": return "COMPLETED";
        }
        
        return "INPROGRESS";
    }
    
    protected function map_status_to_local_format($mno_status, $local_bug_id)
    {
        $mno_status_format = $this->push_set_or_delete_value($mno_status);
        
        $local_bug_status = "DUMMYVALUE";
        
        if (!empty($local_bug_id)) {
            $bug_table_query = "SELECT status FROM mantis_bug_table WHERE id='$local_bug_id'";
            $bug_table_result = db_query_bound($bug_table_query);
            $bug_table_record = db_fetch_array($bug_table_result);

            if ($bug_table_record) {
                $local_bug_status = $bug_table_record['status'];
            }
        }
        
        switch ($mno_status_format) {
            case "TODO": return "10";
            case "INPROGRESS": 
                if (in_array($local_bug_status, array("20", "30", "40", "50"))) {
                    return $local_bug_status;
                }
                return "50";
            case "COMPLETED":
                if (in_array($local_bug_status, array("80", "90"))) {
                    return $local_bug_status;
                }
                return "80";
            case "ABANDONED": return "90";
        }
        
        return "50";
    }
    
    protected function pullTasks() 
    {
        // UPSERT TASKS
        if (!empty($this->_tasks)) {
            foreach($this->_tasks as $mno_task_id => $task) {
                $local_task_id_obj = MnoSoaDB::getLocalIdByMnoId($mno_task_id, "TASKS", "TASKS");
                $local_task_id = null;
                
                $name = $this->pull_set_or_delete_value($task->name);
                $description = $this->pull_set_or_delete_value($task->description);
                $status = $this->pull_set_or_delete_value($task->status);
                $start_date = $this->map_date_to_local_format($task->startDate);
                $mno_tasklist_id = $this->pull_set_or_delete_value($task->tasklist);
                
                if (MnoSoaDB::isValidIdentifier($local_task_id_obj)) {
                    $local_task_id = $local_task_id_obj->_id;
                    // TO DO - UPDATE MAP ENTITY ASSIGNEES FUNCTION
                    $mno_assignedTo_user_id = $this->map_entity_assignees_to_local_single_entity_assignee($task, $local_task_id, "mantis_bug_table");
                    $local_assignedTo_user_id = MnoSoaDB::getLocalUserIdByMnoUserId($mno_assignedTo_user_id);
                    if (empty($local_assignedTo_user_id)) { continue; }
                    MnoSoaLogger::debug("local_assignedTo_user_id={$local_assignedTo_user_id} mno_assignedTo_user_id={$mno_assignedTo_user_id}");
                    $local_status = $this->map_status_to_local_format($status, $local_task_id);
                    // TO DO - ADD BUG TEXT INSERTION/UPDATE FUNCTION
                    $tasklists_query = "UPDATE mantis_bug_table "
                                     . "SET summary='$name', mno_status='$status', date_submitted='$start_date', handler_id='$local_assignedTo_user_id', status='$local_status', mno_tasklist_id='$mno_tasklist_id', last_updated=unix_timestamp(now()) "
                                     . "WHERE id='$local_task_id' ";
                    db_query_bound($tasklists_query);
                } else if (MnoSoaDB::isNewIdentifier($local_task_id_obj)) {
                    // TO DO - UPDATE FIND FIRST ACTIVE MNO ENTITY ASSIGNEE FUNCTION
                    $mno_assignedTo_user_id = $this->find_first_active_mno_entity_assignee($task);
                    $local_assignedTo_user_id = MnoSoaDB::getLocalUserIdByMnoUserId($mno_assignedTo_user_id);
                    if (empty($local_assignedTo_user_id)) { continue; }
                    MnoSoaLogger::debug("local_assignedTo_user_id={$local_assignedTo_user_id} mno_assignedTo_user_id={$mno_assignedTo_user_id}");
                    $local_status = $this->map_status_to_local_format($status, $local_task_id);
                    // TO DO - ADD BUG TEXT INSERTION/UPDATE FUNCTION
                    $bug_text_id = "";
                    $tasklists_query = "INSERT INTO mantis_bug_table "
                                     . "(project_id, reporter_id, handler_id, bug_text_id, os, os_build, platform, version, fixed_in_version, build, summary, target_version, date_submitted, mno_status, status, mno_tasklist_id, last_updated ) "
                                     . "VALUES ('{$this->_local_project_id}', '$local_assignedTo_user_id', '$local_assignedTo_user_id', '$bug_text_id', '', '', '', '', '', '', '$name', '', '$start_date', '$status', '$local_status', '$mno_tasklist_id', unix_timestamp(now())) ";
                    db_query_bound($tasklists_query);
                    $local_task_id = db_insert_id("mantis_bug_table");
                    MnoSoaDB::addIdMapEntry($local_task_id, "TASKS", $mno_task_id, "TASKS");
                }
                
                $this->map_task_description_to_local_table($local_task_id, $description);
                $this->map_assignees_to_local_table($task, $local_task_id);
            }
        }
    }
    
    protected function map_task_description_to_local_table($local_entity_id, $description)
    {
        $description_select_query = "   SELECT  bugtext.id as bug_text_id
                                        FROM    mantis_bug_table bug 
                                                LEFT OUTER JOIN mantis_bug_text_table bugtext  ON bug.bug_text_id = bugtext.id 
                                        WHERE   bug.id='{$local_entity_id}' AND bug.bug_text_id IS NOT NULL AND bug.bug_text_id > 0 ";
        $description_select_result = db_query_bound($description_select_query);
        $description_select_record = db_fetch_array($description_select_result);
        
        $bug_text_id = $description_select_record['bug_text_id'];
        
        if ($description_select_record) {
            $description_upsert_query = "UPDATE mantis_bug_text_table SET description='{$description}' WHERE id IN (SELECT bug_text_id FROM mantis_bug_table WHERE id='{$local_entity_id}')";
            db_query_bound($description_upsert_query);
        } else {
            $description_upsert_query = "INSERT INTO mantis_bug_text_table(description, steps_to_reproduce, additional_information) VALUES ('{$description}', '', '') ";
            db_query_bound($description_upsert_query);
            $bug_text_id = db_insert_id("mantis_bug_table");
        }
        
        // UPDATE MANTIS_BUG_TABLE WITH BUG_TEXT_ID
        $bug_text_id_update_query = "UPDATE mantis_bug_table SET bug_text_id='{$bug_text_id}' WHERE id='{$local_entity_id}'";
        db_query_bound($bug_text_id_update_query);
    }
    
    protected function map_entity_assignees_to_local_single_entity_assignee($entity, $local_entity_id, $table_name)
    {
        $assignees_query = "SELECT handler_id FROM {$table_name} WHERE id = '{$local_entity_id}'";
        MnoSoaLogger::debug("assignees_query={$assignees_query}");
        $local_assignee_result = db_query_bound($assignees_query);
        $local_assignee_row = db_fetch_array($local_assignee_result);
        
        $mno_user_id = (!empty($local_assignee_row['handler_id'])) ? MnoSoaDB::getMnoUserIdByLocalUserId($local_assignee_row['handler_id']) : null;
        
        if (empty($entity->assignedTo)) { return null; }
        if (empty($mno_user_id)) { return $this->find_first_active_mno_entity_assignee($entity); }
        if (empty($entity->assignedTo->{$mno_user_id})) { return null; }
        if ($entity->assignedTo->{$mno_user_id} == 'ACTIVE') { return $mno_user_id; }

        return $this->find_first_active_mno_entity_assignee($entity);
    }
    
    protected function find_first_active_mno_entity_assignee($entity) {
        if (empty($entity->assignedTo)) { return null; }
        $assigned_to = $entity->assignedTo;
        foreach ($assigned_to as $mno_assignee_id => $status) {
            if ($status == 'ACTIVE') {
                return $mno_assignee_id;
            }
        }
        return null;
    }
    
    protected function map_assignees_to_local_table($entity, $local_entity_id)
    {
        foreach ($entity->assignedTo as $mno_assignee_id => $status) {
            $local_user_id = MnoSoaDB::getLocalUserIdByMnoUserId($mno_assignee_id);
            if (empty($local_user_id)) { continue; }
            
            $assignees_select_query = "SELECT * FROM mantis_bug_handler_table WHERE bug_id='$local_entity_id' AND handler_id='$local_user_id'";
            $assignees_select_result = db_query_bound($assignees_select_query);
            $assignees_select_record = db_fetch_array($assignees_select_result);
            
            if ($assignees_select_record) {
                $assignees_upsert_query = "UPDATE mantis_bug_handler_table SET status='$status' WHERE bug_id='$local_entity_id' AND handler_id='$local_user_id' ";
            } else {
                $assignees_upsert_query = "INSERT INTO mantis_bug_handler_table (bug_id, handler_id, status) VALUES ('$local_entity_id', '$local_user_id', '$status') ";
            }
            db_query_bound($assignees_upsert_query);            
        }
    }
        
    protected function saveLocalEntity($push_to_maestrano, $status) 
    {
        //$this->_local_entity->save();
    }
    
    public function getLocalEntityIdentifier() 
    {
        return $this->_local_project_id;
    }
    
    public function setLocalEntityIdentifier($local_identifier)
    {
        $this->_local_project_id = $local_identifier;
    }
    
    public function getLocalEntityByLocalIdentifier($local_id)
    {
        return get_project_object($local_id);
    }
    
    public function createLocalEntity()
    {
        return (object) array();
    }
    
    public function map_date_to_local_format($date)
    {
        $date_format = $this->pull_set_or_delete_value($date);
        return (!empty($date_format) && ctype_digit($date_format)) ? (string) ((int) round(intval($date_format)/1000)) : "0";
    }
    
    public function map_project_status_to_mno_format($status, $completed_date, $mno_status)
    {
        $status_format = $this->push_set_or_delete_value($status);
        $completed_date_format = $this->push_set_or_delete_value($completed_date);
        $mno_status_format = $this->push_set_or_delete_value($mno_status, null);
        
        if (empty($status_format)) { return null; }
        
        switch ($status_format) {
            case "INACTIVE": return "ABANDONED";
            case "ACTIVE":
                if (!empty($completed_date_format)) { return "COMPLETED"; }
                if ($mno_status_format === null || $mno_status_format == "INPROGRESS") { return "INPROGRESS"; }
                else if ($mno_status_format == "TODO") { return "TODO"; }
                return "INPROGRESS";
        }
        
        return null;
    }
    
    public function map_project_status_to_local_format($status)
    {
        $status_format = $this->pull_set_or_delete_value($status);
        
        if (empty($status)) { return "ACTIVE"; }
        
        switch ($status_format) {
            case "TODO": return "ACTIVE";
            case "INPROGRESS": return "ACTIVE";
            case "COMPLETED": return "ACTIVE";
            case "ABANDONED": return "INACTIVE";
        }
        
        return "ACTIVE";
    }
}

?>