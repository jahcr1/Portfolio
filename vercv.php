<?php

// Especifica la ruta al archivo PDF
$pdfFile = 'componentes/cv.pdf';

if (file_exists($pdfFile)) {
  // Establecemos el encabezado para la visualización en el navegador
  header('Content-Type: application/pdf');
  header('Content-Disposition: inline; filename="' . basename($pdfFile) . '#zoom=75"');
  header('Content-Transfer-Encoding: binary');
  header('Accept-Ranges: bytes');

  // Lee el archivo y lo envía al navegador
  @readfile($pdfFile);
} else {
  echo "El archivo no existe.";
}
