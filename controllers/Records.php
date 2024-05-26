<?php
class Controller_Records extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/records/lists.php",
        "orderlists"    => "actions/records/Orderlists.php",
        "orderlistsv2"  => "actions/records/Orderlistsv2.php",
        "accountlists"  => "actions/records/Accountlists.php",
        "teacherlists"  => "actions/records/Teacherlists.php",
        "studentlists"  => "actions/records/Studentlists.php",
    );
}
