<?PHP
/*
    Registration/Login script from HTML Form Guide
    V1.0

    This program is free software published under the
    terms of the GNU Lesser General Public License.
    http://www.gnu.org/copyleft/lesser.html
    

This program is distributed in the hope that it will
be useful - WITHOUT ANY WARRANTY; without even the
implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.

For updates, please visit:
http://www.html-form-guide.com/php-form/php-registration-form.html
http://www.html-form-guide.com/php-form/php-login-form.html

*/

require_once("class.phpmailer.php");
require_once("formvalidator.php");

class FGMembersite{
    var $admin_email;
    var $from_address;
    
    var $username;
    var $pwd;
    var $database;
    var $tablename;
    var $connection;
    var $rand_key;
    
    var $error_message;
    
    //-----Initialization -------
    function FGMembersite(){
        $this->sitename = 'YourWebsiteName.com';
        $this->rand_key = '0iQx5oBk66oVZep';
    }
    
    function InitDB($host,$uname,$pwd,$database,$tablename){
        $this->db_host   = $host;
        $this->username  = $uname;
        $this->pwd       = $pwd;
        $this->database  = $database;
        $this->tablename = $tablename;
        
    }
	
    function SetAdminEmail($email){
        $this->admin_email = $email;
    }
    
    function SetWebsiteName($sitename){
        $this->sitename = $sitename;
    }
    
    function SetRandomKey($key){
        $this->rand_key = $key;
    }
    
    //-------Main Operations ----------------------
    function RegisterUser(){
        if(!isset($_POST['submitted'])){
           return false;
        }
        
        $formvars = array();
        
        if(!$this->ValidateRegistrationSubmission()){
            return false;
        }
		
        $this->CollectRegistrationSubmission($formvars);
        
		if(!$this->checkSimilar($formvars)){
			return false;
		}
		
        if(!$this->SaveToDatabase($formvars)){
            return false;
        }
		
        
        /*if(!$this->SendUserConfirmationEmail($formvars)){
            return false;
        }*/

        //$this->SendAdminIntimationEmail($formvars);
        
        return true;
    }

    function ConfirmUser(){
        if(empty($_GET['code'])||strlen($_GET['code'])<=10){
            $this->HandleError("Please provide the confirm code");
            return false;
        }
        $user_rec = array();
        if(!$this->UpdateDBRecForConfirmation($user_rec)){
            return false;
        }
        
        $this->SendUserWelcomeEmail($user_rec);
        
        $this->SendAdminIntimationOnRegComplete($user_rec);
        
        return true;
    }    
    
    function Login(){
        if(empty($_POST['U_email'])){
            $this->HandleError("Email is empty!");
            return false;
        }
        
        if(empty($_POST['U_pwd'])){
            $this->HandleError("Password is empty!");
            return false;
        }
        
        $email    = trim($_POST['U_email']);
        $password = trim($_POST['U_pwd']);
        
        if(!isset($_SESSION)){ session_start(); }
        if(!$this->CheckLoginInDB($email, $password)){
            return false;
        }
        
        $_SESSION[$this->GetLoginSessionVar()] = $email;
        
        return true;
    }
    
    function CheckLogin(){
         if(!isset($_SESSION)){ session_start(); }

         $sessionvar = $this->GetLoginSessionVar();
         
         if(empty($_SESSION[$sessionvar]))
         {
            return false;
         }
         return true;
    }
    
    function UserFullName(){
        return isset($_SESSION['name_of_user']) ? $_SESSION['name_of_user'] : $_SESSION['email_of_user'];
    }
    
    function UserEmail(){
        return isset($_SESSION['email_of_user']) ? $_SESSION['email_of_user'] : '';
    }
    
    function LogOut(){
        session_start();
        
        $sessionvar = $this->GetLoginSessionVar();
        
        $_SESSION[$sessionvar]=NULL;
        
        unset($_SESSION[$sessionvar]);
    }
    
    function EmailResetPasswordLink(){
        if(empty($_POST['email'])){
            $this->HandleError("Email is empty!");
            return false;
        }
		
        $user_rec = array();
		
        if(false === $this->GetUserFromEmail($_POST['email'], $user_rec)){
            return false;
        }
		
        if(false === $this->SendResetPasswordLink($user_rec)){
            return false;
        }
        return true;
    }
    
    function ResetPassword(){
        if(empty($_GET['email'])){
            $this->HandleError("Email is empty!");
            return false;
        }
        if(empty($_GET['code'])){
            $this->HandleError("reset code is empty!");
            return false;
        }
        $email = trim($_GET['email']);
        $code = trim($_GET['code']);
        
        if($this->GetResetPasswordCode($email) != $code){
            $this->HandleError("Bad reset code!");
            return false;
        }
        
        $user_rec = array();
        if(!$this->GetUserFromEmail($email,$user_rec)){
            return false;
        }
        
        $new_password = $this->ResetUserPasswordInDB($user_rec);
        if(false === $new_password || empty($new_password)){
            $this->HandleError("Error updating new password");
            return false;
        }
        
        if(false == $this->SendNewPassword($user_rec,$new_password)){
            $this->HandleError("Error sending new password");
            return false;
        }
        return true;
    }
    
    function ChangePassword(){
        if(!$this->CheckLogin()){
            $this->HandleError("Not logged in!");
            return false;
        }
        
        if(empty($_POST['oldpwd'])){
            $this->HandleError("Old password is empty!");
            return false;
        }
        if(empty($_POST['newpwd'])){
            $this->HandleError("New password is empty!");
            return false;
        }
        
        $user_rec = array();
        if(!$this->GetUserFromEmail($this->UserEmail(),$user_rec)){
            return false;
        }
        
        $pwd = trim($_POST['oldpwd']);
        
        if($user_rec['password'] != md5($pwd)){
            $this->HandleError("The old password does not match!");
            return false;
        }
        $newpwd = trim($_POST['newpwd']);
        
        if(!$this->ChangePasswordInDB($user_rec, $newpwd)){
            return false;
        }
        return true;
    }
    
    //-------Public Helper functions -------------
    function GetSelfScript(){
        return htmlentities($_SERVER['PHP_SELF']);
    }    
    
    function SafeDisplay($value_name){
        if(empty($_POST[$value_name])){
            return'';
        }
        return htmlentities($_POST[$value_name]);
    }
    
    function RedirectToURL($url){
        header("Location: $url");
        exit;
    }
    
    function GetSpamTrapInputName(){
        return 'sp'.md5('KHGdnbvsgst'.$this->rand_key);
    }
    
    function GetErrorMessage(){
        if(empty($this->error_message)){
            return '';
        }
        $errormsg = nl2br(htmlentities($this->error_message));
        return $errormsg;
    }    
    //-------Private Helper functions-----------
    
    function HandleError($err){
        $this->error_message .= $err."\r\n";
    }
    
    function HandleDBError($err){
        $this->HandleError($err."\r\n mysqlerror:".mysql_error());
    }
    
    function GetFromAddress(){
        if(!empty($this->from_address)){
            return $this->from_address;
        }

        $host = $_SERVER['SERVER_NAME'];

        $from ="viskoservices@visko.net";
        return $from;
    } 
    
    function GetLoginSessionVar(){
        $retvar = md5($this->rand_key);
        $retvar = 'usr_'.substr($retvar,0,10);
        return $retvar;
    }
    
    function CheckLoginInDB($username, $password){
        if(!$this->DBLogin()){
            $this->HandleError("Database login failed!");
            return false;
        }
		
        $email = $this->SanitizeForSQL($username);
        $pwdmd5 = md5($password);
        //$qry = "Select email from $this->tablename where username='$username' and password='$pwdmd5' and confirmcode='y'";
        $qry = "SELECT U_first, U_last, U_email, U_pwd FROM $this->tablename WHERE U_email='$email' and U_pwd='$pwdmd5'";
        
        $result = mysql_query($qry, $this->connection);
        
        if(!$result || mysql_num_rows($result) <= 0){
            $this->HandleError("Error logging in. The username or password does not match");
            return false;
        }
        
        $row = mysql_fetch_assoc($result);
        
        
        $_SESSION['name_of_user']  = $row['U_first'].' '.$row['U_last'];
        $_SESSION['email_of_user'] = $row['U_email'];
        
        return true;
    }
    
    function UpdateDBRecForConfirmation(&$user_rec){
        if(!$this->DBLogin()){
            $this->HandleError("Database login failed!");
            return false;
        }   
        $confirmcode = $this->SanitizeForSQL($_GET['code']);
        
        $result = mysql_query("Select name, email from $this->tablename where confirmcode='$confirmcode'",$this->connection);   
        if(!$result || mysql_num_rows($result) <= 0){
            $this->HandleError("Wrong confirm code.");
            return false;
        }
        $row = mysql_fetch_assoc($result);
        $user_rec['name'] = $row['name'];
        $user_rec['email']= $row['email'];
        
        $qry = "Update $this->tablename Set confirmcode='y' Where  confirmcode='$confirmcode'";
        
        if(!mysql_query( $qry ,$this->connection)){
            $this->HandleDBError("Error inserting data to the table\nquery:$qry");
            return false;
        }      
        return true;
    }
    
    function ResetUserPasswordInDB($user_rec){
        $new_password = substr(md5(uniqid()),0,10);
        
        if(false == $this->ChangePasswordInDB($user_rec,$new_password)){
            return false;
        }
        return $new_password;
    }
    
    function ChangePasswordInDB($user_rec, $newpwd){
        $newpwd = $this->SanitizeForSQL($newpwd);
        
        $qry = "Update $this->tablename Set password='".md5($newpwd)."' Where  id_user=".$user_rec['id_user']."";
        
        if(!mysql_query( $qry ,$this->connection)){
            $this->HandleDBError("Error updating the password \nquery:$qry");
            return false;
        }     
        return true;
    }
    
	//used
    function GetUserFromEmail($email, &$user_rec){
        if(!$this->DBLogin()){
            $this->HandleError("Database login failed!");
            return false;
        }
		
        $email = $this->SanitizeForSQL($email);
        
        $result = mysql_query("SELECT * FROM $this->tablename where U_email = '$email'", $this->connection);  

        if(!$result || mysql_num_rows($result) <= 0){
            $this->HandleError("There is no user with email: $email");
            return false;
        }
        $user_rec = mysql_fetch_assoc($result);
        
        return true;
    }
    
    function SendUserWelcomeEmail(&$user_rec){
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($user_rec['email'],$user_rec['name']);
        
        $mailer->Subject = "Welcome to ".$this->sitename;

        $mailer->From = $this->GetFromAddress();        
        
        $mailer->Body ="Hello ".$user_rec['name']."\r\n\r\n".
        "Welcome! Your registration  with ".$this->sitename." is completed.\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;

        if(!$mailer->Send()){
            $this->HandleError("Failed sending user welcome email.");
            return false;
        }
        return true;
    }
    
    function SendAdminIntimationOnRegComplete(&$user_rec){
        if(empty($this->admin_email)){
            return false;
        }
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($this->admin_email);
        
        $mailer->Subject = "Registration Completed: ".$user_rec['name'];

        $mailer->From = $this->GetFromAddress();         
        
        $mailer->Body ="A new user registered at ".$this->sitename."\r\n".
        "Name: ".$user_rec['name']."\r\n".
        "Email address: ".$user_rec['email']."\r\n";
        
        if(!$mailer->Send()){
            return false;
        }
        return true;
    }
    
    function GetResetPasswordCode($email){
       return substr(md5($email.$this->sitename.$this->rand_key),0,10);
    }
    
    function SendResetPasswordLink($user_rec){
        $email = $user_rec['U_email'];
        $pwd = md5($user_rec['U_pwd']);
        
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($email, $user_rec['name']);
        
        $mailer->Subject = "Your reset password request at " . $this->sitename;

        $mailer->From = $this->GetFromAddress();
        
        //$link = $this->GetAbsoluteURLFolder().'/resetpwd.php?email='.urlencode($email).'&code='.urlencode($this->GetResetPasswordCode($email));

        $mailer->Body ="Hello " . $email . "\r\n\r\n".
        "There was a request to reset your password at " . $this->sitename . "\r\n".
        "Your password is: " . $pwd . "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        
        if(!$mailer->Send()){
            return false;
        }
        return true;
    }
    
    function SendNewPassword($user_rec, $new_password){
        $email = $user_rec['email'];
        
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($email,$user_rec['name']);
        
        $mailer->Subject = "Your new password for ".$this->sitename;

        $mailer->From = $this->GetFromAddress();
        
        $mailer->Body ="Hello ".$user_rec['name']."\r\n\r\n".
        "Your password is reset successfully. ".
        "Here is your updated login:\r\n".
        "username:".$user_rec['username']."\r\n".
        "password:$new_password\r\n".
        "\r\n".
        "Login here: ".$this->GetAbsoluteURLFolder()."/login.php\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        
        if(!$mailer->Send()){
            return false;
        }
        return true;
    }    
    
	//Used for the registration of the user.
	// if needed to validate any other user input just duplicate from line 509 to 536
    function ValidateRegistrationSubmission(){
        //This is a hidden input field. Humans won't fill this field.
        if(!empty($_POST[$this->GetSpamTrapInputName()]) ){
            //The proper error is not given intentionally
            $this->HandleError("Automated submission prevention: case 2 failed");
            return false;
        }
        
        $validator = new FormValidator();
        $validator->addValidation("U_email",     "req",   " Please Fill in Your Email");
        $validator->addValidation("U_email",     "email", " Please input a valid Email");
        $validator->addValidation("U_email_Con", "req",   " Please Confirm Your Email");
        $validator->addValidation("U_pwd",       "req",   " Please Fill in Your Password");
        $validator->addValidation("U_pwd_Con",   "req",   " Please Confirm Your Password");
        //$validator->addValidation("U_first",     "req",   " Please Fill in Your Firstname");
        //$validator->addValidation("U_last",      "req",   " Please Fill in Your Lastname");
        //$validator->addValidation("orgAffiliation", "req",   " Please fill in UserName");
		
        if(!$validator->ValidateForm()){
            $error='';
            $error_hash = $validator->GetErrors();
            foreach($error_hash as $inpname => $inp_err){
                $error .= $inpname.':'.$inp_err."\n";
            }
            $this->HandleError($error);
            return false;
        }
		
        return true;
    }
	
    function CollectRegistrationSubmission(&$formvars){
        $formvars['U_email'] = $this->Sanitize($_POST['U_email']);
        $formvars['U_email_Con'] = $this->Sanitize($_POST['U_email_Con']);
        $formvars['U_pwd']   = $this->Sanitize($_POST['U_pwd']);
        $formvars['U_pwd_Con']   = $this->Sanitize($_POST['U_pwd_Con']);
        $formvars['U_first'] = $this->Sanitize($_POST['U_first']);
        $formvars['U_last']  = $this->Sanitize($_POST['U_last']);
    }
	
	//newly added function
	function checkSimilar(&$formvars){
		$validator = new FormValidator();
		
		$email1 = $formvars['U_email'];
		$email2 = $formvars['U_email_Con'];
		$pass1  = $formvars['U_pwd'];
		$pass2  = $formvars['U_pwd_Con'];
		
		if($email1 !== $email2){
			$this->HandleError("Emails do not match");
            return false;
		}
		
		if($pass1 !== $pass2){
			$this->HandleError("Passwords do not match");
            return false;
		}
        return true;
	}
    
    function SendUserConfirmationEmail(&$formvars){
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($formvars['email'],$formvars['name']);
        
        $mailer->Subject = "Your registration with ".$this->sitename;

        $mailer->From = $this->GetFromAddress();        
        
        $confirmcode = $formvars['confirmcode'];
        
        $confirm_url = $this->GetAbsoluteURLFolder().'/confirmreg.php?code='.$confirmcode;
        
        $mailer->Body ="Hello ".$formvars['name']."\r\n\r\n".
        "Thanks for your registration with ".$this->sitename."\r\n".
        "Please click the link below to confirm your registration.\r\n".
        "$confirm_url\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;

        if(!$mailer->Send()){
            $this->HandleError("Failed sending registration confirmation email.");
            return false;
        }
        return true;
    }
	
    function GetAbsoluteURLFolder(){
        $scriptFolder = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://';
        $scriptFolder .= $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        return $scriptFolder;
    }
    
    function SendAdminIntimationEmail(&$formvars){
        if(empty($this->admin_email)){
            return false;
        }
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($this->admin_email);
        
        $mailer->Subject = "New registration: ".$formvars['name'];

        $mailer->From = $this->GetFromAddress();         
        
        $mailer->Body ="A new user registered at ".$this->sitename."\r\n".
        "Name: ".$formvars['name']."\r\n".
        "Email address: ".$formvars['email']."\r\n".
        "UserName: ".$formvars['username'];
        
        if(!$mailer->Send()){
            return false;
        }
        return true;
    }
    
	//used for the VisKo project
	// If need to use, just duplicate from line 607 to 632.
    function SaveToDatabase(&$formvars){
        if(!$this->DBLogin()){
            $this->HandleError("Database login failed!");
            return false;
        }
		
        if(!$this->ensureUserTable()){
            return false;
        }
		
		//checking if email is unique
        if(!$this->IsFieldUnique($formvars, 'email')){
            $this->HandleError("This email is already registered");
            return false;
        }
        
        /*if(!$this->IsFieldUnique($formvars, 'username')){
            $this->HandleError("This UserName is already used. Please try another username");
            return false;
        }*/
		
        if(!$this->InsertIntoUser($formvars)){
            $this->HandleError("Inserting to Database failed!");
            return false;
        }
        return true;
    }
    
	//used for the VisKo project
	// If need to use, just invoke the method
    function IsFieldUnique($formvars, $fieldname){
        $field_val = $this->SanitizeForSQL($formvars[$fieldname]);
        $qry = "SELECT U_email FROM $this->tablename WHERE $fieldname='".$field_val."'";
        
		$result = mysql_query($qry, $this->connection);   
        if($result && mysql_num_rows($result) > 0){
            return false;
        }
        return true;
    }
	
    //used for the VisKo project
	// If need to use, just invoke the method
    function DBLogin(){

        $this->connection = mysql_connect($this->db_host,$this->username,$this->pwd);

        if(!$this->connection){   
            $this->HandleDBError("Database Login failed! Please make sure that the DB login credentials provided are correct");
            return false;
        }
		
        if(!mysql_select_db($this->database, $this->connection)){
            $this->HandleDBError('Failed to select database: '.$this->database.' Please make sure that the database name provided is correct');
            return false;
        }
		
        if(!mysql_query("SET NAMES 'UTF8'",$this->connection)){
            $this->HandleDBError('Error setting utf8 encoding');
            return false;
        }
        return true;
    }    
    
	//used for the VisKo project
    function ensureUserTable(){
        $result = mysql_query("SHOW COLUMNS FROM $this->tablename");   
        if(!$result || mysql_num_rows($result) <= 0){
            return $this->createUserTable();
        }
        return true;
    }
    
	//same logic can be used for the other tables
    function createUserTable(){
        $qry = "CREATE TABLE IF NOT EXISTS $this->tablename(".
                "U_id INT NOT NULL AUTO_INCREMENT,".
                "U_email CHAR(255) NOT NULL,".
                "U_first CHAR(255),".
                "U_last CHAR(255),".
                "U_pwd CHAR(255) NOT NULL,".
                "U_reg_user BOOLEAN DEFAULT true,".
                "U_confirmed BOOLEAN DEFAULT false,".
                "U_reg_date CHAR(255) NOT NULL,".
                "U_affiliation CHAR(255) DEFAULT 'N/A',".
                "PRIMARY KEY(U_id, U_email)".
                ");";
                
        if(!mysql_query($qry, $this->connection)){
            $this->HandleDBError("Error creating the $this->tablename table \nquery was\n $qry");
            return false;
        }
        return true;
    }
    
	//inserts into designated table and creates a confirmation code
    function InsertIntoUser(&$formvars){
    
        //$confirmcode = $this->MakeConfirmationMd5($formvars['email']);
        
        //$formvars['confirmcode'] = $confirmcode;
        
        $insert_query = 'INSERT INTO ' . $this->tablename.'(U_email, U_first, U_last, U_pwd, U_affiliation)
                values(
                "' . $this->SanitizeForSQL($formvars['U_email']) . '",
                "' . $this->SanitizeForSQL($formvars['U_first']) . '",
                "' . $this->SanitizeForSQL($formvars['U_last']) . '",
                "' . md5($formvars['U_pwd']) . '",
                "' . $this->SanitizeForSQL($formvars['U_affiliation']) . '"
                )';
				
				//"' . $confirmcode . '"
        if(!mysql_query($insert_query, $this->connection)){
            $this->HandleDBError("Error inserting data to the $this->tablename table\nquery: >>> $insert_query <<<");
            return false;
        }        
        return true;
    }
	
    function MakeConfirmationMd5($email){
        $randno1 = rand();
        $randno2 = rand();
        return md5($email.$this->rand_key.$randno1.''.$randno2);
    }
	
    function SanitizeForSQL($str){
        if( function_exists( "mysql_real_escape_string" ) ){
              $ret_str = mysql_real_escape_string( $str );
        } else {
              $ret_str = addslashes( $str );
        }
        return $ret_str;
    }
    
 /*
    Sanitize() function removes any potential threat from the
    data submitted. Prevents email injections or any other hacker attempts.
    if $remove_nl is true, newline chracters are removed from the input.
    */
    function Sanitize($str,$remove_nl=true){
        $str = $this->StripSlashes($str);

        if($remove_nl){
            $injections = array('/(\n+)/i',
                '/(\r+)/i',
                '/(\t+)/i',
                '/(%0A+)/i',
                '/(%0D+)/i',
                '/(%08+)/i',
                '/(%09+)/i'
                );
            $str = preg_replace($injections,'',$str);
        }

        return $str;
    }
	
    function StripSlashes($str){
        if(get_magic_quotes_gpc()){
            $str = stripslashes($str);
        }
        return $str;
    }    
}
?>