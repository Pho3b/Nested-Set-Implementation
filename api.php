<?php
header('Content-type: application/json; charset=utf-8');


$configs = include_once("config.php");



//*************** FUNCTIONS *********************

function openConn($configs) {
    $HOST = $configs["host"];
    $USERNAME = $configs["username"];
    $PASSWORD = $configs["password"];
    $DB = $configs["db"];

    //Db connection
    $conn = new mysqli($HOST, $USERNAME, $PASSWORD, $DB) or die ("Connection failed " . $conn-> error);


    return $conn;
}

function returnError($error) {
    return '{"error":"' . $error . '"}';
}




$conn = openConn($configs);

$search_keyword = "";

//Assigning GET variables
if(isset($_GET['node_id']) && $_GET['node_id'] != ""){
    $idNode = $_GET['node_id'];
}else{
    exit(returnError("Missing mandatory params"));
}

if(isset($_GET['language']) && $_GET['language'] != ""){
    $language = $_GET['language'];
}else{
    exit(returnError("Missing mandatory params"));
}

if(isset($_GET['search_keyword'])){
    $search_keyword = $_GET['search_keyword'];
}

if(isset($_GET['page_num']) && $_GET['page_num'] > 0){
    $page_num = $_GET['page_num'];
}else{
    exit(returnError("Invalid page number requested"));
}

if(isset($_GET['page_size']) && $_GET['page_size'] >= 0 && $_GET['page_size'] <= 100){
    $page_size = $_GET['page_size'];
}else{
    exit(returnError("Invalid page size requested"));
}



/**************************** RETRIEVING THE 3 USEFULL DATA THAT I'LL USE IN THE NEXT QUERY ************************/
$sql = "SELECT iLeft,iRight,level FROM node_tree WHERE idNode = " . $idNode;
$result = $conn->query($sql);

$iLeft = 0;
$iRight = 0;
$level = 0;

if ($result && $result->num_rows > 0) {
    $row = mysqli_fetch_array($result);
    $iLeft = $row["iLeft"];
    $iRight = $row["iRight"];
    $level = $row["level"];
}else {
    exit(returnError("Invalid node id"));
}



//This query retrieves the requested node and all of his children
$sql = "SELECT * FROM node_tree JOIN node_tree_names ON node_tree_names.idNode = node_tree.idNode WHERE iLeft BETWEEN " . $iLeft . " AND " . $iRight . " AND node_tree_names.language = '" . $language . "'";

if ($search_keyword != null && $search_keyword != "") {
    $sql .= "AND (node_tree_names.nodeName LIKE '%" . $search_keyword . "%' AND " . $level . " < node_tree.level)";
}



$result = $conn->query($sql);


$nodes_arr = [];
$node_arr = [];



if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $node_arr["node_id"] = $row["idNode"];
        $node_arr["name"] = $row["nodeName"];


        $sql = "SELECT COUNT(idNode) AS children FROM node_tree WHERE iLeft BETWEEN " . $row["iLeft"] . " AND " . $row["iRight"] . " AND level > " . $row["level"];
        $res = $conn->query($sql);
        $temp_row = mysqli_fetch_array($res);

        $node_arr["children_count"] = $temp_row["children"];

        array_push($nodes_arr, $node_arr);

    }

}else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}





var_dump(json_encode($nodes_arr));
