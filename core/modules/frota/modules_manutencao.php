<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 * or see https://www.gnu.org/
 */

/**
 *  \class      ModelePDFManutencao
 *  \brief      Parent class for maintenance sheets models
 */
abstract class ModelePDFManutencao extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 7.0 = array(7, 0)
	 */
	public $phpmin = array(7, 0);

	/**
	 * Return list of active generation modules
	 *
	 * @param   DoliDB      $db                     Database handler
	 * @param   integer     $maxfilenamelength      Max length of value to show
	 * @return  array                               List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		global $conf;

		$type = 'manutencao';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}
}

/**
 *  Create a document onto disk according to template module.
 *
 *  @param     DoliDB      $db                     Database handler
 *  @param     Manutencao  $object                 Object manutencao
 *  @param     string      $modele                 Force model to use ('' to not force)
 *  @param     Translate   $outputlangs            Object langs to use for output
 *  @param     int         $hidedetails            Hide details of lines
 *  @param     int         $hidedesc               Hide description
 *  @param     int         $hideref                Hide ref
 *  @param     null|array  $moreparams             Array to provide more information
 *  @return    int                                 0 if KO, 1 if OK
 */
function manutencao_pdf_create(DoliDB $db, Manutencao $object, $modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
{
	return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
}