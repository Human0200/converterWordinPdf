<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
     "NAME" => GetMessage("GED_DESCR_NAME"),
     "DESCRIPTION" => GetMessage("GED_DESCR_DESCR"),
     "TYPE" => "activity",
     "CLASS" => "GenerateDocument",
     "JSCLASS" => "BizProcActivity",
     "CATEGORY" => array(
         "ID" => "other",
     ),
     "RETURN" => array(
        "ConvertedFileId" => array(
            "NAME" => 'Айди конвертированого файла',
            "TYPE" => "int",
        ),
     ),
);