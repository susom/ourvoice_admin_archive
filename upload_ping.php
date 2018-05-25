<?php
$walk_id = isset($_GET["walk_id"]) ? $_GET["walk_id"] : null;

$payload = $_POST;

$return_payload = array("_id" => $walk_id, "payload" => $payload);

print_r(json_encode($return_payload));
