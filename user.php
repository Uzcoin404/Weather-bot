<?php 
    function setData($chat_id, $where, $data){
        file_put_contents("users/$where/$chat_id.txt", $data);
    }
    function getData($chat_id, $where){
        return file_get_contents("users/$where/$chat_id.txt");
    }
?>