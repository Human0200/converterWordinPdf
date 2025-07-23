<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<!-- ID документа -->
<tr>
     <td align="right" width="40%"><b><?=GetMessage("GED_ID_DOC_LABEL")?></b> :</td>
     <td width="60%">
         <?=CBPDocument::ShowParameterField("string", 'id_doc', $arCurrentValues['id_doc'], Array('size'=>'50'))?>
     </td>
</tr>
