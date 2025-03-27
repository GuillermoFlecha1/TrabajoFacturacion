<?php

namespace Drupal\facturation\Utils;

use setasign\Fpdi\Fpdi;

class PdfWithRotation extends Fpdi {
    protected $angle = 0;

    public function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) $x = $this->GetX();
        if ($y == -1) $y = $this->GetY();
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
            $this->_out(sprintf('q %.2F %.2F %.2F %.2F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', 
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }
}
