<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    $secure_session = gpc_get_bool( 'secure_session', false );
    
    if (auth_attempt_login($this->uid,'',true,true)) {
      
      session_set('secure_session', $secure_session );
      return true;
        
    } else {
        
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
      $user_data = $this->buildLocalUser();
      
    	$query = "INSERT INTO mantis_user_table( username, email, password, date_created, last_visit,
    				     enabled, access_level, login_count, cookie_string, realname ) VALUES( " . db_param() . ', 
              ' . db_param() . ', 
              ' . db_param() . ', 
              ' . db_param() . ', 
              ' . db_param()  . ",
    				  " . db_param() . ',
              ' . db_param() . ',
              ' . db_param() . ',
              ' . db_param() . ', 
              ' . db_param() . ')';
    	
      db_query_bound( $query, $user_data);

      
      
    	# Create preferences for the user
    	$lid = db_insert_id( $t_user_table );
        
        $language = config_get_global( 'fallback_language' );
        $language = empty($language) ? 'english' : $language;
	$default_timezone = config_get_global( 'default_timezone' );
        $default_timezone = empty($default_timezone) ? 'Australia/Sydney' : $default_timezone;
        
        # Insert user prefences
        $role = $this->getRoleIdToAssign();
        $pref_query = "INSERT INTO `mantis_user_pref_table` (`user_id`, `project_id`, `default_profile`, `default_project`, `refresh_delay`, `redirect_delay`, `bugnote_order`, `email_on_new`, `email_on_assigned`, `email_on_feedback`, `email_on_resolved`, `email_on_closed`, `email_on_reopened`, `email_on_bugnote`, `email_on_status`, `email_on_priority`, `email_on_priority_min_severity`, `email_on_status_min_severity`, `email_on_bugnote_min_severity`, `email_on_reopened_min_severity`, `email_on_closed_min_severity`, `email_on_resolved_min_severity`, `email_on_feedback_min_severity`, `email_on_assigned_min_severity`, `email_on_new_min_severity`, `email_bugnote_limit`, `language`, `timezone`) VALUES
        ({$lid}, 0, 0, 0, 30, 2, 'ASC', 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '{$language}', '{$default_timezone}');";
        db_query_bound($pref_query);
    }
    
    return $lid;
  }
  
  /**
   * Build a local user for creation
   *
   * @return a hash containing the user data
   */
  protected function buildLocalUser()
  {
  	$seed = $this->email . $this->uid;
  	$cookie_string = auth_generate_unique_cookie_string($seed);
    
    $user_data = Array(
      $this->uid, //username
      $this->email, //email
      $this->generatePassword(), //password
      db_now(), //date_created
      db_now(), //last_visit
      1, //enabled
      $this->getRoleIdToAssign(),
      0, //login_count
      $cookie_string, //cookie_string
      "$this->name $this->surname" //realname
    );
    
    return $user_data;
  }
  
  /**
   * Return the role to give to the user based on context
   * If the user is the owner of the app or at least Admin
   * for each organization, then it is given the role of 'Admin'.
   * Return 'User' role otherwise
   *
   * @return the role level to assign
   */
  public function getRoleIdToAssign() {
    $role_id = 25; // User (reporter)
    
    if ($this->app_owner) {
      $role_id = 90; // Admin
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $role_id = 90;
        } else {
          $role_id = 25;
        }
      }
    }
    
    return $role_id;
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
  	$query = "SELECT id FROM mantis_user_table WHERE mno_uid = " . db_param();
  	$result = db_query_bound($query, Array($this->uid));
    $result = db_fetch_array($result);
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
  	$query = "SELECT id FROM mantis_user_table WHERE email = " . db_param();
  	$result = db_query_bound($query, Array($this->email));
    $result = db_fetch_array($result);
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       
       $query = "UPDATE mantis_user_table SET 
        email = " . db_param() .",
        realname = " . db_param() . ",
        username = " . db_param() . "
        WHERE id = " . db_param();
      
     	$upd = db_query_bound($query, Array($this->email, "$this->name $this->surname", $this->uid, $this->local_id));
      
       return $upd;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
     	$query = "UPDATE mantis_user_table SET 
        mno_uid = " . db_param() . "
        WHERE id = " . db_param();
     	$upd = db_query_bound($query, Array($this->uid, $this->local_id));
      
       return $upd;
    }
    
    return false;
  }
}