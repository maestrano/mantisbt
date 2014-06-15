<?php

/**
 * Mno Person Interface
 */
class MnoSoaBaseProject extends MnoSoaBaseEntity
{
    protected $_mno_entity_name = "PROJECTS";
    protected $_create_rest_entity_name = "projects";
    protected $_create_http_operation = "POST";
    protected $_update_rest_entity_name = "projects";
    protected $_update_http_operation = "POST";
    protected $_receive_rest_entity_name = "projects";
    protected $_receive_http_operation = "GET";
    protected $_delete_rest_entity_name = "projects";
    protected $_delete_http_operation = "DELETE";    
    
    protected $_id;
    protected $_name;
    protected $_description;
    protected $_status;
    protected $_start_date;
    protected $_due_date;
    protected $_completed_date;
    protected $_priority;
    protected $_parent;
    protected $_project_owner;
    protected $_stakeholders;
    protected $_milestones;
    protected $_tasklists;
    protected $_tasks;

    /**************************************************************************
     *                    ABSTRACT DATA MAPPING METHODS                       *
     **************************************************************************/
    
    public function pushId() {
	// DO NOTHING
    }
    
    public function pullId() {
        // DO NOTHING
    }
    
    protected function pushProject() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pullProject() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pushStakeholders() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pullStakeholders() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pushMilestones() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pullMilestones() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pushTasklists() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pullTasklists() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pushTasks() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    protected function pullTasks() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }   
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    /**************************************************************************
     *                       ABSTRACT GET/SET METHODS                         *
     **************************************************************************/
    
    public function getLocalEntityIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    public function getLocalEntityByLocalIdentifier($local_id) {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    public function createLocalEntity() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoProject class!');
    }
    
    /**************************************************************************
     *                       COMMON INHERITED METHODS                         *
     **************************************************************************/
    
    /**
    * Build a Maestrano organization message
    * 
    * @return Organization the organization json object
    */
    protected function build() {
        MnoSoaLogger::debug(" start");
        $this->pushProject();
        MnoSoaLogger::debug(" after pushProject");
        $this->pushStakeholders();
        MnoSoaLogger::debug(" after pushStakeholders");
        $this->pushMilestones();
        MnoSoaLogger::debug(" after pushMilestones");
        // pushTasklists is invoked internally within pushTasks
        //$this->pushTasklists();
        //MnoSoaLogger::debug(" after pushTasklists");
        $this->pushTasks();
        MnoSoaLogger::debug(" after pushTasks");
        
        if ($this->_name != null) { $msg['project']->name = $this->_name; }
        if ($this->_description != null) { $msg['project']->description = $this->_description; }
        if ($this->_status != null) { $msg['project']->status = $this->_status; }
        if ($this->_start_date != null) { $msg['project']->startDate = $this->_start_date; }
        if ($this->_due_date != null) { $msg['project']->dueDate = $this->_due_date; }
        if ($this->_completed_date != null) { $msg['project']->completedDate = $this->_completed_date; }
        if ($this->_priority != null) { $msg['project']->priority= $this->_priority; }
        if ($this->_parent != null) { $msg['project']->parent = $this->_parent; }
        if ($this->_project_owner != null) { $msg['project']->projectOwner = $this->_project_owner; }
        if ($this->_stakeholders != null) { $msg['project']->stakeholders = $this->_stakeholders; }
        if ($this->_milestones != null) { $msg['project']->milestones = $this->_milestones; }
        if ($this->_tasklists != null) { $msg['project']->tasklists = $this->_tasklists; }
        if ($this->_tasks != null) { $msg['project']->tasks = $this->_tasks; }
	
        MnoSoaLogger::debug(" after creating message array");
        
        $message = $msg['project'];
        
        MnoSoaLogger::debug(" message=".json_encode($message));
        
        return $message;
    }
    
    protected function persist($mno_entity) {
        MnoSoaLogger::debug(" mno_entity = " . json_encode($mno_entity));
        
        if (!empty($mno_entity->project)) {
            $mno_entity = $mno_entity->project;
        }
        
        if (empty($mno_entity->id)) {
            return false;
        }
        
        $this->_id = $mno_entity->id;
        $this->set_if_array_key_has_value($this->_name, 'name', $mno_entity);
        $this->set_if_array_key_has_value($this->_description, 'description', $mno_entity);
        $this->set_if_array_key_has_value($this->_status, 'status', $mno_entity);
        $this->set_if_array_key_has_value($this->_start_date, 'startDate', $mno_entity);
        $this->set_if_array_key_has_value($this->_due_date, 'dueDate', $mno_entity);
        $this->set_if_array_key_has_value($this->_completed_date, 'completedDate', $mno_entity);
        $this->set_if_array_key_has_value($this->_priority, 'priority', $mno_entity);
        $this->set_if_array_key_has_value($this->_parent, 'parent', $mno_entity);
        $this->set_if_array_key_has_value($this->_project_owner, 'projectOwner', $mno_entity);
        $this->set_if_array_key_has_value($this->_stakeholders, 'stakeholders', $mno_entity);
        $this->set_if_array_key_has_value($this->_milestones, 'milestones', $mno_entity);
        $this->set_if_array_key_has_value($this->_tasklists, 'tasklists', $mno_entity);
        $this->set_if_array_key_has_value($this->_tasks, 'tasks', $mno_entity);

        MnoSoaLogger::debug(" persist id = " . $this->_id);        

        MnoSoaLogger::debug(" start pull functions");
        $this->pullProject();
        MnoSoaLogger::debug(" after pullProject");
        $this->pullStakeholders();
        MnoSoaLogger::debug(" after pullStakeholders");
        $this->pullMilestones();
        MnoSoaLogger::debug(" after pullMilestones");
        $this->pullTasklists();
        MnoSoaLogger::debug(" after pullTasklists");
        $this->pullTasks();
        MnoSoaLogger::debug(" end");
        
        return true;
    }
}

?>