            .dropbtn {
                background-color: #ff7800;
                color: white;
                padding: 10px;
                font-size: 16px;
                border: none;
                border-top-right-radius: 7px;
                border-top-left-radius: 7px;
                cursor: pointer;
                outline: none;
            }

            .dropbtn:hover, .dropbtn:focus {
                background-color: #ee6700;
            }

            .dropdown {
                float: right;
                position: relative;
                display: inline-block;
            }

            .dropdown-content {
                display: none;
                position: absolute;
                background-color: #ee6700;
                min-width: 200px;
                overflow: auto;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                right: 0;
                z-index: 1;
            }

            .dropdown-content a {
                color: white;
                padding: 6px 16px;
                text-decoration: none;
                display: block;
            }

            .dropdown a:hover {background-color: #ff7800}

            .show {display:block;}
            