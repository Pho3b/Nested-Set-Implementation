<?php

header("Content-type: application/json; charset=utf-8;");

$configs = include_once("config.php");


/************************************ API LOGIC ****************************************/

//Checking for input params validity
checkForValidParams();

//Opening connection with the DataBase (Connection data are stored in the config.php file)
$conn = openConn($configs);

//Assigning the GET Input params array and the Parent Node data
$INPUT_PARAMS = populateInputParamsArray();
$PARENT_NODE = populatePrentNodeDataArray($conn, $INPUT_PARAMS);

//Retrieving all the nodes that match with the user input parameters
$matching_nodes = retrieveMatchingNodes($PARENT_NODE, $INPUT_PARAMS);


//Building the result array
$nodes = [];
$single_node = [];

if ($matching_nodes) {
    while ($node = mysqli_fetch_array($matching_nodes)) {
        $single_node["node_id"] = $node["idNode"];
        $single_node["name"] = $node["nodeName"];
        $single_node["children_count"] = countChildren($node);

        array_push($nodes, $single_node);
    }
}else {
    exit("[]");
}

//Applying the pagination over the resulting nodes array
$nodes = applyPagination($nodes,$INPUT_PARAMS);

//Returning the JSON response and replacing all UTF-8 chars
echo Utf8_ansi(json_encode($nodes));



/************************************ FUNCTIONS ****************************************/

function applyPagination($nodes, $INPUT_PARAMS) {
    $nodes_length = count($nodes);
    $result = Array();

    //Returning the whole initial array cause the page size is larger or equal to the result
    if ($INPUT_PARAMS['page_size'] >= $nodes_length) {
        return $nodes;

    //Just returning the size of the page cause starting index in this case is always zero
    } else if ($INPUT_PARAMS['page_num'] == 0) {
        $from = 0;
        $to = $INPUT_PARAMS['page_size'];

    //No special case. Here i calculate the starting point and the ending point of the requested page
    } else {
        $to = 0;
        for ($i = 0; $i <= intval($INPUT_PARAMS['page_num']); $i++) {
            $to += $INPUT_PARAMS['page_size'];
        }

        $from = $to - $INPUT_PARAMS['page_size'];
    }


    //Cycling the nodes to create a new array from the page starting index to the ending index
    for ($i = 0; $i < $nodes_length; $i++) {
        if($i >= $from && $i < $to) {
            array_push($result, $nodes[$i]);
        }
    }

    return $result;
}



//This function executes the query that retrieves the requested node and all of his children
function retrieveMatchingNodes($PARENT_NODE, $INPUT_PARAMS) {

    $sql = "SELECT * FROM node_tree JOIN node_tree_names ON node_tree_names.idNode = node_tree.idNode 
        WHERE iLeft BETWEEN " . $PARENT_NODE['iLeft'] . " AND " . $PARENT_NODE['iRight'] . " AND node_tree_names.language = '" . $INPUT_PARAMS['language'] . "'";

    //Case when the user sends a search_keyword, in this case i don't want to retrieve the parent Node but i want to get all of his children filtered by the search_keyword
    if ($INPUT_PARAMS["search_keyword"] != "") {
        $sql .= "AND (node_tree_names.nodeName LIKE '%" . $INPUT_PARAMS["search_keyword"] . "%' AND " . $PARENT_NODE['level'] . " < node_tree.level)";
    }

    return $GLOBALS['conn']->query($sql);
}


//Executes a query to count the children of a single node (TODO: implement a more efficient way to do this)
function countChildren($current_node) {

    $sql = "SELECT COUNT(idNode) AS children FROM node_tree WHERE iLeft BETWEEN " . $current_node["iLeft"] . " AND " . $current_node["iRight"] . " AND level > " . $current_node["level"];
    $result = $GLOBALS['conn']->query($sql);
    $fetched_result = mysqli_fetch_array($result);

    return $fetched_result["children"];
}

//Opens the connection to the Database, accepts a 1 dimension config array as parameter.
function openConn($configs) {

    $HOST = $configs["host"];
    $USERNAME = $configs["username"];
    $PASSWORD = $configs["password"];
    $DB = $configs["db"];

    //Db connection
    $conn = new mysqli($HOST, $USERNAME, $PASSWORD, $DB) or die ("Connection failed " . $conn-> error);
    $conn->query("SET NAMES 'utf8'");

    return $conn;
}



//Returns a string error message enclosed into a JSON format
function returnError($error) {

    return '{"error":"' . $error . '"}';
}

//Checks the validity of the required input params retrieved from a GET HTTP request
function checkForValidParams() {

    //Missing or empty required params (Node_id and language are required params)
    if (!isset($_GET['node_id']) || $_GET['node_id'] == "" || !isset($_GET['language']) || $_GET['language'] == "")
        exit(returnError("Missing mandatory params"));

    //Checking for page number validity (Must be a number higher than zero)
    if (isset($_GET['page_num'])) {
        if ($_GET['page_num'] < 0)
            exit(returnError("Invalid page number requested"));
    }

    //Checking for page_size validity (Must be between 0 and 1000)
    if (isset($_GET['page_size'])) {
        if ($_GET['page_size'] < 0 || $_GET['page_size'] > 1000)
            exit(returnError("Invalid page size requested"));
    }
}

//Populate the input parameters array and performs additional checks on the data before assigning them
function populateInputParamsArray() {

    //Initializing default params values and assigning input GET variables
    $idNode = $_GET['node_id'];
    $language = $_GET['language'];

    if(isset($_GET['search_keyword']))
        $search_keyword = $_GET['search_keyword'];
    else
        $search_keyword = "";

    if(isset($_GET['page_num']) && $_GET['page_num'] != "")
        $page_num = $_GET['page_num'];
    else
        $page_num = 0;

    if(isset($_GET['page_size']) && $_GET['page_size'] != "")
        $page_size = $_GET['page_size'];
    else
        $page_size = 100;


    return array(
        "idNode" => $idNode,
        "language" => $language,
        "search_keyword" => $search_keyword,
        "page_num" => $page_num,
        "page_size" => $page_size
    );

}

//Populate the Parent Node array, returns an "Invalid node id" error if the query returns empty result
function populatePrentNodeDataArray($conn,$INPUT_PARAMS) {

    $sql = "SELECT iLeft,iRight,level FROM node_tree WHERE idNode = " . $INPUT_PARAMS["idNode"];
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = mysqli_fetch_array($result);

        return array(
            "iLeft" => $row["iLeft"],
            "iRight" => $row["iRight"],
            "level" => $row["level"]
        );

    } else {
        exit(returnError("Invalid node id"));
    }
}

//Converts char from utf-8 encoding to ansi
function Utf8_ansi($valor = '') {

    $utf8_ansi2 = array(
        "\u00c0" =>"À",
        "\u00c1" =>"Á",
        "\u00c2" =>"Â",
        "\u00c3" =>"Ã",
        "\u00c4" =>"Ä",
        "\u00c5" =>"Å",
        "\u00c6" =>"Æ",
        "\u00c7" =>"Ç",
        "\u00c8" =>"È",
        "\u00c9" =>"É",
        "\u00ca" =>"Ê",
        "\u00cb" =>"Ë",
        "\u00cc" =>"Ì",
        "\u00cd" =>"Í",
        "\u00ce" =>"Î",
        "\u00cf" =>"Ï",
        "\u00d1" =>"Ñ",
        "\u00d2" =>"Ò",
        "\u00d3" =>"Ó",
        "\u00d4" =>"Ô",
        "\u00d5" =>"Õ",
        "\u00d6" =>"Ö",
        "\u00d8" =>"Ø",
        "\u00d9" =>"Ù",
        "\u00da" =>"Ú",
        "\u00db" =>"Û",
        "\u00dc" =>"Ü",
        "\u00dd" =>"Ý",
        "\u00df" =>"ß",
        "\u00e0" =>"à",
        "\u00e1" =>"á",
        "\u00e2" =>"â",
        "\u00e3" =>"ã",
        "\u00e4" =>"ä",
        "\u00e5" =>"å",
        "\u00e6" =>"æ",
        "\u00e7" =>"ç",
        "\u00e8" =>"è",
        "\u00e9" =>"é",
        "\u00ea" =>"ê",
        "\u00eb" =>"ë",
        "\u00ec" =>"ì",
        "\u00ed" =>"í",
        "\u00ee" =>"î",
        "\u00ef" =>"ï",
        "\u00f0" =>"ð",
        "\u00f1" =>"ñ",
        "\u00f2" =>"ò",
        "\u00f3" =>"ó",
        "\u00f4" =>"ô",
        "\u00f5" =>"õ",
        "\u00f6" =>"ö",
        "\u00f8" =>"ø",
        "\u00f9" =>"ù",
        "\u00fa" =>"ú",
        "\u00fb" =>"û",
        "\u00fc" =>"ü",
        "\u00fd" =>"ý",
        "\u00ff" =>"ÿ");

    return strtr($valor, $utf8_ansi2);
}
