<?php
session_start();
// index Version .5
// last modified 140221	
// modified by: dgs
// TODO
// 1. Check if we have a valid cookie and use it to keep us logged in
// 
$debug=0;
include 'retro_vars.php';
include 'retro_functions.php';
include 'debug_code.php';

$launch_page_num = rand( 1, 3 );
?>
<!DOCTYPE html>
<!-- Copyright 2021 geekpower -->
<html>
    <head>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
        <link rel="stylesheet" type="text/css" href="retrostyle.css?<?php echo time(); ?>">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, minimal-ui">
        <title>RetroChipDB</title>
        <style>
            body{
                background: none;
            }
            html{
                background-image: url('img/launch_page_white_<?php echo $launch_page_num; ?>.jpg');
                background-repeat: no-repeat;
                background-attachment: fixed;
                background-position: center;
            }
            iframe{
                width: 560px;
                height: 315px;
            }

            .bs-example{
                margin: 20px;
            }
            .modal-content iframe{
                margin: 0 auto;
                display: block;
            }
            
            /* The Modal (background) */
            .modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1; /* Sit on top */
                padding-top: 200px; /* Location of the box */
                left: 0; 
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgb(0,0,0); /* Fallback color */
                background-color: rgba(0,25,50,0.4); /* Black w/ opacity */
            }

            /* Modal Content */
            .modal-content {
                position: relative;
                background-color: white;
                margin: auto;
                padding: 0;
                border: 1px solid #ea842a; /* this creates a solid border around the box */
                border-radius: 7px;
                width: 40%; /* the width of the box in the browser window */
                box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
                -webkit-animation-name: animatetop;
                -webkit-animation-duration: 0.4s;
                animation-name: animatetop;
                animation-duration: 0.4s
            }
            
            /* Modal Video Content */
            .modal-video {
                position: relative;
                background-color: white;
                margin: auto;
                padding: 0;
                border: 1px solid #ea842a; /* this creates a solid border around the box */
                border-radius: 7px;
                width: 600px; /* the width of the box in the browser window */
                box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
                -webkit-animation-name: animatetop;
                -webkit-animation-duration: 0.4s;
                animation-name: animatetop;
                animation-duration: 0.4s
            }
            
            
            /* Add Animation */
            @-webkit-keyframes animatetop {
                from {top:-300px; opacity:0} 
                to {top:0; opacity:1}
            }

            @keyframes animatetop {
                from {top:-300px; opacity:0}
                to {top:0; opacity:1}
            }

            /* The Close Button */
            .close {
                color: white;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: #000;
                text-decoration: none;
                cursor: pointer;
            }

            .modal-header {
                padding: 2px 16px;
                background-color: #ea842a;
                border-top-left-radius: 7px;
                border-top-right-radius: 7px;
                color: white;
            }

            .modal-body {
                padding: 2px 16px;
                background-color: white;
            }

            .modal-footer {
                padding: 2px 16px;
                background-color: #ea842a;
                border-bottom-left-radius: 7px;
                border-bottom-right-radius: 7px;
                color: white;
            }
            
            /* The Login Button */
            #myBtn {
                background-color: #ff7800;
                border: none;
                color: white;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }
            
            #myInfoBtn {
                background-color: #ffffff;
                border: 1px solid #888;                
                color: black;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }
            
            #myRequestBtn {
                background-color: #ff7800;
                border: none;
                color: white;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }

            .orangeButton {
                background-color: #ff7800;
                border: none;
                color: white;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }
            
           #myVideoBtn {
                background-color: #ffffff;
                border: 1px solid #888;                
                color: black;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }

            #learnMoreBtn {
                background-color: #ffffff;
                border: 1px solid #888;                
                color: black;
                padding: 10px 32px;
                text_decoration: none;
                margin: 4px 2px;
                border-radius: 7px;
                cursor: pointer;
                outline: none;
            }
            @media only screen and (max-width: 600px) {
                html{
                    background-image: url('img/SMALL_launch_page_white.jpg') ;
                }
                iframe{
                    width: 260px;
                    height: 160px;
                }
                .modal {
                    padding-top: 100px; /* Location of the box */
                    left: 0; 
                    top: 0;
                    width: 100%; /* Full width */
                    height: 100%; /* Full height */
                    overflow: auto; /* Enable scroll if needed */
                    background-color: rgb(0,0,0); /* Fallback color */
                    background-color: rgba(0,25,50,0.4); /* Black w/ opacity */
                }                
                .modal-content{
                    width: 90%;
                }
                .modal-video{
                    width: 90%;
                }
            /* The Login Button */
                #myBtn {
                    background-color: #ff7800;
                    border: none;
                    color: white;
                    padding: 10px 15px;
                    text_decoration: none;
                    margin: 4px 2px;
                    border-radius: 7px;
                    cursor: pointer;
                    outline: none;
                }

                #myInfoBtn {
                    background-color: #ffffff;
                    border: 1px solid #888;                
                    color: black;
                    padding: 10px 15px;
                    text_decoration: none;
                    margin: 4px 2px;
                    border-radius: 7px;
                    cursor: pointer;
                    outline: none;
                }

                #myRequestBtn {
                    background-color: #ff7800;
                    border: none;
                    color: white;
                    padding: 10px 15px;
                    text_decoration: none;
                    margin: 4px 2px;
                    border-radius: 7px;
                    cursor: pointer;
                    outline: none;
                }

               #myVideoBtn {
                    background-color: #ffffff;
                    border: 1px solid #888;                
                    color: black;
                    padding: 10px 15px;
                    text_decoration: none;
                    margin: 4px 2px;
                    border-radius: 7px;
                    cursor: pointer;
                    outline: none;
                }

                #learnMoreBtn {
                    background-color: #ffffff;
                    border: 1px solid #888;                
                    color: black;
                    padding: 10px 32px;
                    text_decoration: none;
                    margin: 4px 2px;
                    border-radius: 7px;
                    cursor: pointer;
                    outline: none;
                }                
            }
        </style>
        
        <!-- bootstrap modal script code -->
        <script type="text/javascript">
        $(document).ready(function(){
            /* Get iframe src attribute value i.e. YouTube video url
            and store it in a variable */
            var url = $("#cartoonVideo").attr('src');

            /* Assign empty url value to the iframe src attribute when
            modal hide, which stop the video playing */
            $("#myVideoModal").on('hide.bs.modal', function(){
                $("#cartoonVideo").attr('src', '');
            });

            /* Assign the initially stored url back to the iframe src
            attribute when modal is displayed again */
            $("#myVideoModal").on('show.bs.modal', function(){
                $("#cartoonVideo").attr('src', url);
            });
        });
        </script>
        
    </head>
    <body>
        <div>
            <header>
                <p align="right">
                    <button id="myBtn">Log In</button>
                </p>
            </header>
        </div>
        
        <!-- The Login Modal -->
        <div id="myModal" class="modal">

          <!-- Modal content -->
          <div class="modal-content">
            <div class="modal-header">
              <span class="close">&times;</span>
              <h2>Log In to RetroChipDB</h2>
            </div>
            <div class="modal-body">
                <form method="post" name="login" action="<?php echo $retro_url; ?>login.php">
                    <input id="username_input" type="text" name="username" placeholder="username" value="" /> <br>
                    <input id="password_input" type="password" name="password" placeholder="password" value="" /> <br>
                    <input type="submit" value="Log In" />
                </form>
            </div>
            <div class="modal-footer">
              <h3>Happiness is knowing where your chips are.</h3>
            </div>
          </div>
          
        </div>
        <!-- Login Modal Done -->
        
        <!-- The Info Modal -->
        <div id="myInfoModal" class="modal">

          <!-- Modal content -->
          <div class="modal-content">
            <div class="modal-header">
              <span class="close" id="close2">&times;</span>
              <h2>About RetroChipDB</h2>
            </div>
            <div class="modal-body">
                The Retro Chip DB exists to help you keep track of the nifty bits you use to
                keep your retro gear running. I developed it for myself and thought that
                there's likely at least one other person who might find this useful. So,
                Trevor, this is for you.
                <p>
                <p align="center">
                    <input type="button" id="learnMoreBtn" value="Learn More" onclick="location.href='/learn_more.php'">
                </p>    
            </div>
          </div>     
        </div>
        <!-- Info Modal Done -->        
        
        <!-- video modal -->
        <div id="myVideoModal" class="modal">

          <!-- Modal content -->
          <div class="modal-video">
            <div class="modal-header">
              <span class="close" id="close3">&times;</span>
              <h2>RetroChipDB</h2>
            </div>
            <div class="modal-body">
                <!--
                <iframe id="cartoonVideo" width="560" height="315" src="//www.youtube.com/embed/YE7VzlLtp-4" frameborder="0" allowfullscreen></iframe>
                -->
                <iframe id="cartoonVideo"   src="https://www.youtube.com/embed/cdWbLOEGvRo" frameborder="0" allowfullscreen></iframe>
                <!--
                <iframe width="560" height="315" src="https://www.youtube.com/embed/XomSDI_ML2A" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                -->
    
            </div>
          </div>     
        </div>              
        <!-- Video modal done -->
        <div> 
                <p align="center">
                    <button id="myInfoBtn">What is RetroChipDB?</button>
                    <!-- <button id="myVideoBtn">Watch the Video</button> -->
                    <!-- <button id="myRequestBtn">Sign Up!</button> -->
                    <button class="orangeButton" onclick = location.href="http://www.retrochipdb.com/create_user_account.php">Sign Up!</button>
                </p>        
        </div>
        <script>
            // Get the modal
            var modal = document.getElementById('myModal');

            // Get the button that opens the modal
            var btn = document.getElementById("myBtn");
            
            // Get the <span> element that closes the modal
            var span = document.getElementsByClassName("close")[0];

            // When the user clicks the button, open the modal 
            btn.onclick = function() {
                modal.style.display = "block";
            };

            // When the user clicks on <span> (x), close the modal
            span.onclick = function() {
                modal.style.display = "none";
            };

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            };    
    
        </script>
        <script>
            //get the Info modal
            var infoModal = document.getElementById('myInfoModal');
                    
            // get the button that opens the modal       
            var infoBtn = document.getElementById("myInfoBtn");
            
            // Get the <span> element that closes the modal
            var span2 = document.getElementById('close2');            
                    
            // when the user clicks the button, open the modal        
            infoBtn.onclick = function() {
                infoModal.style.display = "block";
            };
            // When the user clicks on <span> (x), close the modal
            span2.onclick = function() {
                infoModal.style.display = "none";
            };
            
            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target === infoModal) {
                    infoModal.style.display = "none";
                }
            };
            
        </script>
        <script>
            //get the Request modal
            var requestModal = document.getElementById('myRequestModal');
                    
            // get the button that opens the modal       
            var requestBtn = document.getElementById("myRequestBtn");
            
            // Get the <span> element that closes the modal
            var span4 = document.getElementById('close4');            
                    
            // when the user clicks the button, open the modal        
            requestBtn.onclick = function() {
                requestModal.style.display = "block";
            };
            // When the user clicks on <span> (x), close the modal
            span4.onclick = function() {
                requestModal.style.display = "none";
            };
            
            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target === requestModal) {
                    requestModal.style.display = "none";
                }
            };
            
        </script>        
        <script>
            //get the modal
            var videoModal = document.getElementById('myVideoModal');
                    
            // get the button that opens the modal       
            var videoBtn = document.getElementById("myVideoBtn");
            
            // Get the <span> element that closes the modal
            var span3 = document.getElementById('close3');            
                    
            // when the user clicks the button, open the modal        
            videoBtn.onclick = function() {
                videoModal.style.display = "block";
            };
            // When the user clicks on <span> (x), close the modal
            span3.onclick = function() {
                videoModal.style.display = "none";
            };
            
            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target === videoModal) {
                    videoModal.style.display = "none";
                }
            };
            
        </script>        
        
    </body>
</html>

