<?php
session_start();

if(isset($_SESSION['role'])){

    if($_SESSION['role'] == "admin"){
        header("Location: admin/dashboard.php");
    }

    else if($_SESSION['role'] == "anggota"){
        header("Location: anggota/dashboard.php");
    }

}
else{
    header("Location: auth/login.php");
}
?>