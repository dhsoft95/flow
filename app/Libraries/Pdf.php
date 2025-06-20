<?php

namespace App\Libraries;

require_once APPPATH . "ThirdParty/tcpdf/tcpdf.php";

class Pdf extends \TCPDF {

    private $pdf_type;

    public function __construct($pdf_type = '') {
        parent::__construct();

        $this->pdf_type = $pdf_type;
        $this->SetFontSize(10);
    }

    public function Header() {
        if ($this->pdf_type == 'invoice') {
            $break_margin = $this->getBreakMargin();
            $auto_page_break = $this->AutoPageBreak;
            $this->SetAutoPageBreak(false, 0);

            $img_file = get_file_from_setting("invoice_pdf_background_image", false, get_setting("timeline_file_path"));
            $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 500, '', false, false, 0);

            // restore auto-page-break status
            $this->SetAutoPageBreak($auto_page_break, $break_margin);
        } else {
            // call the original Header method from the parent class
            parent::Header();
        }
    }

}
