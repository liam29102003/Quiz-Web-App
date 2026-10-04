<!DOCTYPE html>
<html lang="en">
<?php 
// Start session
session_start();
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎯Quizzie Login</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
               extend:{
                screens: {
                'sm': '300px',   
                'md': '876px' 
                },
               }
          }
            
        }
    </script>

    <style>
         body {
            font-family: 'Itim', cursive;
        }
    </style>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="w-full flex">
        <div class="md:w-1/2 sm:hidden bg-[url('../images/icons/playinggame.png')] md:flex flex-col justify-center items-center h-lvh sticky top-0 bg-cover bg-center" > </div>

        <div class="md:w-1/2 sm:w-full flex justify-center items-center my-[50px]">
            <div class="w-2/3 ">
                <div class="flex justify-center">
                   <!-- <h1 class="text-2xl font-bold text-[#8035FB]">🎯 Quizzie</h1> -->
                </div>
               
                
                
                <div>
                    <form action="login.php" method="post"  onsubmit="return validateForm()">
                       
                        <h3 class="font-bold text-3xl text-center mb-5">Welcome Player!</h3>
                        <div class="mt-3 w-full">
                            <div class="text-base">Email<span class="font-bold text-red-500">*</span></div>
                            <input type="email" name="email" class="w-full h-10 border border-[#9696E8] py-1 px-2 rounded-md" placeholder="" value="<?php setValue("email") ?>" required/>
                        </div>
                        
                        <div class="mt-3 w-full">
                            <div class="text-base">Password<span class="font-bold text-red-500">*</span></div>
                            <div class="flex flex-row w-full h-10 justify-between rounded border border-[#9696E8]">
	                            <input type="password" name="password" class="w-full py-1 px-2 rounded-md " id="cpassword1" placeholder="" value="<?php setValue("password") ?>"required/>
	                            <button class="mt-2 p-0" type="button" id="handle_btn1">
						            <i class="fa-solid fa-eye w-8 h-8 pointer" id="show_password1"></i>
						            <i class="fa-solid fa-eye-slash hidden w-8 h-8 pointer" id="hide_password1"></i>
						        </button>
					        </div>
                        </div>
        
                        <div class="mt-5 w-full">
                            <input type="submit" class="w-full h-10 bg-[#6859DE] hover:bg-blue-900 cursor-pointer text-white py-2 px-2 rounded-md" value="Login"/>
                        </div>

                        <div class="mt-2 w-full flex justify-center">
                            <span class="text-sm ">Doesn't have an account?</span> &nbsp;<a href="signup.php" class="font-bold text-sm text-[#8035FB]">Sign up</a>
                     </div>
                    </form>
    
                    
                </div>


            </div>
        </div>
        
    </div>

   
</body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    const handle_btn1 =document.getElementById('handle_btn1');
    const show_password1=document.getElementById('show_password1');
    const hide_password1=document.getElementById('hide_password1');
    var password1=document.getElementById("cpassword1") ;

    handle_btn1.addEventListener('click', function () {
        if (password1.type === "password") {
            hide_password1.classList.toggle('hidden');
            show_password1.classList.toggle('hidden');
            password1.type = "text";
        } else {
            password1.type = "password";
            hide_password1.classList.toggle('hidden');
            show_password1.classList.toggle('hidden');
        }
    }); });
</script>

<?php
require 'connect.php';
require '../connect_redis.php';

function setValue($fieldName)
{
    if( isset($_POST[$fieldName]) )
    {
        echo htmlspecialchars($_POST[$fieldName]);
    }
}

// Validate the login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form inputs
    $email = pg_escape_string($conn, $_POST['email']);
    $password = pg_escape_string($conn, $_POST['password']);

    $query1 = "SELECT id, name, email, password_hash, profile_picture FROM admin WHERE email = $1";
    $result = pg_query_params($conn, $query1, array($email));
    if ($result && pg_num_rows($result) > 0) {
        $admin = pg_fetch_assoc($result);
        if (password_verify($password, $admin['password_hash'])) {
           
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['profile_picture'] = $admin['profile_picture'];

            echo "<script>window.location.href = '../Admin/admin_dashboard.php';</script>";
        }
    }else {
        
        // Query the users table to verify the email and password
        $query = "SELECT id, name, email, password_hash, coins, profile_picture FROM users WHERE email = $1";
        $result = pg_query_params($conn, $query, array($email));

        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            // print_r($user);
            // exit;
            // Verify password using password_hash and password_verify
            if (password_verify($password, $user['password_hash'])) {
                
                // Correct password, create session
                $_SESSION['isLoggedIn'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['coins'] = $user['coins'];
                $_SESSION['profile_picture'] = $user['profile_picture'];


                // Add the user to Redis (active users list)
                //$redis->set("user:{$user['id']}:active", true); // Set user as active
                // $redis->sadd("active_users", $user['id']);
                if (isset($_SESSION['user_id'])) {
                    $redis->sadd("active_users", $user['id']);
                    //$redis->setex('active_user:' . $_SESSION['user_id'], 1800, 'online');
                    
                }

                
               // Redirect to the dashboard or another page
                echo "<script>window.location.href = './home.php';</script>";
            } else {
                echo "<script>alert('Incorrect email or password');</script>";
            }
        } else {
            echo "<script>alert('User not found');</script>";
        }   
    }
    pg_free_result($result); // Free the query result
}

pg_close($conn); // Close the PostgreSQL connection
?>

</html>