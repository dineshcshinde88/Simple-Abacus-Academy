<?php
// Minimal embedded FPDF-compatible subset for this project.
// Supports AddPage, SetFont, Cell, Ln, and Output with core Helvetica font.

class FPDF
{
    protected $page = 0;
    protected $pages = [];
    protected $wPt;
    protected $hPt;
    protected $w;
    protected $h;
    protected $k;
    protected $lMargin = 10;
    protected $tMargin = 10;
    protected $rMargin = 10;
    protected $bMargin = 10;
    protected $x = 10;
    protected $y = 10;
    protected $lasth = 0;
    protected $FontSizePt = 12;
    protected $FontSize = 12;
    protected $AutoPageBreak = true;
    protected $PageBreakTrigger;

    public function __construct($orientation='P', $unit='mm', $size='A4')
    {
        $this->k = ($unit === 'mm') ? 72/25.4 : 1;
        $sizes = [
            'A4' => [595.28, 841.89]
        ];
        $size = is_string($size) ? $sizes[strtoupper($size)] : $size;
        if (strtoupper($orientation) === 'P') {
            $this->wPt = $size[0];
            $this->hPt = $size[1];
        } else {
            $this->wPt = $size[1];
            $this->hPt = $size[0];
        }
        $this->w = $this->wPt / $this->k;
        $this->h = $this->hPt / $this->k;
        $this->PageBreakTrigger = $this->h - $this->bMargin;
    }

    public function SetAutoPageBreak($auto, $margin=0)
    {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    public function AddPage($orientation='', $size='')
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }

    public function SetFont($family, $style='', $size=12)
    {
        $this->FontSizePt = $size;
        $this->FontSize = $size;
    }

    protected function _escape($s)
    {
        return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $s);
    }

    protected function _out($s)
    {
        $this->pages[$this->page] .= $s . "\n";
    }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        if ($h == 0) {
            $h = $this->FontSizePt * 0.6;
        }
        if ($this->AutoPageBreak && $this->y + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        if ($txt !== '') {
            $x = $this->x * $this->k;
            $y = ($this->h - ($this->y + ($h * 0.7))) * $this->k;
            $text = $this->_escape($txt);
            $this->_out(sprintf('BT /F1 %.2F Tf %.2F %.2F Td (%s) Tj ET', $this->FontSizePt, $x, $y, $text));
        }
        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln == 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }

    public function Ln($h=null)
    {
        $this->x = $this->lMargin;
        $this->y += ($h === null) ? $this->lasth : $h;
    }

    public function Output($dest='', $name='doc.pdf')
    {
        $pdf = $this->buildPdf();
        if ($dest === 'D' || $dest === 'I') {
            if (ob_get_length()) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            $disposition = $dest === 'D' ? 'attachment' : 'inline';
            header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $pdf;
            return '';
        }
        if ($dest === 'S') {
            return $pdf;
        }
        file_put_contents($name, $pdf);
        return '';
    }

    protected function buildPdf(): string
    {
        $objects = [];
        $offsets = [];
        $buffer = "%PDF-1.3\n";

        $kids = [];
        $pageObjects = [];

        // Font object (1)
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        // Page contents objects
        foreach ($this->pages as $pageNum => $content) {
            $stream = $content;
            $len = strlen($stream);
            $objects[] = "<< /Length {$len} >>\nstream\n{$stream}endstream";
            $contentObj = count($objects); // index 2+ in objects list
            $pageObjects[] = $contentObj;
        }

        // Pages and page objects
        $pageCount = count($this->pages);
        $pageKids = [];

        $fontObjId = 1;
        $pageObjIds = [];

        // Build page objects referencing content
        $baseIndex = 1;
        for ($i = 0; $i < $pageCount; $i++) {
            $contentObjId = $baseIndex + 1 + $i; // font is 1, contents start at 2
            $pageObj = "<< /Type /Page /Parent {PAGES_ID} 0 R /MediaBox [0 0 {$this->wPt} {$this->hPt}] /Resources << /Font << /F1 {$fontObjId} 0 R >> >> /Contents {$contentObjId} 0 R >>";
            $objects[] = $pageObj;
            $pageObjIds[] = count($objects);
            $pageKids[] = count($objects) . ' 0 R';
        }

        // Pages object
        $pagesObjId = count($objects) + 1;
        $pagesObj = "<< /Type /Pages /Kids [" . implode(' ', $pageKids) . "] /Count {$pageCount} >>";
        $objects[] = $pagesObj;

        // Catalog object
        $catalogObjId = count($objects) + 1;
        $objects[] = "<< /Type /Catalog /Pages {$pagesObjId} 0 R >>";

        // Assemble objects
        foreach ($objects as $i => $obj) {
            $objId = $i + 1;
            $obj = str_replace('{PAGES_ID}', (string)$pagesObjId, $obj);
            $offsets[$objId] = strlen($buffer);
            $buffer .= $objId . " 0 obj\n" . $obj . "\nendobj\n";
        }

        // xref
        $xrefPos = strlen($buffer);
        $buffer .= "xref\n0 " . (count($objects) + 1) . "\n";
        $buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $buffer .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }

        // trailer
        $buffer .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogObjId} 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";
        return $buffer;
    }
}
?>
