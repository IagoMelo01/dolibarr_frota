<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see &lt;https://www.gnu.org/licenses/&gt;.
 */

/**
 * \file    frota/core/modules/frota/modules_veiculo.php
 * \ingroup frota
 * \brief   Parent class for veiculo document models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 *	Parent class for veiculo document models
 */
abstract class ModelePDFVeiculo extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation models
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'veiculo';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}
}

/**
 *  Create a document onto disk according to template model.
 *
 *  @param	    DoliDB		$db  			Database handler
 *  @param	    Veiculo		$object			Object veiculo
 *  @param	    string		$modele			Force template to use ('' to not force)
 *  @param		Translate	$outputlangs	Object lang a utiliser pour traduction
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @param      null|array  $moreparams     Array to provide more information
 *  @return     int         				0 if KO, 1 if OK
 */
function veiculo_create($db, $object, $modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
{
	global $conf, $langs, $user;

	$error = 0;

	$srctemplatepath = '';

	$model = new $modele($db);
	$result = $model->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $moreparams);
	if ($result <= 0) {
		dol_print_error($db, "veiculo_pdf_create Error: ".$model->error);
		return 0;
	}

	return 1;
}