
<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

require_once __DIR__ . '/vendor/autoload.php';

use Bitrix\Main\Loader;
use Bitrix\Disk\File;

class CBPGenerateDocument extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = [
            'idDoc' => null,           // ID исходного файла (DOCX)
            'ConvertedFileId' => null  // ID созданного PDF (результат)
        ];

        $this->SetPropertiesTypes([
            'idDoc' => ['Type' => 'int'],
            'ConvertedFileId' => ['Type' => 'int']
        ]);
    }

    public function Execute()
    {
        if (!Loader::includeModule('disk')) {
            $this->WriteToTrackingService("Модуль disk не подключен", 0, CBPTrackingType::Error);
            return CBPActivityExecutionStatus::Closed;
        }

        // Обработка входящего ID
        $idDoc = is_array($this->idDoc) ? (int)$this->idDoc[0] : (int)$this->idDoc;
        $this->WriteToTrackingService("Начало конвертации. ID файла: " . $idDoc, 0, CBPTrackingType::Report);

        // Получаем исходный файл
        $sourceFile = File::getById($idDoc);
        if (!$sourceFile) {
            $this->WriteToTrackingService("Файл с ID $idDoc не найден", 0, CBPTrackingType::Error);
            return CBPActivityExecutionStatus::Closed;
        }

        // Путь к исходному DOCX
        $srcPath = $_SERVER['DOCUMENT_ROOT'] . $sourceFile->getFile()["SRC"];
        if (!file_exists($srcPath)) {
            $this->WriteToTrackingService("Файл не существует по пути: $srcPath", 0, CBPTrackingType::Error);
            return CBPActivityExecutionStatus::Closed;
        }

        // Конвертация в PDF
        $pdfPath = $this->convertToPdf($srcPath);
        if (!$pdfPath) {
            return CBPActivityExecutionStatus::Closed;
        }

        // Загрузка PDF в Disk (в ту же папку, где исходный файл)
        $storage = $sourceFile->getStorage();
        $folder = $sourceFile->getParent();
        
        $fileArray = \CFile::MakeFileArray($pdfPath);
        $fileArray['name'] = basename($sourceFile->getName(), '.docx') . '.pdf';

        $convertedFile = $folder->uploadFile(
            $fileArray,
            [
                'CREATED_BY' => $GLOBALS['USER']->GetID(),
                'NAME' => $fileArray['name']
            ]
        );

        if (!$convertedFile) {
            $this->WriteToTrackingService("Ошибка загрузки PDF в Disk", 0, CBPTrackingType::Error);
            unlink($pdfPath);
            return CBPActivityExecutionStatus::Closed;
        }

        // Возвращаем ID нового файла ->getFileId(); - это айди физического файла
        $this->ConvertedFileId = $convertedFile->getId();
        
        $this->WriteToTrackingService("PDF создан. ID: " . $convertedFile->getId(), 0, CBPTrackingType::Report);

        // Удаляем временный PDF
        unlink($pdfPath);

        return CBPActivityExecutionStatus::Closed;
    }

    /**
     * Конвертация DOCX в PDF через LibreOffice
     */
    private function convertToPdf(string $docxPath): ?string
    {
        $outputDir = sys_get_temp_dir();
        $libreofficePath = 'libreoffice'; // Для Windows: '"C:\Program Files\LibreOffice\program\soffice.exe"'

        $command = sprintf(
            '%s --headless --convert-to pdf --outdir %s %s',
            $libreofficePath,
            escapeshellarg($outputDir),
            escapeshellarg($docxPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->WriteToTrackingService(
                "Ошибка конвертации: " . implode("\n", $output),
                0,
                CBPTrackingType::Error
            );
            return null;
        }

        return $outputDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
    }

  public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
  {
    $runtime = CBPRuntime::GetRuntime();
    $documentService = $runtime->GetService("DocumentService");

    $arMap = array(
      'idDoc' => 'id_doc'
    );

    if (!is_array($arCurrentValues)) {
      $arCurrentValues = array();
      $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
      if (is_array($arCurrentActivity["Properties"])) {
        foreach ($arMap as $k => $v) {
          if (array_key_exists($k, $arCurrentActivity["Properties"])) {
            $arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
          } else {
            $arCurrentValues[$arMap[$k]] = "";
          }
        }
      } else {
        foreach ($arMap as $k => $v)
          $arCurrentValues[$arMap[$k]] = "";
      }
    }

    $arFieldTypes = $documentService->GetDocumentFieldTypes($documentType);
    $arDocumentFields = $documentService->GetDocumentFields($documentType);

    return $runtime->ExecuteResourceFile(
      __FILE__,
      "properties_dialog.php",
      array(
        "arCurrentValues" => $arCurrentValues,
        "arDocumentFields" => $arDocumentFields,
        "arFieldTypes" => $arFieldTypes,
        "formName" => $formName,
        "popupWindow" => &$popupWindow,
      )
    );
  }

  public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
  {
    $arErrors = array();
    $runtime = CBPRuntime::GetRuntime();
    $arMap = array(
      'idDoc' => 'id_doc'
    );
    $arProperties = array();

    foreach ($arMap as $key => $value) {
      $arProperties[$key] = $arCurrentValues[$value];
    }

    $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
    $arCurrentActivity["Properties"] = $arProperties;

    return true;
  }
}
?>