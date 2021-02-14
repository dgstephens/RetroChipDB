        <div>
            <header>
                <?php if( $_SESSION["user_id"] > 0) { ?>
                
                <div class="dropdown">
                <button onclick="dropdownMenu()" class="dropbtn"><?php echo $_SESSION["first_name"]; ?></button>
                  <div id="myDropdown" class="dropdown-content">
                    <a href="<?php echo $retro_url; ?>user_account_info.php">Profile</a>
                    <a href="<?php echo $retro_url; ?>change_password.php">Change Password</a>
                <?php
                    // are we an admin user?
                    if( $_SESSION["admin_user"] == 1 ) { echo "<a href=\"" . $retro_url . "admin_area.php\"><i>Admin Area</i></a>\n"; }
                ?>
                    <a href="<?php echo $retro_url; ?>logout.php">Logout</a>
                  </div>
                </div>
            <?php    } ?>
            </header>
        </div>

        <script>
        /* When the user clicks on the button, 
        toggle between hiding and showing the dropdown content */
        function dropdownMenu() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
          if (!event.target.matches('.dropbtn')) {

            var dropdowns = document.getElementsByClassName("dropdown-content");
            var i;
            for (i = 0; i < dropdowns.length; i++) {
              var openDropdown = dropdowns[i];
              if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
              }
            }
          }
        }
        </script>
        
        <?php if( $_SESSION["user_id"] > 0) { ?> 
        <a href="<?php echo $retro_url; ?>login.php"><span class="title">RetroChipDB</span></a> <span class="sm_orange">alpha</span></p>
        <?php } else { ?>
        <a href="<?php echo $retro_url; ?>index.php"><span class="title">RetroChipDB</span></a> <span class="sm_orange">alpha</span></p>
        <?php } ?>