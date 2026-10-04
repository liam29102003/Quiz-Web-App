<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
  <title>🎯Quizzie Sign Up</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
   
<!-- jQuery (Required) for flag country select -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet"/>

<!-- Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</head>
<body>
    <div class="w-full flex">

        <div class="md:w-1/2 sm:hidden bg-[url('../images/icons/11317239.jpg')] md:flex justify-center items-center h-lvh sticky top-0 bg-cover bg-center" >    </div>

        <div class="md:w-1/2 sm:w-full flex justify-center items-center my-[50px]">
            <div class="w-2/3 ">
                <div class="flex justify-center">
                  <!--<img src="" alt="Logo"> -->
                </div>
                
                <div class="text-center font-bold text-3xl mb-3">Welcome New User!</div>
                                
                <div class="relative">
                    <form action="signup.php" method="post" id="" class="sForm"  onsubmit="return validateForm1()">
                                              
                        <div class="flex gap-3">
                                    
                            <div class="flex-1">
                                <div class="text-base">Username<span class="font-bold text-red-500">*</span></div>
                                <input type="text" name="username" class="w-full h-8 border border-[#9696E8]  py-1 px-2 rounded-md" placeholder=""  value="<?php setValue("username") ?>" required/>
                            </div>
                        
                        </div>
        
                        <div class="mt-3 w-full">
                            <div class="text-base">Email<span class="font-bold text-red-500">*</span></div>
                            <input type="email" name="email" class="w-full h-8 border border-[#9696E8]  py-1 px-2 rounded-md" placeholder=""  value="<?php setValue("email") ?>" required/>
                        </div>
        
                        <div class=" mt-3 w-full ">    
                            <div class="text-base">Country<span class="font-bold text-red-500">*</span></div>
                               <select name="country" id="country-select" class="w-full h-8 border border-[#9696E8]  py-1 px-2 rounded-md" required autocomplete="on">           
                                                       
                               </select>                            
                        </div>
                        
                        <div class="mt-3 w-full">
                            <div class="text-base">Password<span class="font-bold text-red-500">*</span></div>
                            <div class="flex flex-row w-full h-10 justify-between rounded border border-[#9696E8] outline-[#9696E8]">
                            <input type="password"  name="password" class="w-full py-1 px-2 rounded-md" id="cpassword1" placeholder=""  value="<?php setValue("password") ?>" required autocomplete="new-password"/>
                            <button class="mt-2 p-0" type="button" id="handle_btn1">
                        <i class="fa-solid fa-eye w-8 h-8 pointer" id="show_password1"></i>
                        <i class="fa-solid fa-eye-slash hidden w-8 h-8 pointer" id="hide_password1"></i>
                    </button>
                            </div>
                        </div>
        
                        <div class="mt-3 w-full">
                            <div class="text-base">Confirm Password<span class="font-bold text-red-500">*</span></div>
                            <div class="flex flex-row w-full h-10 justify-between rounded border border-[#9696E8] outline-[#9696E8]">
                            <input type="password" name="confirmpassword" class="w-full py-1 px-2 rounded-md" id="cpassword2" placeholder="" value="<?php setValue("confirmpassword") ?>" required autocomplete="new-password"/>
                              <button class="mt-2 p-0" type="button" id="handle_btn2">
                        <i class="fa-solid fa-eye w-8 h-8 pointer" id="show_password2"></i>
                        <i class="fa-solid fa-eye-slash hidden w-8 h-8 pointer" id="hide_password2"></i>
                      </button>
                    </div> 
                        </div>
                        
                 <!-- Display error message if present -->
                
                <span id="nPError" class="text-red-500 text-sm"></span>
                        <div class="mt-5 w-full">
                            <input type="submit" name="signup" class="w-full bg-[#6859DE] hover:bg-blue-900 cursor-pointer text-white py-2 px-2 rounded-md" value="Create Account"/>
                        </div>
                        
                        <div class="mt-2 w-full flex justify-center">
                               <span class="text-sm ">Already have an account?</span> &nbsp; <a href="login.php" class="font-bold text-sm text-[#8035FB]">Login</a>
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
        });

        const handle_btn2 =document.getElementById('handle_btn2');
        const show_password2=document.getElementById('show_password2');
        const hide_password2=document.getElementById('hide_password2');
        var password2=document.getElementById("cpassword2") ;

        handle_btn2.addEventListener('click', function () {
            if (password2.type === "password") {
                hide_password2.classList.toggle('hidden');
                show_password2.classList.toggle('hidden');
                password2.type = "text";
            } else {
                password2.type = "password";
                hide_password2.classList.toggle('hidden');
                show_password2.classList.toggle('hidden');
            }
        });
     
    }
    );
    
    function validateForm1() {
        var isValid = true;

        // Get input values
        var cP = document.getElementsByName("password")[0].value;
        var nP = document.getElementsByName("confirmpassword")[0].value;
        // Get error message elements       
        var nPError = document.getElementById("nPError");
        

        // Clear previous error messages        
        nPError.textContent = "";
        

        // Password strength pattern (e.g., 8+ chars, at least one uppercase, one lowercase, one number, and one special character)
        var passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

       

        // Validate new password strength
        if (!nP.match(passwordPattern)) {
            nPError.textContent = "Password must be at least 8 characters long and contain one uppercase letter, one lowercase letter, one number, and one special character.";
            isValid = false;
        }

        // Validate confirm password matches new password
        if (nP !== cP) {
            nPError.textContent = "Confirm password does not match .";
            isValid = false;
        }

        // Return false to prevent form submission if validation fails
        return isValid;
    }
 
</script>
<script>

// Fetch country data from the JSON file
fetch('country.json')
    .then(response => response.json())
    .then(countryData => {
        const selectElement = document.getElementById("country-select");
        const selectedCountry = "<?php echo isset($_POST['country']) ? $_POST['country'] : ''; ?>";

        // Clear existing options (in case it re-renders)
        selectElement.innerHTML = '<option value="" disabled selected>Select a country</option>';

        // Create options dynamically
        countryData.forEach(country => {
            const option = document.createElement("option");
            option.value = country.value;
            option.dataset.image = country["data-image"];
            option.textContent = country.value;

            selectElement.appendChild(option);
        });

        $(document).ready(function() {
        $('#country-select').select2({
            templateResult: formatCountry,
            templateSelection: formatCountry
        });
    
    })
    .catch(error => {
        console.error('Error fetching country data:', error);
    });

    
        function formatCountry(option) {
            if (!option.id) {
                return option.text;
            }
    
            var imageUrl = $(option.element).data('image');
            if (!imageUrl) {
                return option.text;
            }
    
            var $option = $(
                '<span class="flex flex-row justify-start items-center"><img src="' + imageUrl + '" style="width: 20px; height: 15px; margin-right: 10px;" >' + option.text + '</span>'
            );
            return $option;
        }


        $(document).on('change', '#country-select', function() {
            var selectedOption = $(this).find('option:selected');
            var countryName = selectedOption.val();
            var flagUrl = selectedOption.data('image');

            if (country.value === selectedCountry) {
                    option.selected = true; // submitted value
            }
            
            console.log('Selected Country:', countryName);
            console.log('Flag URL:', flagUrl);
        });
    });
</script>

<?php
require 'connect.php';

function setValue($fieldName)
{
    if( isset($_POST[$fieldName]) )
    {
        echo htmlspecialchars($_POST[$fieldName]);
    }
}


// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $country = trim($_POST["country"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmpassword"];
    $profile = "../images/users/dfuser.png";
    
    // Validate passwords match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Hash the password before storing
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    // Check if username or email already exists
    $checkQuery = "SELECT * FROM users WHERE email = $1";
    $checkResult = pg_query_params($conn, $checkQuery, array($email));

    if (pg_num_rows($checkResult) > 0) {
        echo "<script>alert('Email already exists!'); window.history.back();</script>";
        exit();
    } 

    // Insert user into database
    $insertQuery = "INSERT INTO users (name, email, country, password_hash, profile_picture) VALUES ($1, $2, $3, $4, $5)";
    $result = pg_query_params($conn, $insertQuery, array($username, $email, $country, $hashedPassword, $profile));

    if ($result) {
        echo "<script>alert('Account created successfully!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Signup failed. Please try again.'); window.history.back();</script>";
    }

}

// Close connection
pg_close($conn);
?>




</html>