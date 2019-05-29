<?php

$configs = include_once("config.php");



function openConn($configs) {
    $HOST = $configs["host"];
    $USERNAME = $configs["username"];
    $PASSWORD = $configs["password"];
    $DB = $configs["db"];

    //Db connection
    $conn = new mysqli($HOST, $USERNAME, $PASSWORD, $DB) or die ("Connection failed " . $conn-> error);


    return $conn;
}



//PHP array containing forenames.
$names = array(
    'Christopher',
    'Ryan',
    'Ethan',
    'John',
    'Zoey',
    'Sarah',
    'Michelle',
    'Samantha',
    'Walker',
    'Thompson',
    'Anderson',
    'Johnson',
    'Tremblay',
    'Peltier',
    'Cunningham',
    'Simpson',
    'Mercado',
    'Sellers',
    'Andrea',
    'Alessandro',
    'Simona',
    'Federica',
    'Alessandro',
    'Freddy',
    'Marcolins'
);

//Genereting random contents for the Docebo workers table
$values = "";
$names_len = count($names);
$rows_len = 5000;

for ($i = 0; $i < $rows_len; $i++) {
    $rand_node_id = mt_rand(1,24);
    $rand_name = $names[mt_rand(0,$names_len - 1)];

    if($i != $rows_len - 1)
        $values .= "(" . $rand_node_id . ", '" . $rand_name . "'),";
    else
        $values .= "(" . $rand_node_id . ", '" . $rand_name . "')";
}


$conn = openConn($configs);



$sql = "INSERT INTO workers (idNode,name) VALUES " . $values . ";";

if ($conn->query($sql) === TRUE) {
    echo "Insertion Succesfull";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
//if ($result) {
//    while ($row = mysqli_fetch_array($result)) {
//        echo $row["iRight"] . "<br>";
//        echo $row["iLeft"] . "<br>";
//    }
//}




$conn->close();


