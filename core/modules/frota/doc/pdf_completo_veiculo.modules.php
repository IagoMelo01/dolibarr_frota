<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

dol_include_once('/frota/core/modules/frota/modules_veiculo.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

// Dolibarr classes
dol_include_once('/frota/class/veiculo.class.php');
dol_include_once('/frota/class/manutencao.class.php');
dol_include_once('/frota/class/abastecimento.class.php');
dol_include_once('/frota/class/seguro.class.php');
dol_include_once('/frota/class/aluguel.class.php');


/**
 *	Class to manage PDF template completo_veiculo
 */
class pdf_completo_veiculo extends ModelePDFVeiculo
{
	public $db;
	public $name;
	public $description;
	public $type;
	public $phpmin = array(7, 0);
	public $version = 'dolibarr';
	public $emetteur;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$langs->loadLangs(array("main", "bills", "frota@frota"));

		$this->db = $db;
		$this->name = "completo_veiculo";
		$this->description = $langs->trans('FullReport');
		$this->update_main_doc_field = 1;

		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2);
		}
	}

	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Veiculo	$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "frota@frota"));

		if (1) {
			$object->fetch_thirdparty();

			$dir = $conf->dolibarr_temp;
			
			if ($object->specimen) {
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$file = $dir."/".$objectref."-".$this->name.".pdf";
			}

			if (file_exists($dir)) {
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				$pdf->Open();
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

				// New page
				$pdf->AddPage();

				// Header
				$this->_pagehead($pdf, $object, 1, $outputlangs);

				$pdf->SetFont('', 'B', $default_font_size + 4);
				$pdf->SetXY($this->marge_gauche, 60);
				$pdf->MultiCell(0, 10, $outputlangs->transnoentities("FullReportVehicle"), 0, 'C');


				// Vehicle data
				$this->_write_vehicle_data($pdf, $object, $outputlangs, 80);

				// Maintenance data
				$this->_write_maintenance_data($pdf, $object, $outputlangs);

				// Supply data
				$this->_write_supply_data($pdf, $object, $outputlangs);
				
				// Insurance data
				$this->_write_insurance_data($pdf, $object, $outputlangs);
				
				// Rent data
				$this->_write_rent_data($pdf, $object, $outputlangs);

				// Footer
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->Close();
				$pdf->Output($file, 'F');
				dolChmod($file);

				$this->result = array('fullpath'=>$file);
				return 1;
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
			return 0;
		}
	}
	
	protected function _write_vehicle_data(&$pdf, $object, $outputlangs, $posy)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell(0, 6, $outputlangs->transnoentities("VehicleData"), 0, 'L');
		$posy += 8;

		$pdf->SetFont('', '', $default_font_size);
		
		$data = array(
			$outputlangs->transnoentities("Ref") => $object->ref,
			$outputlangs->transnoentities("Modelo") => $object->modelo,
			$outputlangs->transnoentities("Placa") => $object->placa,
			$outputlangs->transnoentities("Chassi") => $object->chassi,
			$outputlangs->transnoentities("Renavam") => $object->renavam,
			$outputlangs->transnoentities("Cor") => $object->cor,
			$outputlangs->transnoentities("Ano") => $object->ano,
			$outputlangs->transnoentities("Status") => $object->getLibStatut(3),
		);

		foreach($data as $label => $value) {
			if ($pdf->GetY() > ($this->page_hauteur - 20)) { $pdf->AddPage(); $posy = $this->marge_haute; }
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(40, 6, $label.':', 0, 'L');
			$pdf->SetXY($this->marge_gauche + 40, $posy);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(0, 6, $outputlangs->convToOutputCharset($value), 0, 'L');
			$posy += 6;
		}
		
		return $posy;
	}

	protected function _write_maintenance_data(&$pdf, $object, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$manutencao = new Manutencao($this->db);
		$list = $manutencao->fetchAll('', '', 0, 0, array('t.fk_veiculo' => $object->id));
		
		if (count($list) > 0) {
			if ($pdf->GetY() > ($this->page_hauteur - 40)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
			
			$pdf->SetY($pdf->GetY() + 10);
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->MultiCell(0, 6, $outputlangs->transnoentities("MaintenanceHistory"), 0, 'L');
			$pdf->SetY($pdf->GetY() + 2);

			// Table Header
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->Cell(30, 7, $outputlangs->transnoentities("Date"), 1, 0, 'C');
			$pdf->Cell(80, 7, $outputlangs->transnoentities("Description"), 1, 0, 'C');
			$pdf->Cell(40, 7, $outputlangs->transnoentities("Km"), 1, 0, 'C');
			$pdf->Cell(30, 7, $outputlangs->transnoentities("Total"), 1, 1, 'C');

			$pdf->SetFont('', '', $default_font_size);
			foreach($list as $item) {
				if ($pdf->GetY() > ($this->page_hauteur - 20)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
				$pdf->Cell(30, 7, dol_print_date($item->data_prevista, 'day'), 1, 0, 'L');
				$pdf->Cell(80, 7, $outputlangs->convToOutputCharset($item->description), 1, 0, 'L');
				$pdf->Cell(40, 7, $item->quilometragem, 1, 0, 'R');
				$pdf->Cell(30, 7, price($item->amount), 1, 1, 'R');
			}
		}
	}
	
	protected function _write_supply_data(&$pdf, $object, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$abastecimento = new Abastecimento($this->db);
		$list = $abastecimento->fetchAll('', '', 0, 0, array('t.fk_veiculo' => $object->id));
		
		if (count($list) > 0) {
			if ($pdf->GetY() > ($this->page_hauteur - 40)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
			
			$pdf->SetY($pdf->GetY() + 10);
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->MultiCell(0, 6, $outputlangs->transnoentities("SupplyHistory"), 0, 'L');
			$pdf->SetY($pdf->GetY() + 2);

			// Table Header
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->Cell(30, 7, $outputlangs->transnoentities("Date"), 1, 0, 'C');
			$pdf->Cell(50, 7, $outputlangs->transnoentities("Liters"), 1, 0, 'C');
			$pdf->Cell(50, 7, $outputlangs->transnoentities("Km"), 1, 0, 'C');
			$pdf->Cell(50, 7, $outputlangs->transnoentities("Total"), 1, 1, 'C');

			$pdf->SetFont('', '', $default_font_size);
			foreach($list as $item) {
				if ($pdf->GetY() > ($this->page_hauteur - 20)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
				$pdf->Cell(30, 7, dol_print_date($item->data_ab, 'day'), 1, 0, 'L');
				$pdf->Cell(50, 7, $item->qty_real, 1, 0, 'R');
				$pdf->Cell(50, 7, $item->km, 1, 0, 'R');
				$pdf->Cell(50, 7, price($item->amount), 1, 1, 'R');
			}
		}
	}
	
	protected function _write_insurance_data(&$pdf, $object, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$seguro = new Seguro($this->db);
		$list = $seguro->fetchAll('', '', 0, 0, array('t.fk_veiculo' => $object->id));
		
		if (count($list) > 0) {
			if ($pdf->GetY() > ($this->page_hauteur - 40)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
			
			$pdf->SetY($pdf->GetY() + 10);
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->MultiCell(0, 6, $outputlangs->transnoentities("InsuranceHistory"), 0, 'L');
			$pdf->SetY($pdf->GetY() + 2);

			// Table Header
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->Cell(40, 7, $outputlangs->transnoentities("Apolice"), 1, 0, 'C');
			$pdf->Cell(40, 7, $outputlangs->transnoentities("StartDate"), 1, 0, 'C');
			$pdf->Cell(60, 7, $outputlangs->transnoentities("Total"), 1, 1, 'C');

			$pdf->SetFont('', '', $default_font_size);
			foreach($list as $item) {
				if ($pdf->GetY() > ($this->page_hauteur - 20)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
				$pdf->Cell(40, 7, $outputlangs->convToOutputCharset($item->ref), 1, 0, 'L');
				$pdf->Cell(40, 7, dol_print_date($item->inicio, 'day'), 1, 0, 'L');
				$pdf->Cell(60, 7, price($item->amount), 1, 1, 'R');
			}
		}
	}
	
	protected function _write_rent_data(&$pdf, $object, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$aluguel = new Aluguel($this->db);
		$list = $aluguel->fetchAll('', '', 0, 0, array('t.veiculo' => $object->id));
		
		if (count($list) > 0) {
			if ($pdf->GetY() > ($this->page_hauteur - 40)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
			
			$pdf->SetY($pdf->GetY() + 10);
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->MultiCell(0, 6, $outputlangs->transnoentities("RentHistory"), 0, 'L');
			$pdf->SetY($pdf->GetY() + 2);

			// Table Header
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->Cell(40, 7, $outputlangs->transnoentities("StartDate"), 1, 0, 'C');
			$pdf->Cell(40, 7, $outputlangs->transnoentities("EndDate"), 1, 0, 'C');
			$pdf->Cell(50, 7, $outputlangs->transnoentities("Total"), 1, 1, 'C');

			$pdf->SetFont('', '', $default_font_size);
			foreach($list as $item) {
				if ($pdf->GetY() > ($this->page_hauteur - 20)) { $pdf->AddPage(); $pdf->SetY($this->marge_haute); }
				$pdf->Cell(40, 7, dol_print_date($item->inicio, 'day'), 1, 0, 'L');
				$pdf->Cell(40, 7, dol_print_date($item->vencimento, 'day'), 1, 0, 'L');
				$pdf->Cell(50, 7, price($item->amount), 1, 1, 'R');
			}
		}
	}

	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs;

		$outputlangs->loadLangs(array("main", "bills", "propal", "companies", "frota@frota"));
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;
		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty(getMultidirOutput($object, 'mycompany'))) {
					$logodir = getMultidirOutput($object, 'mycompany');
				}
				$logo = $logodir.'/logos/'.($this->emetteur->logo_small ? $this->emetteur->logo_small : $this->emetteur->logo);
				if (is_readable($logo)) {
					$height = pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("FullReport");
		$pdf->MultiCell($w, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);
		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		$pdf->MultiCell($w, 4, $textref, '', 'R');
		
		return 0;
	}

	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
		return pdf_pagefoot($pdf, $outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
