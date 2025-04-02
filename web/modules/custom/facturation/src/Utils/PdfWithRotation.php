<?php

namespace Drupal\facturation\Utils;

use setasign\Fpdi\Fpdi;

class PdfWithRotation extends Fpdi {
    protected $angle = 0;
    protected array $extgstates = []; // Declarar la propiedad correctamente

    // Nueva propiedad para la marca de agua
    public $watermark = '';

    // Sobrescribe AddPage para aplicar la marca de agua automáticamente.
    // Ahora se incluye $rotation para ser compatible con la firma de la clase base.
    public function AddPage($orientation = '', $size = '', $rotation = 0) {
        parent::AddPage($orientation, $size, $rotation);
        if (!empty($this->watermark)) {
            $this->AddWatermark($this->watermark);
        }
    }

    public function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) {
            $x = $this->GetX();
        }
        if ($y == -1) {
            $y = $this->GetY();
        }
        if ($this->angle != 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle_rad = $angle * M_PI / 180;
            $c = cos($angle_rad);
            $s = sin($angle_rad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.2F %.2F %.2F %.2F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', 
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
            ));
        }
    }

    /**
     * Dibuja la marca de agua en la página.
     *
     * @param string $text
     *   El texto de la marca de agua.
     */
    public function AddWatermark($text) {
        // Ajustar transparencia
        $this->SetAlpha(0.1);
        
        // Configurar color y fuente (gris claro, negrita e itálica a 50 pts)
        $this->SetTextColor(150, 150, 150);
        $this->SetFont('Arial', 'BI', 50);
        
        // Obtener dimensiones de la página
        $pageWidth = $this->GetPageWidth();
        $pageHeight = $this->GetPageHeight();

        // Dibujar la marca grande en diagonal (centrada)
        $x = ($pageWidth / 2) - 40;
        $y = ($pageHeight / 2);
        $this->Rotate(45, $x, $y);
        $this->Text($x, $y, $text);
        $this->Rotate(0);

        // Dibujar la marca pequeña repetida en diagonal
        $this->SetFont('Arial', '', 12);
        for ($i = 20; $i < $pageWidth; $i += 50) {
            for ($j = 20; $j < $pageHeight; $j += 50) {
                $this->Rotate(45, $i, $j);
                $this->Text($i, $j, $text);
                $this->Rotate(0);
            }
        }
        
        // Restaurar opacidad, color y fuente
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 12);
        $this->SetAlpha(1);
    }
    
    public function SetAlpha($alpha, $blendMode = 'Normal') {
        $alpha = max(0, min(1, $alpha));
        $gsIndex = count($this->extgstates) + 1;
        $gs = sprintf('/GS%d gs', $gsIndex);
        $this->extgstates[] = [
            'ca' => $alpha,
            'CA' => $alpha,
            'BM' => '/' . $blendMode
        ];
        $this->_out($gs);
    }
}