<?php
  write_log($_POST);
 
    $missing_content = "Please supply all information.";
    $email_invalid   = "Email Address Invalid.";

    $response = "";
    if (isset($_POST['submitted'])) {
        write_log("Submitted");
        $name = $_POST['message_name'];
        $email = $_POST['message_email'];
       
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
            $response = store_scores_generate_response("error", $email_invalid);
            write_log("Bad email");
        } else { //email is valid
            write_log("Good email");
        }
        
      
    } else {
        write_log("NOT Submitted");
        $name = "";
        $email = "";
    }
?>


<style type="text/css">
  .error{
    padding: 5px 9px;
    border: 1px solid red;
    color: red;
    border-radius: 3px;
  }
 
  .success{
    padding: 5px 9px;
    border: 1px solid green;
    color: green;
    border-radius: 3px;
  }
 
  form span{
    color: red;
  }
</style>
 
<div id="respond">
  <?php echo $response; ?>
  <form action="<?php the_permalink(); ?>" method="post">
    <p><label for="name">Name: <span>*</span> <br><input type="text" name="message_name" value="<?php echo esc_attr($name); ?>"></label></p>
    <p><label for="message_email">Email: <span>*</span> <br><input type="text" name="message_email" value="<?php echo esc_attr($email); ?>"></label></p>

    <input type="hidden" name="submitted" value="1">
    <p><input type="submit"></p>
  </form>
</div>

